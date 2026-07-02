# Response Attribute Bag + `wp_turbo/send_headers` Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Give the bundle two neutral primitives — an opaque `attributes` bag on `Response` and a `wp_turbo/send_headers` action fired at emit time — so a downstream theme/plugin can attach `Cache-Tag` headers to Turbo fragment responses without the bundle knowing anything about caching.

**Architecture:** `Response` is an immutable value object; we add a 4th constructor param plus read accessors and one immutable wither, all opaque (the bundle stores and passes attributes through, never reads them). `FrameResponseFactory::frame()` gains a matching pass-through param. `ResponseEmitter::emit()` fires `do_action( 'wp_turbo/send_headers', $response )` after the response's own headers are queued and before the body is echoed — the exact ordering that lets an emit-time listener see queued headers via `headers_list()` while output hasn't started. This is Part 1 of 4 of the fragment cache-tags work; it is mergeable on its own and adds **no** dependency on other repos.

**Tech Stack:** PHP 8.3, WordPress plugin API (`do_action`), PHPUnit 9.5 running inside `@wordpress/env` (wp-env), PHPCS (WordPress standard).

---

## Prerequisites — how to run the tests

Tests run **inside the wp-env container**, not on the host. There is **no `composer test`** script; ignore any reference to one.

**One-time, before you start** (starts the Docker containers):

```bash
pnpm install
pnpm exec wp-env start
```

**Run a single test class/method** (fast red-green loop) — substitute the filter:

```bash
pnpm exec wp-env run tests-cli --env-cwd=wp-content/plugins/wp-turbo-bundle vendor/bin/phpunit --filter HttpResponseTest
```

**Run the whole PHP suite** (clears the cache first, matches CI):

```bash
pnpm test
```

**Lint** (runs on the host against the WordPress coding standard):

```bash
composer lint      # phpcs — report only
composer format    # phpcbf — auto-fix, use if lint complains
```

> Test conventions to match (see `tests/php/FrameResponseFactoryTest.php`): namespace
> `AchttienVijftien\Bundle\WpTurboBundle\Test`, extend `WP_UnitTestCase`, method
> names `test_snake_case_describing_behavior`, assertions via `self::assertSame(...)`.

**Task ordering matters:** do Task 1 before Task 2 — Task 2's test calls `get_attributes()`,
which Task 1 introduces on `Response`.

---

### Task 1: Add the opaque attribute bag to `Response`

A general-purpose metadata channel — **not** a typed `cache_tags` field. The bundle
never interprets the contents; `cache_tags` is just one convention a consumer may use.

**Files:**
- Modify: `src/Http/Response.php`
- Test: `tests/php/HttpResponseTest.php` (new — no `Response` test exists today)

**Step 1: Write the failing test**

Create `tests/php/HttpResponseTest.php`:

```php
<?php
/**
 * Response value-object tests: the opaque attribute bag.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle\Test
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Test;

use AchttienVijftien\Bundle\WpTurboBundle\Http\Response;
use WP_UnitTestCase;

class HttpResponseTest extends WP_UnitTestCase {

	public function test_attributes_default_to_empty(): void {
		$response = new Response( 'body' );

		self::assertSame( [], $response->get_attributes() );
	}

	public function test_get_attribute_returns_default_when_absent(): void {
		$response = new Response( 'body' );

		self::assertNull( $response->get_attribute( 'cache_tags' ) );
		self::assertSame( 'fallback', $response->get_attribute( 'cache_tags', 'fallback' ) );
	}

	public function test_get_attribute_returns_a_constructed_value(): void {
		$response = new Response( 'body', 200, [], [ 'cache_tags' => [ 'author-7' ] ] );

		self::assertSame( [ 'author-7' ], $response->get_attribute( 'cache_tags' ) );
	}

	public function test_with_attribute_adds_a_key_without_mutating_the_original(): void {
		$original = new Response( 'body' );
		$copy     = $original->with_attribute( 'cache_tags', [ 'author-7' ] );

		self::assertSame( [], $original->get_attributes() );
		self::assertSame( [ 'cache_tags' => [ 'author-7' ] ], $copy->get_attributes() );
	}

	public function test_with_attribute_overrides_an_existing_key(): void {
		$response = ( new Response( 'body', 200, [], [ 'cache_tags' => [ 'old' ] ] ) )
			->with_attribute( 'cache_tags', [ 'new' ] );

		self::assertSame( [ 'new' ], $response->get_attribute( 'cache_tags' ) );
	}

	public function test_with_attribute_preserves_body_status_and_headers(): void {
		$response = ( new Response( 'body', 201, [ 'X-Foo' => 'bar' ] ) )
			->with_attribute( 'cache_tags', [ 'author-7' ] );

		self::assertSame( 'body', $response->get_body() );
		self::assertSame( 201, $response->get_status() );
		self::assertSame( [ 'X-Foo' => 'bar' ], $response->get_headers() );
	}
}
```

