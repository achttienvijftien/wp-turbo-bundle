<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\Http\TurboFrame;
use WP_UnitTestCase;

class HttpTurboFrameTest extends WP_UnitTestCase {

	private bool $had_header = false;

	private ?string $previous_header = null;

	public function set_up(): void {
		parent::set_up();

		$this->had_header = array_key_exists( 'HTTP_TURBO_FRAME', $_SERVER );

		if ( $this->had_header ) {
			$this->previous_header = $_SERVER['HTTP_TURBO_FRAME'];
		}
	}

	public function tear_down(): void {
		if ( $this->had_header ) {
			$_SERVER['HTTP_TURBO_FRAME'] = $this->previous_header;
		} else {
			unset( $_SERVER['HTTP_TURBO_FRAME'] );
		}

		parent::tear_down();
	}

	public function test_from_globals_without_header_is_not_a_frame_request(): void {
		unset( $_SERVER['HTTP_TURBO_FRAME'] );

		$frame = TurboFrame::from_globals();

		self::assertFalse( $frame->is_frame_request() );
		self::assertNull( $frame->get_request_id() );
	}

	public function test_from_globals_with_header_exposes_the_frame_id(): void {
		$_SERVER['HTTP_TURBO_FRAME'] = 'author-footer';

		$frame = TurboFrame::from_globals();

		self::assertTrue( $frame->is_frame_request() );
		self::assertSame( 'author-footer', $frame->get_request_id() );
	}

	public function test_constructed_with_null_is_not_a_frame_request(): void {
		$frame = new TurboFrame( null );

		self::assertFalse( $frame->is_frame_request() );
		self::assertNull( $frame->get_request_id() );
	}

	public function test_constructed_with_id_is_a_frame_request(): void {
		$frame = new TurboFrame( 'sidebar' );

		self::assertTrue( $frame->is_frame_request() );
		self::assertSame( 'sidebar', $frame->get_request_id() );
	}
}
