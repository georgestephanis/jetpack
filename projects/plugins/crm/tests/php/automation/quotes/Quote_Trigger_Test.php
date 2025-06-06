<?php

namespace Automattic\Jetpack\CRM\Automation\Tests;

use Automattic\Jetpack\CRM\Automation\Automation_Workflow;
use Automattic\Jetpack\CRM\Automation\Data_Types\Quote_Data;
use Automattic\Jetpack\CRM\Automation\Triggers\Quote_Accepted;
use Automattic\Jetpack\CRM\Automation\Triggers\Quote_Created;
use Automattic\Jetpack\CRM\Automation\Triggers\Quote_Deleted;
use Automattic\Jetpack\CRM\Automation\Triggers\Quote_Status_Updated;
use Automattic\Jetpack\CRM\Automation\Triggers\Quote_Updated;
use Automattic\Jetpack\CRM\Entities\Quote;
use Automattic\Jetpack\CRM\Tests\JPCRM_Base_TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;

require_once __DIR__ . '../../tools/class-automation-faker.php';

/**
 * Test Automation's quote triggers
 *
 * @covers Automattic\Jetpack\CRM\Automation\Triggers\Quote_Accepted
 * @covers Automattic\Jetpack\CRM\Automation\Triggers\Quote_Created
 * @covers Automattic\Jetpack\CRM\Automation\Triggers\Quote_Deleted
 * @covers Automattic\Jetpack\CRM\Automation\Triggers\Quote_Status_Updated
 * @covers Automattic\Jetpack\CRM\Automation\Triggers\Quote_Updated
 */
#[CoversClass( Quote_Accepted::class )]
#[CoversClass( Quote_Created::class )]
#[CoversClass( Quote_Deleted::class )]
#[CoversClass( Quote_Status_Updated::class )]
#[CoversClass( Quote_Updated::class )]
class Quote_Trigger_Test extends JPCRM_Base_TestCase {

	private $automation_faker;

	public function setUp(): void {
		parent::setUp();
		$this->automation_faker = Automation_Faker::instance();
	}

	/**
	 * @testdox Test the quote updated trigger executes the workflow with an action
	 */
	#[TestDox( 'Test the quote updated trigger executes the workflow with an action' )]
	public function test_quote_updated_trigger() {

		$workflow_data = $this->automation_faker->workflow_without_initial_step_customize_trigger( 'jpcrm/quote_updated' );

		$trigger = new Quote_Updated();

		// Build a PHPUnit mock Automation_Workflow
		$workflow = $this->getMockBuilder( Automation_Workflow::class )
			->setConstructorArgs( array( $workflow_data ) )
			->onlyMethods( array( 'execute' ) )
			->getMock();

		// Init the Quote_Updated trigger.
		$trigger->init( $workflow );

		/** @var Quote $quote */
		$quote      = $this->automation_faker->quote();
		$quote_data = new Quote_Data( $quote );

		// We expect the workflow to be executed on quote_update event with the quote data
		$workflow->expects( $this->once() )
		->method( 'execute' )
		->with(
			$trigger,
			$quote_data
		);

		// Run the quote_update action.
		do_action( 'jpcrm_quote_update', $quote );
	}

	/**
	 * @testdox Test the quote status updated trigger executes the workflow with an action
	 */
	#[TestDox( 'Test the quote status updated trigger executes the workflow with an action' )]
	public function test_quote_status_updated_trigger() {

		$workflow_data = $this->automation_faker->workflow_without_initial_step_customize_trigger( 'jpcrm/quote_status_updated' );

		$trigger = new Quote_Status_Updated();

		// Build a PHPUnit mock Automation_Workflow
		$workflow = $this->getMockBuilder( Automation_Workflow::class )
			->setConstructorArgs( array( $workflow_data ) )
			->onlyMethods( array( 'execute' ) )
			->getMock();

		// Init the Quote_Updated trigger.
		$trigger->init( $workflow );

		/** @var Quote $quote */
		$quote      = $this->automation_faker->quote();
		$quote_data = new Quote_Data( $quote );

		// We expect the workflow to be executed on quote_status_update event with the quote data
		$workflow->expects( $this->once() )
		->method( 'execute' )
		->with(
			$trigger,
			$quote_data
		);

		// Run the quote_status_update action.
		do_action( 'jpcrm_quote_status_update', $quote );
	}

