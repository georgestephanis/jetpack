import { createWriteStream, existsSync } from 'fs';
import fs from 'fs/promises';
import os from 'os';
import path from 'path';
import process from 'process';
import util from 'util';
import chalk from 'chalk';
import chokidar from 'chokidar';
import Configstore from 'configstore';
import enquirer from 'enquirer';
import { execa } from 'execa';
import pDebounce from 'p-debounce';
import tmp from 'tmp';
import { tracks } from '../helpers/analytics.js';
import { projectDir } from '../helpers/install.js';
import { listProjectFiles } from '../helpers/list-project-files.js';
import { allProjectsByType, dirs } from '../helpers/projectHelpers.js';
import { runCommand } from '../helpers/runCommand.js';

const rsyncConfigStore = new Configstore( 'automattic/jetpack-cli/rsync' );
let isOpenrsync = false;

/**
 * Escapes dots in a string. Useful when saving destination as key in Configstore. Otherwise it tries strings with dots
 * as nested objects.
 *
 * @param { string } key - String of the destination key.
 * @return { string } - Returns the key with escaped periods.
 */
function escapeKey( key ) {
	return key.replace( /\./g, '\\.' );
}

/**
 * Stores the destination in the configstore.
 * Takes an optional alias arg.
 *
 * @param { string }       pluginDestPath - Destination path to plugins.
 * @param { string|false } alias          - Alias key, if set.
 */
function setRsyncDest( pluginDestPath, alias = false ) {
	const key = alias || pluginDestPath;
	rsyncConfigStore.set( escapeKey( key ), pluginDestPath );
}

/**
 * Entry point for the CLI.
 * Prompts for the plugin.
 * Prompts for the destination.
 * Finds and uses the real wp plugin slug for dest.
 *
 * @param {object} argv - The argv for the command line.
 */
