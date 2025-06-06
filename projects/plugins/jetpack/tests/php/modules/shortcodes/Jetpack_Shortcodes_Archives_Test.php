<?php

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;

require_once JETPACK__PLUGIN_DIR . '3rd-party/class.jetpack-amp-support.php';
require_once __DIR__ . '/trait.http-request-cache.php';

/**
 * @covers ::archives_shortcode
 */
#[CoversFunction( 'archives_shortcode' )]
class Jetpack_Shortcodes_Archives_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;
	use Automattic\Jetpack\Tests\HttpRequestCacheTrait;

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_exists() {
		$this->assertTrue( shortcode_exists( 'archives' ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives() {
		$content = '[archives]';

		$shortcode_content = do_shortcode( $content );

		$this->assertNotEquals( $content, $shortcode_content );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_type_default() {
		$archives = archives_shortcode( array() );

		$this->assertEquals( $archives, '<p>' . __( 'Your blog does not currently have any published posts.', 'jetpack' ) . '</p>' );
	}

	/**
	 * Gets the test data for test_shortcodes_archives_format_option().
	 *
	 * @since 8.5.0
	 *
	 * @return array The test data.
	 */
	public static function get_data_archives_format_option() {
		return array(
			'non_amp' => array(
				false,
				'<select name="archive-dropdown" onchange="document.location.href=this.options[this.selectedIndex].value;"><option value="">--</option>	<option value=\'{{permalink}}\'> {{title}} </option>' . "\n" . '</select>',
			),
			'amp'     => array(
				true,
				'<select name="archive-dropdown" on="change:AMP.navigateTo(url=event.value)"><option value="">--</option>	<option value=\'{{permalink}}\'> {{title}} </option>' . "\n" . '</select>',
			),
		);
	}

	/**
	 * Test [archives format="option"].
	 *
	 * @dataProvider get_data_archives_format_option
	 * @author scotchfield
	 * @since 3.2
	 *
	 * @param bool   $is_amp Whether this is an AMP endpoint.
	 * @param string $expected The expected return value of the shortcode callback.
	 */
	#[DataProvider( 'get_data_archives_format_option' )]
	public function test_shortcodes_archives_format_option( $is_amp, $expected ) {
		if ( $is_amp && defined( 'IS_WPCOM' ) && IS_WPCOM ) {
			self::markTestSkipped( 'WordPress.com is in the process of removing AMP plugin.' );
			return; // @phan-suppress-current-line PhanPluginUnreachableCode
		}

		if ( $is_amp ) {
			add_filter( 'jetpack_is_amp_request', '__return_true' );
		}

		$post     = self::factory()->post->create_and_get();
		$expected = str_replace(
			array( '{{permalink}}', '{{title}}' ),
			array( get_permalink( $post ), $post->post_title ),
			$expected
		);

		$this->assertEquals(
			$expected,
			archives_shortcode( array( 'format' => 'option' ) )
		);
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_format_html() {
		self::factory()->post->create( array() );
		$attr = array(
			'format' => 'html',
		);

		$archives = archives_shortcode( $attr );

		$this->assertEquals( '<ul', substr( $archives, 0, 3 ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_type_yearly() {
		self::factory()->post->create(
			array(
				'post_date' => '2014-01-01 01:00:00',
			)
		);
		$attr = array(
			'type' => 'yearly',
		);

		$archives = archives_shortcode( $attr );

		$this->assertEquals( ! false, strpos( $archives, 'm=2014' ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_type_monthly() {
		self::factory()->post->create(
			array(
				'post_date' => '2014-01-01 01:00:00',
			)
		);
		$attr = array(
			'type' => 'monthly',
		);

		$archives = archives_shortcode( $attr );

		$this->assertEquals( ! false, strpos( $archives, 'm=201401' ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_type_weekly() {
		self::factory()->post->create(
			array(
				'post_date' => '2014-01-01 01:00:00',
			)
		);
		$attr = array(
			'type' => 'weekly',
		);

		$archives = archives_shortcode( $attr );

		$this->assertEquals( ! false, strpos( $archives, 'w=1' ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_type_daily() {
		self::factory()->post->create(
			array(
				'post_date' => '2014-01-01 01:00:00',
			)
		);
		$attr = array(
			'type' => 'daily',
		);

		$archives = archives_shortcode( $attr );

		$this->assertEquals( ! false, strpos( $archives, 'm=20140101' ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_limit_one() {
		self::factory()->post->create( array() );
		self::factory()->post->create( array() );
		$attr = array(
			'format' => 'html',
			'limit'  => '1',
		);

		$archives = archives_shortcode( $attr );

		$this->assertSame( 1, substr_count( $archives, '<li>' ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_limit_zero_is_all() {
		self::factory()->post->create( array() );
		self::factory()->post->create( array() );
		$attr = array(
			'format' => 'html',
			'limit'  => '0',
		);

		$archives = archives_shortcode( $attr );

		$this->assertEquals( 2, substr_count( $archives, '<li>' ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_showcount() {
		self::factory()->post->create(
			array(
				'post_date' => '2014-01-01 01:00:00',
			)
		);
		self::factory()->post->create(
			array(
				'post_date' => '2014-01-01 01:00:00',
			)
		);
		$attr = array(
			'showcount' => 'true',
			'type'      => 'yearly',
		);

		$archives = archives_shortcode( $attr );

		$this->assertEquals( ! false, strpos( $archives, '(2)' ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_before() {
		$content = 'test_string';

		self::factory()->post->create( array() );
		$attr = array(
			'format' => 'html',
			'before' => $content,
		);

		$archives = archives_shortcode( $attr );

		$this->assertEquals( ! false, strpos( $archives, $content ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_after() {
		$content = 'test_string';

		self::factory()->post->create( array() );
		$attr = array(
			'format' => 'html',
			'after'  => $content,
		);

		$archives = archives_shortcode( $attr );

		$this->assertEquals( ! false, strpos( $archives, $content ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_order_asc() {
		self::factory()->post->create(
			array(
				'post_title' => 'first',
				'post_date'  => '2014-01-01 01:00:00',
			)
		);
		self::factory()->post->create(
			array(
				'post_title' => 'last',
				'post_date'  => '2014-01-01 02:00:00',
			)
		);
		$attr = array(
			'order' => 'asc',
		);

		$archives = archives_shortcode( $attr );

		$this->assertGreaterThan( strpos( $archives, 'first' ), strpos( $archives, 'last' ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_archives_order_desc() {
		self::factory()->post->create(
			array(
				'post_title' => 'first',
				'post_date'  => '2014-01-01 01:00:00',
			)
		);
		self::factory()->post->create(
			array(
				'post_title' => 'last',
				'post_date'  => '2014-01-01 02:00:00',
			)
		);
		$attr = array(
			'order' => 'desc',
		);

		$archives = archives_shortcode( $attr );

		$this->assertLessThan( strpos( $archives, 'first' ), strpos( $archives, 'last' ) );
	}
}
