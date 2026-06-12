<?php
/**
 * HTTP response emitter.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Http;

/**
 * Sends a Response and ends the request. Deliberately ten dumb lines that
 * stay UNTESTED: it exits the PHP process, so everything testable lives in
 * the exit-free Dispatcher::handle() instead.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class ResponseEmitter {

	/**
	 * Emits the response and terminates the request.
	 *
	 * @param Response $response The response to send.
	 *
	 * @return void
	 */
	public function emit( Response $response ): void {
		status_header( $response->get_status() );

		foreach ( $response->get_headers() as $name => $value ) {
			header( "$name: $value" );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the body IS the rendered response; escaping is the controller/renderer's responsibility.
		echo $response->get_body();

		exit;
	}
}
