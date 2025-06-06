<?php
/**
 * User interface for the changelogger tool.
 *
 * @package automattic/jetpack-changelogger
 */

namespace Automattic\Jetpack\Changelogger;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * User interface for the changelogger tool.
 */
class Application extends SymfonyApplication {

	const VERSION = '6.0.5';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( 'Jetpack Changelogger', self::VERSION );
		$this->setCommandLoader( new CommandLoader() );
	}

	/**
	 * Called when the application is run.
	 *
	 * @param InputInterface  $input InputInterface.
	 * @param OutputInterface $output OutputInterface.
	 * @return int
	 */
	public function doRun( InputInterface $input, OutputInterface $output ): int {
		$output->getFormatter()->setStyle( 'warning', new OutputFormatterStyle( 'black', 'yellow' ) );
		// @phan-suppress-next-line PhanUndeclaredMethodInCallable,PhanUndeclaredMethod -- Being checked before being called. See also https://github.com/phan/phan/issues/1204.
		$errout = is_callable( array( $output, 'getErrorOutput' ) ) ? $output->getErrorOutput() : $output;

		// Try to find a composer.json, if COMPOSER isn't set.
		if ( ! getenv( 'COMPOSER' ) ) {
			$dir = getcwd();
			$ok  = false;
			do {
				$composer = $dir . DIRECTORY_SEPARATOR . 'composer.json';
				if ( file_exists( $composer ) ) {
					$ok = true;
					break;
				}

				$prev = $dir;
				$dir  = dirname( $dir );
			} while ( $prev !== $dir );

			// If we found one in a parent directory, ask if it should be used.
			if ( getcwd() !== $dir ) {
				if ( ! $ok || ! $input->isInteractive() ) {
					$errout->writeln( '<error>File composer.json is not found in ' . getcwd() . '.</>' );
					$errout->writeln( '<error>Run changelogger from the appropriate directory, or set the environment variable COMPOSER to point to composer.json.</>' );
					return -1;
				}

				$question = new ConfirmationQuestion( "<info>No composer.json in current directory, do you want to use the one at $composer? [Y/n]</> ", true );
				$helper   = $this->getHelperSet()->get( 'question' );
				'@phan-var QuestionHelper $helper';
				if ( ! $helper->ask( $input, $output, $question ) ) {
					return -1;
				}
			}
			Config::setComposerJsonPath( $composer );
		}

		try {
			return parent::doRun( $input, $output );
		} catch ( ConfigException $ex ) {
			$errout->writeln( "<error>{$ex->getMessage()}</>" );
			return -1;
		}
	}
}
