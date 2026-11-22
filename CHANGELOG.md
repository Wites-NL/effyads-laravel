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

### Changed
- All required `illuminate/*` components are now listed in `composer.json`.
- `sync/status` reports the installed package version instead of a hardcoded `1.0.0`.
- Default platform URL is `https://app.effyads.com` everywhere.
- `EFFYADS_QUEUE_CONNECTION` defaults to `sync` (no longer the app's
  `QUEUE_CONNECTION`); sync events are sent after the response.
- An already configured API key is masked in the installer output.
