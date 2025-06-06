<?php
/**
 * Tests the TOS package.
 *
 * @package automattic/jetpack-connection
 */

namespace Automattic\Jetpack;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class Terms_Of_Service_Test
 *
 * @package Automattic\Jetpack
 * @covers \Automattic\Jetpack\Terms_Of_Service
 */
#[CoversClass( Terms_Of_Service::class )]
class Terms_Of_Service_Test extends TestCase {
	use MockeryPHPUnitIntegration;

	/**
	 * Terms_Of_Service mock object.
	 *
	 * @var Terms_Of_Service
	 */
	public $terms_of_service;

	/**
	 * Test setup.
	 */
	public function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->terms_of_service = $this->createPartialMock(
			__NAMESPACE__ . '\\Terms_Of_Service',
			array( 'get_raw_has_agreed', 'is_offline_mode', 'set_agree', 'set_reject' )
		);
	}

	/**
	 * Test teardown.
	 */
	public function tearDown(): void {
		parent::tearDown();
		Monkey\tearDown();
	}

	/**
	 * Tests the agree function.
	 */
	public function test_agree() {
		Functions\expect( 'do_action' )->once()->with( 'jetpack_agreed_to_terms_of_service' );
		Functions\expect( 'do_action' )->never();
		$this->terms_of_service->expects( $this->once() )->method( 'set_agree' );

		$this->terms_of_service->agree();
	}

	/**
	 * Tests the revoke function.
	 */
	public function test_revoke() {
		Functions\expect( 'do_action' )->never();
		Functions\expect( 'do_action' )->once()->with( 'jetpack_reject_terms_of_service' );
		$this->terms_of_service->expects( $this->once() )->method( 'set_reject' );

		$this->terms_of_service->reject();
	}

	/**
	 * Tests if has_agreed returns correctly if TOS not agreed to.
	 */
	public function test_returns_false_if_not_agreed() {
		$this->terms_of_service->expects( $this->once() )->method( 'get_raw_has_agreed' )->willReturn( false );
		$this->assertFalse( $this->terms_of_service->has_agreed() );
	}

	/**
	 * Tests if has_agreed returns corrected if agreed but in dev mode.
	 */
	public function test_returns_false_if_has_agreed_but_is_offline_mode() {
		// is_offline_mode.
		$this->terms_of_service->method( 'get_raw_has_agreed' )->willReturn( true );
		$this->terms_of_service->expects( $this->once() )->method( 'is_offline_mode' )->willReturn( true );
		$this->assertFalse( $this->terms_of_service->has_agreed() );
	}
}