> Why the last test: it guards against a copy-paste slip in the wither where the
> constructor args get reordered — the immutable copy must carry body/status/headers
> through unchanged.

**Step 2: Run the test to verify it fails**

```bash
pnpm exec wp-env run tests-cli --env-cwd=wp-content/plugins/wp-turbo-bundle vendor/bin/phpunit --filter HttpResponseTest
```

Expected: FAIL — `Error: Call to undefined method …\Response::get_attributes()`
(PHP ignores the extra 4th constructor arg silently; the missing accessor is what blows up).

**Step 3: Write the minimal implementation**

Edit `src/Http/Response.php`. First update the class docblock:

```php
/**
 * Immutable response produced by Turbo controllers and consumed by the
 * ResponseEmitter. Deliberately minimal: body, status, headers, attributes.
 *
 * Attributes are an opaque metadata bag: the bundle stores and passes them
 * through but never reads them. Conventions such as a `cache_tags` key are
 * agreed on by the consumers (theme + plugin), not by this class.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
```

Add the 4th constructor param (update the constructor docblock too):

```php
	/**
	 * Response constructor.
	 *
	 * @param string $body       The response body.
	 * @param int    $status     The HTTP status code.
	 * @param array  $headers    Header name => value map.
	 * @param array  $attributes Opaque metadata bag; never interpreted here.
	 */
	public function __construct(
		private readonly string $body,
		private readonly int $status = 200,
		private readonly array $headers = [],
		private readonly array $attributes = [],
	) {
	}
```

Add the three accessors after `get_headers()`:

```php
	/**
	 * Returns the full attribute bag.
	 *
	 * @return array
	 */
	public function get_attributes(): array {
		return $this->attributes;
	}

	/**
	 * Returns a single attribute, or $default when the key is absent.
	 *
	 * @param string $key     The attribute key.
	 * @param mixed  $default Value returned when the key is not set.
	 *
	 * @return mixed
	 */
	public function get_attribute( string $key, mixed $default = null ): mixed {
		return $this->attributes[ $key ] ?? $default;
	}

	/**
	 * Returns a copy of the response with one attribute added or overridden.
	 *
	 * @param string $key   The attribute key.
	 * @param mixed  $value The attribute value.
	 *
	 * @return self
	 */
	public function with_attribute( string $key, mixed $value ): self {
		return new self(
			$this->body,
			$this->status,
			$this->headers,
			[ ...$this->attributes, $key => $value ],
		);
	}
```

**Step 4: Run the test to verify it passes**

```bash
pnpm exec wp-env run tests-cli --env-cwd=wp-content/plugins/wp-turbo-bundle vendor/bin/phpunit --filter HttpResponseTest
```

Expected: PASS (6 tests, OK).

**Step 5: Lint the changed file**

```bash
composer lint
```

Expected: no errors for `src/Http/Response.php` / `tests/php/HttpResponseTest.php`
(run `composer format` if it flags spacing/alignment, then re-run `composer lint`).

**Step 6: Commit**

```bash
git add src/Http/Response.php tests/php/HttpResponseTest.php
git commit -m "feat: add opaque attribute bag to Response"
```

---

### Task 2: Pass attributes through `FrameResponseFactory::frame()`

**Files:**
- Modify: `src/Frame/FrameResponseFactory.php:31-39`
- Test: `tests/php/FrameResponseFactoryTest.php` (add two cases)

**Step 1: Write the failing tests**

Add these two methods to `tests/php/FrameResponseFactoryTest.php` (inside the existing class):

