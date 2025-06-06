<?php
/**
 * Tests for the semver versioning plugin.
 *
 * @package automattic/jetpack-changelogger
 */

namespace Automattic\Jetpack\Changelogger\Tests\Plugins;

use Automattic\Jetpack\Changelog\ChangeEntry;
use Automattic\Jetpack\Changelogger\Plugins\SemverVersioning;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Tests for the semver versioning plugin.
 *
 * @covers \Automattic\Jetpack\Changelogger\Plugins\SemverVersioning
 */
#[CoversClass( SemverVersioning::class )]
class SemverVersioningTest extends TestCase {

	/**
	 * Test parseVersion and normalizeVersion.
	 *
	 * @dataProvider provideParseVersion
	 * @param string                          $version Version.
	 * @param string|InvalidArgumentException $expect Expected parse result.
	 * @param string|null                     $normalized Normalized value, if different from `$version`.
	 */
	#[DataProvider( 'provideParseVersion' )]
	public function testParseVersion( $version, $expect, $normalized = null ) {
		$obj = new SemverVersioning();
		if ( $expect instanceof InvalidArgumentException ) {
			$this->expectException( InvalidArgumentException::class );
			$this->expectExceptionMessage( $expect->getMessage() );
			$obj->parseVersion( $version );
		} else {
			$this->assertSame( $expect, $obj->parseVersion( $version ) );
			$this->assertSame( null === $normalized ? $version : $normalized, $obj->normalizeVersion( $version ) );
		}
	}

	/**
	 * Data provider for testParseVersion.
	 */
	public static function provideParseVersion() {
		return array(
			array(
				'1.2.3',
				array(
					'major'      => 1,
					'minor'      => 2,
					'patch'      => 3,
					'version'    => '1.2.3',
					'prerelease' => null,
					'buildinfo'  => null,
				),
			),
			array(
				'11.22.33',
				array(
					'major'      => 11,
					'minor'      => 22,
					'patch'      => 33,
					'version'    => '11.22.33',
					'prerelease' => null,
					'buildinfo'  => null,
				),
			),
			array(
				'0.0.0',
				array(
					'major'      => 0,
					'minor'      => 0,
					'patch'      => 0,
					'version'    => '0.0.0',
					'prerelease' => null,
					'buildinfo'  => null,
				),
			),
			array(
				'1.2.3-alpha',
				array(
					'major'      => 1,
					'minor'      => 2,
					'patch'      => 3,
					'version'    => '1.2.3',
					'prerelease' => 'alpha',
					'buildinfo'  => null,
				),
			),
			array(
				'1.2.3-alpha.1',
				array(
					'major'      => 1,
					'minor'      => 2,
					'patch'      => 3,
					'version'    => '1.2.3',
					'prerelease' => 'alpha.1',
					'buildinfo'  => null,
				),
			),
			array(
				'1.2.3+foobar',
				array(
					'major'      => 1,
					'minor'      => 2,
					'patch'      => 3,
					'version'    => '1.2.3',
					'prerelease' => null,
					'buildinfo'  => 'foobar',
				),
			),
			array(
				'1.2.3+foobar.2',
				array(
					'major'      => 1,
					'minor'      => 2,
					'patch'      => 3,
					'version'    => '1.2.3',
					'prerelease' => null,
					'buildinfo'  => 'foobar.2',
				),
			),
			array(
				'1.2.3-alpha+foobar',
				array(
					'major'      => 1,
					'minor'      => 2,
					'patch'      => 3,
					'version'    => '1.2.3',
					'prerelease' => 'alpha',
					'buildinfo'  => 'foobar',
				),
			),
			array(
				'1.2.3-alpha.1+foobar.2',
				array(
					'major'      => 1,
					'minor'      => 2,
					'patch'      => 3,
					'version'    => '1.2.3',
					'prerelease' => 'alpha.1',
					'buildinfo'  => 'foobar.2',
				),
			),
			array(
				'0001.0002.000-000alpha000.0001+000foobar000.0002',
				array(
					'major'      => 1,
					'minor'      => 2,
					'patch'      => 0,
					'version'    => '1.2.0',
					'prerelease' => '000alpha000.1',
					'buildinfo'  => '000foobar000.0002',
				),
				'1.2.0-000alpha000.1+000foobar000.0002',
			),

			array( '1.2', new InvalidArgumentException( 'Version number "1.2" is not in a recognized format.' ) ),
			array( '1.2.x', new InvalidArgumentException( 'Version number "1.2.x" is not in a recognized format.' ) ),
			array( '1.x.4', new InvalidArgumentException( 'Version number "1.x.4" is not in a recognized format.' ) ),
			array( '1..4', new InvalidArgumentException( 'Version number "1..4" is not in a recognized format.' ) ),
			array( '.2.3', new InvalidArgumentException( 'Version number ".2.3" is not in a recognized format.' ) ),
			array( '1.2.', new InvalidArgumentException( 'Version number "1.2." is not in a recognized format.' ) ),
			array( '1.2.-1', new InvalidArgumentException( 'Version number "1.2.-1" is not in a recognized format.' ) ),
			array( 'v1.2.3', new InvalidArgumentException( 'Version number "v1.2.3" is not in a recognized format.' ) ),
			array( '1.2.3.4', new InvalidArgumentException( 'Version number "1.2.3.4" is not in a recognized format.' ) ),
			array( '1.2-alpha', new InvalidArgumentException( 'Version number "1.2-alpha" is not in a recognized format.' ) ),
			array( '1.2.3-?', new InvalidArgumentException( 'Version number "1.2.3-?" is not in a recognized format.' ) ),
			array( '1.2.3+?', new InvalidArgumentException( 'Version number "1.2.3+?" is not in a recognized format.' ) ),
			array( '1.2.3-a..b', new InvalidArgumentException( 'Version number "1.2.3-a..b" is not in a recognized format.' ) ),
			array( '1.2.3+a..b', new InvalidArgumentException( 'Version number "1.2.3+a..b" is not in a recognized format.' ) ),
		);
	}

