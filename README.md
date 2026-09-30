# EffyAds for Laravel

Laravel equivalent of the `wordpress-plugin/` — syncs advertisements pushed
from the EffyAds platform, renders them via a Blade component, and reports
view/click events back.

## Install

```bash
composer require effyads/laravel
php artisan effyads:install     # generates EFFYADS_API_KEY, prints .env instructions
php artisan migrate
```

Paste the printed API key and your site's URL into the EffyAds dashboard to
connect this site.

## Rendering an ad

Define placement zones in `config/effyads.php` (published via
`--tag=effyads-config`), then:

```blade
<x-effyads::ad zone="homepage-hero" />
```

## API surface (mirrors WordPress plugin's `effyads/v1` contract)

All routes below require the `X-EffyAds-API-Key` header (or `?api_key=`
fallback), verified with `hash_equals` against `EFFYADS_API_KEY`.

| Method | Path | Purpose |
|---|---|---|
| GET/POST | `/effyads/v1/advertisements` | list / create (upsert by `platform_id`) |
| GET/PUT/DELETE | `/effyads/v1/advertisements/{platform_id}` | read / update / delete one |
| GET | `/effyads/v1/sync/status` | health check |
| POST | `/effyads/v1/sync/bulk` | reconcile against a list of platform ids |
| GET | `/effyads/v1/targeting/options` | available zones (Laravel has no taxonomy/post-type concept, unlike WordPress) |
| POST | `/effyads/v1/media/upload` | base64 image upload to the configured filesystem disk |

Public (no API key — hit directly by visitor browsers):

| Method | Path | Purpose |
|---|---|---|
| GET | `/effyads/track/view/{platform_id}` | 1x1 tracking pixel |
| GET | `/effyads/track/click/{platform_id}` | logs click, redirects to ad link |

Both tracking routes dispatch an `EffyAds\Laravel\Jobs\RecordTrackingEvent`
queued job that POSTs to `{EFFYADS_PLATFORM_URL}/api/tracking` — set
`EFFYADS_QUEUE_CONNECTION=sync` if you don't run a queue worker.

## Differences from the WordPress plugin

- **Targeting**: WordPress targets by taxonomy/post-type/page. A Laravel app
  has no universal equivalent, so targeting uses app-defined **zones**
  (`config('effyads.zones')`) instead.
- **Response shape**: uses `id` (local row id) instead of WordPress's
  `wordpress_id` — the platform-side integration needs a small adapter per
  CMS type rather than assuming identical field names.
- **CORS**: not handled by this package. Laravel ships its own CORS
  middleware — add `effyads/v1/*` to `paths` in `config/cors.php` if the
  platform calls these endpoints directly from a browser context.
