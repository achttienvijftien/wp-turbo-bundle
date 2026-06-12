<?php
/**
 * Smoke test for the wp-env test harness.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle\Test
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

/**
 * Verifies the WordPress test environment and the package autoloader work.
 */
class SmokeTest extends \WP_UnitTestCase {

	/**
	 * WordPress boots and the package dependencies autoload.
	 */
	public function test_autoload_and_wordpress_work(): void {
		self::assertTrue( \function_exists( 'add_filter' ) );
		self::assertGreaterThan( 0, did_action( 'init' ) );
		self::assertTrue( \class_exists( \Symfony\UX\Turbo\TurboBundle::class ) );
		self::assertTrue( \class_exists( \Symfony\UX\TwigComponent\TwigComponentBundle::class ) );
	}
}
