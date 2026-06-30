<?php
/**
 * Compiler pass that collects Turbo controllers into the route table.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Compiler;

use AchttienVijftien\Bundle\WpTurboBundle\Attribute\WithFrameContext;
use AchttienVijftien\Bundle\WpTurboBundle\Frame\Context\NullContext;
use AchttienVijftien\Bundle\WpTurboBundle\Routing\Dispatcher;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Reads the #[Route] (and optional #[WithFrameContext]) attributes of every
 * wp_turbo.controller tagged service (the autoconfigured tag for
 * TurboControllerInterface implementations) into:
 *
 * - the `wp_turbo.routes` container parameter, the compile-time route table
 *   the RouteRegistry matches and generates against, and
 * - a class-keyed service locator of all controllers plus every referenced
 *   FrameContext service, injected into the Dispatcher, so controllers stay
 *   lazy and the Dispatcher depends on nothing it does not dispatch to.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class ControllerPass implements CompilerPassInterface {

	/**
	 * Builds the route table and the Dispatcher's service locator.
	 *
	 * @param ContainerBuilder $container The container builder.
	 *
	 * @return void
	 * @throws \InvalidArgumentException When a controller misses its #[Route] attribute or route
	 *                                   name, declares a path outside /_turbo/, or reuses a name.
	 */
	public function process( ContainerBuilder $container ): void {
		$routes   = [];
		$services = [];

		foreach ( $container->findTaggedServiceIds( 'wp_turbo.controller', true ) as $id => $attributes ) {
			$definition      = $container->getDefinition( $id );
			$class_reference = $container->getParameterBag()->resolveValue( $definition->getClass() );

			$route    = $this->read_route( $class_reference );
			$contexts = $this->read_contexts( $class_reference );
			$name     = $route->getName();

			if ( isset( $routes[ $name ] ) ) {
				// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new \InvalidArgumentException(
					sprintf(
						// phpcs:ignore Generic.Files.LineLength.MaxExceeded
						'Turbo route name "%s" is declared by both "%s" and "%s"; route names must be unique, they are the path()/url() lookup key.',
						$name,
						$routes[ $name ]['service'],
						$class_reference
					)
				);
				// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}

			$routes[ $name ] = [
				'path'     => $route->getPath(),
				'methods'  => $route->getMethods(),
				'service'  => $class_reference,
				'contexts' => $contexts,
			];

			// Key by class_reference: that is what the route table stores, and for
			// autoconfigured services the id is the class_reference name anyway.
			$services[ $class_reference ] = new Reference( $id );
		}

		$container->setParameter( 'wp_turbo.routes', $routes );

		if ( ! $container->hasDefinition( Dispatcher::class ) ) {
			return;
		}

		// The Dispatcher resolves contexts by class_reference too; NullContext is the
		// default for routes without #[WithFrameContext].
		$contexts = array_unique( array_merge( ...array_column( $routes, 'contexts' ), ...[ [] ] ) );

		foreach ( [ NullContext::class, ...$contexts ] as $context_class ) {
			$services[ $context_class ] = new Reference( $context_class );
		}

		$container->getDefinition( Dispatcher::class )
			->setArgument( '$services', ServiceLocatorTagPass::register( $container, $services ) );
	}

	/**
	 * Reads the controller's #[Route] attribute (class first, __invoke as
	 * fallback) and validates it.
	 *
	 * @param string $class_reference The controller class name.
	 *
	 * @return Route
	 * @throws \InvalidArgumentException When the attribute or its name is missing.
	 */
	private function read_route( string $class_reference ): Route {
		$reflection = new \ReflectionClass( $class_reference );

		$attributes = $reflection->getAttributes( Route::class, \ReflectionAttribute::IS_INSTANCEOF );

		if ( ! $attributes && $reflection->hasMethod( '__invoke' ) ) {
			$attributes = $reflection->getMethod( '__invoke' )
				->getAttributes( Route::class, \ReflectionAttribute::IS_INSTANCEOF );
		}

		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		if ( ! $attributes ) {
			throw new \InvalidArgumentException(
				sprintf(
					'Turbo controller "%s" must declare a #[Route] attribute on the class or its __invoke method.',
					$class_reference
				)
			);
		}

		$route = $attributes[0]->newInstance();

		if ( null === $route->getName() || '' === $route->getName() ) {
			throw new \InvalidArgumentException(
				sprintf(
					// phpcs:ignore Generic.Files.LineLength.MaxExceeded
					'The #[Route] attribute of Turbo controller "%s" must declare a route name (name: \'...\'); it is the path()/url() lookup key.',
					$class_reference
				)
			);
		}

		if ( ! \is_string( $route->getPath() ) || '' === $route->getPath() ) {
			throw new \InvalidArgumentException(
				sprintf(
					'The #[Route] attribute of Turbo controller "%s" must declare a single string path.',
					$class_reference
				)
			);
		}

		// Only /_turbo/* is ceded to the Dispatcher by WpRewriteRegistrar's
		// rewrite rule; a route outside it would compile but never dispatch.
		if ( ! str_starts_with( $route->getPath(), '/_turbo/' ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					// phpcs:ignore Generic.Files.LineLength.MaxExceeded
					'The #[Route] path "%s" of Turbo controller "%s" must start with "/_turbo/"; only that namespace reaches the Dispatcher.',
					$route->getPath(),
					$class_reference
				)
			);
		}
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		return $route;
	}

	/**
	 * Reads the controller's optional, repeatable #[WithFrameContext] attributes.
	 *
	 * @param string $class_reference The controller class name.
	 *
	 * @return string[] The FrameContext class names in declaration order; empty
	 *                  means the NullContext default.
	 */
	private function read_contexts( string $class_reference ): array {
		return array_map(
			static fn ( \ReflectionAttribute $attribute ): string => $attribute->newInstance()->context_class,
			( new \ReflectionClass( $class_reference ) )->getAttributes( WithFrameContext::class )
		);
	}
}
