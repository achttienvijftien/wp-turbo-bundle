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
assets, the shared JS runtime, conditional enqueue) is the
`achttienvijftien/wp-turbo` mu-plugin, which depends on this bundle; WordPress
projects typically require that package instead of this one directly.

## What works today

Authoring Turbo Frames and Streams end to end through the host (Timber) Twig
environment:

```twig
<twig:Turbo:Frame id="author-footer" src="/_turbo/author-footer?post_id=42" loading="lazy">
    <p class="skeleton">loading…</p>
</twig:Turbo:Frame>

<twig:Turbo:Stream:Update target="#results">
    <p>fresh results</p>
</twig:Turbo:Stream:Update>
```

Note: the Stream components take a `target` prop (a full CSS selector, e.g.
`#results`) and emit it as the `targets` attribute.

## Planned

Routing (a `/_turbo/*` catch-all rewrite + `#[Route]` controllers), context
resolvers that rebuild WordPress state for frame requests, response/stream
helpers, and the shared JS runtime (Turbo with Drive disabled + one Stimulus
application, conditionally enqueued). Mercure/broadcast is out of scope.

## Development

```bash
nvm use && pnpm install
pnpm wp-env start
pnpm test            # wp-env + WP test suite; clears var/cache first
```

The wp-env config maps the sibling `../wp-twig-bundle` checkout over the
composer path-repository symlink so the container can resolve it.
