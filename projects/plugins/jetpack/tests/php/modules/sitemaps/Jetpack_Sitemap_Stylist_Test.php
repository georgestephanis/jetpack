<?php
/**
 * Tests for the Jetpack_Sitemap_Stylist class.
 *
 * @package automattic/jetpack
 * @since 4.7.0
 */

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

require_once JETPACK__PLUGIN_DIR . 'modules/sitemaps/sitemap-stylist.php';

/**
 * Test class for Jetpack_Sitemap_Stylist.
 *
 * @since 4.7.0
 * @covers \Jetpack_Sitemap_Stylist
 */
#[CoversClass( Jetpack_Sitemap_Stylist::class )]
class Jetpack_Sitemap_Stylist_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * Check that the sitemap XSL is valid XML.
	 *
	 * @group jetpack-sitemap
	 * @since 4.7.0
	 */
	#[Group( 'jetpack-sitemap' )]
	public function test_sitemap_stylist_sitemap_xsl_is_valid() {
		// The sitemap xsl.
		$result = simplexml_load_string( Jetpack_Sitemap_Stylist::sitemap_xsl() );

		$this->assertNotFalse( $result );
	}

	/**
	 * Check that the sitemap XSL has root element 'stylesheet'
	 * in the 'xsl' namespace.
	 *
	 * @group jetpack-sitemap
	 * @since 4.7.0
	 */
	#[Group( 'jetpack-sitemap' )]
	public function test_sitemap_stylist_sitemap_xsl_is_xsl_stylesheet() {
		$result = simplexml_load_string( Jetpack_Sitemap_Stylist::sitemap_xsl() );

		// Assume that simplexml_load_string succeeded.
		$this->assertEquals( 'stylesheet', $result->getName() );

		$namespaces = array_keys( $result->getNamespaces() );
		$this->assertEquals( array( 'xsl' ), $namespaces );
	}

	/**
	 * Check that the sitemap index XSL is valid XML.
	 *
	 * @group jetpack-sitemap
	 * @since 4.7.0
	 */
	#[Group( 'jetpack-sitemap' )]
	public function test_sitemap_stylist_sitemap_index_xsl_is_valid() {
		// The sitemap index xsl.
		$result = simplexml_load_string( Jetpack_Sitemap_Stylist::sitemap_index_xsl() );

		$this->assertNotFalse( $result );
	}

	/**
	 * Check that the sitemap index XSL has root element 'stylesheet'
	 * in the 'xsl' namespace.
	 *
	 * @group jetpack-sitemap
	 * @since 4.7.0
	 */
	#[Group( 'jetpack-sitemap' )]
	public function test_sitemap_stylist_sitemap_index_xsl_is_xsl_stylesheet() {
		$result = simplexml_load_string( Jetpack_Sitemap_Stylist::sitemap_index_xsl() );

		// Assume that simplexml_load_string succeeded.
		$this->assertEquals( 'stylesheet', $result->getName() );

		$namespaces = array_keys( $result->getNamespaces() );
		$this->assertEquals( array( 'xsl' ), $namespaces );
	}

	/**
	 * Check that the image sitemap XSL is valid XML.
	 *
	 * @group jetpack-sitemap
	 * @since 4.7.0
	 */
	#[Group( 'jetpack-sitemap' )]
	public function test_sitemap_stylist_image_sitemap_xsl_is_valid() {
		// The image sitemap xsl.
		$result = simplexml_load_string( Jetpack_Sitemap_Stylist::image_sitemap_xsl() );

		$this->assertNotFalse( $result );
	}

	/**
	 * Check that the image sitemap XSL has root element 'stylesheet'
	 * in the 'xsl' namespace.
	 *
	 * @group jetpack-sitemap
	 * @since 4.7.0
	 */
	#[Group( 'jetpack-sitemap' )]
	public function test_sitemap_stylist_image_sitemap_xsl_is_xsl_stylesheet() {
		$result = simplexml_load_string( Jetpack_Sitemap_Stylist::image_sitemap_xsl() );

		// Assume that simplexml_load_string succeeded.
		$this->assertEquals( 'stylesheet', $result->getName() );

		$namespaces = array_keys( $result->getNamespaces() );
		$this->assertEquals( array( 'xsl' ), $namespaces );
	}

	/**
	 * Check that the news sitemap XSL is valid XML.
	 *
	 * @group jetpack-sitemap
	 * @since 4.7.0
	 */
	#[Group( 'jetpack-sitemap' )]
	public function test_sitemap_stylist_news_sitemap_xsl_is_valid() {
		// The news sitemap xsl.
		$result = simplexml_load_string( Jetpack_Sitemap_Stylist::news_sitemap_xsl() );

		$this->assertNotFalse( $result );
	}

	/**
	 * Check that the news_sitemap XSL has root element 'stylesheet'
	 * in the 'xsl' namespace.
	 *
	 * @group jetpack-sitemap
	 * @since 4.7.0
	 */
	#[Group( 'jetpack-sitemap' )]
	public function test_sitemap_stylist_news_sitemap_xsl_is_xsl_stylesheet() {
		$result = simplexml_load_string( Jetpack_Sitemap_Stylist::news_sitemap_xsl() );

		// Assume that simplexml_load_string succeeded.
		$this->assertEquals( 'stylesheet', $result->getName() );

		$namespaces = array_keys( $result->getNamespaces() );
		$this->assertEquals( array( 'xsl' ), $namespaces );
	}
}