export async function rsyncInit( argv ) {
	// Apple ships a special fork of openrsync, which has various quirks.
	// In particular, it doesn't handle symlink recursion well, which breaks
	// in macOS 15.4 and copies child node_modules dirs in 15.5.
	//
	// See also:
	// * p1742486518169009-slack-CDLH4C1UZ
	// * https://github.com/apple-oss-distributions/rsync/tree/main/openrsync
	if ( os.platform() === 'darwin' ) {
		const { stdout: rsyncVersion } = await execa( 'rsync', [ '--version' ] );
		isOpenrsync = rsyncVersion.indexOf( 'openrsync' ) >= 0;
		if ( isOpenrsync ) {
			const { stdout: macOS_version } = await execa( 'sw_vers', [ '--productVersion' ] );
			if ( macOS_version === '15.4' ) {
				console.error(
					chalk.red(
						'The implementation of rsync in macOS 15.4 is unable to properly sync symlinks.'
					)
				);
				console.error( chalk.red( 'Please install standard rsync (e.g. `brew install rsync`).' ) );
				process.exit( 1 );
			} else {
				console.error(
					chalk.yellow(
						'The implementation of rsync in macOS is unable to properly sync symlinks.'
					)
				);
				console.error(
					chalk.yellow( 'Installing standard rsync (e.g. `brew install rsync`) is recommended.' )
				);
				if ( argv.nonInteractive ) {
					process.exit( 1 );
				}
				console.error();
				await enquirer
					.prompt( {
						type: 'confirm',
						name: 'proceedWithOpenrsync',
						message:
							'Continuing will not break anything, but will copy many unneeded files.\nProceed to sync files?',
						initial: false,
					} )
					.then( answer => {
						if ( ! answer.proceedWithOpenrsync ) {
							process.exit( 0 );
						}
					} );
			}
		}
	}

	if ( argv.config ) {
		if ( argv.nonInteractive ) {
			console.warn( 'Config management is not available in non-interactive mode.' );
		} else {
			await promptToManageConfig();
		}
		return;
	}
	argv = await maybePromptForPlugin( argv );
	argv = await maybePromptForDest( argv );
	const sourcePluginPath = projectDir( `plugins/${ argv.plugin }` ) + '/';
	// Append plugin slug if we got a "plugins" directory, otherwise just make sure it ends in a slash.
	let finalDest;
	if ( argv.dest.match( /\/(?:mu-)?plugins\/?$/ ) ) {
		// Pull the actual plugin slug from composer.json.
		const pluginComposerJson = JSON.parse(
			await fs.readFile( projectDir( `plugins/${ argv.plugin }/composer.json` ) )
		);
		const wpPluginSlug =
			pluginComposerJson?.extra?.[ 'wp-plugin-slug' ] ??
			pluginComposerJson?.extra?.[ 'beta-plugin-slug' ];
		if ( ! wpPluginSlug ) {
			console.error( chalk.red( `Failed to determine plugin slug for ${ argv.plugin }.` ) );
			process.exit( 1 );
		}
		finalDest = path.join( argv.dest, wpPluginSlug + '/' );
	} else {
		finalDest = path.join( argv.dest, '/' );
	}

	const untrackedFiles = await getUntrackedFiles( sourcePluginPath );
	if ( untrackedFiles ) {
		console.log( 'The below untracked files were detected in your plugin path:\n' );
		console.log( untrackedFiles.replace( /^/gm, '  ' ) + '\n' );
		if ( ! argv.nonInteractive ) {
			await enquirer
				.prompt( {
					type: 'confirm',
					name: 'ignoreUntracked',
					message: 'Untracked files will not be synced. Proceed?',
					initial: false,
				} )
				.then( answer => {
					if ( ! answer.ignoreUntracked ) {
						process.exit( 0 );
					}
				} );
		}
	}

	if ( argv.watch ) {
		await tracks( 'rsync_watch' );
		let watcher;
		const rsyncAndUpdateWatches = async ( event, eventfile ) => {
			if ( argv.v ) {
				console.debug( `rsync due to event ${ event } for ${ eventfile }` );
			}

			const paths = await rsyncToDest( sourcePluginPath, finalDest );

			// Warn but don't fail if file was intentionally not synced. We still want to sync
			// if a change event occurs, as other change events could have been debounced.
			if (
				argv.v &&
				event === 'change' &&
				! paths.has( eventfile ) &&
				! [ '../../../.git/index', '.gitignore', '.gitattributes' ].includes( eventfile ) // ignore some specific files
			) {
				let unsync_reason;
				if ( ! ( await isFileTracked( sourcePluginPath + eventfile ) ) ) {
					unsync_reason = 'not tracked by git';
				} else if ( await isFileExcluded( sourcePluginPath + eventfile ) ) {
					unsync_reason = 'excluded from production';
				} else {
					unsync_reason = 'something odd 🤷';
				}
				console.debug(
					`Sync was triggered by a change to '${ eventfile }', but it was not synced.`
				);
				console.debug( `Reason: ${ unsync_reason }` );
			}

			// On some systems, using multiple 'watcher.add()' calls breaks the firing of the 'ready' event.
			// Instead, we add all the paths to an array and call add() once.
			const pathsToAdd = [];

			if ( ! watcher ) {
				watcher = chokidar.watch( [], {
					cwd: sourcePluginPath,
					followSymlinks: false,
					disableGlobbing: true,
					ignoreInitial: true,
					depth: 0,
				} );

				// Always watch the plugin base dir.
				pathsToAdd.push( '.' );

				// Also watch the git index for changes to catch `git add`, as that may change which files are synced.
				pathsToAdd.push( path.join( process.cwd(), '.git/index' ) );

				// Watch `.gitignore` and `.gitattributes` in parent dirs, as they too may change which files are synced.
				// Here we assume sourcePluginPath is always `projects/plugins/whatever`
				for ( const dir of [ '.', 'projects', 'projects/plugins' ] ) {
					const ignorepath = path.join( process.cwd(), dir, '.gitignore' );
					if ( existsSync( ignorepath ) ) {
						pathsToAdd.push( ignorepath );
					}
					const attributespath = path.join( process.cwd(), dir, '.gitattributes' );
					if ( existsSync( attributespath ) ) {
						pathsToAdd.push( attributespath );
					}
				}

				watcher.once( 'ready', () => {
					console.log( 'jetpack rsync --watch is now watching for changes to:', sourcePluginPath );
					watcher.on( 'all', pDebounce( rsyncAndUpdateWatches, 1000 ) );
				} );
			}

			// Any dirs that aren't already being watched should start being watched.
			const curPaths = watcher.getWatched();
			for ( const dir of paths ) {
				if ( dir.endsWith( '/' ) && ! curPaths[ dir.substring( 0, dir.length - 1 ) ]?.length ) {
					pathsToAdd.push( dir );
				}
			}

			// Any dirs currently being watched that aren't synced anymore should no longer be watched.
			for ( const dir of Object.keys( curPaths ) ) {
				if (
					dir !== '.' &&
					dir !== '..' &&
					! dir.startsWith( '../' ) &&
					curPaths[ dir ].length &&
					! paths.has( dir + '/' )
				) {
					watcher.unwatch( dir );
				}
			}

			watcher.add( pathsToAdd );
		};
		await rsyncAndUpdateWatches( 'startup', 'jetpack rsync --watch' );
	} else {
		await rsyncToDest( sourcePluginPath, finalDest );

		console.log( '\n' );
		console.log(
			chalk.black.bgYellow(
				'*************************************************************************************'
			)
		);
		console.log(
			chalk.black.bgYellow(
				'**  Make sure you have set ' +
					chalk.bold( "define( 'JETPACK_AUTOLOAD_DEV', true );" ) +
					' in a mu-plugin  **'
			)
		);
		console.log(
			chalk.black.bgYellow(
				'**  on the remote site. Otherwise the wrong versions of packages may be loaded!    **'
			)
		);
		console.log(
			chalk.black.bgYellow(
				'*************************************************************************************'
			)
		);
		console.log( '\n' );

		if ( argv.nonInteractive ) {
			return;
		}
		await promptForRsyncConfig( argv.dest );
	}
}