```php
	public function test_attributes_pass_through_to_the_response(): void {
		$response = ( new FrameResponseFactory() )->frame(
			'author-footer',
			'x',
			[],
			[ 'cache_tags' => [ 'author-7' ] ]
		);

		self::assertSame( [ 'cache_tags' => [ 'author-7' ] ], $response->get_attributes() );
		self::assertSame( [ 'author-7' ], $response->get_attribute( 'cache_tags' ) );
	}

	public function test_attributes_default_to_empty(): void {
		$response = ( new FrameResponseFactory() )->frame( 'author-footer', 'x' );

		self::assertSame( [], $response->get_attributes() );
	}
```

**Step 2: Run the tests to verify they fail**

```bash
pnpm exec wp-env run tests-cli --env-cwd=wp-content/plugins/wp-turbo-bundle vendor/bin/phpunit --filter FrameResponseFactoryTest
```

Expected: FAIL on `test_attributes_pass_through_to_the_response` — the 4th arg is
silently dropped by the current 3-param `frame()`, so `get_attributes()` returns `[]`:
`Failed asserting that two arrays are equal.` (`test_attributes_default_to_empty` may
already pass since the default is `[]` — that's fine, it's the regression guard.)

**Step 3: Write the minimal implementation**

Edit `src/Frame/FrameResponseFactory.php` — add the 4th param and forward it, updating the docblock:

```php
	/**
	 * Wraps rendered content in its Turbo Frame.
	 *
	 * @param string $frame_id   The frame id, matching the placeholder's.
	 * @param string $content    The rendered fragment (already escaped where needed).
	 * @param array  $headers    Extra headers; Content-Type defaults to HTML.
	 * @param array  $attributes Opaque response metadata (e.g. a `cache_tags` key); passed through untouched.
	 *
	 * @return Response
	 */
	public function frame( string $frame_id, string $content, array $headers = [], array $attributes = [] ): Response {
		$headers += [ 'Content-Type' => 'text/html; charset=UTF-8' ];

		return new Response(
			'<turbo-frame id="' . esc_attr( $frame_id ) . '">' . $content . '</turbo-frame>',
			200,
			$headers,
			$attributes
		);
	}
```

**Step 4: Run the tests to verify they pass**

```bash
pnpm exec wp-env run tests-cli --env-cwd=wp-content/plugins/wp-turbo-bundle vendor/bin/phpunit --filter FrameResponseFactoryTest
```

Expected: PASS (all cases in the class, including the pre-existing ones).

**Step 5: Lint**

```bash
composer lint
```

Expected: no errors for the changed files.

**Step 6: Commit**

```bash
git add src/Frame/FrameResponseFactory.php tests/php/FrameResponseFactoryTest.php
git commit -m "feat: forward attributes through FrameResponseFactory::frame()"
```

---

### Task 3: Fire `wp_turbo/send_headers` in `ResponseEmitter::emit()`

**Files:**
- Modify: `src/Http/ResponseEmitter.php:26-37`

**No unit test.** `emit()` ends in `exit`, which would terminate the PHPUnit process,
so the whole method is deliberately kept untested (see its existing class docblock).
The new `do_action` line inherits that status: it is a lifecycle emission, not logic.
Unit-testing it would require a larger "make emit injectable" refactor — **out of scope**
for this PR. We verify it by inspection + the full suite staying green.

**Step 1: Insert the hook and update the docblock**

Edit `src/Http/ResponseEmitter.php`. Update the class docblock to note the emission:

```php
/**
 * Sends a Response and ends the request. Deliberately a few dumb lines that
 * stay UNTESTED: it exits the PHP process, so everything testable lives in
 * the exit-free Dispatcher::handle() instead. The `wp_turbo/send_headers`
 * emission below inherits that untested status — it is a lifecycle event, not
 * logic, and can't be exercised without the exit.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
```

Then insert the `do_action` between the header loop and the `echo` — **this ordering is the whole point**: an emit-time listener must see the response's own headers via `headers_list()`, and output must not have started yet.

```php
	public function emit( Response $response ): void {
		status_header( $response->get_status() );

		foreach ( $response->get_headers() as $name => $value ) {
			header( "$name: $value" );
		}

		/**
		 * Fires after the response's own headers are queued and before the
		 * body is sent — the Turbo analog of WordPress's `send_headers`.
		 * Listeners may emit further headers via header().
		 *
		 * @param Response $response The response being emitted.
		 */
		do_action( 'wp_turbo/send_headers', $response );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the body IS the rendered response; escaping is the controller/renderer's responsibility.
		echo $response->get_body();

		exit;
	}
```

