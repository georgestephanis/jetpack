#!/usr/bin/env node

import { spawn, spawnSync } from 'child_process';
import { fileURLToPath } from 'url';
import chalk from 'chalk';
import isJetpackDraftMode from './jetpack-draft.mjs';

/**
 * Exec a command and collect the lines.
 *
 * @param {string}   cmd     - Command.
 * @param {string[]} args    - Arguments.
 * @param {object}   options - Options.
 * @return {string[]} Lines of output.
 */
async function spawnAndReadStdout( cmd, args, options = {} ) {
	const lines = [];

	await new Promise( ( resolve, reject ) => {
		let buffer = '';

		const proc = spawn( cmd, args, {
			...options,
			stdio: [ 'ignore', 'pipe', 'inherit' ],
		} );

		proc.stdout.on( 'data', data => {
			buffer += data.toString();
			let i;
			while ( ( i = buffer.indexOf( '\n' ) ) >= 0 ) {
				lines.push( buffer.substring( 0, i ) );
				buffer = buffer.substring( i + 1 );
			}
		} );

		proc.on( 'close', code => {
			if ( buffer !== '' ) {
				lines.push( buffer );
			}
			if ( code !== 0 ) {
				reject( new Error( `Command failed with code ${ code }` ) );
			} else {
				resolve();
			}
		} );

		proc.on( 'error', err => {
			reject( err );
		} );
	} );

	return lines;
}

/**
 * Checks for filename collsisions on case-insensitive file systems.
 *
 * This is probably impossible to get 100% right due to potential locale
 * differences in filesystem case folding, but this should be most of the way there.
 */
async function checkFilenameCollisions() {
	if ( process.exitCode !== 0 ) {
		return;
	}

	console.log( chalk.green( 'Checking for filename collisions. Just a sec...' ) );

	const compare = Intl.Collator( 'und', { sensitivity: 'accent' } ).compare;

	const files = (
		await spawnAndReadStdout( 'git', [
			'-c',
			'core.quotepath=off',
			'ls-tree',
			'-rt',
			'--name-only',
			'HEAD',
		] )
	).sort( compare );

	const collisions = new Set();
	let prev = null;
	for ( const file of files ) {
		if ( prev !== null && compare( file, prev ) === 0 ) {
			collisions.add( prev );
			collisions.add( file );
		}
		prev = file;
	}

	if ( collisions.size > 0 ) {
		process.exitCode = 1;
		console.error(
			chalk.red(
				'This commit contains filenames that differ only in case! This will break things for Mac users.'
			)
		);
		console.error( '- ' + [ ...collisions ].join( '\n- ' ) );
	} else {
		console.log( chalk.green( 'No collisions detected.' ) );
	}
}

/**
 * Print the "push again" message.
 */
function pushAgain() {
	console.log(
		'\n\n   _____ _____ _______   _____  _    _  _____ _    _            _____          _____ _   _\n  / ____|_   _|__   __| |  __ \\| |  | |/ ____| |  | |     /\\   / ____|   /\\   |_   _| \\ | |\n | |  __  | |    | |    | |__) | |  | | (___ | |__| |    /  \\ | |  __   /  \\    | | |  \\| |\n | | |_ | | |    | |    |  ___/| |  | |\\___ \\|  __  |   / /\\ \\| | |_ | / /\\ \\   | | | . ` |\n | |__| |_| |_   | |    | |    | |__| |____) | |  | |  / ____ \\ |__| |/ ____ \\ _| |_| |\\  |\n  \\_____|_____|  |_|    |_|     \\____/|_____/|_|  |_| /_/    \\_\\_____/_/    \\_\\_____|_| \\_|\n\n'
	);
	console.log(
		chalk.green(
			'Changelog file(s) committed! Ignore error below and `git push` again to include changelog files.'
		)
	);
	process.exitCode = 75;
}

/**
 * Checks if changelog files are required.
 */
async function checkChangelogFiles() {
	if ( process.exitCode !== 0 ) {
		return;
	}

	console.log( chalk.green( 'Checking if changelog files are needed. Just a sec...' ) );

	// Bail if we're pushing to a release branch, like boost/branch-1.3.0
	let currentBranch = spawnSync( 'git', [ 'branch', '--show-current' ] );
	currentBranch = currentBranch.stdout.toString().trim();
	const branchReg = /.*\/branch-/; // match example: jetpack/branch-1.2.3
	if ( currentBranch.match( branchReg ) ) {
		console.log( chalk.green( 'Release branch detected. Skipping changelog test.' ) );
		return;
	}
	if ( currentBranch === 'prerelease' ) {
		console.log( chalk.green( 'Prerelease branch detected. Skipping changelog test.' ) );
		return;
	}

	// Check if any changelog files are needed.
	const needChangelog = spawnSync(
		'tools/check-changelogger-use.php',
		[ '--maybe-merge', 'origin/trunk', 'HEAD' ],
		{
			stdio: 'inherit',
			cwd: fileURLToPath( new URL( '../../../', import.meta.url ) ),
		}
	);

	// If a changelog file is needed, quit the push.
	if ( needChangelog.status === 0 ) {
		console.log( chalk.green( 'Changelog check passed.' ) );
	} else if ( needChangelog.status === 8 ) {
		pushAgain();
	} else if ( isJetpackDraftMode() ) {
		console.log(
			chalk.yellow(
				"Allowing push because you're in draft mode. To exit draft mode, use `jetpack draft disable`"
			)
		);
	} else if (
		! process.stdin.isTTY ||
		( needChangelog.status !== 2 && needChangelog.status !== 10 )
	) {
		process.exitCode = 1;
	} else {
		process.exitCode = 1;
		try {
			// Run the changelogger.
			const autoChangelog = spawnSync( 'pnpm', [ 'jetpack', 'changelog', 'add' ], {
				stdio: 'inherit',
			} );

			// If the autochangelogger worked, commit the changelog files.
			if ( autoChangelog.status === 0 ) {
				const filesToCommit = [];
				const changelogFiles = await spawnAndReadStdout( 'git', [
					'-c',
					'core.quotepath=off',
					'diff',
					'--name-only',
					'--diff-filter=A',
					'--cached',
				] );

				for ( const file of changelogFiles ) {
					const match = file.match( /^projects\/([^/]+\/[^/]+)\/changelog\// );
					if ( match ) {
						filesToCommit.push( file );
					}
				}

				if ( filesToCommit.length > 0 ) {
					const commitFiles = spawnSync( 'git', [ 'commit', ...filesToCommit, '-m', 'changelog' ] );
					if ( commitFiles.status === 0 ) {
						pushAgain();
					}
				}
			}
		} catch ( e ) {
			console.log( 'Something went wrong', e );
		}
	}
}

process.exitCode = 0;
await checkFilenameCollisions();
await checkChangelogFiles();
