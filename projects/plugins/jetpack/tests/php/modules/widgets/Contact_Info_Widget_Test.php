<?php

use PHPUnit\Framework\Attributes\CoversClass;

require_once JETPACK__PLUGIN_DIR . 'modules/widgets/contact-info.php';

/**
 * Test class for the Contact Info & Map Widget.
 *
 * @covers Jetpack_Contact_Info_Widget
 */
#[CoversClass( Jetpack_Contact_Info_Widget::class )]
class Contact_Info_Widget_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	const TEST_API_KEY = '12345abcde';

	private $contact_info_widget;

	/**
	 * This method is called before each test.
	 */
	public function set_up() {
		parent::set_up();
		remove_all_filters( 'jetpack_google_maps_api_key' );
		$this->contact_info_widget = new Jetpack_Contact_Info_Widget();
	}

	/**
	 * No filter callback is set. The API key field should be displayed.
	 */
	public function test_form_apikey_field_with_no_filter() {
		ob_start();
		$this->contact_info_widget->form( null );
		$output_string = ob_get_clean();

		$this->assertStringNotContainsString( '<input type="hidden" id="widget-widget_contact_info', $output_string );
	}

	/**
	 * The filter callback returns the same api key as $instance['apikey'].
	 * The API key field should not be displayed.
	 */
	public function test_form_apikey_field_filter_with_instance_apikey() {
		$instance           = array();
		$instance['apikey'] = self::TEST_API_KEY;

		add_filter(
			'jetpack_google_maps_api_key',
			function () {
				return self::TEST_API_KEY;
			}
		);

		ob_start();
		$this->contact_info_widget->form( $instance );
		$output_string = ob_get_clean();

		$this->assertStringContainsString( '<input type="hidden" id="widget-widget_contact_info', $output_string );
	}

	/**
	 * The filter callback returns the input value. The API field should
	 * be displayed.
	 */
	public function test_form_apikey_field_with_pass_through_filter() {
		$instance           = array();
		$instance['apikey'] = self::TEST_API_KEY;

		add_filter(
			'jetpack_google_maps_api_key',
			function ( $value ) {
				return $value;
			}
		);

		ob_start();
		$this->contact_info_widget->form( $instance );
		$output_string = ob_get_clean();

		$this->assertStringNotContainsString( '<input type="hidden" id="widget-widget_contact_info', $output_string );
	}
}
