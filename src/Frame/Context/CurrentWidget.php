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
	 * Widget type of a block widget: its settings live in its block markup.
	 *
	 * @var string
	 */
	public const string TYPE_BLOCK = 'block';

	/**
	 * Widget type of a classic widget: its settings live in its instance option.
	 *
	 * @var string
	 */
	public const string TYPE_LEGACY = 'legacy';

	/**
	 * Id prefix WordPress gives every block widget instance.
	 *
	 * @var string
	 */
	private const string BLOCK_ID_PREFIX = 'block-';

	/**
	 * The validated widget id for the current request.
	 *
	 * @var string|null
	 */
	private ?string $widget_id = null;

	/**
	 * The widget's own block, once resolved.
	 *
	 * @var array|null
	 */
	private ?array $block = null;

	/**
	 * Whether the block was resolved, telling an absent block from an unresolved one.
	 *
	 * @var bool
	 */
	private bool $block_resolved = false;

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

		$this->widget_id      = $widget_id;
		$this->block          = null;
		$this->block_resolved = false;
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
	 * Returns where the widget keeps its settings: TYPE_BLOCK or TYPE_LEGACY.
	 *
	 * @return string
	 * @throws \LogicException When called before setup() validated a request.
	 */
	public function get_type(): string {
		return str_starts_with( $this->get_id(), self::BLOCK_ID_PREFIX ) ? self::TYPE_BLOCK : self::TYPE_LEGACY;
	}

	/**
	 * Returns the block the widget holds, resolved once per request.
	 *
	 * @return array|null
	 * @throws \LogicException When called before setup() validated a request.
	 */
	public function get_block(): ?array {
		if ( ! $this->block_resolved ) {
			// Memoized: parse_blocks() is expensive and the instance serves one request.
			$this->block          = $this->resolve_block();
			$this->block_resolved = true;
		}

		return $this->block;
	}

	/**
	 * Reads the widget's stored content and parses out the block it holds.
	 *
	 * @return array|null
	 * @throws \LogicException When called before setup() validated a request.
	 */
	private function resolve_block(): ?array {
		if ( self::TYPE_BLOCK !== $this->get_type() ) {
			return null;
		}

		$number   = (int) substr( $this->get_id(), strlen( self::BLOCK_ID_PREFIX ) );
		$instance = get_option( 'widget_block', [] )[ $number ] ?? null;
		$content  = is_array( $instance ) ? (string) ( $instance['content'] ?? '' ) : '';

		if ( '' === $content ) {
			return null;
		}

		// A block widget holds one block; whitespace yields entries with a null
		// blockName, so the first named entry is that block.
		foreach ( parse_blocks( $content ) as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			return $block;
		}

		return null;
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