/**
 * Prompts to delete, clear or list out the stored destinations.
 */
async function promptToManageConfig() {
	const promptClearAll = async () => {
		console.log( rsyncConfigStore.all );
		await enquirer
			.prompt( {
				type: 'confirm',
				name: 'clearAll',
				message: 'are you sure you want to clear them all?',
				initial: true,
			} )
			.then( answer => {
				if ( answer.clearAll ) {
					rsyncConfigStore.clear();
					console.log( 'Empty! Cleared them all.' );
				}
			} );
	};
	const clearOne = key => rsyncConfigStore.delete( escapeKey( key ) );
	const configManage = await enquirer.prompt( {
		type: 'select',
		name: 'manageConfig',
		message: 'Manage saved destination paths.',
		choices: [ 'list', 'add', 'remove' ],
	} );
	if ( configManage.manageConfig === 'list' ) {
		console.log( "Here's what you have saved:" );
		console.log( rsyncConfigStore.all );
	}
	if ( configManage.manageConfig === 'add' ) {
		const aliasDest = await promptNewDest();
		if ( aliasDest ) {
			await promptForSetAlias( aliasDest );
		}
	}
	if ( configManage.manageConfig === 'remove' ) {
		const configKeys = Object.keys( rsyncConfigStore.all );
		configKeys.push( 'All of them!' );
		const removeDest = await enquirer.prompt( {
			type: 'select',
			name: 'removeKey',
			message: 'which would you like to remove?',
			choices: configKeys,
		} );
		if ( removeDest.removeKey === 'All of them!' ) {
			await promptClearAll();
		} else {
			clearOne( removeDest.removeKey );
			console.log( `Done! Removed ${ removeDest.removeKey }` );
		}
	}
}

/**
 * Fetch the list of files to rsync.
 *
 * @param {string} source - Source path.
 * @return {Promise<Set>} List of paths.
 */
async function collectPaths( source ) {
	const paths = new Set();

	await addVendorFilesToPathSet( `${ source }/vendor/`, paths );
	await addFilesToPathSet( source, '', paths );

	return paths;
}

/**
 * Add a file, and all directories containing it, to the set of paths.
 *
 * @param {string} file  - File to add.
 * @param {Set}    paths - Set of paths to add to.
 * @return {void}
 */
async function addFileToPathSet( file, paths ) {
	// Rsync requires we also list all the directories containing the file.
	let prev;
	do {
		paths.add( file );
		prev = file;
		file = path.dirname( file ) + '/';
	} while ( file !== '/' && file !== './' && file !== prev );
}

/**
 * Collect paths to rsync.
 *
 * @param {string} source - Source path.
 * @param {string} prefix - Source path prefix.
 * @param {Set}    paths  - Set to add paths into.
 * @return {Promise<void>}
 */
