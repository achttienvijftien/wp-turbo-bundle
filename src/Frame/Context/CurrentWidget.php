<?php
/**
 * Widget-instance frame context.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Frame\Context;

use AchttienVijftien\Bundle\WpTurboBundle\Http\NotFoundException;

/**
 * Identifies the widget instance a frame request renders for, from a
 * `widget_id` parameter, so the controller can read that instance's own
 * configuration exactly as the widget's inline render would. The id is
 * validated against the actively placed sidebar widgets, so requests
 * cannot probe configuration of arbitrary or unplaced widget ids.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class CurrentWidget implements FrameContext {

	/**
	 * The validated widget id for the current request.
	 *
	 * @var string|null
	 */
	private ?string $widget_id = null;

	/**
	 * {@inheritDoc}
	 *
	 * @throws NotFoundException thrown if widget was not found or not placed in active sidebar.
	 */
	public function setup( array $params ): void {
		$widget_id = (string) ( $params['widget_id'] ?? '' );

		if ( '' === $widget_id || ! $this->is_active_widget( $widget_id ) ) {
			throw new NotFoundException( 'Widget not found or not placed in an active sidebar.' );
		}

		$this->widget_id = $widget_id;
	}

	/**
	 * Returns the validated widget id.
	 *
	 * @return string
	 * @throws \LogicException When called before setup() validated a request.
	 */
	public function get_id(): string {
		if ( null === $this->widget_id ) {
			// phpcs:ignore Generic.Files.LineLength.MaxExceeded
			throw new \LogicException( 'No widget id captured; CurrentWidget::setup() runs first (declare it via #[WithFrameContext]).' );
		}

		return $this->widget_id;
	}

	/**
	 * Whether the widget id is placed in an active sidebar.
	 *
	 * @param string $widget_id The widget id (e.g. rmg_widget_author_footer-2).
	 *
	 * @return bool
	 */
	private function is_active_widget( string $widget_id ): bool {
		// The option, not wp_get_sidebars_widgets(): the latter serves a
		// request-global cache that ignores later option changes.
		foreach ( (array) get_option( 'sidebars_widgets', [] ) as $sidebar => $widgets ) {
			// Parked widgets are not rendered anywhere, so they are not served.
			if ( 'wp_inactive_widgets' === $sidebar || ! is_array( $widgets ) ) {
				continue;
			}

			if ( in_array( $widget_id, $widgets, true ) ) {
				return true;
			}
		}

		return false;
	}
}
