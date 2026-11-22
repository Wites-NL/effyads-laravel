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

### Changed
- All required `illuminate/*` components are now listed in `composer.json`.
- `sync/status` reports the installed package version instead of a hardcoded `1.0.0`.
- Default platform URL is `https://app.effyads.com` everywhere.
- `EFFYADS_QUEUE_CONNECTION` defaults to `sync` (no longer the app's
  `QUEUE_CONNECTION`); sync events are sent after the response.
- An already configured API key is masked in the installer output.
