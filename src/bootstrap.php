<?php
/**
 * Composer autoload.files bootstrap.
 *
 * Runs before WordPress loads (Bedrock requires vendor/autoload.php in
 * config/application.php), so add_filter() is unavailable: pre-seed the
 * global $wp_filter array instead. WordPress merges pre-existing entries
 * via WP_Hook::build_preinitialized_hooks(). Same pattern as
 * achttienvijftien/service-container's autoload.php.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle;

// Idempotency guard: autoload.files may run more than once (project vendor
// plus a package-local vendor during development). Guards on the constant,
// not on function_exists(): PHP early-binds unconditional top-level function
// declarations at compile time, before any statement in this file runs, so a
// function_exists() check against this file's own functions always passes.
if ( \defined( __NAMESPACE__ . '\BUNDLES_FILTER' ) ) {
	return;
}

const BUNDLES_FILTER = 'achttienvijftien/container_bundles';
const PRIORITY       = 10;

// The function declarations are wrapped in a conditional to keep them
// runtime-declared (no early binding): a second include of this file from
// another vendor dir would otherwise fatal on redeclare during compilation,
// before the guard above could return.
if ( ! \function_exists( __NAMESPACE__ . '\register_bundles' ) ) {

	/**
	 * Adds the Turbo bundles to the container's bundle list.
	 *
	 * Does not overwrite entries a project registered explicitly.
	 *
	 * @param array $bundles Bundle class => environments map.
	 *
	 * @return array
	 */
	function register_bundles( array $bundles ): array {
		// The TwigBundle self-registers via wp-twig-bundle's own composer bootstrap.
		$bundles[ \Symfony\UX\TwigComponent\TwigComponentBundle::class ] ??= [ 'all' => true ];
		$bundles[ \Symfony\UX\Turbo\TurboBundle::class ]                 ??= [ 'all' => true ];
		$bundles[ WpTurboBundle::class ]                                 ??= [ 'all' => true ];

		return $bundles;
	}

	/**
	 * Pre-seeds the bundles filter into the global $wp_filter array.
	 *
	 * @return void
	 */
	function add_hooks(): void {
		if ( \function_exists( 'add_filter' ) ) {
			\add_filter( BUNDLES_FILTER, __NAMESPACE__ . '\register_bundles', PRIORITY );

			return;
		}

		global $wp_filter;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- preinitialization of hook
		$wp_filter[ BUNDLES_FILTER ][ PRIORITY ] = array_merge(
			$wp_filter[ BUNDLES_FILTER ][ PRIORITY ] ?? [],
			[
				[
					'accepted_args' => 1,
					'function'      => __NAMESPACE__ . '\register_bundles',
				],
			]
		);
	}
}

namespace\add_hooks();
