<?php
/**
 * Tests the Roles package/
 *
 * @package automattic/jetpack-roles
 */

namespace Automattic\Jetpack;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class Roles_Test
 *
 * @package Automattic\Jetpack
 * @covers \Automattic\Jetpack\Roles
 */
#[CoversClass( Roles::class )]
class Roles_Test extends TestCase {
	use MockeryPHPUnitIntegration;

	/**
	 * Roles instance.
	 *
	 * @var Roles
	 */
	protected $roles;

	/**
	 * Test setup.
	 */
	public function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->roles = new Roles();
	}

	/**
	 * Test teardown.
	 */
	public function tearDown(): void {
		parent::tearDown();
		Monkey\tearDown();
	}

	/**
	 * Tests the current user by role.
	 */
	public function test_current_user_to_role_with_role() {
		Functions\when( 'current_user_can' )->alias(
			function ( $cap ) {
				return 'administrator' === $cap;
			}
		);

		$this->assertEquals( 'administrator', $this->roles->translate_current_user_to_role() );
	}

	/**
	 * Tests the current user by capability.
	 */
	public function test_current_user_to_role_with_capability() {
		Functions\when( 'current_user_can' )->alias(
			function ( $cap ) {
				return 'edit_others_posts' === $cap;
			}
		);

		$this->assertTrue( current_user_can( 'edit_others_posts' ) );
		// phpcs:ignore WordPress.WP.Capabilities.Unknown
		$this->assertFalse( current_user_can( 'foobar' ) );
		// phpcs:ignore WordPress.WP.Capabilities.RoleFound
		$this->assertFalse( current_user_can( 'administrator' ) );

		$this->assertEquals( 'editor', $this->roles->translate_current_user_to_role() );
	}

	/**
	 * Test current user with no match.
	 */
	public function test_current_user_to_role_with_no_match() {
		Functions\when( 'current_user_can' )->justReturn( false );

		$this->assertFalse( $this->roles->translate_current_user_to_role() );
	}

	/**
	 * Test translating an user to a role by role.
	 */
	public function test_user_to_role_with_role() {
		$user_mock = (object) array();
		'@phan-var \WP_User $user_mock'; // Not really, but it doesn't matter.

		Functions\when( 'user_can' )->alias(
			function ( $user, $cap ) use ( $user_mock ) {
				return $user_mock === $user && 'administrator' === $cap;
			}
		);

		$this->assertEquals( 'administrator', $this->roles->translate_user_to_role( $user_mock ) );
	}

	/**
	 * Test translating an user to a role by capablity.
	 */
	public function test_user_to_role_with_capability() {
		$user_mock = (object) array();
		'@phan-var \WP_User $user_mock'; // Not really, but it doesn't matter.

		Functions\when( 'user_can' )->alias(
			function ( $user, $cap ) use ( $user_mock ) {
				return $user_mock === $user && 'edit_others_posts' === $cap;
			}
		);

		$this->assertEquals( 'editor', $this->roles->translate_user_to_role( $user_mock ) );
	}

	/**
	 * Test translating an user to a role with no match.
	 */
	public function test_user_to_role_with_no_match() {
		$user_mock = (object) array();
		'@phan-var \WP_User $user_mock'; // Not really, but it doesn't matter.

		Functions\when( 'user_can' )->justReturn( false );

		$this->assertFalse( $this->roles->translate_user_to_role( $user_mock ) );
	}

	/**
	 * Test translating a role to a cap with an existing role.
	 */
	public function test_role_to_cap_existing_role() {
		$this->assertEquals( 'edit_others_posts', $this->roles->translate_role_to_cap( 'editor' ) );
	}

	/**
	 * Test translating a role to a cap with a non-existing role.
	 */
	public function test_role_to_cap_non_existing_role() {
		$this->assertFalse( $this->roles->translate_role_to_cap( 'follower' ) );
	}
}
