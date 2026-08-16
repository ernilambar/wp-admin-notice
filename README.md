# WP Admin Notice

![Type: Library](https://img.shields.io/badge/Type-Library-brightgreen.svg)
![CMS: WordPress](https://img.shields.io/badge/CMS-WordPress-blue.svg)
![PHP: Requires PHP 7.2](https://img.shields.io/badge/PHP-Requires%20PHP%207.2-8892BF.svg)
![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)

WordPress helper for showing a dismissible "please review" admin notice after a plugin or theme has been in use for a number of days.

## Installation

```bash
composer require ernilambar/wp-admin-notice
```

## Requirements

- WP 6.0+
- PHP 7.2+

## Usage

Call `Notice::init()` on `admin_init`. `slug` must match the WordPress.org plugin or theme slug, since it builds the review URL.

```php
use Nilambar\AdminNotice\Notice;

add_action( 'admin_init', function () {
	Notice::init(
		[
			'slug' => 'my-plugin',
			'name' => 'My Plugin',
			'days' => 7,
		]
	);
} );
```

## Arguments

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `slug` | string | — | **Required.** WordPress.org slug. Also prefixes option keys and hook names. Missing or empty returns `null`. |
| `name` | string | Title-cased `slug` | Name used in the default message. |
| `type` | string | `plugin` | `plugin` or `theme`; anything else falls back to `plugin`. |
| `days` | int | `7` | Days before showing, and the delay added by "maybe later". |
| `capability` | string | `manage_options` | Capability needed to see and dismiss. |
| `screens` | array | `[]` | Screen IDs to limit to. Empty shows everywhere. |
| `classes` | array | `[]` | Extra CSS classes. `notice notice-info` is always applied. |
| `action_labels` | array | `review`, `later`, `dismiss` | Link labels. Empty string hides that link. |
| `message` | string | Built-in text | Notice body. **Echoed unescaped** — escape it yourself. Empty hides the notice. |

## License

This project is licensed under the [MIT](https://opensource.org/license/MIT).
