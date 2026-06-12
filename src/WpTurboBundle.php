<?php
/**
 * Symfony UX Turbo (Frames + Streams) for WordPress: the bundle class.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle;

use AchttienVijftien\Bundle\WpTurboBundle\Compiler\ControllerPass;
use AchttienVijftien\Bundle\WpTurboBundle\Compiler\RemoveUninstantiableUxDefinitionsPass;
use AchttienVijftien\Bundle\WpTurboBundle\Routing\Dispatcher;
use AchttienVijftien\Bundle\WpTurboBundle\Routing\TurboControllerInterface;
use AchttienVijftien\Bundle\WpTurboBundle\Routing\WpRewriteRegistrar;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * WP Turbo bundle (Symfony UX Turbo for WordPress).
 *
 * NOT named TurboBundle: Bundle::getName() uses the class short name and the
 * service container rejects duplicates, which would collide with
 * Symfony\UX\Turbo\TurboBundle.
 *
 * The generic Twig bridging (the `twig` service, the twig.extension and
 * twig.runtime tags, @BundleName template namespaces) lives in
 * achttienvijftien/wp-twig-bundle; this bundle only contributes the
 * UX-specific environment setup through its `wp_twig.configurator` tag
 * (see Configurator\ComponentLexerConfigurator).
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class WpTurboBundle extends AbstractBundle {

	/**
	 * Builds the bundle.
	 *
	 * @param ContainerBuilder $container The container builder.
	 *
	 * @return void
	 */
	public function build( ContainerBuilder $container ): void {
		parent::build( $container );

		$container->addCompilerPass( new RemoveUninstantiableUxDefinitionsPass() );

		$container->registerForAutoconfiguration( TurboControllerInterface::class )
			->addTag( 'wp_turbo.controller' );

		$container->addCompilerPass( new ControllerPass() );
	}

	/**
	 * Boots the bundle (runs on muplugins_loaded when the kernel boots the
	 * bundles): wires the routing layer into WordPress's request lifecycle.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'init', [ $this->container->get( WpRewriteRegistrar::class ), 'register' ] );
		add_action( 'parse_request', [ $this->container->get( Dispatcher::class ), 'dispatch' ] );
	}

	/**
	 * Loads the bundle's service definitions.
	 *
	 * @param array                 $config    The bundle configuration.
	 * @param ContainerConfigurator $container The container configurator.
	 * @param ContainerBuilder      $builder   The container builder.
	 *
	 * @return void
	 */
	public function loadExtension( array $config, ContainerConfigurator $container, ContainerBuilder $builder ): void {
		$container->import( '../config/services.yaml' );
	}

	/**
	 * Prepends configuration for the UX bundles' extensions.
	 *
	 * @param ContainerConfigurator $container The container configurator.
	 * @param ContainerBuilder      $builder   The container builder.
	 *
	 * @return void
	 */
	public function prependExtension( ContainerConfigurator $container, ContainerBuilder $builder ): void {
		// twig_component requires explicit config since 2.13: where anonymous
		// component templates live and which naming defaults apply. The
		// anonymous_template_directory value gets reconciled against
		// ux-turbo's actual template layout when the render path lands.
		$builder->prependExtensionConfig(
			'twig_component',
			[
				'anonymous_template_directory' => 'components/',
				'defaults'                     => [],
			]
		);
	}
}
