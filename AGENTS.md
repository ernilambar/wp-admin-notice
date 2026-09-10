# AGENTS.md

## Overview

`ernilambar/wp-admin-notice` is a WordPress helper library that shows a dismissible "please review" admin notice after a plugin or theme has been active for a number of days. It is plain PHP (no build tooling, no JavaScript), targeting WordPress 6.0+ and PHP 7.2+, and is consumed via Composer as `Nilambar\AdminNotice\Notice`.

## Setup

```bash
composer install
```

No environment file, database, or WordPress install is required. PHP 7.2.24+ and Composer 2 are assumed (Composer pins `platform.php` to 7.2.24; CI runs PHP 7.2, 7.4, 8.0, and 8.4).

## Commands

```bash
# Build (there are no compiled assets; this produces the optimized production autoloader)
composer install --no-dev --optimize-autoloader

# Test (installs the isolated PHPUnit toolchain in build-phpunit/, then runs it)
composer run test

# Lint (PHP syntax + WordPress Coding Standards)
composer run lint

# Format (auto-fixes violations with phpcbf)
composer run format

# Typecheck (no type checker is configured; parallel-lint is the static check)
composer run lint-php
```

## Conventions

- **PSR-4, one class per file.** Namespace `Nilambar\AdminNotice\` maps to `src/`; every source file starts with `defined( 'WPINC' ) || die;`. Put new classes in `src/`, never in `build-phpunit/` or `vendor/` (both are generated and ignored).
- **No hardcoded user-facing strings.** The library owns no text domain; every label/message is passed in by the consumer. Do not re-add `__( ... )` calls to default strings.
- **Tests stub WordPress, not install it.** `tests/bootstrap.php` defines every WordPress function the library calls. Any new WP function used in `src/` must get a stub there, and stub state must be reset in `wpan_reset_state()`.
- **Escape at output, deliberately.** Use `esc_html()` / `esc_attr()` / `esc_url()` for all output. The notice `message` is intentionally echoed unescaped and carries a `phpcs:ignore` — leave that one alone.
- **Namespace option and meta keys through `key()`.** Option keys use `{slug}_wpan_{key}`; the dismissed user-meta key is further suffixed with the blog ID so dismissal is per-site on multisite.

Follow WordPress Coding Standards: tabs for indentation, short array syntax `[]`, Yoda conditions, snake_case methods and test names. PHPCS excludes `tests/`.

## Quality gate

Run these in order and confirm every one exits `0` before declaring a task complete:

```bash
composer run lint-php
composer run phpcs
composer run test
```

Do not modify `vendor/`, `build-phpunit/vendor/`, or `build-phpunit/composer.json` by hand.
