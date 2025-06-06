<?php
/**
 * Integration tests
 *
 * @package automattic/jetpack-phan-plugins
 */

use Phan\CLIBuilder;
use Phan\CodeBase;
use Phan\Output\Printer\CapturingJSONPrinter;
use Phan\Phan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests.
 */
class IntegrationTest extends TestCase {

	/** @var CodeBase|null */
	private $codeBase = null;

	public function setUp(): void {
		parent::setUp();
		// Do what Phan does in its own CodeBaseAwareTest class.
		// They say it's slow to create. Also I find reconstructing it seems to raise PHP constant deprecations on subsequent runs.
		static $codeBase = null;
		if ( ! $codeBase ) {
			$codeBase = require __DIR__ . '/../../vendor/phan/phan/src/codebase.php';

			// PHPUnit 11+ doesn't like that Phan registers error and exception handlers. And we don't seem to need them for the tests.
			restore_error_handler();
			restore_exception_handler();
		}

		$this->codeBase = $codeBase->shallowClone();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->codeBase = null;
	}

	/**
	 * Choose a file based on flags
	 *
	 * @param string $base Base filename.
	 * @param bool   $usePolyfill Whether to force the polyfill parser.
	 * @param bool   $analyzeTwice Whether to analyze twice.
	 * @return string|null
	 */
	private function chooseFile( $base, $usePolyfill, $analyzeTwice ) {
		$i     = strrpos( $base, '.' );
		$start = substr( $base, 0, $i );
		$end   = substr( $base, $i + 1 );

		if ( $usePolyfill && $analyzeTwice && file_exists( "$start.twice.polyfill.$end" ) ) {
			return "$start.twice.polyfill.$end";
		}
		if ( $usePolyfill && file_exists( "$start.polyfill.$end" ) ) {
			return "$start.polyfill.$end";
		}
		if ( $analyzeTwice && file_exists( "$start.twice.$end" ) ) {
			return "$start.twice.$end";
		}
		return $base;
	}

	/**
	 * @param string $dir Test dir.
	 * @param bool   $usePolyfill Whether to force the polyfill parser.
	 * @param bool   $analyzeTwice Whether to analyze twice.
	 */
	private function runPhan( $dir, $usePolyfill = false, $analyzeTwice = false ) {
		if ( ! $usePolyfill && ! extension_loaded( 'ast' ) ) {
			$this->markTestSkipped( 'This test requires PHP extension \'ast\' loaded' );
		}

		$skipTest = $this->chooseFile( "$dir/skip.php", $usePolyfill, $analyzeTwice );
		if ( file_exists( $skipTest ) ) {
			// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath
			$func   = require "$dir/skip.php";
			$reason = $func( $dir, $usePolyfill, $analyzeTwice );
			if ( $reason ) {
				$this->markTestSkipped( $reason );
			}
		}

		$cliBuilder = new CLIBuilder();
		$cliBuilder->setOption( 'project-root-directory', $dir );
		$cliBuilder->setOption( 'config-file', $this->chooseFile( "$dir/config.php", $usePolyfill, $analyzeTwice ) );
		$cliBuilder->setOption( 'directory', '.' );
		$cliBuilder->setOption( 'no-progress-bar' );
		if ( $usePolyfill ) {
			$cliBuilder->setOption( 'force-polyfill-parser' );
		}
		if ( $analyzeTwice ) {
			$cliBuilder->setOption( 'analyze-twice' );
		}
		$cli = $cliBuilder->build();

		$printer = new CapturingJSONPrinter();
		Phan::setPrinter( $printer );

		$old = error_reporting();
		try {
			if ( $usePolyfill ) {
				// microsoft/tolerant-php-parser raises PHP 8.4 deprecation warnings.
				// @todo If it gets updated at some point, hopefully this can be removed.
				error_reporting( $old & ~E_DEPRECATED );
			}
			Phan::analyzeFileList(
				$this->codeBase,
				static function () use ( $cli ) {
					return $cli->getFileList();
				}
			);
		} finally {
			error_reporting( $old );
		}

		$expectFile = $this->chooseFile( "$dir/expect.json", $usePolyfill, $analyzeTwice );
		$expect     = json_decode( file_get_contents( $expectFile ), true );
		$actual     = $printer->getIssues();
		if ( getenv( 'UPDATE_EXPECT' ) && $expect !== $printer->getIssues() ) {
			file_put_contents( $expectFile, json_encode( $actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR ) );
		}
		$this->assertSame( $expect, $actual );
	}

	/**
	 * @dataProvider provideIntegration
	 */
	#[DataProvider( 'provideIntegration' )]
	public function testIntegration( $dir ) {
		$this->runPhan( $dir, false, false );
	}

	/**
	 * @dataProvider provideIntegration
	 */
	#[DataProvider( 'provideIntegration' )]
	public function testIntegration_Polyfill( $dir ) {
		$this->runPhan( $dir, true, false );
	}

	/**
	 * @dataProvider provideIntegration
	 */
	#[DataProvider( 'provideIntegration' )]
	public function testIntegration_AnalyzeTwice( $dir ) {
		$this->runPhan( $dir, false, true );
	}

	/**
	 * @dataProvider provideIntegration
	 */
	#[DataProvider( 'provideIntegration' )]
	public function testIntegration_Polyfill_AnalyzeTwice( $dir ) {
		$this->runPhan( $dir, true, true );
	}

	public static function provideIntegration() {
		$iterator = new DirectoryIterator( __DIR__ . '/integration' );

		foreach ( $iterator as $dir ) {
			if ( $dir->isDot() || ! $dir->isDir() ) {
				continue;
			}
			$pathName = $dir->getPathname();
			$testName = basename( $pathName );
			yield $testName => array( $pathName );
		}
	}
}
