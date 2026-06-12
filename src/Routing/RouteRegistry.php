<?php
/**
 * The compile-time route table: matching and URL generation.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Routing;

use AchttienVijftien\Bundle\WpTurboBundle\Http\NotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Wraps the wp_turbo.routes parameter (built by Compiler\ControllerPass) in
 * Symfony's routing model: UrlMatcher for path matching, UrlGenerator for
 * Twig's path()/url(). The collection is built lazily so requests that never
 * touch /_turbo/* pay nothing beyond the parameter.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class RouteRegistry {

	/**
	 * Lazily built collection, keyed by route name like $routes.
	 *
	 * @var RouteCollection|null
	 */
	private ?RouteCollection $collection = null;

	/**
	 * Memoized per-request generator; home_url() does not change mid-request.
	 *
	 * @var UrlGenerator|null
	 */
	private ?UrlGenerator $generator = null;

	/**
	 * RouteRegistry constructor.
	 *
	 * @param array $routes The wp_turbo.routes parameter: route name =>
	 *                      [ 'path', 'methods', 'service', 'contexts' ].
	 */
	public function __construct( private readonly array $routes ) {
	}

	/**
	 * Matches a path against the route table.
	 *
	 * @param string $path   The path to match, including the /_turbo/ prefix.
	 * @param string $method The HTTP request method, enforced against the route's `methods`.
	 *
	 * @return array{name: string, params: array, service: string, contexts: string[]}
	 * @throws NotFoundException When no route matches the path and method.
	 */
	public function match( string $path, string $method = 'GET' ): array {
		$matcher = new UrlMatcher( $this->get_collection(), new RequestContext( method: strtoupper( $method ) ) );

		try {
			// Note: the path arrives urldecoded once by WP's parse_request,
			// and UrlMatcher rawurldecode()s again; route requirements apply
			// to the post-decode value.
			$matched = $matcher->match( $path );
		} catch ( ResourceNotFoundException | MethodNotAllowedException $exception ) {
			throw new NotFoundException( sprintf( 'No Turbo route matches "%s".', $path ), 0, $exception );
		}

		$name = $matched['_route'];
		unset( $matched['_route'] );

		return [
			'name'    => $name,
			'params'  => $matched,
			'service' => $this->routes[ $name ]['service'],
			'contexts' => $this->routes[ $name ]['contexts'],
		];
	}

	/**
	 * Exposes the memoized URL generator, published as the container's
	 * UrlGeneratorInterface service for wp-twig-bundle's RoutingExtension.
	 *
	 * @return UrlGeneratorInterface
	 */
	public function get_generator(): UrlGeneratorInterface {
		return $this->generator ??= new UrlGenerator( $this->get_collection(), $this->get_request_context() );
	}

	/**
	 * Generates a URL for a named route; convenience wrapper around get_generator().
	 *
	 * @param string $name     The route name.
	 * @param array  $params   Route placeholder values; extras become query parameters.
	 * @param bool   $absolute Whether to generate an absolute URL (scheme + host).
	 *
	 * @return string
	 */
	public function generate( string $name, array $params = [], bool $absolute = false ): string {
		return $this->get_generator()->generate(
			$name,
			$params,
			$absolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH
		);
	}

	/**
	 * Builds the RouteCollection once; per-route metadata (service, contexts)
	 * stays in $routes, keyed by the same names.
	 *
	 * @return RouteCollection
	 */
	private function get_collection(): RouteCollection {
		if ( null === $this->collection ) {
			$this->collection = new RouteCollection();

			foreach ( $this->routes as $name => $route ) {
				$this->collection->add( $name, new Route( $route['path'], methods: $route['methods'] ) );
			}
		}

		return $this->collection;
	}

	/**
	 * Builds the generator's RequestContext from home_url(), so absolute
	 * URLs carry the site's scheme/host/port and subdirectory installs get
	 * home_url()'s path prepended to generated paths.
	 *
	 * @return RequestContext
	 */
	private function get_request_context(): RequestContext {
		$home   = wp_parse_url( home_url() );
		$scheme = $home['scheme'] ?? 'http';
		$port   = $home['port'] ?? null;

		return new RequestContext(
			rtrim( $home['path'] ?? '', '/' ),
			'GET',
			$home['host'] ?? 'localhost',
			$scheme,
			( 'http' === $scheme && $port ) ? $port : 80,
			( 'https' === $scheme && $port ) ? $port : 443
		);
	}
}
