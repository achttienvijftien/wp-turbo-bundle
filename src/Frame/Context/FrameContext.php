<?php
/**
 * Frame context contract.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Frame\Context;

use AchttienVijftien\Bundle\WpTurboBundle\Http\NotFoundException;

/**
 * Rebuilds WordPress state for a frame request.
 *
 * A frame endpoint is its own HTTP request: WordPress never ran the main
 * query for the page the frame sits on, so widgets/components relying on
 * get_the_ID() or conditional tags would misbehave. Implementations restore
 * exactly the state a controller's components expect.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
interface FrameContext {

	/**
	 * Establishes WordPress state for a frame request.
	 *
	 * @param array $params Merged route and query parameters; route placeholders win.
	 *
	 * @return void
	 * @throws NotFoundException When the referenced state does not exist or is not public.
	 */
	public function setup( array $params ): void;
}