	/**
	 * Test normalizeVersion, beyond what testParseVersion tests.
	 *
	 * @dataProvider provideNormalizeVersion
	 * @param string|array                    $version Version.
	 * @param string|InvalidArgumentException $expect Expected result.
	 * @param array                           $extra Extra, if any.
	 */
	#[DataProvider( 'provideNormalizeVersion' )]
	public function testNormalizeVersion( $version, $expect, $extra = array() ) {
		$obj = new SemverVersioning();
		if ( $expect instanceof InvalidArgumentException ) {
			$this->expectException( InvalidArgumentException::class );
			$this->expectExceptionMessage( $expect->getMessage() );
			$obj->normalizeVersion( $version, $extra );
		} else {
			$this->assertSame( $expect, $obj->normalizeVersion( $version, $extra ) );
		}
	}

	/**
	 * Data provider for testNormalizeVersion.
	 */
	public static function provideNormalizeVersion() {
		return array(
			'add prerelease = alpha'          => array(
				'1.2.3',
				'1.2.3-alpha',
				array( 'prerelease' => 'alpha' ),
			),
			'add prerelease and buildinfo'    => array(
				'1.2.3-alpha',
				'1.2.3-beta+12345',
				array(
					'prerelease' => 'beta',
					'buildinfo'  => '12345',
				),
			),
			'remove prerelease and buildinfo' => array(
				'1.2.3-beta+12345',
				'1.2.3',
				array(
					'prerelease' => null,
					'buildinfo'  => null,
				),
			),

			'Invalid array input'             => array(
				array(
					'major' => 1,
					'minor' => 2,
				),
				new InvalidArgumentException( 'Version array is not in a recognized format.' ),
			),
			'Invalid prerelease component'    => array(
				'1.2.3',
				new InvalidArgumentException( 'Invalid prerelease data' ),
				array( 'prerelease' => 'delta?' ),
			),
			'Invalid buildinfo component'     => array(
				'1.2.3',
				new InvalidArgumentException( 'Invalid buildinfo data' ),
				array( 'buildinfo' => 'build?' ),
			),
		);
	}

