<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\WpTurboBundle;
use WP_UnitTestCase;

class BootstrapTest extends WP_UnitTestCase {

	public function test_bundle_is_registered_on_the_container_bundles_filter(): void {
		$bundles = apply_filters( 'achttienvijftien/container_bundles', [] );

		self::assertArrayHasKey( WpTurboBundle::class, $bundles );
		self::assertSame( [ 'all' => true ], $bundles[ WpTurboBundle::class ] );

		self::assertArrayHasKey( \Symfony\UX\TwigComponent\TwigComponentBundle::class, $bundles );
		self::assertSame( [ 'all' => true ], $bundles[ \Symfony\UX\TwigComponent\TwigComponentBundle::class ] );

		self::assertArrayHasKey( \Symfony\UX\Turbo\TurboBundle::class, $bundles );
		self::assertSame( [ 'all' => true ], $bundles[ \Symfony\UX\Turbo\TurboBundle::class ] );
	}

	public function test_existing_project_registrations_are_not_overwritten(): void {
		$bundles = apply_filters(
			'achttienvijftien/container_bundles',
			[ WpTurboBundle::class => [ 'all' => false ] ]
		);

		self::assertSame( [ 'all' => false ], $bundles[ WpTurboBundle::class ] );
	}

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

		// wp-twig-bundle's composer bootstrap registered the REAL TwigBundle
		// whose load-bearing short class name satisfies ux-twig-component's
		// kernel.bundles check.
		self::assertSame(
			\AchttienVijftien\Bundle\WpTwigBundle\TwigBundle::class,
			$bundles['TwigBundle'] ?? null
		);
	}
}
