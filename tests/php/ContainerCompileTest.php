<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTwigBundle\TwigEnvironmentHolder;
use WP_UnitTestCase;

class ContainerCompileTest extends WP_UnitTestCase {

	public function test_container_compiles_with_ux_bundles(): void {
		$container = apply_filters( 'achttienvijftien/container', null );

		self::assertNotNull( $container );
		self::assertTrue( $container->has( 'twig' ) );
		self::assertTrue( $container->has( TwigEnvironmentHolder::class ) );

		$bundles = $container->getParameter( 'kernel.bundles' );
		self::assertContains( \Symfony\UX\TwigComponent\TwigComponentBundle::class, $bundles );
		self::assertContains( \Symfony\UX\Turbo\TurboBundle::class, $bundles );

		// Proves the regression simulation is active: a project-like bundle
		// loads before twig_component (see tests/bootstrap.php), which is the
		// ordering that wiped the kernel.bundles shim in production when it
		// lived in prependExtension().
		self::assertContains( \AchttienVijftien\Bundle\WpTurboBundle\Test\Support\DummyProjectBundle::class, $bundles );

		// This INVERTS the old assertArrayNotHasKey tripwire on purpose: the
		// 'TwigBundle' key is no longer a parameter shim to be stripped but a
		// REAL bundle registered via config/bundles.php (wp-twig-bundle's
		// TwigBundle, whose short class name is load-bearing for
		// ux-twig-component's check).
		self::assertSame(
			\AchttienVijftien\Bundle\WpTwigBundle\TwigBundle::class,
			$bundles['TwigBundle'] ?? null
		);
	}
}
