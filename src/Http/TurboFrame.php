<?php
/**
 * Turbo-Frame request detection.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Http;

/**
 * Value object describing whether the current request was issued by a Turbo
 * Frame (Turbo sends the frame id in the `Turbo-Frame` request header).
 *
 * Intentionally independent of ux-turbo internals.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class TurboFrame {

	/**
	 * TurboFrame constructor.
	 *
	 * @param string|null $frame_id The requesting frame's id, null when the
	 *                              request was not issued by a frame.
	 */
	public function __construct( private readonly ?string $frame_id ) {
	}

	/**
	 * Builds the value object from the PHP request globals.
	 *
	 * @return static
	 */
	public static function from_globals(): static {
		$header = $_SERVER['HTTP_TURBO_FRAME'] ?? null;

		return new static( is_string( $header ) && '' !== $header ? $header : null );
	}

	/**
	 * Whether the request was issued by a Turbo Frame.
	 *
	 * @return bool
	 */
	public function is_frame_request(): bool {
		return null !== $this->frame_id;
	}

	/**
	 * Returns the requesting frame's id. Untrusted client input; escape on output.
	 *
	 * @return string|null
	 */
	public function get_request_id(): ?string {
		return $this->frame_id;
	}
}
