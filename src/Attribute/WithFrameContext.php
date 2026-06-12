<?php
/**
 * Frame context declaration attribute.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Attribute;

/**
 * Declares which FrameContext service the dispatcher runs before invoking
 * the controller. When absent, the dispatcher defaults to NullContext.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
#[\Attribute( \Attribute::TARGET_CLASS )]
class WithFrameContext {

	/**
	 * WithFrameContext constructor.
	 *
	 * @param string $context_class The FrameContext implementation's class name.
	 */
	public function __construct( public readonly string $context_class ) {
	}
}