	/**
	 * @testdox Test the quote created trigger executes the workflow with an action
	 */
	#[TestDox( 'Test the quote created trigger executes the workflow with an action' )]
	public function test_quote_created_trigger() {

		$workflow_data = $this->automation_faker->workflow_without_initial_step_customize_trigger( 'jpcrm/quote_created' );

		$trigger = new Quote_Created();

		// Build a PHPUnit mock Automation_Workflow
		$workflow = $this->getMockBuilder( Automation_Workflow::class )
			->setConstructorArgs( array( $workflow_data ) )
			->onlyMethods( array( 'execute' ) )
			->getMock();

		// Init the Quote_Created trigger.
		$trigger->init( $workflow );

		/** @var Quote $quote */
		$quote      = $this->automation_faker->quote();
		$quote_data = new Quote_Data( $quote );

		// We expect the workflow to be executed on quote_created event with the quote data
		$workflow->expects( $this->once() )
		->method( 'execute' )
		->with(
			$trigger,
			$quote_data
		);

		// Run the quote_created action.
		do_action( 'jpcrm_quote_created', $quote );
	}

	/**
	 * @testdox Test the quote new trigger executes the workflow with an action
	 */
	#[TestDox( 'Test the quote new trigger executes the workflow with an action' )]
	public function test_quote_accepted_trigger() {

		$workflow_data = $this->automation_faker->workflow_without_initial_step_customize_trigger( 'jpcrm/quote_accepted' );

		$trigger = new Quote_Accepted();

		// Build a PHPUnit mock Automation_Workflow
		$workflow = $this->getMockBuilder( Automation_Workflow::class )
			->setConstructorArgs( array( $workflow_data ) )
			->onlyMethods( array( 'execute' ) )
			->getMock();

		// Init the Quote_Created trigger.
		$trigger->init( $workflow );

		/** @var Quote $quote */
		$quote      = $this->automation_faker->quote();
		$quote_data = new Quote_Data( $quote );

		// We expect the workflow to be executed on quote_created event with the quote data
		$workflow->expects( $this->once() )
		->method( 'execute' )
		->with(
			$trigger,
			$quote_data
		);

		// Notify the quote_accepted event.
		do_action( 'jpcrm_quote_accepted', $quote );
	}

	/**
	 * @testdox Test the quote deleted trigger executes the workflow with an action
	 */
	#[TestDox( 'Test the quote deleted trigger executes the workflow with an action' )]
	public function test_quote_deleted_trigger() {

		$workflow_data = $this->automation_faker->workflow_without_initial_step_customize_trigger( 'jpcrm/quote_deleted' );

		$trigger = new Quote_Deleted();

		// Build a PHPUnit mock Automation_Workflow
		$workflow = $this->getMockBuilder( Automation_Workflow::class )
			->setConstructorArgs( array( $workflow_data ) )
			->onlyMethods( array( 'execute' ) )
			->getMock();

		// Init the Quote_Deleted trigger.
		$trigger->init( $workflow );

		/** @var Quote $quote */
		$quote      = $this->automation_faker->quote();
		$quote_data = new Quote_Data( $quote );

		// We expect the workflow to be executed on quote_deleted event with the quote data
		$workflow->expects( $this->once() )
		->method( 'execute' )
		->with(
			$trigger,
			$quote_data
		);

		// Run the quote_deleted action.
		do_action( 'jpcrm_quote_delete', $quote );
	}
}
