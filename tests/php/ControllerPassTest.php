<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\Compiler\ControllerPass;
use AchttienVijftien\Bundle\WpTurboBundle\Frame\Context\CurrentPost;
use AchttienVijftien\Bundle\WpTurboBundle\Test\Support\BadPathController;
use AchttienVijftien\Bundle\WpTurboBundle\Test\Support\DuplicateRouteNameController;
use AchttienVijftien\Bundle\WpTurboBundle\Test\Support\HelloController;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WP_UnitTestCase;

/**
 * The ControllerPass turns #[Route]-attributed, TurboControllerInterface
 * services into entries of the wp_turbo.routes container parameter.
 *
 * Invalid-controller cases run the pass directly on a throwaway
 * ContainerBuilder (the way Symfony unit-tests its own passes): registering
 * a bad fixture in DummyProjectBundle would fail the suite's container
 * compile instead of one test.
 */
class ControllerPassTest extends WP_UnitTestCase {

	/**
	 * Builds a throwaway ContainerBuilder with the given controller classes
	 * tagged the way autoconfiguration would tag them.
	 *
	 * @param string ...$classes Controller class names.
	 *
	 * @return ContainerBuilder
	 */
	private function builder_with_controllers( string ...$classes ): ContainerBuilder {
		$builder = new ContainerBuilder();

		foreach ( $classes as $class ) {
			$builder->register( $class, $class )->addTag( 'wp_turbo.controller' );
		}

		return $builder;
	}

	public function test_routes_parameter_contains_the_fixture_controller(): void {
		$container = apply_filters( 'achttienvijftien/container', null );

		self::assertNotNull( $container );

		$routes = $container->getParameter( 'wp_turbo.routes' );

		self::assertArrayHasKey( 'turbo_hello', $routes );
		self::assertSame( '/_turbo/hello/{name}', $routes['turbo_hello']['path'] );
		self::assertSame( HelloController::class, $routes['turbo_hello']['service'] );
		self::assertSame( CurrentPost::class, $routes['turbo_hello']['context'] );
		self::assertSame( [ 'GET' ], $routes['turbo_hello']['methods'] );
	}

	public function test_duplicate_route_names_fail_the_compile(): void {
		$builder = $this->builder_with_controllers( HelloController::class, DuplicateRouteNameController::class );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessageMatches(
			'/turbo_hello.+HelloController.+DuplicateRouteNameController/s'
		);

		( new ControllerPass() )->process( $builder );
	}

	public function test_paths_outside_the_turbo_namespace_fail_the_compile(): void {
		$builder = $this->builder_with_controllers( BadPathController::class );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessageMatches( '/_turbo/' );

		( new ControllerPass() )->process( $builder );
	}
}