async function addFilesToPathSet( source, prefix, paths ) {
	// Include just the files that are published to the mirror.
	for await ( const rfile of listProjectFiles( source, execa ) ) {
		const file = path.join( prefix, rfile );

		// If the file is a monorepo vendor symlink, we need to follow it.
		const fullpath = path.join( source, file );
		if (
			await fs.lstat( fullpath ).then(
				dirent => dirent.isSymbolicLink(),
				() => false
			)
		) {
			const target = path.relative(
				process.cwd(),
				path.join( path.dirname( fullpath ), await fs.readlink( fullpath ) )
			);
			if (
				target.startsWith( 'projects/' ) &&
				( await fs.access( target ).then(
					() => true,
					() => false
				) )
			) {
				await addFilesToPathSet( target, file, paths );
				continue;
			}
		}

		await addFileToPathSet( file, paths );
	}
}

/**
 * Explicitly add any /vendor files that are otherwise not symlinked.
 * Necessary when rsyncing development builds.
 *
 * @param {string} source - Source path.
 * @param {Set}    paths  - Set to add paths into.
 * @return {void}
 */
async function addVendorFilesToPathSet( source, paths ) {
	const dirents = await fs.readdir( source, { withFileTypes: true } );
	const files = await Promise.all(
		dirents.map( dirent => {
			const fileSource = path.resolve( source, dirent.name );
			return dirent.isDirectory() ? addVendorFilesToPathSet( fileSource, paths ) : fileSource;
		} )
	);

	for ( const file of files ) {
		if (
			await fs.lstat( file ).then(
				dirent => dirent.isSymbolicLink(),
				() => false
			)
		) {
			// Skip the symlinks.
			continue;
		}
		if ( file ) {
			// Relative file path to the project dir.
			const relativeFilePath = file.substring( file.indexOf( '/vendor' ) + 1 );
			if ( relativeFilePath.startsWith( 'vendor' ) ) {
				await addFileToPathSet( relativeFilePath, paths );
			}
		}
	}
}

/**
 * Create a temporary rsync filter file.
 *
 * @param {Set} paths - Paths to rsync.
 * @return {object} As from `tmp.fileSync()`.
 */
