<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\Frame\FramePlaceholder;
use WP_UnitTestCase;

/**
 * Covers the PHP-side placeholder helper: markup shape, attribute escaping
 * and reservation, and the runtime script enqueue side effect.
 */
class FramePlaceholderTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		// $wp_scripts persists across tests, so each test starts from a clean registry and queue.
		unset( $GLOBALS['wp_scripts'] );
	}

	public function tear_down(): void {
		unset( $GLOBALS['wp_scripts'] );

		parent::tear_down();
	}

	private function placeholder(): FramePlaceholder {
		$container = apply_filters( 'achttienvijftien/container', null );

		return $container->get( FramePlaceholder::class );
	}

	public function test_lazy_renders_the_frame_markup(): void {
		$html = $this->placeholder()->lazy(
			'latest-news',
			'turbo_hello',
			[
				'name'     => 'x',
				'per_page' => 3,
			]
		);

		self::assertSame(
			'<turbo-frame id="latest-news" src="/_turbo/hello/x?per_page=3" loading="lazy"></turbo-frame>',
			$html
		);
	}

	public function test_eager_renders_the_frame_markup(): void {
		$html = $this->placeholder()->eager(
			'latest-news',
			'turbo_hello',
			[ 'name' => 'x' ]
		);

		self::assertSame(
			'<turbo-frame id="latest-news" src="/_turbo/hello/x" loading="eager"></turbo-frame>',
			$html
		);
	}

	public function test_lazy_appends_extra_attributes_and_skips_reserved_keys(): void {
		$html = $this->placeholder()->lazy(
			'latest-news',
			'turbo_hello',
			[ 'name' => 'x' ],
			[
				'target'  => '_top',
				'id'      => 'override',
				'src'     => 'https://evil.example/override',
				'loading' => 'eager',
			]
		);

		self::assertStringContainsString( ' target="_top"', $html );
		self::assertStringContainsString( 'id="latest-news"', $html );
		self::assertStringContainsString( 'loading="lazy"', $html );
		self::assertSame( 1, substr_count( $html, 'id=' ) );
		self::assertSame( 1, substr_count( $html, 'src=' ) );
		self::assertSame( 1, substr_count( $html, 'loading=' ) );
	}

	public function test_lazy_enqueues_the_runtime_script(): void {
		// Registered here as the achttienvijftien/wp-turbo mu-plugin would; WP only reports 'enqueued' for registered handles.
		wp_register_script( 'wp-turbo-runtime', 'https://example.test/turbo.js', [], '1.0.0', true );

		self::assertFalse( wp_script_is( 'wp-turbo-runtime', 'enqueued' ) );

		$this->placeholder()->lazy( 'latest-news', 'turbo_hello', [ 'name' => 'x' ] );

		self::assertTrue( wp_script_is( 'wp-turbo-runtime', 'enqueued' ) );
	}

	public function test_lazy_without_the_mu_plugin_parks_the_enqueue_harmlessly(): void {
		$this->placeholder()->lazy( 'latest-news', 'turbo_hello', [ 'name' => 'x' ] );

		// Unregistered handle: WP parks the enqueue, so nothing would print.
		self::assertFalse( wp_script_is( 'wp-turbo-runtime', 'enqueued' ) );

		// A later registration (the mu-plugin loading) promotes the parked enqueue.
		wp_register_script( 'wp-turbo-runtime', 'https://example.test/turbo.js', [], '1.0.0', true );

		self::assertTrue( wp_script_is( 'wp-turbo-runtime', 'enqueued' ) );
	}
}
