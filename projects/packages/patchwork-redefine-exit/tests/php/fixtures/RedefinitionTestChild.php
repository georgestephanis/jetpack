<?php
/**
 * "Child" test for RedefinitionTest::testRemoval().
 *
 * @package automattic/patchwork-redefine-exit
 */

namespace Automattic\RedefineExit\Tests;

use PHPUnit\Framework\TestCase;

/**
 * "Child" test for RedefinitionTest::testRemoval().
 *
 * This should not be run normally.
 */
// phpcs:ignore Jetpack.PHPUnit.TestClassName.DoesNotEndWithTest
class RedefinitionTestChild extends TestCase {

	public function tearDown(): void {
		parent::tearDown();
		\Patchwork\restoreAll();
	}

	public function testSomething() {
		$this->assertTrue( true );
	}
}
