<?php
/**
 * Frame context declaration attribute.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Attribute;

/**
 * Declares which FrameContext service the dispatcher runs before invoking
 * the controller. Repeatable: multiple declarations run in declaration
 * order (e.g. CurrentPost plus CurrentWidget). When absent, the dispatcher
 * defaults to NullContext.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
#[\Attribute( \Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE )]
class WithFrameContext {

	/**
	 * WithFrameContext constructor.
	 *
	 * @param string $context_class The FrameContext implementation's class name.
	 */
	public function __construct( public readonly string $context_class ) {
	}
}
