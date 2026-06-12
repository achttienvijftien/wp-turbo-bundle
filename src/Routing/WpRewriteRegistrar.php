<?php
/**
 * WordPress rewrite registration for the /_turbo/* namespace.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Routing;

/**
 * Registers ONE static catch-all rewrite rule that cedes the whole /_turbo/*
 * namespace to the Dispatcher, the way WordPress cedes /wp-json to REST.
 *
 * The rule set never changes per route: actual route matching happens in PHP
 * against the RouteRegistry (see Dispatcher), so adding or removing a
 * #[Route] controller never touches the rewrite rules. Only a change to THIS
 * class's rule warrants a flush, tracked by REWRITE_VERSION.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class WpRewriteRegistrar {

	/**
	 * Bump when the rewrite rule itself changes; triggers a one-time
	 * flush_rewrite_rules() on the next request.
	 */
	public const REWRITE_VERSION = 1;

	/**
	 * Option storing the rule version the rewrite rules were last flushed for.
	 */
	private const VERSION_OPTION = 'wp_turbo_rewrite_version';

	/**
	 * The query var carrying the matched /_turbo/ sub-path.
	 */
	private const QUERY_VAR = '__turbo_route';

	/**
	 * Registers the catch-all rule and the query var; hooked on `init`.
	 *
	 * @return void
	 */
	public function register(): void {
		add_rewrite_rule( '^_turbo/(.+?)/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );

		add_filter( 'query_vars', [ $this, 'add_query_var' ] );

		// Deliberately non-atomic check-then-flush: concurrent flushes are idempotent.
		if ( (string) self::REWRITE_VERSION !== (string) get_option( self::VERSION_OPTION ) ) {
			flush_rewrite_rules();
			update_option( self::VERSION_OPTION, self::REWRITE_VERSION );
		}
	}

	/**
	 * Whitelists the route query var so WP::parse_request() keeps it.
	 *
	 * @param array $vars The public query vars.
	 *
	 * @return array
	 */
	public function add_query_var( array $vars ): array {
		return [ ...$vars, self::QUERY_VAR ];
	}
}
