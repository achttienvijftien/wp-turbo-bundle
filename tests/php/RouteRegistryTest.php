<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\Frame\Context\CurrentPost;
use AchttienVijftien\Bundle\WpTurboBundle\Http\NotFoundException;
use AchttienVijftien\Bundle\WpTurboBundle\Routing\RouteRegistry;
use AchttienVijftien\Bundle\WpTurboBundle\Test\Support\HelloController;
use WP_UnitTestCase;

class RouteRegistryTest extends WP_UnitTestCase {

	private function registry(): RouteRegistry {
		$container = apply_filters( 'achttienvijftien/container', null );

		self::assertNotNull( $container );

		return $container->get( RouteRegistry::class );
	}

	public function test_match_resolves_a_known_path(): void {
		$match = $this->registry()->match( '/_turbo/hello/world' );

		self::assertSame( 'turbo_hello', $match['name'] );
		self::assertSame( [ 'name' => 'world' ], $match['params'] );
		self::assertSame( HelloController::class, $match['service'] );
		self::assertSame( CurrentPost::class, $match['context'] );
	}

	public function test_match_throws_not_found_for_unknown_path(): void {
		$this->expectException( NotFoundException::class );

		$this->registry()->match( '/_turbo/nope' );
	}

	public function test_match_allows_a_declared_method(): void {
		$match = $this->registry()->match( '/_turbo/hello/world', 'GET' );

		self::assertSame( 'turbo_hello', $match['name'] );
	}

	public function test_match_throws_not_found_for_a_disallowed_method(): void {
		$this->expectException( NotFoundException::class );

		$this->registry()->match( '/_turbo/hello/world', 'POST' );
	}

	public function test_generate_builds_a_relative_path(): void {
		self::assertSame(
			'/_turbo/hello/x',
			$this->registry()->generate( 'turbo_hello', [ 'name' => 'x' ] )
		);
	}

	public function test_generate_builds_an_absolute_url_from_home_url(): void {
		$url = $this->registry()->generate( 'turbo_hello', [ 'name' => 'x' ], true );

		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );

		self::assertStringContainsString( $home_host, $url );
		self::assertStringEndsWith( '/_turbo/hello/x', $url );
	}
}
