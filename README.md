# EffyAds for Laravel

Shows advertisements managed in [EffyAds](https://app.effyads.com) on a
Laravel site. The platform pushes advertisements to the site, the package
renders them with a Blade component and reports views and clicks back.

Supports Laravel 10, 11, 12 and 13 (PHP 8.1+, Laravel 13 needs PHP 8.3+).

## Installation

The package is distributed privately through Private Packagist. You get a
repository URL and an access token from EffyAds.

### 1. Add the EffyAds package repository

```bash
composer config repositories.effyads composer https://repo.packagist.com/effyads/
composer config --auth http-basic.repo.packagist.com token YOUR_TOKEN
```

The second command stores the token in `auth.json` next to `composer.json`.
Do not commit that file: add `auth.json` to `.gitignore`, or use
`composer config --global --auth ...` to store it for your user instead. On a
server or in CI, set the `COMPOSER_AUTH` environment variable:

```bash
COMPOSER_AUTH='{"http-basic":{"repo.packagist.com":{"username":"token","password":"YOUR_TOKEN"}}}'
```

### 2. Install the package

```bash
composer require effyads/laravel
```

### 3. Run the installer

```bash
php artisan effyads:install
```

This publishes `config/effyads.php`, generates an API key, prints the `.env`
lines and asks whether to run the migrations. Options:

| Option | Effect |
|---|---|
| `--write-env` | Adds the lines to `.env` after confirmation. Existing values are never overwritten. |
| `--show-key` | Shows the full key when one is already configured (it is masked by default). |

### 4. Run the migrations

```bash
php artisan migrate
```

Skip this if you let the installer run them.

### 5. Check your `.env`

```dotenv
EFFYADS_API_KEY=...            # printed by effyads:install
EFFYADS_PLATFORM_URL=https://app.effyads.com
EFFYADS_QUEUE_CONNECTION=sync  # optional, sync is the default
```

`EFFYADS_QUEUE_CONNECTION` decides how views and clicks reach EffyAds:

- `sync` (default): sent right after the response has been delivered to the
  visitor. No queue worker needed, and the pixel and click redirect stay fast.
- A queue connection such as `redis` or `database`: only use this when a
  worker runs for that connection, otherwise events stay in the queue unsent.
  Failed sends are retried 3 times.

### 6. Place an advertisement

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
```

### 7. Connect the site in the dashboard

In the EffyAds dashboard, use **Connect a website** and enter a name, the
site's URL and the API key from step 3.

The site must be **publicly reachable**: EffyAds calls it to push
advertisements, so `localhost` or a `.test` domain does not work. To test
locally, share the site with a tunnel and connect that URL instead:

```bash
herd share          # Laravel Herd
expose share http://myapp.test
ngrok http 80
```

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

## Differences from the WordPress plugin

- **Targeting**: WordPress targets by taxonomy, post type and page. A Laravel
  app has no universal equivalent, so targeting uses app-defined **zones**
  (`config('effyads.zones')`).
- **Response shape**: responses use `id` (the local row id) instead of
  WordPress's `wordpress_id`.
- **CORS**: not handled by this package. Add `effyads/v1/*` to `paths` in
  `config/cors.php` if the platform ever calls these endpoints from a browser.

## Distribution (for EffyAds maintainers)

The package lives in `laravel-package/` of the EffyAds monorepo. The
`Laravel package split` workflow mirrors that folder to the private repo
`Wites-NL/effyads-laravel`:

- every push to `main` updates the split repo's `main` branch (`1.x-dev`);
- a monorepo tag `laravel-vX.Y.Z` becomes tag `vX.Y.Z` in the split repo.

Private Packagist reads the split repo and serves the tagged versions to
customers. Each customer gets their own token, so access can be revoked per
customer.

To release: update `CHANGELOG.md`, merge to `main`, then
`git tag laravel-v1.0.0 && git push origin laravel-v1.0.0`.

The workflow needs the repository secret `EFFYADS_LARAVEL_SPLIT_TOKEN`: a
fine-grained token with *Contents: read and write* on the split repo.

**Alternative: Satis.** Instead of Private Packagist you can host a static
Satis repository (for example behind HTTP basic auth) that points at the
split repo. Customers then use that URL in step 1 with their own credentials.
This costs nothing, but you manage hosting, rebuilds and per-customer access
yourself.

## Development

```bash
composer install
vendor/bin/phpunit
```
