<?php
/**
 * This configuration will be read and overlaid on top of the
 * default configuration. Command-line arguments will be applied
 * after this file is read.
 *
 * @package automattic/jetpack-publicize
 */

// Require base config.
require __DIR__ . '/../../../../.phan/config.base.php';

return make_phan_config(
	dirname( __DIR__ ),
	array(
		'+stubs'          => array( 'wpcom' ),
		'parse_file_list' => array(
			// Reference files to handle code checking for stuff from Jetpack-the-plugin or other in-monorepo plugins.
			// Wherever feasible we should really clean up this sort of thing instead of adding stuff here.
			//
			// DO NOT add references to files in other packages like this! Generally packages should be listed in composer.json 'require'.
			// If there are truly optional dependencies or circular dependencies that can't be cleaned up, one package may list the
			// other in 'require-dev' and `extra.dependencies.test-only' instead. See packages/config for an example.
			__DIR__ . '/../../../plugins/jetpack/class.jetpack.php',                                 // class Jetpack
			__DIR__ . '/../../../plugins/jetpack/_inc/lib/admin-pages/class.jetpack-admin-page.php', // class Jetpack_Admin_Page
			__DIR__ . '/../../../plugins/jetpack/modules/subscriptions.php',                         // class Jetpack_Subscriptions
			__DIR__ . '/../../../plugins/jetpack/functions.global.php',                              // function jetpack_render_tos_blurb
			__DIR__ . '/../../../plugins/jetpack/_inc/lib/core-api/load-wpcom-endpoints.php', // function wpcom_rest_api_v2_load_plugin
		),
	)
);
