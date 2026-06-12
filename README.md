# wp-turbo-bundle

Symfony UX Turbo (Frames + Streams) for WordPress, running on
`achttienvijftien/service-container`.

Registers the real `symfony/ux-turbo` and `symfony/ux-twig-component` bundles
on the container and contributes the UX-specific Twig environment setup (the
`<twig:...>` component lexer, escaper safe-classes) through the
`wp_twig.configurator` tag. The generic Twig bridging (the `twig` service,
`twig.extension`/`twig.runtime` tag consumption, `@BundleName` template
namespaces, the Timber adapter) lives in `achttienvijftien/wp-twig-bundle`,
which this package depends on.

Self-invoking: requiring the package registers everything through composer
`autoload.files` and the `achttienvijftien/container_bundles` filter. No
activation, no config edits.

This is a pure-PHP bundle (`type: library`). The frontend carrier (webroot
assets, the Turbo JS runtime, asset registration) is the
`achttienvijftien/wp-turbo` mu-plugin, which depends on this bundle; WordPress
projects typically require that package instead of this one directly.

## Frame endpoints

WordPress cedes the `/_turbo/*` namespace through one static catch-all
rewrite rule (like `/wp-json` for REST); route matching happens in PHP via
symfony/routing. An endpoint is a `#[Route]`-attributed service implementing
the marker interface:

```php
#[Route( '/_turbo/author-footer', name: 'turbo_author_footer', methods: [ 'GET' ] )]
#[WithFrameContext( CurrentPost::class )]
#[WithFrameContext( CurrentWidget::class )]
class AuthorFooterController implements TurboControllerInterface {

	public function __construct(
		private readonly FrameResponseFactory $frame_response,
		private readonly CurrentWidget $current_widget,
	) {
	}

	public function __invoke( array $params, TurboFrame $frame ): Response {
		// get_the_ID(), is_single() etc. behave as on the real post page,
		// and the widget instance's own settings are within reach.
		$settings = get_fields( 'widget_' . $this->current_widget->get_id() );

		return $this->frame_response->frame(
			'author-footer',
			/* rendered fragment */ '',
			[ 'Cache-Control' => 'public, max-age=300' ]
		);
	}
}
```

- Route `name:` is required and unique (compile-time validated); paths must
  live under `/_turbo/` (also compile-time validated).
- `#[WithFrameContext]` is repeatable; the declared `FrameContext` services
  run in declaration order before the controller and rebuild WordPress state
  for the request: `CurrentPost` (validates a public `post_id` and restores
  singular context), `CurrentWidget` (validates a `widget_id` against
  actively placed sidebar widgets), or your own implementation. Unknown
  routes, non-public posts and unplaced widgets get a controlled
  `text/plain` 404.
- The dispatcher only answers requests whose real path lies under
  `/_turbo/`, enforces the declared methods, and merges matched path
  placeholders over query parameters (placeholders win) for contexts and
  controller alike.

## Building placeholders

PHP render sites (widgets) use the `FramePlaceholder` service:

```php
echo $this->frame_placeholder->eager( 'author-footer', 'turbo_author_footer', [ 'post_id' => $post_id ] );
```

`lazy()` exists too, but a lazy frame only loads once the element occupies
space: an empty frame is a 0x0 inline element that never triggers
visibility-based loading. Give it fallback content (a skeleton) or CSS
dimensions first; use `eager()` otherwise.

This bundle ships no JavaScript. Placeholders fire
`wp_turbo/frame_placeholder` (with the frame id), and whoever owns the Turbo
runtime listens and enqueues its own script: the `achttienvijftien/wp-turbo`
mu-plugin is the default carrier (composer `suggest`), a theme bundle can
take over by registering its own listener. When a placeholder renders with
no listener at all, the helper raises `_doing_it_wrong()` so the broken
setup is loud instead of a frame that never loads.

Twig render sites author the native component, with `path()`/`url()`
provided through the `UrlGeneratorInterface` contract this bundle fulfills
(wp-twig-bundle registers the Twig functions when the contract is present):

```twig
<twig:Turbo:Frame id="author-footer" src="{{ path('turbo_author_footer', { post_id: post.id }) }}" loading="lazy">
    <p class="skeleton">loading…</p>
</twig:Turbo:Frame>

<twig:Turbo:Stream:Update target="#results">
    <p>fresh results</p>
</twig:Turbo:Stream:Update>
```

Note: the Stream components take a `target` prop (a full CSS selector, e.g.
`#results`) and emit it as the `targets` attribute.

Controllers wrap their fragment through `FrameResponseFactory` (Turbo swaps
by frame id, so the response frame must echo the placeholder's id).

## Planned

A stream response helper for `<twig:Turbo:Stream:*>` endpoints.
Mercure/broadcast is out of scope.

## Development

```bash
composer install     # the wp-twig-bundle path dist resolves against a sibling checkout
nvm use && pnpm install
pnpm wp-env start
pnpm test            # wp-env + WP test suite; clears var/cache first
```

The wp-env config maps a sibling `../wp-twig-bundle` checkout over the
installed dependency inside the container.
