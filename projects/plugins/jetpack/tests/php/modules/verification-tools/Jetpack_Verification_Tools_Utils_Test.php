<?php

use PHPUnit\Framework\Attributes\CoversFunction;
require __DIR__ . '/../../../../modules/verification-tools/verification-tools-utils.php';

/**
 * @covers ::jetpack_verification_validate
 */
#[CoversFunction( 'jetpack_verification_validate' )]
class Jetpack_Verification_Tools_Utils_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * @author cbauerman
	 * @since 6.5.0
	 */
	public function test_jetpack_verification_validate_google_raw_code() {
		$this->assertEquals(
			array( 'google' => 'W2gxpExLATRT5c0dgRjlJsXRnrLE7vpr_1YtYxEnDIzn9ylj7C' ),
			jetpack_verification_validate( array( 'google' => 'W2gxpExLATRT5c0dgRjlJsXRnrLE7vpr_1YtYxEnDIzn9ylj7C' ) ),
			'raw code should be accepeted'
		);
	}

	/**
	 * @author cbauerman
	 * @since 6.5.0
	 */
	public function test_jetpack_verification_validate_google_code_in_meta_double_quotes() {
		$this->assertEquals(
			array( 'test' => 'bX1szG_kxD6O0CGSVgS8m4F5gKvgUPMdo96McTiJ7pZ5Ax7mQr' ),
			jetpack_verification_validate( array( 'test' => '<meta name="google-site-verification" content="bX1szG_kxD6O0CGSVgS8m4F5gKvgUPMdo96McTiJ7pZ5Ax7mQr" />' ) ),
			'google-style meta tag with double quotes should be accepeted'
		);
	}

	/**
	 * @author cbauerman
	 * @since 6.5.0
	 */
	public function test_jetpack_verification_validate_google_code_in_meta_single_quotes() {
		$this->assertEquals(
			array( 'test' => 'jLjbTBvtuQepL3eR09id83p4q_w8JBStrB5DKCunOX7kK1XKub' ),
			jetpack_verification_validate( array( 'test' => '<meta name="google-site-verification" content=\'jLjbTBvtuQepL3eR09id83p4q_w8JBStrB5DKCunOX7kK1XKub\' />' ) ),
			'google-style meta tag with single quotes should be accepeted'
		);
	}
}
