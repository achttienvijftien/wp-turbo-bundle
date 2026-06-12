<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\Routing\WpRewriteRegistrar;
use WP_UnitTestCase;

class WpRewriteRegistrarTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		delete_option( 'wp_turbo_rewrite_version' );
	}

	public function test_register_adds_the_catch_all_rule(): void {
		$this->set_permalink_structure( '/%postname%/' );

		( new WpRewriteRegistrar() )->register();

		$rules = $GLOBALS['wp_rewrite']->wp_rewrite_rules();

		self::assertArrayHasKey( '^_turbo/(.+?)/?$', $rules );
		self::assertSame( 'index.php?__turbo_route=$matches[1]', $rules['^_turbo/(.+?)/?$'] );
	}

	public function test_register_whitelists_the_query_var(): void {
		( new WpRewriteRegistrar() )->register();

		self::assertContains( '__turbo_route', apply_filters( 'query_vars', [] ) );
	}

	public function test_register_flushes_once_per_rule_version(): void {
		( new WpRewriteRegistrar() )->register();

		self::assertSame(
			(string) WpRewriteRegistrar::REWRITE_VERSION,
			(string) get_option( 'wp_turbo_rewrite_version' )
		);
	}
}
