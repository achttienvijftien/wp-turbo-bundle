<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTwigBundle\Twig\RoutingExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use WP_UnitTestCase;

/**
 * path()/url() must work through the cross-package contract: this bundle
 * publishes UrlGeneratorInterface from the RouteRegistry, wp-twig-bundle's
 * RegisterRoutingExtensionPass picks it up and registers its
 * RoutingExtension on the twig.extension tag, and the TwigBootstrapper
 * attaches it via the same two filters Timber drives in production.
 */
class RoutingContractTest extends WP_UnitTestCase {

	private function twig(): Environment {
		$loader = new FilesystemLoader( [ \dirname( __DIR__ ) . '/fixtures/templates' ] );
		$loader = apply_filters( 'timber/loader/loader', $loader );

		return apply_filters( 'timber/twig', new Environment( $loader ) );
	}

	public function test_container_provides_the_url_generator_contract(): void {
		$container = apply_filters( 'achttienvijftien/container', null );

		self::assertNotNull( $container );
		self::assertTrue( $container->has( UrlGeneratorInterface::class ) );
		self::assertTrue( $container->has( RoutingExtension::class ) );
	}

	public function test_path_generates_a_route_path(): void {
		$html = $this->twig()
			->createTemplate( "{{ path('turbo_hello', {name: 'x'}) }}" )
			->render( [] );

		self::assertSame( '/_turbo/hello/x', $html );
	}

	public function test_url_generates_an_absolute_url(): void {
		$html = $this->twig()
			->createTemplate( "{{ url('turbo_hello', {name: 'x'}) }}" )
			->render( [] );

		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );

		self::assertStringContainsString( $home_host, $html );
		self::assertStringEndsWith( '/_turbo/hello/x', $html );
	}
}
