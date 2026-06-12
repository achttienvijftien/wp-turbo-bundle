<?php
/**
 * <turbo-frame> placeholder rendering for PHP render sites.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Frame;

use AchttienVijftien\Bundle\WpTurboBundle\Routing\RouteRegistry;

/**
 * Builds <turbo-frame> placeholders for PHP render sites (widgets); Twig
 * render sites author the native <twig:Turbo:Frame> component with path()
 * instead.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class FramePlaceholder {

	/**
	 * Attributes the generated markup owns; caller-supplied values for these keys are skipped.
	 *
	 * @var string[]
	 */
	private const RESERVED_ATTRIBUTES = [ 'id', 'src', 'loading' ];

	/**
	 * FramePlaceholder constructor.
	 *
	 * @param RouteRegistry $registry The route table.
	 */
	public function __construct( private readonly RouteRegistry $registry ) {
	}

	/**
	 * Renders a lazy placeholder: Turbo fetches when the frame scrolls into view.
	 *
	 * CAUTION: visibility detection needs the element to occupy space, so a
	 * lazy frame only loads when the placeholder has content (a skeleton) or
	 * CSS dimensions; an empty frame is a 0x0 inline element and never
	 * triggers. Use eager() until a fallback gives the frame a box.
	 *
	 * @param string $frame_id   The frame id the endpoint's response frame must echo.
	 * @param string $route      The route name.
	 * @param array  $params     Route placeholder values; extras become query parameters.
	 * @param array  $attributes Extra HTML attributes as key => value pairs.
	 *
	 * @return string
	 */
	public function lazy( string $frame_id, string $route, array $params = [], array $attributes = [] ): string {
		return $this->placeholder( 'lazy', $frame_id, $route, $params, $attributes );
	}

	/**
	 * Renders an eager placeholder: Turbo fetches as soon as the page loads.
	 *
	 * @param string $frame_id   The frame id the endpoint's response frame must echo.
	 * @param string $route      The route name.
	 * @param array  $params     Route placeholder values; extras become query parameters.
	 * @param array  $attributes Extra HTML attributes as key => value pairs.
	 *
	 * @return string
	 */
	public function eager( string $frame_id, string $route, array $params = [], array $attributes = [] ): string {
		return $this->placeholder( 'eager', $frame_id, $route, $params, $attributes );
	}

	/**
	 * Builds the placeholder markup.
	 *
	 * @param string $loading    The Turbo loading mode ('lazy' or 'eager').
	 * @param string $frame_id   The frame id the endpoint's response frame must echo.
	 * @param string $route      The route name.
	 * @param array  $params     Route placeholder values; extras become query parameters.
	 * @param array  $attributes Extra HTML attributes as key => value pairs.
	 *
	 * @return string
	 */
	private function placeholder( string $loading, string $frame_id, string $route, array $params, array $attributes ): string {
		// Handle registered by the achttienvijftien/wp-turbo mu-plugin; when that is absent WP parks the enqueue of the unregistered handle, a harmless no-op.
		wp_enqueue_script( 'wp-turbo-runtime' );

		$extra = '';

		foreach ( $attributes as $key => $value ) {
			// Reserved keys are skipped because they would clobber the attributes this markup owns.
			if ( in_array( $key, self::RESERVED_ATTRIBUTES, true ) ) {
				continue;
			}

			$extra .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		return sprintf(
			'<turbo-frame id="%s" src="%s" loading="%s"%s></turbo-frame>',
			esc_attr( $frame_id ),
			esc_url( $this->registry->generate( $route, $params ) ),
			esc_attr( $loading ),
			$extra
		);
	}
}
