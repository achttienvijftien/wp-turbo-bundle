<?php
/**
 * HTTP response value object.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Http;

/**
 * Immutable response produced by Turbo controllers and consumed by the
 * ResponseEmitter. Deliberately minimal: body, status, headers.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class Response {

	/**
	 * Response constructor.
	 *
	 * @param string $body    The response body.
	 * @param int    $status  The HTTP status code.
	 * @param array  $headers Header name => value map.
	 */
	public function __construct(
		private readonly string $body,
		private readonly int $status = 200,
		private readonly array $headers = [],
	) {
	}

	/**
	 * Returns the response body.
	 *
	 * @return string
	 */
	public function get_body(): string {
		return $this->body;
	}

	/**
	 * Returns the HTTP status code.
	 *
	 * @return int
	 */
	public function get_status(): int {
		return $this->status;
	}

	/**
	 * Returns the headers as a name => value map.
	 *
	 * @return array
	 */
	public function get_headers(): array {
		return $this->headers;
	}
}
