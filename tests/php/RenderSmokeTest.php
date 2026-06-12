<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use WP_UnitTestCase;

/**
 * Exit criterion for the Twig seam: <twig:Turbo:*> components render to real
 * <turbo-frame>/<turbo-stream> HTML through the full package wiring,
 * driving the same two filters Timber drives in production.
 */
class RenderSmokeTest extends WP_UnitTestCase {

	private function twig(): Environment {
		$loader = new FilesystemLoader( [ \dirname( __DIR__ ) . '/fixtures/templates' ] );
		$loader = apply_filters( 'timber/loader/loader', $loader );

		return apply_filters( 'timber/twig', new Environment( $loader ) );
	}

	public function test_turbo_frame_component_renders_a_turbo_frame_element(): void {
		$html = $this->twig()->render( 'frame.html.twig' );

		self::assertStringContainsString( '<turbo-frame', $html );
		self::assertStringContainsString( 'id="author-footer"', $html );
		self::assertStringContainsString( 'src="/_turbo/author-footer?post_id=42"', $html );
		self::assertStringContainsString( 'loading="lazy"', $html );
		self::assertStringContainsString( 'skeleton', $html, 'Fallback body must be inside the frame.' );
	}

	public function test_turbo_stream_component_renders_a_turbo_stream_element(): void {
		$html = $this->twig()->render( 'stream.html.twig' );

		self::assertStringContainsString( '<turbo-stream', $html );
		self::assertStringContainsString( 'action="update"', $html );
		self::assertStringContainsString( 'targets="#results"', $html );
		self::assertStringContainsString( 'fresh results', $html );
	}
}
