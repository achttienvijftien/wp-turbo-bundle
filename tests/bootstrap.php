<?php
/**
 * PHPUnit bootstrap file: WordPress test suite via wp-env.
 *
 * The package autoloader is required before WordPress boots, so the
 * composer autoload.files entry (src/bootstrap.php) runs first and its
 * $wp_filter pre-seed pattern is exercised exactly as in production.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle\Test
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Simulate a real project where other bundles (Stud, theme, plugins) register
// and load BEFORE this package's bundles: priority 5 runs ahead of the
// package's priority-10 callback, and union keeps the dummy first in the
// array, so its extension loads first during the container compile. See
// Test\Support\DummyProjectBundle for the regression this guards.
$GLOBALS['wp_filter']['achttienvijftien/container_bundles'][5][] = [
	'accepted_args' => 1,
	'function'      => static function ( array $bundles ): array {
		return [ AchttienVijftien\Bundle\WpTurboBundle\Test\Support\DummyProjectBundle::class => [ 'all' => true ] ] + $bundles;
	},
];

// Get tests dir.
$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: getenv( 'WP_PHPUNIT__DIR' );

if ( ! $_tests_dir || ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo 'Could not find the WordPress test suite. Run the tests via wp-env: npm test' . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the package being tested.
 */
function _manually_load_plugin(): void {
	require dirname( __DIR__ ) . '/vendor/autoload.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
