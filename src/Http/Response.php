<?php
/**
 * HTTP response value object.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Http;

/**
 * Immutable response produced by Turbo controllers and consumed by the
 * ResponseEmitter. Deliberately minimal: body, status, headers, attributes.
 *
 * Attributes are an opaque metadata bag: the bundle stores and passes them
 * through but never reads them. Conventions for specific keys are agreed on
 * by the consumers (theme + plugin), not by this class.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class Response {

	/**
	 * Response constructor.
	 *
	 * @param string $body       The response body.
	 * @param int    $status     The HTTP status code.
	 * @param array  $headers    Header name => value map.
	 * @param array  $attributes Opaque metadata bag; never interpreted here.
	 */
	public function __construct(
		private readonly string $body,
		private readonly int $status = 200,
		private readonly array $headers = [],
		private readonly array $attributes = [],
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

	/**
	 * Returns the full attribute bag.
	 *
	 * @return array
	 */
	public function get_attributes(): array {
		return $this->attributes;
	}

	// phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound -- "$default" is the natural name for the fallback value.
	/**
	 * Returns a single attribute, or $default when the key is absent.
	 *
	 * @param string $key     The attribute key.
	 * @param mixed  $default Value returned when the key is not set.
	 *
	 * @return mixed
	 */
	public function get_attribute( string $key, mixed $default = null ): mixed {
		return $this->attributes[ $key ] ?? $default;
	}
	// phpcs:enable Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound

	/**
	 * Returns a copy of the response with one attribute added or overridden.
	 *
	 * @param string $key   The attribute key.
	 * @param mixed  $value The attribute value.
	 *
	 * @return self
	 */
	public function with_attribute( string $key, mixed $value ): self {
		$attributes         = $this->attributes;
		$attributes[ $key ] = $value;

		return new self(
			$this->body,
			$this->status,
			$this->headers,
			$attributes,
		);
	}
}
