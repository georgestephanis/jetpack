<?php
/**
 * This configuration will be read and overlaid on top of the
 * default configuration. Command-line arguments will be applied
 * after this file is read.
 *
 * @package automattic/jetpack-boost
 */

// Require base config.
require __DIR__ . '/../../../../.phan/config.base.php';

return make_phan_config(
	dirname( __DIR__ ),
	array(
		'+stubs'            => array( 'amp', 'woocommerce' ),
		'exclude_file_list' => getenv( 'NO_PHAN_UNUSED_SUPPRESSION' ) ? array() : array(
			// Polyfill for a class added in WP 6.8.0.
			'app/modules/optimizations/speculation-rules/class-wp-speculation-rules.php',
		),
		'parse_file_list'   => array(
			// Reference files to handle code checking for stuff from Jetpack-the-plugin or other in-monorepo plugins.
			// Wherever feasible we should really clean up this sort of thing instead of adding stuff here.
			//
			// DO NOT add references to files in packages like this! Packages should be listed in composer.json 'require',
			// or 'require-dev' if they're only needed in tests or build scripts.
			__DIR__ . '/../../../plugins/jetpack/class.jetpack.php',       // class Jetpack
			__DIR__ . '/../../../plugins/super-cache/wp-cache-phase2.php', // function wp_cache_clear_cache
			__DIR__ . '/../../../plugins/super-cache/wp-cache.php',        // function wp_cache_is_enabled
		),
	)
);
