<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\Frame\FramePlaceholder;
use WP_UnitTestCase;

/**
 * Covers the PHP-side placeholder helper: markup shape, attribute escaping
 * and reservation, and the wp_turbo/frame_placeholder runtime contract.
 */
class FramePlaceholderTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		// Simulates an installed runtime carrier (achttienvijftien/wp-turbo,
		// or a theme bundle): without any listener the helper raises
		// _doing_it_wrong, which would fail every test here.
		add_action( 'wp_turbo/frame_placeholder', '__return_null' );
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

	public function test_placeholders_announce_themselves_to_the_runtime_carrier(): void {
		$announced = [];

		add_action(
			'wp_turbo/frame_placeholder',
			static function ( string $frame_id ) use ( &$announced ): void {
				$announced[] = $frame_id;
			}
		);

		$this->placeholder()->lazy( 'latest-news', 'turbo_hello', [ 'name' => 'x' ] );
		$this->placeholder()->eager( 'author-footer', 'turbo_hello', [ 'name' => 'y' ] );

		self::assertSame( [ 'latest-news', 'author-footer' ], $announced );
	}

	public function test_a_missing_runtime_carrier_raises_doing_it_wrong(): void {
		remove_action( 'wp_turbo/frame_placeholder', '__return_null' );

		$this->setExpectedIncorrectUsage(
			'AchttienVijftien\Bundle\WpTurboBundle\Frame\FramePlaceholder::placeholder'
		);

		$html = $this->placeholder()->lazy( 'latest-news', 'turbo_hello', [ 'name' => 'x' ] );

		// The placeholder still renders; only the runtime is missing.
		self::assertStringContainsString( '<turbo-frame', $html );
	}
}