**Step 2: Verify by inspection**

Confirm, by reading the final method top-to-bottom, that the call order is:
`status_header` → `header()` loop → `do_action( 'wp_turbo/send_headers', $response )`
→ `echo` → `exit`. The action must sit **after** the loop and **before** `echo`.

**Step 3: Run the full suite (nothing should regress)**

```bash
pnpm test
```

Expected: PASS — all tests OK, including `RenderSmokeTest` and `SmokeTest`.

**Step 4: Lint**

```bash
composer lint
```

Expected: no errors for `src/Http/ResponseEmitter.php`.

**Step 5: Commit**

```bash
git add src/Http/ResponseEmitter.php
git commit -m "feat: fire wp_turbo/send_headers at emit time"
```

---

### Task 4: Document the new surface in the README

There is no dedicated hook list in `README.md` today; the closest home is the
paragraph on controllers wrapping fragments through `FrameResponseFactory` (near
the end of the "Building placeholders" section). Add a short note there covering
the `attributes` param and the new action.

**Files:**
- Modify: `README.md` (after the line "Controllers wrap their fragment through `FrameResponseFactory` …")

**Step 1: Add the note**

Insert after the existing sentence:

> Controllers wrap their fragment through `FrameResponseFactory` (Turbo swaps
> by frame id, so the response frame must echo the placeholder's id).

the following paragraph:

```markdown
`frame()` also takes an optional `attributes` array — an opaque metadata bag
carried on the `Response` (the bundle stores it but never reads it). At emit
time the bundle fires `wp_turbo/send_headers` (the Turbo analog of WordPress's
`send_headers`), after the response's own headers are queued and before the
body, so a listener can inspect the `Response` and emit further headers. The
`cache_tags` key is a convention agreed on by consumers (e.g. a theme bridge
turning tags into `Cache-Tag` headers), not bundle API.
```

**Step 2: Verify**

```bash
git diff README.md
```

Expected: the new paragraph appears once, in the right place, with no other churn.

**Step 3: Commit**

```bash
git add README.md
git commit -m "docs: document response attributes and wp_turbo/send_headers"
```

---

## Final verification

**Step 1: Full suite green**

```bash
pnpm test
```

Expected: OK — every test passes, including `HttpResponseTest`,
`FrameResponseFactoryTest`, `RenderSmokeTest`, `SmokeTest`.

**Step 2: Lint clean**

```bash
composer lint
```

Expected: no PHPCS errors.

**Step 3: Confirm the diff is exactly the four changes**

```bash
git log --oneline -4
git diff --stat main
```

Expected files touched: `src/Http/Response.php`, `src/Frame/FrameResponseFactory.php`,
`src/Http/ResponseEmitter.php`, `tests/php/HttpResponseTest.php`,
`tests/php/FrameResponseFactoryTest.php`, `README.md`.

---

## Notes / conventions (carried from the spec)

- **BC-safe:** every new param is optional. The 404 `Response` in
  `Dispatcher::handle()` (`src/Routing/Dispatcher.php:128`) and all existing
  tests keep working untouched.
- **Hook name:** `wp_turbo/send_headers` (slash-namespaced) matches the codebase
  convention (`achttienvijftien/container`, `wp_turbo/frame_placeholder`, …). This
  is the bundle's first *bundle-defined* WP action.
- **`cache_tags` is a convention, not bundle API** — the theme and plugin agree on
  the key; the bundle only stores the opaque `attributes` array.
- **No new dependency** on cloudfront-cache or the theme. The bundle stays
  standalone: `attributes` is just data and `wp_turbo/send_headers` is just an event.
- **Dropped from scope:** a general `wp_turbo/response` transform filter in
  `Dispatcher` (considered, then cut). Not needed for this feature.
- **Downstream:** this PR is consumed by PR 3 (theme cache bridge), which listens
  on `wp_turbo/send_headers` and relays into the cloudfront-cache plugin's own
  emission (PR 2). Nothing in those PRs is needed to merge this one.
