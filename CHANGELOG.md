# Changelog

All notable changes to `effyads/laravel` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added
- Laravel 13 support.
- Test suite (Orchestra Testbench) and a CI matrix for PHP 8.2–8.4 × Laravel 10–13.
- `effyads:install` publishes `config/effyads.php`, prints every `.env` line
  (including `EFFYADS_QUEUE_CONNECTION`), asks to run the migrations, and
  supports `--write-env` and `--show-key`.
- Tracking jobs retry 3 times with backoff when run on a real queue.
- Multi-site support: `EffyAds::resolveSiteUsing()` and
  `EffyAds::resolveApiKeyUsing()` scope advertisements, API calls, the Blade
  component, tracking and `sync/bulk` to the current site. New migration adds
  a `site` column with a unique index on `(site, platform_id)`.
- `effyads:install --site=...` prints a new key for one site.
- `effyads.route_middleware` config option to add middleware (e.g. tenancy
  identification) to all EffyAds routes.
- `sync/status` returns `site` and `site_url`; `sync/bulk` returns `site`.

- Rate limiters `effyads-api` (120/min per IP) and `effyads-tracking`
  (60/min per IP), both configurable.
- Tracking deduplicates per visitor, ad and event type
  (`effyads.tracking.dedupe_seconds`) and skips bots
  (`effyads.tracking.bot_user_agents`).
- `effyads.media_max_kb` (default 5120).
- Random rotation between matching ads, weighted by `targeting.weight`
  (`effyads.rotation`: `random` (default) or `first`).
- `<x-effyads::ad>` accepts `class` (and other attributes), `width` and
  `height`; images get `loading="lazy"`. New optional `media_width` and
  `media_height` fields on advertisements (new migration).
- Warning in the log (debug mode) for zones missing from `effyads.zones`.

### Changed
- All required `illuminate/*` components and `guzzlehttp/guzzle` are now listed in `composer.json`.
- `sync/status` reports the installed package version instead of a hardcoded `1.0.0`.
- Default platform URL is `https://app.effyads.com` everywhere.
- `EFFYADS_QUEUE_CONNECTION` defaults to `sync` (no longer the app's
  `QUEUE_CONNECTION`); sync events are sent after the response.
- An already configured API key is masked in the installer output.
- Views and clicks are only recorded for existing, active advertisements.
- `scheduled` advertisements are shown once their start date has passed.
- The component filters status and dates in SQL and loads active ads once per
  site per request, instead of loading every active ad on every render.
- Media uploads go to `effyads/{site}/` in multi-site installs.
- The tracking job no longer uses `SerializesModels`.

### Removed
- **Breaking:** the `?api_key=` query parameter is no longer accepted by
  default. Send the `X-EffyAds-API-Key` header (the EffyAds platform already
  does). Set `EFFYADS_ALLOW_QUERY_API_KEY=true` to accept it temporarily.

### Security
- Media upload detects the MIME type from the bytes with `finfo`, checks the
  file is a real image with `getimagesizefromstring`, derives the extension
  from the detected type and never uses the client's filename or
  `mime_type`. Previously a `.php` file sent with `mime_type=image/png` was
  stored on the public disk.
- API key failures return a generic `403 Unauthorized` without a reason.
