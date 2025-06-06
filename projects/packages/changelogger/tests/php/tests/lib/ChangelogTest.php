<?php
/**
 * Tests for the changelog Changelog class.
 *
 * @package automattic/jetpack-changelogger
 */

namespace Automattic\Jetpack\Changelog\Tests;

use Automattic\Jetpack\Changelog\Changelog;
use Automattic\Jetpack\Changelog\ChangelogEntry;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the changelog Changelog class.
 *
 * @covers \Automattic\Jetpack\Changelog\Changelog
 */
#[CoversClass( Changelog::class )]
class ChangelogTest extends TestCase {

	/**
	 * Test prologue and epilogue.
	 */
	public function testPrologueAndEpilogue() {
		$changelog = new Changelog();
		$this->assertSame( '', $changelog->getPrologue() );
		$this->assertSame( '', $changelog->getEpilogue() );

		$this->assertSame( $changelog, $changelog->setPrologue( 'Foo' )->setEpilogue( 'Bar' ) );
		$this->assertSame( 'Foo', $changelog->getPrologue() );
		$this->assertSame( 'Bar', $changelog->getEpilogue() );

		// @phan-suppress-next-line PhanTypeMismatchArgument -- This is testing the type casting.
		$this->assertSame( $changelog, $changelog->setPrologue( 123 )->setEpilogue( 456 ) );
		$this->assertSame( '123', $changelog->getPrologue() );
		$this->assertSame( '456', $changelog->getEpilogue() );
	}

	/**
	 * Test entries.
	 */
	public function testEntries() {
		$changelog = new Changelog();
		$entries   = array(
			4 => new ChangelogEntry( '4.0' ),
			3 => new ChangelogEntry( '3.0' ),
			2 => new ChangelogEntry( '2.0' ),
			1 => new ChangelogEntry( '1.0' ),
		);

		$this->assertSame( array(), $changelog->getEntries() );
		$this->assertSame( null, $changelog->getLatestEntry() );
		$this->assertSame( array(), $changelog->getVersions() );

		$this->assertSame( $changelog, $changelog->setEntries( $entries ) );
		$this->assertSame( array_values( $entries ), $changelog->getEntries() );
		$this->assertSame( $entries[4], $changelog->getLatestEntry() );
		$this->assertSame( array( '4.0', '3.0', '2.0', '1.0' ), $changelog->getVersions() );

		$e = new ChangelogEntry( '5.0' );
		$this->assertSame( $changelog, $changelog->addEntry( $e ) );
		$this->assertSame( array( $e, $entries[4], $entries[3], $entries[2], $entries[1] ), $changelog->getEntries() );
		$this->assertSame( $e, $changelog->getLatestEntry() );
		$this->assertSame( array( '5.0', '4.0', '3.0', '2.0', '1.0' ), $changelog->getVersions() );
	}

	/**
	 * Test setEntries error.
	 */
	public function testSetEntries_error1() {
		$changelog = new Changelog();
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Automattic\\Jetpack\\Changelog\\Changelog::setEntries: Expected a ChangelogEntry, got NULL at index 0' );
		// @phan-suppress-next-line PhanTypeMismatchArgument -- This is testing the error case.
		$changelog->setEntries( array( null ) );
	}

	/**
	 * Test setEntries error.
	 */
	public function testSetEntries_error2() {
		$changelog = new Changelog();
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Automattic\\Jetpack\\Changelog\\Changelog::setEntries: Expected a ChangelogEntry, got Automattic\\Jetpack\\Changelog\\Changelog at index 0' );
		// @phan-suppress-next-line PhanTypeMismatchArgument -- This is testing the error case.
		$changelog->setEntries( array( $changelog ) );
	}

	/**
	 * Test JSON serialization.
	 *
	 * @dataProvider provideJson
	 * @param string           $json JSON data.
	 * @param Changelog|string $changelog Changelog, or error message if decoding should fail.
	 */
	#[DataProvider( 'provideJson' )]
	public function testJson( $json, $changelog ) {
		if ( is_string( $changelog ) ) {
			$this->expectException( InvalidArgumentException::class );
			$this->expectExceptionMessage( $changelog );
			Changelog::jsonUnserialize( json_decode( $json ) );
		} else {
			$this->assertSame( $json, json_encode( $changelog ) );
			$this->assertEquals( $changelog, Changelog::jsonUnserialize( json_decode( $json ) ) );
		}
	}

	/**
	 * Data provider for testJson.
	 */
	public static function provideJson() {
		return array(
			'Basic serialization'              => array(
				'{"__class__":"Automattic\\\\Jetpack\\\\Changelog\\\\Changelog","prologue":"","epilogue":"","entries":[]}',
				new Changelog(),
			),
			'Serialization with data'          => array(
				'{"__class__":"Automattic\\\\Jetpack\\\\Changelog\\\\Changelog","prologue":"Prologue","epilogue":"Epilogue","entries":[{"__class__":"Automattic\\\\Jetpack\\\\Changelog\\\\ChangelogEntry","version":"1.1","link":null,"timestamp":"2021-02-18T00:00:00+0000","prologue":"","epilogue":"","changes":[]},{"__class__":"Automattic\\\\Jetpack\\\\Changelog\\\\ChangelogEntry","version":"1.0","link":null,"timestamp":"2021-02-17T00:00:00+0000","prologue":"","epilogue":"","changes":[]}]}',
				( new Changelog() )->setPrologue( 'Prologue' )->setEpilogue( 'Epilogue' )->setEntries(
					array(
						new ChangelogEntry( '1.1', array( 'timestamp' => '2021-02-18' ) ),
						new ChangelogEntry( '1.0', array( 'timestamp' => '2021-02-17' ) ),
					)
				),
			),
			'Bad unserialization, no class'    => array(
				'{"prologue":"","epilogue":"","entries":[]}',
				'Invalid data',
			),
			'Bad unserialization, wrong class' => array(
				'{"__class__":"Automattic\\\\Jetpack\\\\Changelog\\\\ChangelogEntry","version":"1.0","link":null,"timestamp":"2021-02-18T00:00:00+0000","prologue":"","epilogue":"","changes":[]}',
				'Cannot instantiate Automattic\\Jetpack\\Changelog\\ChangelogEntry via Automattic\\Jetpack\\Changelog\\Changelog::jsonUnserialize',
			),
		);
	}
}
