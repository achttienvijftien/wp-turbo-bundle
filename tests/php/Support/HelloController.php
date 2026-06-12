<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test\Support;

use AchttienVijftien\Bundle\WpTurboBundle\Attribute\WithFrameContext;
use AchttienVijftien\Bundle\WpTurboBundle\Frame\Context\CurrentPost;
use AchttienVijftien\Bundle\WpTurboBundle\Http\Response;
use AchttienVijftien\Bundle\WpTurboBundle\Http\TurboFrame;
use AchttienVijftien\Bundle\WpTurboBundle\Routing\TurboControllerInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Test fixture: a Turbo endpoint as a consuming project would write one.
 * Registered as a service by DummyProjectBundle::loadExtension().
 *
 * Emits plain text (see the Content-Type header), so nothing is escaped on
 * purpose; HTML endpoints must escape untrusted values like the frame id.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
#[Route( '/_turbo/hello/{name}', name: 'turbo_hello', methods: [ 'GET' ] )]
#[WithFrameContext( CurrentPost::class )]
class HelloController implements TurboControllerInterface {

	public function __invoke( array $params, TurboFrame $frame ): Response {
		return new Response(
			'Hello ' . $params['name'] . ' on post ' . get_the_ID()
			. ( $frame->is_frame_request() ? ' in frame ' . $frame->get_request_id() : '' ),
			200,
			[ 'Content-Type' => 'text/plain; charset=UTF-8' ]
		);
	}
}
