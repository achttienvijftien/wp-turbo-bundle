<?php
/**
 * Turbo controller marker interface.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Routing;

/**
 * Marker interface: implementing it plus a Symfony #[Route] attribute (on
 * the class or its __invoke method) makes a service a Turbo endpoint. The
 * autoconfigured `wp_turbo.controller` tag feeds Compiler\ControllerPass,
 * which collects the route metadata and exposes the controller to the
 * Dispatcher.
 *
 * Invocation convention (enforced by the Dispatcher, not the interface, so
 * controllers stay plain invokables):
 *
 *     public function __invoke( array $params, TurboFrame $frame ): Response
 *
 * $params are the matched route placeholders merged over the request's query
 * parameters (path parameters win); $frame describes the requesting Turbo
 * Frame, when any.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
interface TurboControllerInterface {
}
