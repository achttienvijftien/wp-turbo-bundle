<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test\Support;

use AchttienVijftien\Bundle\WpTurboBundle\Attribute\WithFrameContext;
use AchttienVijftien\Bundle\WpTurboBundle\Frame\Context\CurrentPost;
use AchttienVijftien\Bundle\WpTurboBundle\Http\Response;
use AchttienVijftien\Bundle\WpTurboBundle\Http\TurboFrame;
use AchttienVijftien\Bundle\WpTurboBundle\Routing\TurboControllerInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Test fixture: a route with a {post_id} PATH placeholder, so a conflicting
 * ?post_id= query parameter can prove that the FrameContext and the
 * controller see the SAME merged parameters (path placeholders win in both).
 *
 * Emits plain text (see the Content-Type header); nothing is escaped on
 * purpose. Registered as a service by DummyProjectBundle::loadExtension().
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
#[Route( '/_turbo/hello-post/{post_id}', name: 'turbo_hello_post', methods: [ 'GET' ] )]
#[WithFrameContext( CurrentPost::class )]
class HelloPostController implements TurboControllerInterface {

	public function __invoke( array $params, TurboFrame $frame ): Response {
		return new Response(
			'context=' . get_the_ID() . ' param=' . $params['post_id'],
			200,
			[ 'Content-Type' => 'text/plain; charset=UTF-8' ]
		);
	}
}
