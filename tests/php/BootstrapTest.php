<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\WpTurboBundle;
use WP_UnitTestCase;

class BootstrapTest extends WP_UnitTestCase {

	public function test_container_booted_with_our_bundle(): void {
		$container = apply_filters( 'achttienvijftien/container', null );

		self::assertNotNull( $container, 'ServiceContainer should have booted on muplugins_loaded.' );

		$bundles = $container->getParameter( 'kernel.bundles' );

		self::assertContains(
			WpTurboBundle::class,
			$bundles,
			'The bundle should be part of the compiled container.'
		);
		self::assertContains( \Symfony\UX\TwigComponent\TwigComponentBundle::class, $bundles );
		self::assertContains( \Symfony\UX\Turbo\TurboBundle::class, $bundles );

		// config/bundles.php registered the REAL TwigBundle (from wp-twig-bundle)
		// whose load-bearing short class name satisfies ux-twig-component's
		// kernel.bundles check.
		self::assertSame(
			\AchttienVijftien\Bundle\WpTwigBundle\TwigBundle::class,
			$bundles['TwigBundle'] ?? null
		);
	}
}
