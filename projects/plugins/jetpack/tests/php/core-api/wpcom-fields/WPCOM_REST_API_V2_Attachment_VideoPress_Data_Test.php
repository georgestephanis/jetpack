<?php
/**
 * Tests that Attachments do have VideoPress data in REST API
 * responses if the VideoPress Module is active.
 *
 * @package automattic/jetpack
 */

/**
 * The base testcase class.
 */
require_once dirname( __DIR__, 2 ) . '/lib/Jetpack_REST_TestCase.php';

use Automattic\Jetpack\VideoPress\WPCOM_REST_API_V2_Attachment_VideoPress_Data;
use Automattic\Jetpack\VideoPress\WPCOM_REST_API_V2_Attachment_VideoPress_Field;
use PHPUnit\Framework\Attributes\Group;

/**
 * VideoPress Data field tests.
 *
 * @group videopress
 * @group rest-api
 */
#[Group( 'videopress' )]
#[Group( 'rest-api' )]
class WPCOM_REST_API_V2_Attachment_VideoPress_Data_Test extends Jetpack_REST_TestCase {
	/**
	 * Checks that the jetpack_videopress field is included in the schema
	 */
	public function test_attachment_fields_videopress_get_schema() {
		$plugin = new WPCOM_REST_API_V2_Attachment_VideoPress_Field();
		$schema = $plugin->get_schema();

		$this->assertSame(
			array(
				'$schema'     => 'http://json-schema.org/draft-04/schema#',
				'title'       => 'jetpack_videopress_guid',
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'description' => __( 'Unique VideoPress ID', 'jetpack' ),
			),
			$schema
		);
	}

	/**
	 * Checks that the jetpack_videopress field is filled with the VideoPress GUID
	 */
	public function test_attachment_fields_videopress_get() {
		$this->markTestSkipped( 'Test is broken' );
		// @phan-suppress-next-line PhanPluginUnreachableCode
		$mock = $this->getMockBuilder( WPCOM_REST_API_V2_Attachment_VideoPress_Data::class )
						->onlyMethods( array( 'get_videopress_data' ) )
						->getMock();

		$mock->expects( $this->once() )
				->method( 'get_videopress_data' )
				->willReturn(
					array(
						'guid'   => 'mocked_videopress_guid',
						'rating' => 'G',
					)
				);

		$attachment_id = self::factory()->attachment->create_upload_object( dirname( __DIR__, 2 ) . '/jetpack-icon.jpg', 0 );
		$object        = array(
			'id' => $attachment_id,
		);
		$request       = new WP_REST_Request( 'GET', sprintf( '/wp/v2/media/%d', $attachment_id ) );
		$data          = $mock->get( $object, $request );

		$this->assertSame(
			array(
				'guid'   => 'mocked_videopress_guid',
				'rating' => 'G',
			),
			$data
		);
	}

	/**
	 * Checks that the jetpack_videopress field is removed for non videos
	 */
	public function test_attachment_fields_videopress_remove_for_non_videos() {
		$plugin = new WPCOM_REST_API_V2_Attachment_VideoPress_Data();

		$attachment                 = new stdClass();
		$attachment->post_mime_type = 'non-video/test';
		'@phan-var WP_Post $attachment'; // Pretend it's the right class.

		$response       = new stdClass();
		$response->data = array(
			'jetpack_videopress' => array(
				'guid'   => 'my-guid',
				'rating' => 'G',
			),
		);
		'@phan-var WP_REST_Response $response'; // Pretend it's the right class.

		$response = $plugin->remove_field_for_non_videos( $response, $attachment );
		$this->assertArrayNotHasKey( 'jetpack_videopress', $response->data );
	}
}
