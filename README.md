# Attachment Redirect

WordPress creates a public page for every media upload. Those attachment pages are
thin, duplicate-prone content that search engines love to index and visitors never
want to land on. This plugin redirects them away.

## What it does

- Redirects attachment pages to the **media file itself** (default), the **parent
  post**, or a **404 page** — your choice in Settings → Attachment Redirect.
- Applies to images only, or to all attachment types (PDFs, videos, audio).
- 301 (permanent) or 302 (temporary) redirect status.
- Falls back gracefully: "parent" mode uses the file URL when an attachment has
  no parent post.

## Filters

- `war_skip_redirect` — return `true` to exempt a specific attachment:
  ```php
  add_filter( 'war_skip_redirect', function ( $skip, $attachment_id ) {
      return 123 === $attachment_id; // Keep this one as a real page.
  }, 10, 2 );
  ```

## Installation

1. Upload the plugin folder to `wp-content/plugins/`.
2. Activate it from Plugins.
3. Choose your target under Settings → Attachment Redirect.

## License

MIT — see `LICENSE`.
