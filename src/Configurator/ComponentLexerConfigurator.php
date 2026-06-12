<?php
/**
 * Contributes ux-twig-component's lexer and escaper setup to the host Twig environment.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Configurator;

use AchttienVijftien\Bundle\WpTwigBundle\EnvironmentConfiguratorInterface;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\Twig\ComponentLexer;
use Twig\Environment;
use Twig\Extension\EscaperExtension;
use Twig\Runtime\EscaperRuntime;

/**
 * What ux-twig-component's TwigEnvironmentConfigurator would do if Symfony's
 * TwigBundle were present (its vendor service decorates a TwigBundle-only
 * service and is removed by RemoveUninstantiableUxDefinitionsPass): the
 * lexer that turns <twig:...> syntax into component calls, and marking
 * ComponentAttributes html-safe so attribute bags render unescaped.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class ComponentLexerConfigurator implements EnvironmentConfiguratorInterface {

	/**
	 * {@inheritDoc}
	 */
	public function configure( Environment $environment ): void {
		$environment->setLexer( new ComponentLexer( $environment ) );

		if ( class_exists( EscaperRuntime::class ) ) {
			$environment->getRuntime( EscaperRuntime::class )->addSafeClass( ComponentAttributes::class, [ 'html' ] );
		} elseif ( $environment->hasExtension( EscaperExtension::class ) ) {
			// Twig <3.10 fallback, the same branch as vendor's configurator.
			$environment->getExtension( EscaperExtension::class )->addSafeClass( ComponentAttributes::class, [ 'html' ] );
		}
	}
}
