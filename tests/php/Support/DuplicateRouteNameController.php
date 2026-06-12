<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test\Support;

use AchttienVijftien\Bundle\WpTurboBundle\Http\Response;
use AchttienVijftien\Bundle\WpTurboBundle\Http\TurboFrame;
use AchttienVijftien\Bundle\WpTurboBundle\Routing\TurboControllerInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Test fixture: reuses HelloController's route name to prove the
 * ControllerPass rejects duplicate route names at compile time.
 *
 * Deliberately NOT registered in DummyProjectBundle (it would fail the
 * suite's container compile); ControllerPassTest feeds it to the pass on a
 * throwaway ContainerBuilder.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
#[Route( '/_turbo/duplicate', name: 'turbo_hello' )]
class DuplicateRouteNameController implements TurboControllerInterface {

	public function __invoke( array $params, TurboFrame $frame ): Response {
		return new Response( '' );
	}
}
