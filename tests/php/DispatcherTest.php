<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\Http\Response;
use AchttienVijftien\Bundle\WpTurboBundle\Http\ResponseEmitter;
use AchttienVijftien\Bundle\WpTurboBundle\Routing\Dispatcher;
use AchttienVijftien\Bundle\WpTurboBundle\Routing\RouteRegistry;
use WP_UnitTestCase;

/**
 * Dispatcher::handle() is the exit-free core of the request lifecycle:
 * match -> frame context -> controller -> Response. Only the dumb
 * ResponseEmitter (status_header/header/echo/exit) stays outside it.
 *
 * dispatch() adds the trust boundary on top: it only fires when the ACTUAL
 * request path is inside the /_turbo/ namespace, never when the query var
 * was smuggled in via ?__turbo_route= on some other URL.
 */
class DispatcherTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unset( $_SERVER['HTTP_TURBO_FRAME'], $_SERVER['REQUEST_METHOD'] );
		$_SERVER['REQUEST_URI'] = '';
		$_GET                   = [];

		parent::tear_down();
	}

	private function dispatcher(): Dispatcher {
		$container = apply_filters( 'achttienvijftien/container', null );

		self::assertNotNull( $container );

		return $container->get( Dispatcher::class );
	}

	/**
	 * Builds a Dispatcher whose emitter only records, so dispatch() can be
	 * exercised without echo/exit. The compiled container doubles as the
	 * service locator: controllers and contexts are public services in it.
	 *
	 * @param \ArrayObject $emitted Collects every emitted Response.
	 *
	 * @return Dispatcher
	 */
	private function recording_dispatcher( \ArrayObject $emitted ): Dispatcher {
		$container = apply_filters( 'achttienvijftien/container', null );

		self::assertNotNull( $container );

		$emitter = new class( $emitted ) extends ResponseEmitter {

			public function __construct( private readonly \ArrayObject $emitted ) {
			}

			public function emit( Response $response ): void {
				$this->emitted->append( $response );
			}
		};

		return new Dispatcher( $container->get( RouteRegistry::class ), $container, $emitter );
	}

	public function test_handle_runs_context_and_controller(): void {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );

		$response = $this->dispatcher()->handle( '/_turbo/hello/world', [ 'post_id' => $post_id ] );

		self::assertSame( 200, $response->get_status() );
		self::assertSame( "Hello world on post $post_id", $response->get_body() );
	}

	public function test_handle_passes_the_turbo_frame_to_the_controller(): void {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );

		$_SERVER['HTTP_TURBO_FRAME'] = 'hello';

		$response = $this->dispatcher()->handle( '/_turbo/hello/world', [ 'post_id' => $post_id ] );

		self::assertSame( "Hello world on post $post_id in frame hello", $response->get_body() );
	}

	public function test_handle_returns_404_when_the_context_throws(): void {
		$post_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );

		$response = $this->dispatcher()->handle( '/_turbo/hello/world', [ 'post_id' => $post_id ] );

		self::assertSame( 404, $response->get_status() );
	}

	public function test_handle_returns_404_for_an_unknown_path(): void {
		$response = $this->dispatcher()->handle( '/_turbo/nope', [] );

		self::assertSame( 404, $response->get_status() );
		self::assertNotSame( '', $response->get_body() );
	}

	public function test_404_responses_are_not_cacheable(): void {
		$response = $this->dispatcher()->handle( '/_turbo/nope', [] );

		self::assertSame( 404, $response->get_status() );
		self::assertSame( 'no-store', $response->get_headers()['Cache-Control'] ?? null );
	}

	public function test_handle_rejects_methods_the_route_does_not_allow(): void {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );

		$response = $this->dispatcher()->handle( '/_turbo/hello/world', [ 'post_id' => $post_id ], 'POST' );

		self::assertSame( 404, $response->get_status() );
	}

	public function test_route_placeholders_win_over_query_parameters(): void {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );

		$response = $this->dispatcher()->handle(
			'/_turbo/hello/world',
			[
				'name'    => 'evil',
				'post_id' => $post_id,
			]
		);

		self::assertStringContainsString( 'world', $response->get_body() );
		self::assertStringNotContainsString( 'evil', $response->get_body() );
	}

	public function test_context_and_controller_see_the_same_params(): void {
		$path_post_id  = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$query_post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );

		$response = $this->dispatcher()->handle(
			'/_turbo/hello-post/' . $path_post_id,
			[ 'post_id' => $query_post_id ]
		);

		self::assertSame( 200, $response->get_status() );
		self::assertSame( "context=$path_post_id param=$path_post_id", $response->get_body() );
	}

	public function test_is_turbo_request_accepts_paths_inside_the_namespace(): void {
		$dispatcher = $this->dispatcher();

		self::assertTrue( $dispatcher->is_turbo_request( '/_turbo/hello/world' ) );
		self::assertTrue( $dispatcher->is_turbo_request( '/_turbo/hello/world?post_id=1' ) );
	}

	public function test_is_turbo_request_rejects_paths_outside_the_namespace(): void {
		$dispatcher = $this->dispatcher();

		self::assertFalse( $dispatcher->is_turbo_request( '/some-page/?__turbo_route=hello/world' ) );
		self::assertFalse( $dispatcher->is_turbo_request( '/?__turbo_route=hello/world' ) );
		self::assertFalse( $dispatcher->is_turbo_request( '' ) );
		self::assertFalse( $dispatcher->is_turbo_request( '/_turbo' ) );
		self::assertFalse( $dispatcher->is_turbo_request( '/prefix/_turbo/hello' ) );
	}

	public function test_is_turbo_request_honours_a_subdirectory_home_url(): void {
		add_filter( 'home_url', static fn () => 'http://example.org/sub' );

		$dispatcher = $this->dispatcher();

		self::assertTrue( $dispatcher->is_turbo_request( '/sub/_turbo/hello/world' ) );
		self::assertFalse( $dispatcher->is_turbo_request( '/_turbo/hello/world' ) );
	}

	public function test_dispatch_emits_for_a_real_turbo_path(): void {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );

		$_SERVER['REQUEST_URI'] = '/_turbo/hello/world?post_id=' . $post_id;
		$_GET                   = [ 'post_id' => (string) $post_id ];

		$emitted    = new \ArrayObject();
		$dispatcher = $this->recording_dispatcher( $emitted );

		$wp                              = new \WP();
		$wp->query_vars['__turbo_route'] = 'hello/world';

		$dispatcher->dispatch( $wp );

		self::assertCount( 1, $emitted );
		self::assertSame( 200, $emitted[0]->get_status() );
		self::assertSame( "Hello world on post $post_id", $emitted[0]->get_body() );
	}

	public function test_dispatch_ignores_the_query_var_outside_the_turbo_namespace(): void {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );

		// The cache-poisoning vector: the public query var set on ANY URL.
		$_SERVER['REQUEST_URI'] = '/some-page/?__turbo_route=hello/world&post_id=' . $post_id;
		$_GET                   = [
			'__turbo_route' => 'hello/world',
			'post_id'       => (string) $post_id,
		];

		$emitted    = new \ArrayObject();
		$dispatcher = $this->recording_dispatcher( $emitted );

		$wp                              = new \WP();
		$wp->query_vars['__turbo_route'] = 'hello/world';

		$dispatcher->dispatch( $wp );

		self::assertCount( 0, $emitted );
	}

	public function test_dispatch_ignores_a_non_string_query_var(): void {
		$_SERVER['REQUEST_URI'] = '/_turbo/hello/world';

		$emitted    = new \ArrayObject();
		$dispatcher = $this->recording_dispatcher( $emitted );

		$wp                              = new \WP();
		$wp->query_vars['__turbo_route'] = [ 'hello/world' ];

		$dispatcher->dispatch( $wp );

		self::assertCount( 0, $emitted );
	}

	public function test_dispatch_keeps_a_zero_sub_path_dispatchable(): void {
		$_SERVER['REQUEST_URI'] = '/_turbo/0';

		$emitted    = new \ArrayObject();
		$dispatcher = $this->recording_dispatcher( $emitted );

		$wp                              = new \WP();
		$wp->query_vars['__turbo_route'] = '0';

		$dispatcher->dispatch( $wp );

		// No route matches '0', but the sub-path must reach handle() (a 404
		// Response), not be dropped by an empty()-style falsiness check.
		self::assertCount( 1, $emitted );
		self::assertSame( 404, $emitted[0]->get_status() );
	}
}
