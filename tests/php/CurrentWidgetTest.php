<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\Frame\Context\CurrentWidget;
use AchttienVijftien\Bundle\WpTurboBundle\Http\NotFoundException;
use WP_UnitTestCase;

class CurrentWidgetTest extends WP_UnitTestCase {

	public function test_setup_captures_a_widget_placed_in_an_active_sidebar(): void {
		update_option( 'sidebars_widgets', [ 'sidebar-test' => [ 'my_widget-2' ] ] );

		$context = new CurrentWidget();
		$context->setup( [ 'widget_id' => 'my_widget-2' ] );

		self::assertSame( 'my_widget-2', $context->get_id() );
	}

	public function test_setup_throws_for_an_unplaced_widget_id(): void {
		update_option( 'sidebars_widgets', [ 'sidebar-test' => [ 'my_widget-2' ] ] );

		$this->expectException( NotFoundException::class );

		( new CurrentWidget() )->setup( [ 'widget_id' => 'my_widget-99' ] );
	}

	public function test_setup_throws_for_a_widget_parked_in_the_inactive_sidebar(): void {
		update_option( 'sidebars_widgets', [ 'wp_inactive_widgets' => [ 'my_widget-2' ] ] );

		$this->expectException( NotFoundException::class );

		( new CurrentWidget() )->setup( [ 'widget_id' => 'my_widget-2' ] );
	}

	public function test_setup_throws_without_a_widget_id(): void {
		$this->expectException( NotFoundException::class );

		( new CurrentWidget() )->setup( [] );
	}

	public function test_get_id_before_setup_throws(): void {
		$this->expectException( \LogicException::class );

		( new CurrentWidget() )->get_id();
	}

	public function test_get_type_tells_a_block_widget_from_a_legacy_one(): void {
		update_option( 'sidebars_widgets', [ 'sidebar-test' => [ 'block-2', 'rmg_widget_mostread-4' ] ] );

		$block_widget = new CurrentWidget();
		$block_widget->setup( [ 'widget_id' => 'block-2' ] );

		$legacy_widget = new CurrentWidget();
		$legacy_widget->setup( [ 'widget_id' => 'rmg_widget_mostread-4' ] );

		self::assertSame( CurrentWidget::TYPE_BLOCK, $block_widget->get_type() );
		self::assertSame( CurrentWidget::TYPE_LEGACY, $legacy_widget->get_type() );
	}

	public function test_get_type_before_setup_throws(): void {
		$this->expectException( \LogicException::class );

		( new CurrentWidget() )->get_type();
	}

	public function test_get_block_returns_the_block_the_widget_holds(): void {
		update_option( 'sidebars_widgets', [ 'sidebar-test' => [ 'block-2' ] ] );
		update_option(
			'widget_block',
			[ 2 => [ 'content' => '<!-- wp:acf/recent-posts /-->' ] ]
		);

		$context = new CurrentWidget();
		$context->setup( [ 'widget_id' => 'block-2' ] );

		$block = $context->get_block();

		self::assertNotNull( $block );
		self::assertSame( 'acf/recent-posts', $block['blockName'] );
	}

	public function test_get_block_resolves_the_stored_content_only_once(): void {
		update_option( 'sidebars_widgets', [ 'sidebar-test' => [ 'block-2' ] ] );
		update_option(
			'widget_block',
			[ 2 => [ 'content' => '<!-- wp:acf/recent-posts /-->' ] ]
		);

		$context = new CurrentWidget();
		$context->setup( [ 'widget_id' => 'block-2' ] );

		$first = $context->get_block();

		// A memoized block ignores content stored after it was resolved.
		update_option( 'widget_block', [ 2 => [ 'content' => '<!-- wp:acf/most-read /-->' ] ] );

		self::assertSame( $first, $context->get_block() );
	}

	public function test_get_block_returns_null_for_a_legacy_widget(): void {
		update_option( 'sidebars_widgets', [ 'sidebar-test' => [ 'rmg_widget_mostread-4' ] ] );

		$context = new CurrentWidget();
		$context->setup( [ 'widget_id' => 'rmg_widget_mostread-4' ] );

		self::assertNull( $context->get_block() );
	}

	public function test_get_block_returns_null_for_an_unknown_instance(): void {
		update_option( 'sidebars_widgets', [ 'sidebar-test' => [ 'block-2' ] ] );
		update_option( 'widget_block', [] );

		$context = new CurrentWidget();
		$context->setup( [ 'widget_id' => 'block-2' ] );

		self::assertNull( $context->get_block() );
	}
}
