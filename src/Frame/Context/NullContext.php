<?php
/**
 * No-op frame context.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Frame\Context;

/**
 * For controllers that work from explicit query parameters only and need no
 * WordPress state rebuilt.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class NullContext implements FrameContext {

	/**
	 * {@inheritDoc}
	 */
	public function setup( array $params ): void {
	}
}
