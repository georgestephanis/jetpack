<?php
/**
 * This configuration will be read and overlaid on top of the
 * default configuration. Command-line arguments will be applied
 * after this file is read.
 *
 * @package automattic/jetpack
 */

// Require base config.
require __DIR__ . '/../../../../.phan/config.base.php';

$config = make_phan_config(
	dirname( __DIR__ ),
	array(
		'+stubs'                          => array( 'akismet', 'amp', 'full-site-editing', 'woocommerce', 'woocommerce-internal', 'woocommerce-packages', 'wpcom' ),
		'exclude_file_list'               => array(
			// Mocks of core classes.
			'tests/php/_inc/lib/mocks/simplepie.php',
			// Mocks of wpcom classes and functions.
			'tests/php/lib/class-wpcom-features.php',
			'tests/php/lib/mock-functions.php',
			// Temporary duplicated defintions of classes.
			'_inc/lib/class.color.php',
			// We have a stub for this because the real file has duplicate trait definitions.
			'tests/php/WP_UnitTestCase_Fix.php',
		),
		'exclude_analysis_directory_list' => array(),
		'parse_file_list'                 => array(
			// Reference files to handle code checking for stuff from other in-monorepo plugins.
			// Wherever feasible we should really clean up this sort of thing instead of adding stuff here.
			//
			// DO NOT add references to files in packages like this! Packages should be listed in composer.json 'require',
			// or 'require-dev' if they're only needed in tests or build scripts.
			__DIR__ . '/../../../plugins/vaultpress/vaultpress.php',                  // class VaultPress
			__DIR__ . '/../../../plugins/crm/includes/ZeroBSCRM.Core.Extensions.php', // functions zeroBSCRM_isExtensionInstalled, zeroBSCRM_extension_install_jetpackforms

			// Make an exception to the above for packages/jetpack-mu-wpcom. Pulling in that whole package here seems more risky than beneficial.
			__DIR__ . '/../../../packages/jetpack-mu-wpcom/src/class-jetpack-mu-wpcom.php', // class Jetpack_Mu_Wpcom
			__DIR__ . '/../../../packages/jetpack-mu-wpcom/src/features/launchpad/launchpad.php', // function wpcom_launchpad_is_fse_next_steps_modal_hidden
		),
	)
);

// For alias support if needed.
$config['enable_class_alias_support'] = true;

return $config;
