<?php
/**
 * Turbo Frame response factory.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Frame;

use AchttienVijftien\Bundle\WpTurboBundle\Http\Response;

/**
 * Builds frame fragment responses: Turbo swaps by frame id, so a frame
 * endpoint's body must wrap its content in a <turbo-frame> whose id matches
 * the placeholder. Controllers use this instead of concatenating markup;
 * see FramePlaceholder for the placeholder side.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class FrameResponseFactory {

	/**
	 * Wraps rendered content in its Turbo Frame.
	 *
	 * @param string $frame_id The frame id, matching the placeholder's.
	 * @param string $content  The rendered fragment (already escaped where needed).
	 * @param array  $headers  Extra headers; Content-Type defaults to HTML.
	 *
	 * @return Response
	 */
	public function frame( string $frame_id, string $content, array $headers = [] ): Response {
		$headers += [ 'Content-Type' => 'text/html; charset=UTF-8' ];

		return new Response(
			'<turbo-frame id="' . esc_attr( $frame_id ) . '">' . $content . '</turbo-frame>',
			200,
			$headers
		);
	}
}
