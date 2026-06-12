<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test\Support;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Simulates a real project's own bundle (Stud, theme, plugin bundles).
 *
 * Registered BEFORE the package's bundles in tests/bootstrap.php so that its
 * extension loads before twig_component. MergeExtensionConfigurationPass
 * restores its pre-prepend parameter snapshot after EVERY extension load, so
 * any kernel.bundles shim that is not part of that snapshot is wiped here,
 * before TwigComponentExtension runs its TwigBundle check. This reproduces
 * the production fatal that an UX-bundles-first test environment can never
 * hit. Registering a REAL bundle named TwigBundle (wp-twig-bundle) is immune
 * to that ordering; this dummy keeps the suite honest about it.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class DummyProjectBundle extends AbstractBundle {

	/**
	 * Registers test fixture services the way a real project would register
	 * its own Turbo controllers: autoconfigured, so the autoconfiguration of
	 * TurboControllerInterface applies the wp_turbo.controller tag.
	 *
	 * @param array                 $config    The bundle configuration.
	 * @param ContainerConfigurator $container The container configurator.
	 * @param ContainerBuilder      $builder   The container builder.
	 *
	 * @return void
	 */
	public function loadExtension( array $config, ContainerConfigurator $container, ContainerBuilder $builder ): void {
		$container->services()
			->set( HelloController::class )
			->autoconfigure()
			->public();

		$container->services()
			->set( HelloPostController::class )
			->autoconfigure()
			->autowire()
			->public();
	}
}
