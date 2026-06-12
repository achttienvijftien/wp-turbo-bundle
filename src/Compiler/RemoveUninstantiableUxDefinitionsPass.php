<?php
/**
 * Compiler pass that removes UX service definitions needing FrameworkBundle/TwigBundle.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Removes UX-bundle service definitions that cannot exist without
 * FrameworkBundle/TwigBundle, which this package deliberately runs without.
 *
 * Registered with the default before-optimization type, so it runs before
 * ResolveChildDefinitionsPass would trip over the missing `cache.system`
 * parent.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class RemoveUninstantiableUxDefinitionsPass implements CompilerPassInterface {

	/**
	 * Removes the uninstantiable definitions.
	 *
	 * @param ContainerBuilder $container The container builder.
	 *
	 * @return void
	 */
	public function process( ContainerBuilder $container ): void {
		// ux-twig-component's cache.php declares this pool with parent
		// `cache.system`, which only FrameworkBundle provides (symfony/cache
		// is not even installed here). Its consumer references it
		// IGNORE_ON_INVALID, so removing it makes ComponentProperties fall
		// back to running uncached.
		$container->removeDefinition( 'cache.ux.twig_component' );

		// This decorates TwigBundle's `twig.configurator.environment`
		// (absent here) and its constructor type-hints TwigBundle's
		// EnvironmentConfigurator class (not installed), so it can never be
		// instantiated in this setup. The host Twig environment gets the
		// ComponentLexer applied by our own bootstrapper instead.
		$container->removeDefinition( 'ux.twig_component.twig.environment_configurator' );
	}
}