async function createFilterFile( paths ) {
	const tmpFile = tmp.fileSync();

	// Wrap the tmpFile fd in a stream.
	const tmpStream = createWriteStream( null, { fd: tmpFile.fd } );
	const writeTmp = data => {
		return new Promise( resolve => {
			if ( ! tmpStream.write( data ) ) {
				tmpStream.once( 'drain', resolve );
			} else {
				resolve();
			}
		} );
	};

	// Exclude any `.git` dirs, mostly in case someone ran composer with --prefer-source (or composer fell back to that).
	await writeTmp( '- .git\r\n' );

	// Include each path.
	for ( const file of paths ) {
		// Rsync differentiates literal strings vs patterns by looking for `[`, `*`, and `?`.
		// Only patterns use backslash escapes, literal strings do not.
		await writeTmp(
			'+ /' + ( file.match( /[[*?]/ ) ? file.replace( /[[*?\\]/g, '\\$&' ) : file ) + '\r\n'
		);
	}

	// Exclude anything not included above.
	await writeTmp( '- *\r\n' );

	// Close the file.
	await util.promisify( tmpStream.close ).call( tmpStream );

	return tmpFile;
}

/**
 * Checks if file is tracked by git.
 *
 * @param {string} filePath - file path.
 * @return {boolean} true if tracked, otherwise false.
 */
async function isFileTracked( filePath ) {
	try {
		await execa( 'git', [ 'ls-files', '--error-unmatch', filePath ] );
		return true;
	} catch {
		return false;
	}
}

/**
 * Checks if file attribute is set to production-exclude by `.gitattributes`.
 *
 * @param {string} filePath - file path.
 * @return {boolean} true if excluded, otherwise false.
 */
async function isFileExcluded( filePath ) {
	try {
		const { stdout } = await execa( 'git', [ 'check-attr', 'production-exclude', '--', filePath ] );
		return stdout.split( ': ' )[ 2 ] === 'set';
	} catch {
		return false;
	}
}

/**
 * Get all untracked files in the plugin directory.
 *
 * @param {string} pluginPath - Path to plugin.
 * @return {string} Files that aren't tracked.
 */
async function getUntrackedFiles( pluginPath ) {
	const paths = [ pluginPath ];

	// Add any Composer-symlinked vendor packages. Assumes the usual directory structure.
	const cwd = await fs.realpath( process.cwd() );
	for ( const dir of [ 'vendor', 'jetpack_vendor' ] ) {
		const dirents = (
			await fs.readdir( path.join( pluginPath, dir ), { withFileTypes: true } ).catch( () => [] )
		).filter( e => e.isDirectory() );
		for ( const dir2 of dirents ) {
			const dirents2 = (
				await fs
					.readdir( path.join( dir2.parentPath, dir2.name ), { withFileTypes: true } )
					.catch( () => [] )
			).filter( e => e.isSymbolicLink() );
			for ( const link of dirents2 ) {
				try {
					const rp = path.relative(
						cwd,
						await fs.realpath( path.join( link.parentPath, link.name ) )
					);
					if ( ! rp.startsWith( '..' + path.sep ) ) {
						paths.push( rp );
					}
				} catch {
					// Probably a broken symlink. Ignore it.
				}
			}
		}
	}

	try {
		const { stdout } = await execa( 'git', [
			'ls-files',
			'--others',
			'--exclude-standard',
			...paths,
		] );
		return stdout;
	} catch ( e ) {
		console.log( e );
		console.error( chalk.red( 'Uh oh! ' + e.message ) );
	}
}

/**
 * Function that does the actual work of rsync.
 *
 * @param {string} source - Source path.
 * @param {string} dest   - Final destination path, including plugin slug.
 * @return {Promise<Set>} Synced path set.
 */
async function rsyncToDest( source, dest ) {
	const paths = await collectPaths( source );
	const tmpFile = await createFilterFile( paths );

	try {
		// Some versions of openrsync partially work with --copy-unsafe-links, so do that for them.
		const copyLinksOpt = isOpenrsync ? '--copy-unsafe-links' : '--copy-links';

		await runCommand( 'rsync', [
			'-azKPv',
			'--prune-empty-dirs',
			'--delete',
			'--delete-after',
			'--delete-excluded',
			copyLinksOpt,
			`--include-from=${ tmpFile.name }`,
			source,
			dest,
		] );
		tmpFile.removeCallback();
	} catch ( e ) {
		console.log( e );
		console.error( chalk.red( 'Uh oh! ' + e.message ) );
		tmpFile.removeCallback();
		process.exit( 1 );
	}

	return paths;
}

/**
 * Prompts for config file.
 *
 * @param { string } pluginDestPath - Passthrough of the argv object.
 */
async function promptForRsyncConfig( pluginDestPath ) {
	const foundValue = Object.keys( rsyncConfigStore.all ).find(
		key => rsyncConfigStore.all[ key ] === pluginDestPath
	);
	if ( foundValue ) {
		return;
	}
	const createPrompt = await enquirer.prompt( {
		name: 'createConfig',
		type: 'select',
		message: `No saved entries for ${ pluginDestPath }. Create one for easier use later?`,
		choices: [ 'Hell yeah!', 'Nah' ],
	} );
	if ( createPrompt.createConfig === 'Hell yeah!' ) {
		await promptForSetAlias( pluginDestPath );
	}
}

/**
 * Prompt to set the destination alias.
 *
 * @param { string } pluginDestPath - String to destination path.
 */
async function promptForSetAlias( pluginDestPath ) {
	const aliasSetPrompt = await enquirer.prompt( {
		name: 'alias',
		type: 'input',
		message: 'Enter an alias for easier reference? (Press enter to skip.)',
	} );
	const alias = aliasSetPrompt.alias || pluginDestPath;
	if ( rsyncConfigStore.has( escapeKey( alias ) ) ) {
		const alreadyFound = await enquirer.prompt( {
			name: 'overwrite',
			type: 'confirm',
			initial: true,
			// prettier-ignore
			message: `This alias already exists for dest: ${ rsyncConfigStore.get( escapeKey( alias ) ) }. Overwrite it?`,
		} );
		if ( ! alreadyFound.overwrite ) {
			console.log( 'Okay!' );
			return;
		}
	}
	await setRsyncDest( pluginDestPath, alias );
	console.log( `Alias '${ alias }' saved for ${ pluginDestPath }` );
}

/**
 * Maybe prompts for the destination path if not already set or found a saved alias.
 * Ends process if destination is not provided and in non-interactive mode.
 *
 * @param {object} argv - Passthrough of the argv object.
 * @return {object} argv object with the project property.
 */
async function maybePromptForDest( argv ) {
	if ( rsyncConfigStore.has( argv.dest ) ) {
		console.log( `Alias found, using dest: ${ rsyncConfigStore.get( argv.dest ) }` );
		argv.dest = rsyncConfigStore.get( argv.dest );
		return argv;
	}
	if ( argv.dest ) {
		return argv;
	}
	if ( argv.nonInteractive ) {
		console.warn( 'Destination must be explicitly specified when in non-interactive mode.' );
		process.exit( 1 );
	}
	const savedDests = Object.keys( rsyncConfigStore.all );
	if ( savedDests.length === 0 ) {
		argv.dest = await promptNewDest();
	} else {
		savedDests.unshift( 'Create new' );
		const response = await enquirer.prompt( {
			type: 'select',
			name: 'dest',
			message: 'Choose destination:',
			choices: savedDests,
		} );
		if ( 'Create new' === response.dest ) {
			argv.dest = await promptNewDest();
		} else {
			argv.dest = rsyncConfigStore.get( escapeKey( response.dest ) );
		}
	}
	return argv;
}

/**
 * Prompts for the destination.
 *
 * @return {Promise<*|string>} - Destination path
 */
async function promptNewDest() {
	const response = await enquirer.prompt( {
		name: 'dest',
		type: 'input',
		message: "Input destination host:path to the plugin's dir or the /plugins or /mu-plugins dir: ",
		validate: v => ( v === '' ? 'Please enter a host:path' : true ),
	} );
	return response.dest;
}

/**
 * Maybe prompt for plugin.
 *
 * If no type is passed via `options`, then it will prompt for the plugin.
 * Ends process if plugin is not provided and in non-interactive mode.
 *
 * @param {object} argv - Passthrough of an object, meant to accept argv.
 * @return {object} object with the type property appended.
 */
export async function maybePromptForPlugin( argv ) {
	let whichPlugin = argv.plugin;
	if (
		! whichPlugin ||
		whichPlugin.length === 0 ||
		( whichPlugin.length > 0 && ! validatePlugin( whichPlugin ) )
	) {
		if ( argv.nonInteractive ) {
			console.warn( 'Plugin must be explicitly specified when in non-interactive mode.' );
			process.exit( 1 );
		}
		whichPlugin = await enquirer.prompt( {
			type: 'autocomplete',
			name: 'plugin',
			message: 'Which plugin?',
			choices: dirs( './projects/plugins' ),
		} );
		argv.plugin = whichPlugin.plugin;
	}

	return argv;
}

/**
 * Make sure the plugin is actually here.
 *
 * @param {string} plugin - The plugin dirname in project/plugins/.
 * @return {boolean} Whether it's found.
 */
function validatePlugin( plugin ) {
	if ( false === allProjectsByType( 'plugins' ).includes( `plugins/${ plugin }` ) ) {
		console.log( plugin + ' not found here!' );
		return false;
	}

	return true;
}

/**
 * Command definition for the rsync subcommand.
 *
 * @param {object} yargs - The Yargs dependency.
 * @return {object} Yargs with the rsync commands defined.
 */
export function rsyncDefine( yargs ) {
	yargs.command(
		'rsync [plugin] [dest]',
		'Will rsync a plugin from projects/plugins/ to a remote destination. Useful for developing against a live site.',
		yarg => {
			yarg
				.positional( 'plugin', {
					describe: 'Name of the plugin',
					type: 'string',
				} )
				.positional( 'dest', {
					describe: 'Destination path to plugins dir',
					type: 'string',
				} )
				.option( 'config', {
					describe: 'List, add, or remove saved destinations in the config.',
					type: 'boolean',
				} )
				.option( 'watch', {
					describe:
						'Watch the plugin for changes and rsync on change. Note this will probably not be useful if rsync prompts for a password.',
					type: 'boolean',
				} )
				.option( 'non-interactive', {
					describe: 'Do not use interactive prompts. Ideal for CI runs.',
					type: 'boolean',
				} );
		},
		async argv => {
			await rsyncInit( argv );
		}
	);

	return yargs;
}