	/**
	 * Test nextVersion.
	 *
	 * @dataProvider provideNextVersion
	 * @param string                          $version Version.
	 * @param ChangeEntry[]                   $changes Changes.
	 * @param array                           $extra Extra components.
	 * @param string|InvalidArgumentException $expect Expected result.
	 * @param string                          $expectOutput Expected output.
	 */
	#[DataProvider( 'provideNextVersion' )]
	public function testNextVersion( $version, array $changes, array $extra, $expect, $expectOutput = '' ) {
		$obj = new SemverVersioning();

		$out = new class() extends BufferedOutput {
			public $err;

			public function __construct() {
				$this->err = new BufferedOutput();
			}

			public function getErrorOutput() {
				return $this->err;
			}
		};

		$obj->setIO( new ArrayInput( array() ), $out );

		if ( $expect instanceof InvalidArgumentException ) {
			$this->expectException( InvalidArgumentException::class );
			$this->expectExceptionMessage( $expect->getMessage() );
			$obj->nextVersion( $version, $changes, $extra );
		} else {
			$this->assertSame( $expect, $obj->nextVersion( $version, $changes, $extra ) );
			$this->assertSame( '', $out->fetch() );
			$this->assertSame( $expectOutput, $out->err->fetch() );
		}
	}

	/**
	 * Data provider for testNextVersion.
	 */
	public static function provideNextVersion() {
		return array(
			'No changes'                               => array(
				'1.2.3',
				array(),
				array(),
				'1.2.4',
			),
			'Patch changes'                            => array(
				'1.2.3',
				array(
					new ChangeEntry( array( 'significance' => 'patch' ) ),
					new ChangeEntry( array( 'significance' => 'patch' ) ),
					new ChangeEntry( array( 'significance' => null ) ),
					new ChangeEntry( array( 'significance' => 'patch' ) ),
				),
				array(),
				'1.2.4',
			),
			'Minor change'                             => array(
				'1.2.3',
				array(
					new ChangeEntry( array( 'significance' => 'patch' ) ),
					new ChangeEntry( array( 'significance' => 'minor' ) ),
					new ChangeEntry( array( 'significance' => null ) ),
					new ChangeEntry( array( 'significance' => 'patch' ) ),
				),
				array(),
				'1.3.0',
			),
			'Major change'                             => array(
				'1.2.3',
				array(
					new ChangeEntry( array( 'significance' => 'patch' ) ),
					new ChangeEntry( array( 'significance' => 'minor' ) ),
					new ChangeEntry( array( 'significance' => null ) ),
					new ChangeEntry( array( 'significance' => 'major' ) ),
					new ChangeEntry( array( 'significance' => 'patch' ) ),
				),
				array(),
				'2.0.0',
			),
			'Version number with extra components'     => array(
				'1.2.3-foo',
				array(),
				array(),
				'1.2.4',
			),
			'Version number with extra components (2)' => array(
				'1.2.9+123',
				array(),
				array(),
				'1.2.10',
			),
			'Version number with extra components (3)' => array(
				'1.2.3-foo+bar',
				array(),
				array(),
				'1.2.4',
			),

			'Non-major update for version 0'           => array(
				'0.1.2',
				array(
					new ChangeEntry( array( 'significance' => 'patch' ) ),
					new ChangeEntry( array( 'significance' => 'minor' ) ),
					new ChangeEntry( array( 'significance' => null ) ),
					new ChangeEntry( array( 'significance' => 'patch' ) ),
				),
				array(),
				'0.2.0',
			),
			'Major update for version 0'               => array(
				'0.1.2',
				array(
					new ChangeEntry( array( 'significance' => 'patch' ) ),
					new ChangeEntry( array( 'significance' => 'major' ) ),
					new ChangeEntry( array( 'significance' => null ) ),
					new ChangeEntry( array( 'significance' => 'patch' ) ),
				),
				array(),
				'0.2.0',
				"<warning>Semver does not automatically move version 0.y.z to 1.0.0.\n<warning>You will have to do that manually when you're ready for the first release.\n",
			),

			'Including extra components'               => array(
				'1.2.3',
				array(),
				array(
					'prerelease' => 'alpha.002',
					'buildinfo'  => 'g12345678.003',
				),
				'1.2.4-alpha.2+g12345678.003',
			),
			'Including extra components (2)'           => array(
				'1.2.3-foo',
				array(),
				array( 'buildinfo' => 'g12345678' ),
				'1.2.4+g12345678',
			),

			'Invalid prerelease component'             => array(
				'1.2.3-foo',
				array(),
				array( 'prerelease' => 'delta?' ),
				new InvalidArgumentException( 'Invalid prerelease data' ),
			),
			'Invalid buildinfo component'              => array(
				'1.2.3-foo',
				array(),
				array( 'buildinfo' => 'build?' ),
				new InvalidArgumentException( 'Invalid buildinfo data' ),
			),
		);
	}

