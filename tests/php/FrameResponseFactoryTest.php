<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\Frame\FrameResponseFactory;
use WP_UnitTestCase;

class FrameResponseFactoryTest extends WP_UnitTestCase {

	public function test_wraps_content_in_the_matching_frame_with_html_defaults(): void {
		$response = ( new FrameResponseFactory() )->frame( 'author-footer', '<p>hi</p>' );

		self::assertSame( '<turbo-frame id="author-footer"><p>hi</p></turbo-frame>', $response->get_body() );
		self::assertSame( 200, $response->get_status() );
		self::assertSame( 'text/html; charset=UTF-8', $response->get_headers()['Content-Type'] );
	}

	public function test_extra_headers_merge_without_losing_the_content_type(): void {
		$response = ( new FrameResponseFactory() )->frame(
			'author-footer',
			'x',
			[ 'Cache-Control' => 'public, max-age=300' ]
		);

		self::assertSame( 'public, max-age=300', $response->get_headers()['Cache-Control'] );
		self::assertArrayHasKey( 'Content-Type', $response->get_headers() );
	}

	public function test_frame_id_is_escaped(): void {
		$response = ( new FrameResponseFactory() )->frame( 'a"b', 'x' );

		self::assertStringContainsString( 'id="a&quot;b"', $response->get_body() );
	}

	public function test_resolvable_from_the_container(): void {
		$container = apply_filters( 'achttienvijftien/container', null );

		self::assertInstanceOf( FrameResponseFactory::class, $container->get( FrameResponseFactory::class ) );
	}
}
