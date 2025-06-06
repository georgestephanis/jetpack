<?php
/**
 * Bootstrap.
 *
 * @package automattic/jetpack-masterbar
 */

/**
 * Include the composer autoloader.
 */
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/antecedent/patchwork/Patchwork.php';

define( 'WP_DEBUG', true );

// Initialize WordPress test environment
\Automattic\Jetpack\Test_Environment::init();

\Automattic\RedefineExit::setup();