	/**
	 * Test nextVersion, 0.x version major update with non-console output.
	 */
	public function testNextVersion_majorNonConsole() {
		$obj = new SemverVersioning();
		$out = new BufferedOutput();
		$obj->setIO( new ArrayInput( array() ), $out );
		$this->assertSame(
			'0.2.0',
			$obj->nextVersion( '0.1.2', array( new ChangeEntry( array( 'significance' => 'major' ) ) ) )
		);
		$this->assertSame( '', $out->fetch() );
	}

	/**
	 * Test compareVersions.
	 *
	 * @dataProvider provideCompareVersions
	 * @param string $a Version A.
	 * @param string $expect Expected result converted to a string, '>', '==', or '<'.
	 * @param string $b Version B.
	 */
	#[DataProvider( 'provideCompareVersions' )]
	public function testCompareVersions( $a, $expect, $b ) {
		$obj = new SemverVersioning();
		$ret = $obj->compareVersions( $a, $b );
		$this->assertIsInt( $ret );
		$ret = $ret < 0 ? '<' : ( $ret > 0 ? '>' : '==' );
		$this->assertSame( $expect, $ret );
	}

	/**
	 * Data provider for testCompareVersions.
	 */
	public static function provideCompareVersions() {
		return array(
			array( '1.0.0', '==', '1.0.0' ),
			array( '1.0.0', '<', '2.0.0' ),
			array( '2.0.0', '>', '1.0.0' ),
			array( '1.1.0', '>', '1.0.0' ),
			array( '1.0.0', '<', '1.1.0' ),
			array( '1.999.999', '<', '2.0.0' ),
			array( '1.1.0', '<', '1.1.1' ),
			array( '1.0.999', '<', '1.1.0' ),
			array( '1.1.2', '>', '1.1.1' ),
			array( '1.1.1-dev', '<', '1.1.1' ),
			array( '1.1.1', '>', '1.1.1-p1' ),
			array( '1.1.1-alpha', '<', '1.1.1-beta' ),
			array( '1.1.1-dev', '>', '1.1.1-beta' ), // No special treatment for "dev".
			array( '1.1.1-alpha.9', '<', '1.1.1-beta.1' ),
			array( '1.1.1-beta.9', '>', '1.1.1-beta.1' ),
			array( '1.1.1-beta.9', '==', '1.1.1-beta.9' ),
			array( '1.1.1-beta.9.1', '>', '1.1.1-beta.9' ),
			array( '1.1.1-beta.9', '<', '1.1.1-beta.a' ),
			array( '1.1.1-beta.1a', '>', '1.1.1-beta.9' ),
			array( '1.1.1-BETA', '<', '1.1.1-beta' ), // Case sensitive.
			array( '1.1.1-ZETA', '<', '1.1.1-beta' ), // Case sensitive.
			array( '1.1.1-alpha2', '>', '1.1.1-alpha10' ), // No natural sorting.
			array( '1.1.1+beta.9.1', '==', '1.1.1+beta.9' ),
		);
	}

	/**
	 * Test firstVersion.
	 *
	 * @dataProvider provideFirstVersion
	 * @param array                           $extra Extra components.
	 * @param string|InvalidArgumentException $expect Expected result.
	 */
	#[DataProvider( 'provideFirstVersion' )]
	public function testFirstVersion( array $extra, $expect ) {
		$obj = new SemverVersioning();

		if ( $expect instanceof InvalidArgumentException ) {
			$this->expectException( InvalidArgumentException::class );
			$this->expectExceptionMessage( $expect->getMessage() );
			$obj->firstVersion( $extra );
		} else {
			$this->assertSame( $expect, $obj->firstVersion( $extra ) );
		}
	}

	/**
	 * Data provider for testFirstVersion.
	 */
	public static function provideFirstVersion() {
		return array(
			'Normal'             => array(
				array(),
				'0.1.0',
			),
			'Some extra'         => array(
				array( 'prerelease' => 'alpha' ),
				'0.1.0-alpha',
			),
			'Invalid prerelease' => array(
				array( 'prerelease' => 'delta?' ),
				new InvalidArgumentException( 'Invalid prerelease data' ),
			),
			'Invalid buildinfo'  => array(
				array( 'buildinfo' => 'build?' ),
				new InvalidArgumentException( 'Invalid buildinfo data' ),
			),
		);
	}
}
