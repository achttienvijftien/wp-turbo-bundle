<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test\Support;

use AchttienVijftien\Bundle\WpTurboBundle\Http\Response;
use AchttienVijftien\Bundle\WpTurboBundle\Http\TurboFrame;
use AchttienVijftien\Bundle\WpTurboBundle\Routing\TurboControllerInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Test fixture: declares a route OUTSIDE the /_turbo/ namespace to prove the
 * ControllerPass rejects such paths at compile time (only /_turbo/* is ceded
 * to the Dispatcher by the rewrite rule).
 *
 * Deliberately NOT registered in DummyProjectBundle (it would fail the
 * suite's container compile); ControllerPassTest feeds it to the pass on a
 * throwaway ContainerBuilder.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
#[Route( '/hello-outside', name: 'turbo_bad_path' )]
class BadPathController implements TurboControllerInterface {

	public function __invoke( array $params, TurboFrame $frame ): Response {
		return new Response( '' );
	}
}
