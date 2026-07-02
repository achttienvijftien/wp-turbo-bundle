<?php
/**
 * HTTP response emitter.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Http;

/**
 * Sends a Response and ends the request. Deliberately a few dumb lines that
 * stay UNTESTED: it exits the PHP process, so everything testable lives in
 * the exit-free Dispatcher::handle() instead. The `wp_turbo/send_headers`
 * emission below inherits that untested status — it is a lifecycle event, not
 * logic, and can't be exercised without the exit.
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

		/**
		 * Fires after the response's own headers are queued and before the
		 * body is sent — the Turbo analog of WordPress's `send_headers`.
		 * Listeners may emit further headers via header().
		 *
		 * @param Response $response The response being emitted.
		 */
		do_action( 'wp_turbo/send_headers', $response );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the body IS the rendered response; escaping is the controller/renderer's responsibility.
		echo $response->get_body();

		exit;
	}
}
