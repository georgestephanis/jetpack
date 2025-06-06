<?php

if ( ! defined( 'JETPACK_BOOST_CONCAT_USE_WP' ) ) {
	define( 'JETPACK_BOOST_CONCAT_USE_WP', false );

	// Load minification library.
	require_once __DIR__ . '/vendor/matthiasmullie/minify/src/Exception.php';
	require_once __DIR__ . '/vendor/matthiasmullie/minify/src/Minify.php';
	require_once __DIR__ . '/vendor/matthiasmullie/minify/src/CSS.php';
	require_once __DIR__ . '/vendor/matthiasmullie/minify/src/JS.php';
	require_once __DIR__ . '/vendor/matthiasmullie/minify/src/Exceptions/BasicException.php';
	require_once __DIR__ . '/vendor/matthiasmullie/minify/src/Exceptions/FileImportException.php';
	require_once __DIR__ . '/vendor/matthiasmullie/minify/src/Exceptions/IOException.php';
}

// Load minify library code.
if ( ! function_exists( 'jetpack_boost_minify_load_library' ) ) {
	require_once __DIR__ . '/app/lib/minify/loader.php';
}

jetpack_boost_page_optimize_service_request();
