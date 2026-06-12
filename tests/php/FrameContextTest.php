<?php

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\Frame\Context\CurrentPost;
use AchttienVijftien\Bundle\WpTurboBundle\Frame\Context\NullContext;
use AchttienVijftien\Bundle\WpTurboBundle\Http\NotFoundException;
use WP_UnitTestCase;

class FrameContextTest extends WP_UnitTestCase {

	public function test_current_post_sets_up_singular_context(): void {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );

		( new CurrentPost() )->setup( [ 'post_id' => $post_id ] );

		self::assertSame( $post_id, get_the_ID() );
		self::assertTrue( is_single() );
		self::assertTrue( is_singular() );
		self::assertFalse( is_home() );
		self::assertFalse( is_archive() );
		self::assertFalse( is_404() );
	}

	public function test_current_post_throws_for_draft_post(): void {
		$post_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );

		$this->expectException( NotFoundException::class );

		( new CurrentPost() )->setup( [ 'post_id' => $post_id ] );
	}

	public function test_current_post_throws_for_password_protected_post(): void {
		$post_id = self::factory()->post->create(
			[
				'post_status'   => 'publish',
				'post_password' => 'secret',
			]
		);

		$this->expectException( NotFoundException::class );

		( new CurrentPost() )->setup( [ 'post_id' => $post_id ] );
	}

	public function test_current_post_throws_for_missing_post(): void {
		$this->expectException( NotFoundException::class );

		( new CurrentPost() )->setup( [ 'post_id' => PHP_INT_MAX ] );
	}

	public function test_current_post_throws_without_post_id(): void {
		$this->expectException( NotFoundException::class );

		( new CurrentPost() )->setup( [] );
	}

	public function test_null_context_leaves_state_untouched(): void {
		$before = get_the_ID();

		( new NullContext() )->setup( [] );

		self::assertSame( $before, get_the_ID() );
	}

	public function test_context_services_are_resolvable_from_the_container(): void {
		$container = apply_filters( 'achttienvijftien/container', null );

		self::assertNotNull( $container );
		self::assertInstanceOf( CurrentPost::class, $container->get( CurrentPost::class ) );
		self::assertInstanceOf( NullContext::class, $container->get( NullContext::class ) );
	}
}
