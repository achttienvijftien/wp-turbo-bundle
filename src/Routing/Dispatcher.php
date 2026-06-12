<?php
/**
 * The /_turbo request dispatcher.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Routing;

use AchttienVijftien\Bundle\WpTurboBundle\Frame\Context\NullContext;
use AchttienVijftien\Bundle\WpTurboBundle\Http\NotFoundException;
use AchttienVijftien\Bundle\WpTurboBundle\Http\Response;
use AchttienVijftien\Bundle\WpTurboBundle\Http\ResponseEmitter;
use AchttienVijftien\Bundle\WpTurboBundle\Http\TurboFrame;
use Psr\Container\ContainerInterface;

/**
 * Handles requests the catch-all rewrite rule captured (see
 * WpRewriteRegistrar): hooked on `parse_request`, after WP filled the query
 * vars but before the main WP_Query runs, it matches the sub-path against
 * the RouteRegistry, runs the route's FrameContext, invokes the controller
 * and emits its Response.
 *
 * Unexpected Throwables from contexts/controllers are deliberately NOT
 * caught: WordPress's fatal handler surfaces them like any other PHP error.
 * Only NotFoundException is part of the contract and becomes a 404.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class Dispatcher {

	/**
	 * Dispatcher constructor.
	 *
	 * @param RouteRegistry      $registry The route table.
	 * @param ContainerInterface $services Class-keyed locator of all Turbo controllers and
	 *                                     FrameContext services (built by Compiler\ControllerPass).
	 * @param ResponseEmitter    $emitter  Sends the response and ends the request.
	 */
	public function __construct(
		private readonly RouteRegistry $registry,
		private readonly ContainerInterface $services,
		private readonly ResponseEmitter $emitter,
	) {
	}

	/**
	 * Dispatches a captured /_turbo request; hooked on `parse_request`.
	 *
	 * @param \WP $wp The WordPress environment instance.
	 *
	 * @return void
	 */
	public function dispatch( \WP $wp ): void {
		$sub_path = $wp->query_vars['__turbo_route'] ?? null;

		// is_string() blocks array smuggling via ?__turbo_route[]=…; the
		// explicit '' comparison (instead of empty()) keeps the degenerate
		// but valid sub-path '0' dispatchable.
		if ( ! is_string( $sub_path ) || '' === $sub_path ) {
			return;
		}

		// __turbo_route is a whitelisted public query var, so WordPress also
		// fills it from plain ?__turbo_route= GET/POST input on ANY URL
		// (REST-style query-var aliasing). That would serve Turbo output
		// under arbitrary page URLs — a cache poisoning vector, because the
		// whole premise of these endpoints is that every response lives at
		// its own distinct URL. Only dispatch when the actual request path
		// is inside the /_turbo/ namespace.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- only parsed and prefix-compared, never output.
		if ( ! $this->is_turbo_request( (string) wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- read-only GET endpoints; validation belongs to the FrameContext and controller.
		$query_params = wp_unslash( $_GET );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- only matched against the route's method whitelist.
		$method = (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' );

		$this->emitter->emit(
			$this->handle( '/_turbo/' . $sub_path, $query_params, $method )
		);
	}

	/**
	 * Whether the request URI's path lies inside the /_turbo/ namespace
	 * (under home_url()'s base path on subdirectory installs).
	 *
	 * @param string $request_uri The raw request URI ($_SERVER['REQUEST_URI']).
	 *
	 * @return bool
	 */
	public function is_turbo_request( string $request_uri ): bool {
		$request_path = wp_parse_url( $request_uri, PHP_URL_PATH ) ?? '';

		// Subdirectory installs serve the namespace under home_url()'s path.
		$base = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );

		return is_string( $request_path ) && str_starts_with( $request_path, $base . '/_turbo/' );
	}

	/**
	 * The exit-free request lifecycle: match, context, controller.
	 *
	 * @param string $path         The request path, including the /_turbo/ prefix.
	 * @param array  $query_params The request's query parameters.
	 * @param string $method       The HTTP request method.
	 *
	 * @return Response
	 */
	public function handle( string $path, array $query_params, string $method = 'GET' ): Response {
		try {
			$route = $this->registry->match( $path, $method );

			// The invocation convention (see TurboControllerInterface):
			// matched path placeholders win over same-named query
			// parameters — and the contexts must see exactly the same
			// parameters the controller will receive.
			$params = $route['params'] + $query_params;

			// Contexts run in declaration order; none declared = NullContext.
			foreach ( $route['contexts'] ?: [ NullContext::class ] as $context ) {
				$this->services->get( $context )->setup( $params );
			}
		} catch ( NotFoundException ) {
			return new Response(
				'Not found.',
				404,
				[
					'Content-Type'  => 'text/plain; charset=UTF-8',
					'Cache-Control' => 'no-store',
				]
			);
		}

		$controller = $this->services->get( $route['service'] );

		return $controller( $params, TurboFrame::from_globals() );
	}
}
