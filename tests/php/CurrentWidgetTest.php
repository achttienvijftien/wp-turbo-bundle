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
}
