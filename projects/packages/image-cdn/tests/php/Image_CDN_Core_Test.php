<?php

use Automattic\Jetpack\Image_CDN\Image_CDN_Core;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use WorDBless\BaseTestCase;

/**
 * @covers \Automattic\Jetpack\Image_CDN\Image_CDN_Core
 */
#[CoversClass( Image_CDN_Core::class )]
class Image_CDN_Core_Test extends BaseTestCase {

	private $custom_photon_domain;

	/**
	 * Tear down.
	 */
	public function tear_down() {
		remove_filter( 'jetpack_photon_domain', array( $this, 'apply_custom_domain' ) );
		unset( $this->custom_photon_domain );
		parent::tear_down();
	}

	public function apply_custom_domain( $domain ) {
		if ( 'jetpack_photon_domain' === current_filter() ) {
			return $this->custom_photon_domain;
		}

		$this->custom_photon_domain = $domain;
		add_filter( 'jetpack_photon_domain', array( $this, 'apply_custom_domain' ) );
	}

	protected function assertMatchesPhotonHost( $host ) {
		$this->assertMatchesRegularExpression( '/^i[0-2]\.wp\.com$/', $host );
	}

	/**
	 * @author kraftbj
	 * @since 3.9.2
	 */
	public function test_photonizing_https_image_adds_ssl_query_arg() {
		$url = Image_CDN_Core::cdn_url( 'https://example.com/images/photon.jpg' );
		parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $args );
		$this->assertSame( '1', $args['ssl'], 'HTTPS image sources should have a ?ssl=1 query string.' );
	}

	/**
	 * @author kraftbj
	 * @since  3.9.2
	 */
	public function test_photonizing_http_image_no_ssl_query_arg() {
		$url = Image_CDN_Core::cdn_url( 'http://example.com/images/photon.jpg' );
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $args );
		$this->assertArrayNotHasKey( 'ssl', $args, 'HTTP image source should not have an ssl query string.' );
	}

	/**
	 * @author donncha
	 * @since 0.2.3
	 */
	public function test_photon_url_with_query_parameters() {
		$args = array(
			'a' => 2,
			'b' => 3,
		);

		add_filter( 'jetpack_photon_add_query_string_to_domain', '__return_true' );
		$url = Image_CDN_Core::cdn_url( 'https://example.com/images/photon.jpg?t=1', $args );
		remove_filter( 'jetpack_photon_add_query_string_to_domain', '__return_true' );

		$this->assertStringContainsString( 'images/photon.jpg?q=t%3D1&a=2&b=3', $url, 'Image URL should have t, a, and b parameters.' );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_no_filter
	 */
	#[Group( 'jetpack_photon_no_filter' )]
	public function test_photon_url_no_filter_http() {
		$url        = Image_CDN_Core::cdn_url( 'http://example.com/img.jpg' );
		$parsed_url = wp_parse_url( $url );

		$this->assertEquals( 'https', $parsed_url['scheme'] );
		$this->assertMatchesPhotonHost( $parsed_url['host'] );
		$this->assertEquals( '/example.com/img.jpg', $parsed_url['path'] );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_no_filter
	 */
	#[Group( 'jetpack_photon_no_filter' )]
	public function test_photon_url_no_filter_http_to_http() {
		$url        = Image_CDN_Core::cdn_url( 'http://example.com/img.jpg', array(), 'http' );
		$parsed_url = wp_parse_url( $url );

		$this->assertEquals( 'http', $parsed_url['scheme'] );
		$this->assertMatchesPhotonHost( $parsed_url['host'] );
		$this->assertEquals( '/example.com/img.jpg', $parsed_url['path'] );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_no_filter
	 */
	#[Group( 'jetpack_photon_no_filter' )]
	public function test_photon_url_no_filter_photonized_https() {
		$url = Image_CDN_Core::cdn_url( 'https://i0.wp.com/example.com/img.jpg' );

		$this->assertEquals( 'https://i0.wp.com/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_no_filter
	 */
	#[Group( 'jetpack_photon_no_filter' )]
	public function test_photon_url_no_filter_photonized_http() {
		$url = Image_CDN_Core::cdn_url( 'http://i0.wp.com/example.com/img.jpg' );

		$this->assertEquals( 'http://i0.wp.com/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_no_filter
	 */
	#[Group( 'jetpack_photon_no_filter' )]
	public function test_photon_url_no_filter_photonized_https_to_http() {
		$url = Image_CDN_Core::cdn_url( 'https://i0.wp.com/example.com/img.jpg', array(), 'http' );

		$this->assertEquals( 'http://i0.wp.com/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_filter_http
	 */
	#[Group( 'jetpack_photon_filter_http' )]
	public function test_photon_url_filter_http_http() {
		$this->apply_custom_domain( 'http://photon.test' );
		$url = Image_CDN_Core::cdn_url( 'http://example.com/img.jpg' );

		$this->assertEquals( 'http://photon.test/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_filter_http
	 */
	#[Group( 'jetpack_photon_filter_http' )]
	public function test_photon_url_filter_http_http_to_http() {
		$this->apply_custom_domain( 'http://photon.test' );
		$url = Image_CDN_Core::cdn_url( 'http://example.com/img.jpg', array(), 'http' );

		$this->assertEquals( 'http://photon.test/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_filter_http
	 */
	#[Group( 'jetpack_photon_filter_http' )]
	public function test_photon_url_filter_http_photonized_http() {
		$this->apply_custom_domain( 'http://photon.test' );
		$url = Image_CDN_Core::cdn_url( 'http://photon.test/example.com/img.jpg' );

		$this->assertEquals( 'http://photon.test/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_filter_http
	 */
	#[Group( 'jetpack_photon_filter_http' )]
	public function test_photon_url_filter_http_photonized_https() {
		$this->apply_custom_domain( 'http://photon.test' );
		$url = Image_CDN_Core::cdn_url( 'https://photon.test/example.com/img.jpg' );

		$this->assertEquals( 'https://photon.test/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_filter_http
	 */
	#[Group( 'jetpack_photon_filter_http' )]
	public function test_photon_url_filter_http_photonized_http_to_https() {
		$this->apply_custom_domain( 'http://photon.test' );
		$url = Image_CDN_Core::cdn_url( 'http://photon.test/example.com/img.jpg', array(), 'https' );

		$this->assertEquals( 'https://photon.test/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_filter_network_path
	 */
	#[Group( 'jetpack_photon_filter_network_path' )]
	public function test_photon_url_filter_network_path_http() {
		$this->apply_custom_domain( '//photon.test' );
		$url = Image_CDN_Core::cdn_url( 'http://example.com/img.jpg' );

		$this->assertEquals( '//photon.test/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_filter_network_path
	 */
	#[Group( 'jetpack_photon_filter_network_path' )]
	public function test_photon_url_filter_network_path_http_to_http() {
		$this->apply_custom_domain( '//photon.test' );
		$url = Image_CDN_Core::cdn_url( 'http://example.com/img.jpg', array(), 'http' );

		$this->assertEquals( 'http://photon.test/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_filter_network_path
	 */
	#[Group( 'jetpack_photon_filter_network_path' )]
	public function test_photon_url_filter_network_path_photonized_http() {
		$this->apply_custom_domain( '//photon.test' );
		$url = Image_CDN_Core::cdn_url( 'http://photon.test/example.com/img.jpg' );

		$this->assertEquals( 'http://photon.test/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_filter_network_path
	 */
	#[Group( 'jetpack_photon_filter_network_path' )]
	public function test_photon_url_filter_network_path_photonized_https() {
		$this->apply_custom_domain( '//photon.test' );
		$url = Image_CDN_Core::cdn_url( 'https://photon.test/example.com/img.jpg' );

		$this->assertEquals( 'https://photon.test/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  jetpack_photon_filter_network_path
	 */
	#[Group( 'jetpack_photon_filter_network_path' )]
	public function test_photon_url_filter_network_path_photonized_to_https() {
		$this->apply_custom_domain( '//photon.test' );
		$url = Image_CDN_Core::cdn_url( '//photon.test/example.com/img.jpg', array(), 'https' );

		$this->assertEquals( 'https://photon.test/example.com/img.jpg', $url );
	}

	/**
	 * @since  0.5.0
	 * @group  jetpack_photon_filter_network_path
	 */
	#[Group( 'jetpack_photon_filter_network_path' )]
	public function test_is_cdn_url_method() {
		$this->apply_custom_domain( '//photon.test' );
		$this->assertTrue( Image_CDN_Core::is_cdn_url( '//photon.test/example.com/img.jpg' ) );

		$this->assertTrue( Image_CDN_Core::is_cdn_url( 'https://i0.wp.com/example.com/img.jpg' ) );
		$this->assertTrue( Image_CDN_Core::is_cdn_url( 'http://i1.wp.com/example.com/img.jpg' ) );
		$this->assertTrue( Image_CDN_Core::is_cdn_url( '//i2.wp.com/example.com/img.jpg' ) );
		$this->assertFalse( Image_CDN_Core::is_cdn_url( '//i3.wp.com/example.com/img.jpg' ) );
		$this->assertFalse( Image_CDN_Core::is_cdn_url( 'http://example.com/img.jpg' ) );
		$this->assertFalse( Image_CDN_Core::is_cdn_url( 'https://example.com/img.jpg' ) );
		$this->assertFalse( Image_CDN_Core::is_cdn_url( '//example.com/img.jpg' ) );
	}

	/**
	 * @since  0.5.1
	 * @group  jetpack_photon_filter_url_encoding
	 */
	#[Group( 'jetpack_photon_filter_url_encoding' )]
	public function test_photon_url_filter_url_encodes_path_parts() {
		// The first two spaces are not standard spaces - https://www.compart.com/en/unicode/U+202F
		$url = Image_CDN_Core::cdn_url( '//example.com/narrow no-break space/name with spaces.jpg', array(), 'https' );

		$this->assertEquals( 'https://i0.wp.com/example.com/narrow%E2%80%AFno-break%E2%80%AFspace/name%20with%20spaces.jpg', $url );
	}

	/**
	 * @since  0.7.3
	 * @group  jetpack_photon_filter_url_encoding
	 */
	#[Group( 'jetpack_photon_filter_url_encoding' )]
	public function test_photon_url_filter_encoded_url_should_not_be_encoded_again() {
		$url = Image_CDN_Core::cdn_url( '//example.com/image%20with%20spaces.jpg', array(), 'https' );
		$this->assertEquals( 'https://i0.wp.com/example.com/image%20with%20spaces.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  Image_CDN_Core::cdn_url_scheme
	 */
	#[Group( 'Image_CDN_Core::cdn_url_scheme' )]
	public function test_photon_url_scheme_valid_url_null_scheme() {
		$url = Image_CDN_Core::cdn_url_scheme( 'https://i0.wp.com/example.com/img.jpg', null );

		$this->assertEquals( 'https://i0.wp.com/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  Image_CDN_Core::cdn_url_scheme
	 */
	#[Group( 'Image_CDN_Core::cdn_url_scheme' )]
	public function test_photon_url_scheme_valid_url_invalid_scheme() {
		$url = Image_CDN_Core::cdn_url_scheme( 'https://i0.wp.com/example.com/img.jpg', 'ftp' );

		$this->assertEquals( 'https://i0.wp.com/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  Image_CDN_Core::cdn_url_scheme
	 */
	#[Group( 'Image_CDN_Core::cdn_url_scheme' )]
	public function test_photon_url_scheme_valid_url_valid_scheme() {
		$url = Image_CDN_Core::cdn_url_scheme( 'https://i0.wp.com/example.com/img.jpg', 'http' );

		$this->assertEquals( 'http://i0.wp.com/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  Image_CDN_Core::cdn_url_scheme
	 */
	#[Group( 'Image_CDN_Core::cdn_url_scheme' )]
	public function test_photon_url_scheme_valid_url_network_path_scheme() {
		$url = Image_CDN_Core::cdn_url_scheme( 'https://i0.wp.com/example.com/img.jpg', 'network_path' );

		$this->assertEquals( '//i0.wp.com/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  Image_CDN_Core::cdn_url_scheme
	 */
	#[Group( 'Image_CDN_Core::cdn_url_scheme' )]
	public function test_photon_url_scheme_invalid_url_null_scheme() {
		$url = Image_CDN_Core::cdn_url_scheme( 'ftp://i0.wp.com/example.com/img.jpg', null );

		$this->assertEquals( 'http://i0.wp.com/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  Image_CDN_Core::cdn_url_scheme
	 */
	#[Group( 'Image_CDN_Core::cdn_url_scheme' )]
	public function test_photon_url_scheme_invalid_url_invalid_scheme() {
		$url = Image_CDN_Core::cdn_url_scheme( 'ftp://i0.wp.com/example.com/img.jpg', 'ftp' );

		$this->assertEquals( 'http://i0.wp.com/example.com/img.jpg', $url );
	}

	/**
	 * @author aduth
	 * @since  4.5.0
	 * @group  Image_CDN_Core::cdn_url_scheme
	 */
	#[Group( 'Image_CDN_Core::cdn_url_scheme' )]
	public function test_photon_url_scheme_invalid_url_valid_scheme() {
		$url = Image_CDN_Core::cdn_url_scheme( 'ftp://i0.wp.com/example.com/img.jpg', 'https' );

		$this->assertEquals( 'https://i0.wp.com/example.com/img.jpg', $url );
	}

	/**
	 * Testing the filter allowing to skip Photon for specific domains.
	 *
	 * @author aduth
	 * @since  5.0.0
	 * @group  Image_CDN_Core::banned_domains
	 * @dataProvider get_photon_domains
	 *
	 * @param bool   $skip If the image should be skipped by Photon.
	 * @param string $image_url URL of the image.
	 */
	#[Group( 'Image_CDN_Core::banned_domains' )]
	#[DataProvider( 'get_photon_domains' )]
	public function test_photon_banned_domains( $skip, $image_url ) {
		$this->assertEquals( $skip, Image_CDN_Core::banned_domains( false, $image_url ) );
	}

	/**
	 * Tests that Photon will rely on native resizing for WordPress.com images.
	 *
	 * @author aforcier
	 * @since  9.5.0
	 */
	public function test_photonizing_wordpress_url() {
		$url = Image_CDN_Core::cdn_url( 'https://jetpack.files.wordpress.com/abcd1234/poster_image.jpg', array( 'w' => 500 ) );
		parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $args );
		$this->assertSame( '500', $args['w'], 'WordPress.com image source should have given params applied.' );
		$this->assertArrayNotHasKey( 'ssl', $args, 'WordPress.com image source should not have an ssl query string.' );
		$this->assertSame( 'jetpack.files.wordpress.com', wp_parse_url( $url )['host'], 'WordPress.com image source should not be wrapped in Photon URL.' );
	}

	/**
	 * Tests that Photon will rely on native resizing for VideoPress poster images.
	 *
	 * @author aforcier
	 * @since  9.5.0
	 */
	public function test_photonizing_videopress_url() {
		$url = Image_CDN_Core::cdn_url( 'https://videos.files.wordpress.com/abcd1234/poster_image.jpg', array( 'w' => 500 ) );
		parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $args );
		$this->assertSame( '500', $args['w'], 'VideoPress poster image source should have given params applied.' );
		$this->assertArrayNotHasKey( 'ssl', $args, 'VideoPress poster image source should not have an ssl query string.' );
		$this->assertSame( 'videos.files.wordpress.com', wp_parse_url( $url )['host'], 'VideoPress poster image source should not be wrapped in Photon URL.' );
	}

	/**
	 * Tests that Photon will only process images with supported extensions.
	 *
	 * @since 0.7.5
	 *
	 * @dataProvider get_different_extensions
	 */
	#[DataProvider( 'get_different_extensions' )]
	public function test_photonizing_check_extensions( $image_url, $expected ) {
		$this->assertEquals( $expected, Image_CDN_Core::cdn_url( $image_url, array( 'w' => 500 ) ) );
	}

	/**
	 * Data provider for test_photon_banned_domains_banned
	 */
	public static function get_photon_domains() {
		return array(
			'Banned Facebook domain'     => array(
				true,
				'http://graph.facebook.com/37512822/picture',
			),
			'Banned Facebook CDN domain' => array(
				true,
				'https://scontent-mrs1-1.xx.fbcdn.net/v/t31.0-8/00000000_000000000000000_0000000000000000000_o.jpg',
			),
			'Allowed W.org subdomain'    => array(
				false,
				'https://s.w.org/style/images/wp-header-logo-2x.png',
			),
			'Banned Wikimedia domain'    => array(
				true,
				'https://commons.wikimedia.org/wiki/File:Dapper_Gentleman.jpg',
			),
			'Banned Dropbox domain'      => array(
				true,
				'https://www.dropbox.com/s/b4ezvx00mm35y7l/step29A.png',
			),
			'Banned Paypal domain'       => array(
				true,
				'https://www.paypalobjects.com/en_US/i/btn/btn_buynow_LG.gif',
			),
			'Banned Wikipedia domain'    => array(
				true,
				'https://en.wikipedia.org/wiki/File:MM10249.jpg',
			),
			'Banned Amazon domain'       => array(
				true,
				'http://m.media-amazon.com/images/I/41YeeCMUwTL._SL300_.jpg',
			),
		);
	}

	/**
	 * Data provider for test_photonizing_check_extensions
	 *
	 * @since 0.7.5
	 *
	 * @return array
	 */
	public static function get_different_extensions() {
		return array(
			'HEIC: supported'     => array(
				'https://example.com/image.heic',
				'https://i0.wp.com/example.com/image.heic?w=500&ssl=1',
			),
			'AVIF: not supported' => array(
				'https://example.com/image.avif',
				'https://example.com/image.avif',
			),
		);
	}
}
