<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Jetpack Beta wp-admin page toggles template.
 *
 * @html-template \Automattic\JetpackBeta\Admin::render -- Via plugin-select.template.php or plugin-manage.template.php
 * @package automattic/jetpack-beta
 */

use Automattic\JetpackBeta\Admin;

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

?>
	<span class="dops-foldable-card__secondary">
		<?php Admin::show_toggle_emails(); ?>
		<?php Admin::show_toggle_autoupdates(); ?>
	</span>
