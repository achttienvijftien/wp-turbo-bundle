<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test\Support;

use AchttienVijftien\Bundle\WpTurboBundle\Http\Response;
use AchttienVijftien\Bundle\WpTurboBundle\Http\TurboFrame;
use AchttienVijftien\Bundle\WpTurboBundle\Routing\TurboControllerInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Test fixture: declares a localized (array) path to prove the ControllerPass
 * rejects it at compile time; the route table needs a single string path.
 *
 * Deliberately NOT registered in DummyProjectBundle (it would fail the
 * suite's container compile); ControllerPassTest feeds it to the pass on a
 * throwaway ContainerBuilder.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
#[Route( [ 'en' => '/_turbo/hello', 'nl' => '/_turbo/hallo' ], name: 'turbo_localized' )]
class LocalizedPathController implements TurboControllerInterface {

	public function __invoke( array $params, TurboFrame $frame ): Response {
		return new Response( '' );
	}
}
