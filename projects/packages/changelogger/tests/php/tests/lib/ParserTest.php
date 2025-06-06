<?php
/**
 * Tests for the changelog Parser base class.
 *
 * @package automattic/jetpack-changelogger
 */

namespace Automattic\Jetpack\Changelog\Tests;

use Automattic\Jetpack\Changelog\ChangeEntry;
use Automattic\Jetpack\Changelog\Changelog;
use Automattic\Jetpack\Changelog\ChangelogEntry;
use Automattic\Jetpack\Changelog\Parser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the changelog Parser base class.
 *
 * @covers \Automattic\Jetpack\Changelog\Parser
 */
#[CoversClass( Parser::class )]
class ParserTest extends TestCase {

	/**
	 * Get a mock Parser.
	 *
	 * @return Parser&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getMockParser() {
		return $this->getMockBuilder( Parser::class )
			->onlyMethods( array( 'parse', 'format' ) )
			->getMock();
	}

	/**
	 * Test parseFromFile.
	 */
	public function testParseFromFile() {
		$mock = $this->getMockParser();
		$mock->method( 'parse' )->willReturnArgument( 0 );

		$temp = tempnam( sys_get_temp_dir(), 'phpunit-testParseFromFile-' );
		try {
			file_put_contents( $temp, 'Foo bar?' );
			$this->assertSame( 'Foo bar?', $mock->parseFromFile( $temp ) );
		} finally {
			unlink( $temp );
		}

		$fp = fopen( 'php://memory', 'w+' );
		fwrite( $fp, 'Foo baz?' );
		rewind( $fp );
		$this->assertSame( 'Foo baz?', $mock->parseFromFile( $fp ) );
	}

	/**
	 * Test formatToFile.
	 */
	public function testFormatToFile() {
		$mock      = $this->getMockParser();
		$changelog = new Changelog();
		$mock->method( 'format' )
			->with( $this->identicalTo( $changelog ) )
			->willReturn( 'Formatted?' );

		$temp = tempnam( sys_get_temp_dir(), 'phpunit-testFormatToFile-' );
		try {
			file_put_contents( $temp, 'Foo bar?' );
			$this->assertTrue( $mock->formatToFile( $temp, $changelog ) );
			$this->assertSame( 'Formatted?', file_get_contents( $temp ) );
		} finally {
			unlink( $temp );
		}

		$fp = fopen( 'php://memory', 'w+' );
		fwrite( $fp, 'Foo baz?' );
		$this->assertTrue( $mock->formatToFile( $fp, $changelog ) );
		fwrite( $fp, '!' );
		rewind( $fp );
		$this->assertSame( 'Foo baz?Formatted?!', stream_get_contents( $fp ) );

		$fp = fopen( 'php://memory', 'r' );
		$this->assertFalse( $mock->formatToFile( $fp, $changelog ) );
	}

	/**
	 * Test newChangelogEntry.
	 */
	public function testNewChangelogEntry() {
		$mock = $this->getMockParser();
		$this->assertInstanceOf( ChangelogEntry::class, $mock->newChangelogEntry( '1.0' ) );
	}

	/**
	 * Test newChangeEntry.
	 */
	public function testNewChangeEntry() {
		$mock = $this->getMockParser();
		$this->assertInstanceOf( ChangeEntry::class, $mock->newChangeEntry() );
	}
}
