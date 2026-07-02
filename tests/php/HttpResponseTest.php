<?php
/**
 * Response value-object tests: the opaque attribute bag.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle\Test
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\Http\Response;
use WP_UnitTestCase;

class HttpResponseTest extends WP_UnitTestCase {

	public function test_attributes_default_to_empty(): void {
		$response = new Response( 'body' );

		self::assertSame( [], $response->get_attributes() );
	}

	public function test_get_attribute_returns_default_when_absent(): void {
		$response = new Response( 'body' );

		self::assertNull( $response->get_attribute( 'cache_tags' ) );
		self::assertSame( 'fallback', $response->get_attribute( 'cache_tags', 'fallback' ) );
	}

	public function test_get_attribute_returns_a_constructed_value(): void {
		$response = new Response( 'body', 200, [], [ 'cache_tags' => [ 'author-7' ] ] );

		self::assertSame( [ 'author-7' ], $response->get_attribute( 'cache_tags' ) );
	}

	public function test_with_attribute_adds_a_key_without_mutating_the_original(): void {
		$original = new Response( 'body' );
		$copy     = $original->with_attribute( 'cache_tags', [ 'author-7' ] );

		self::assertSame( [], $original->get_attributes() );
		self::assertSame( [ 'cache_tags' => [ 'author-7' ] ], $copy->get_attributes() );
	}

	public function test_with_attribute_overrides_an_existing_key(): void {
		$response = ( new Response( 'body', 200, [], [ 'cache_tags' => [ 'old' ] ] ) )
			->with_attribute( 'cache_tags', [ 'new' ] );

		self::assertSame( [ 'new' ], $response->get_attribute( 'cache_tags' ) );
	}

	public function test_with_attribute_preserves_body_status_and_headers(): void {
		$response = ( new Response( 'body', 201, [ 'X-Foo' => 'bar' ] ) )
			->with_attribute( 'cache_tags', [ 'author-7' ] );

		self::assertSame( 'body', $response->get_body() );
		self::assertSame( 201, $response->get_status() );
		self::assertSame( [ 'X-Foo' => 'bar' ], $response->get_headers() );
	}
}
