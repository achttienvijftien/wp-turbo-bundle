<?php
/**
 * Singular-post frame context.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Frame\Context;

use AchttienVijftien\Bundle\WpTurboBundle\Http\NotFoundException;

/**
 * Rebuilds the context of a singular post from a `post_id` parameter, so the
 * controller's widgets/components behave exactly as on the real post page
 * (get_the_ID(), is_single(), the loop's global $post).
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class CurrentPost implements FrameContext {

	/**
	 * {@inheritDoc}
	 */
	public function setup( array $params ): void {
		$post_id = (int) ( $params['post_id'] ?? 0 );
		$post    = $post_id ? get_post( $post_id ) : null;

		// Decision: frame endpoints render public read data; password-
		// protected content is not public, so it is treated as not found.
		if ( ! $post || ! is_post_publicly_viewable( $post ) || post_password_required( $post ) ) {
			throw new NotFoundException( 'Post not found or not publicly viewable.' );
		}

		global $wp_query;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- rebuilding request state is this class's job.
		$GLOBALS['post'] = $post;
		setup_postdata( $post );

		// Make conditional tags answer as they would on the post's own page.
		$wp_query->queried_object    = $post;
		$wp_query->queried_object_id = $post->ID;
		$wp_query->is_single         = true;
		$wp_query->is_singular       = true;
		$wp_query->is_home           = false;
		$wp_query->is_front_page     = false;
		$wp_query->is_archive        = false;
		$wp_query->is_404            = false;
	}
}
