# EffyAds for Laravel

Shows advertisements managed in [EffyAds](https://app.effyads.com) on a
Laravel site. The platform pushes advertisements to the site, the package
renders them with a Blade component and reports views and clicks back.

Supports Laravel 10, 11, 12 and 13 (PHP 8.1+, Laravel 13 needs PHP 8.3+).

## Installation

### 1. Install the package

```bash
composer require effyads/laravel
```

### 2. Run the installer

```bash
php artisan effyads:install
```

This publishes `config/effyads.php`, generates an API key, prints the `.env`
lines and asks whether to run the migrations. Options:

| Option | Effect |
|---|---|
| `--write-env` | Adds the lines to `.env` after confirmation. Existing values are never overwritten. |
| `--show-key` | Shows the full key when one is already configured (it is masked by default). |

### 3. Run the migrations

```bash
php artisan migrate
```

Skip this if you let the installer run them.

### 4. Check your `.env`

```dotenv
EFFYADS_API_KEY=...            # printed by effyads:install
EFFYADS_PLATFORM_URL=https://app.effyads.com
EFFYADS_QUEUE_CONNECTION=sync  # optional, sync is the default
```

Copy `EFFYADS_PLATFORM_URL` from the Laravel install page in the dashboard.
It must be reachable from your server without logging in: a host behind
password or Vercel deployment protection answers every tracking call with
401, and no views or clicks are recorded. Failed tracking calls are logged
as `[EffyAds] tracking event failed with status ...`.

`EFFYADS_QUEUE_CONNECTION` decides how views and clicks reach EffyAds:

- `sync` (default): sent right after the response has been delivered to the
  visitor. No queue worker needed, and the pixel and click redirect stay fast.
- A queue connection such as `redis` or `database`: only use this when a
  worker runs for that connection, otherwise events stay in the queue unsent.
  Failed sends are retried 3 times.

### 5. Place an advertisement

Define zones in `config/effyads.php`:

```php
'zones' => [
    'homepage-hero' => 'Homepage hero banner',
    'blog-sidebar'  => 'Blog sidebar',
],
```

Then render one in any view:

```blade
<x-effyads::ad zone="homepage-hero" />
<x-effyads::ad zone="blog-sidebar" class="my-6" :width="300" :height="250" />
```

- Shows advertisements with status `active`, or `scheduled` once the start
  date has passed, within their start and end date.
- When several advertisements match a zone, one is picked at random on every
  render, weighted by `targeting.weight` (default 1, `0` = never). Set
  `'rotation' => 'first'` in the config to always show the oldest match.
- Extra attributes such as `class` are added to the wrapper `<div>`. The
  image gets `loading="lazy"`, and `width`/`height` from the component or
  from the advertisement's `media_width`/`media_height`.
- All zones on a page share one database query.
- With `APP_DEBUG=true`, a zone that is not in `config('effyads.zones')` logs
  a warning.

#### Ad formats

Give a zone a standard ad size and it only shows advertisements made for that
size (the format is chosen per advertisement in the dashboard):

```blade
<x-effyads::ad zone="blog-sidebar" format="medium-rectangle" />
<x-effyads::ad zone="homepage-hero" format="leaderboard" />
```

| Format | Size |
| --- | --- |
| `leaderboard` | 728×90 |
| `billboard` | 970×250 |
| `medium-rectangle` | 300×250 |
| `large-rectangle` | 336×280 |
| `small-rectangle` | 180×150 |
| `square` | 250×250 |
| `wide-skyscraper` | 160×600 |
| `half-page` | 300×600 |
| `mobile-banner` | 320×50 |
| `large-mobile-banner` | 320×100 |

- A zone without `format` shows any advertisement, as before.
- Advertisements set to "Any size" only appear in zones without `format`.
- The wrapper gets `effyads-format-{format}` and the image the format's
  `width`/`height`, so you can style each size.

### 6. Connect the site in the dashboard

In the EffyAds dashboard, use **Connect a website** and enter a name, the
site's URL and the API key from step 2.

The site must be **publicly reachable**: EffyAds calls it to push
advertisements, so `localhost` or a `.test` domain does not work. To test
locally, share the site with a tunnel and connect that URL instead:

```bash
herd share          # Laravel Herd
expose share http://myapp.test
ngrok http 80
```

## Multiple sites in one Laravel app

By default the package serves one site with one API key. A multi-tenant app
(for example `groningen.example.nl` and `assen.example.nl`, each connected as
its own website in the dashboard) needs every site to have its own
advertisements and its own key. Otherwise all sites show the same ads, and a
`sync/bulk` from one site deletes the ads of the others.

Register two resolvers, usually in `AppServiceProvider::boot()`:

```php
use EffyAds\Laravel\EffyAds;
use Illuminate\Http\Request;

// Which site does this request belong to? null = no site.
EffyAds::resolveSiteUsing(fn (Request $request): ?string => /* ... */);

// Which API key must the platform send for this site?
EffyAds::resolveApiKeyUsing(fn (?string $site): ?string => /* ... */);
```

Once a site resolver is set, every advertisement is stored with its site, and
the API, the Blade component and the tracking routes only see that site's
advertisements. `sync/bulk` only deletes within the site. `sync/status`
returns `site` and `site_url`, so the dashboard can check that it talks to
the right site. Without resolvers nothing changes.

Generate a key per site and store it where your key resolver reads it:

```bash
php artisan effyads:install --site=groningen   # only prints a new key
```

### Example: stancl/tenancy

The EffyAds routes are registered by the package, outside your tenant route
file, so add the tenancy middleware to them in `config/effyads.php`:

```php
'route_middleware' => [
    \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
    \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
],
```

Then resolve the site and key from the current tenant. This example keeps the
key in the tenant's `data` column as `effyads_api_key`:

```php
use App\Models\Tenant;
use EffyAds\Laravel\EffyAds;

public function boot(): void
{
    EffyAds::resolveSiteUsing(fn () => tenant()?->getTenantKey());

    EffyAds::resolveApiKeyUsing(fn (?string $site) => $site
        ? Tenant::find($site)?->effyads_api_key
        : null);
}
```

Store a key for each tenant, for example in `php artisan tinker`:

```php
Tenant::find('groningen')->update(['effyads_api_key' => '<key from effyads:install --site=groningen>']);
```

Connect each tenant domain as a separate website in the EffyAds dashboard,
with that tenant's key.

With this middleware the EffyAds routes, including the public tracking pixel
and click redirect, only work on tenant domains. That is intended: on the
central domain there is no site, so there are no advertisements to track.

## API surface

All routes below require the `X-EffyAds-API-Key` header, compared with
`hash_equals` against the configured key.

| Method | Path | Purpose |
|---|---|---|
| GET/POST | `/effyads/v1/advertisements` | list / create (upsert by `platform_id`) |
| GET/PUT/DELETE | `/effyads/v1/advertisements/{platform_id}` | read / update / delete one |
| GET | `/effyads/v1/sync/status` | health check |
| POST | `/effyads/v1/sync/bulk` | reconcile against a list of platform ids |
| GET | `/effyads/v1/targeting/options` | available zones |
| POST | `/effyads/v1/media/upload` | base64 image upload to the configured filesystem disk |

Public routes (hit directly by visitors' browsers):

| Method | Path | Purpose |
|---|---|---|
| GET | `/effyads/track/view/{platform_id}` | 1x1 tracking pixel |
| GET | `/effyads/track/click/{platform_id}` | records the click, redirects to the ad's link |

Both tracking routes dispatch `EffyAds\Laravel\Jobs\RecordTrackingEvent`,
which POSTs to `{EFFYADS_PLATFORM_URL}/api/tracking`.

**Testing tip:** with the default `sync` connection the event is sent after
the response, when the app terminates. In a feature test that makes several
requests on the same app instance, these after-response callbacks can run
more than once, so `Http::assertSentCount()` may count an event twice. This
does not happen in production, where every request gets a fresh app. In your
own tests, use `Bus::fake()` and `Bus::assertDispatchedAfterResponse()`, or set
`effyads.queue_connection` to another connection and use `Queue::fake()`.

## Security

| Setting (`config/effyads.php`) | Default | Purpose |
|---|---|---|
| `api_rate_limit` | `120` | Requests per minute per IP on `effyads/v1` (`throttle:effyads-api`). |
| `allow_query_api_key` | `false` | Temporarily accept `?api_key=` (logs a deprecation warning). Keys in URLs end up in logs, so use the header. |
| `media_max_kb` | `5120` | Maximum size of an uploaded image. |
| `tracking.rate_limit` | `60` | Requests per minute per IP on the tracking routes (`throttle:effyads-tracking`). |
| `tracking.dedupe_seconds` | `view: 1800, click: 60` | The same visitor (IP + user agent) is counted once per ad per window. `0` disables. |
| `tracking.bot_user_agents` | regex | Matching (and empty) user agents are not counted. `null` counts everything. |

- **Media upload**: the file type is detected from the bytes, never from the
  client's `filename` or `mime_type`. Only real JPEG, PNG, GIF and WebP
  images are stored, under a random name with the matching extension.
- **Tracking**: views and clicks are only recorded for advertisements that
  exist and are active (for the current site). An unknown ad still gets the
  pixel, but nothing is recorded.
- **API key**: any failure returns the same `403 Unauthorized`, without a
  reason.

## Differences from the WordPress plugin

- **Targeting**: WordPress targets by taxonomy, post type and page. A Laravel
  app has no universal equivalent, so targeting uses app-defined **zones**
  (`config('effyads.zones')`).
- **Response shape**: responses use `id` (the local row id) instead of
  WordPress's `wordpress_id`.
- **CORS**: not handled by this package. Add `effyads/v1/*` to `paths` in
  `config/cors.php` if the platform ever calls these endpoints from a browser.

## Distribution (for EffyAds maintainers)

The package is developed in `laravel-package/` of the private EffyAds
monorepo. The `Laravel package split` workflow mirrors that folder to the
public, read-only repo [`Wites-NL/effyads-laravel`](https://github.com/Wites-NL/effyads-laravel),
which [Packagist](https://packagist.org/packages/effyads/laravel) reads:

- every push to `master` updates the split repo's `main` branch (`1.x-dev`);
- a monorepo tag `laravel-vX.Y.Z` becomes tag `vX.Y.Z` in the split repo,
  and Packagist publishes it as version `X.Y.Z`.

To release: update `CHANGELOG.md`, merge `staging` into `master`, then
`git tag laravel-v1.0.0 && git push origin laravel-v1.0.0`.

The workflow needs the monorepo secret `EFFYADS_LARAVEL_SPLIT_TOKEN`: a
fine-grained token with *Contents: read and write* on the split repo.

Changes made directly in the split repo are overwritten by the next split.

## Development

```bash
composer install
vendor/bin/phpunit
```
