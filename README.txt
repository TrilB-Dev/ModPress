=== ModPress ===
Contributors: trilbdev
Tags: mod, knowledge base, documentation, modular, rest api
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modular mod platform for WordPress by TrilB.Dev, featuring custom mod post types, grouped settings, REST API endpoints, shared helpers, Bootstrap-powered admin UI, and an extension system for internal and external plugins.

== Description ==
ModPress is a modular mod framework for WordPress, built by TrilB.Dev to provide a structured, scalable foundation for knowledge bases, documentation hubs, and collaborative content systems. It introduces dedicated mod and mod‑page post types, complete with taxonomies and a streamlined admin experience designed for high‑volume content management.

### Under the Hood
ModPress includes a wide array of built‑in modules designed to support every aspect of creating and maintaining a powerful mod inside WordPress. These internal plugins handle core features such as content types, taxonomy registration, REST routing, settings storage, admin UI components, and shared helper libraries. Each module is purpose‑built, self‑contained, and designed to work together seamlessly, giving you a stable foundation for building documentation systems, knowledge bases, and content‑heavy mod platforms.

### Built for Expansion
ModPress is engineered for extensibility at its core. It automatically detects WordPress plugins that are built to expand ModPress, allowing developers to register new features, admin tools, content structures, or REST endpoints with minimal boilerplate. With a wide array of helpers, utilities, and APIs—covering sanitization, permissions, queries, content formatting, URLs, forms, and more—developers can extend ModPress cleanly and consistently. Whether you’re building internal modules or standalone WordPress plugins, the system gives you everything you need to integrate with ease.

### Developer Architecture
Developers gain access to a clean, consistent architecture that encourages modular design and predictable behavior. ModPress provides database‑backed settings grouped by feature, shared helper libraries, Bootstrap‑based admin assets compiled via Webpack and Sass, and a fully documented REST API under /wp-json/modpress/v1. Every part of the system is built to reduce friction, improve reliability, and make custom development faster—whether you’re enhancing ModPress itself or embedding mod functionality into a larger WordPress ecosystem.

Whether you're building a documentation platform, a knowledge mod, or a custom content‑driven application, ModPress provides the structure, tools, and extensibility you need.

== Features ==
Wiki and mod‑page post types

Custom taxonomies for structured content

REST API endpoints under /wp-json/modpress/v1

Database‑backed settings grouped by feature

Shared sanitization, request, permission, query, content, URL, and form helpers

Reusable shortcode definitions and registration for ModPress extensions

Bootstrap‑based admin UI compiled with Webpack and Sass

Font Awesome integration

Internal ModPress plugin discovery

Widgets and Block
 for Elementor & Gutenberg Editors
Integration with separately installed WordPress plugins

== Installation ==
Upload the plugin files to /wp-content/plugins/modpress/, or install via the WordPress plugin installer.

Activate the plugin through the Plugins menu.

Access ModPress settings under ModPress → Settings.

Begin creating mod content using the Wiki and Wiki Page post types.

== Frequently Asked Questions ==
Does ModPress work with custom themes?
Yes. ModPress is theme‑agnostic and works with any properly coded WordPress theme.

Can I extend ModPress with my own plugin?
Absolutely. ModPress automatically detects compatible extension plugins and provides helper libraries and APIs to make development easy.

How do I add a shortcode to a ModPress extension?
Implement ShortcodeProviderInterface and return a list created with ShortcodeHelper::define() from get_shortcodes(). The callback receives attributes, enclosed content, and the shortcode tag, and must return its output.

Example:

	use ModPress\\Includes\\Functions\\Helpers\\ShortcodeHelper;
	use ModPress\\Includes\\Plugins\\ShortcodeProviderInterface;

	public function get_shortcodes(): array {
		return [
			ShortcodeHelper::define(
				'my_modpress_box',
				[ $this, 'render_box' ],
				[ 'title' => 'ModPress' ],
				[ 'description' => 'Render a ModPress content box.', 'enclosing' => true ]
			),
		];
	}

	public function render_box( array $atts, ?string $content, string $tag ): string {
		return '<div class="my-modpress-box"><strong>' . esc_html( $atts['title'] ) . '</strong>' . do_shortcode( (string) $content ) . '</div>';
	}

Is there a REST API?
Yes. All core mod functionality is exposed under /wp-json/modpress/v1.

Does ModPress include its own styling?
The admin UI uses Bootstrap and Font Awesome. Frontend styling is intentionally minimal so themes can control presentation.

== Screenshots ==
ModPress admin dashboard

Wiki post type editor

Wiki‑page hierarchy view

Settings grouped by feature

== Changelog ==
1.0.0
Initial release

Wiki and mod‑page post types

REST API endpoints

Modular internal plugin system

Shared helper libraries

Bootstrap + Sass admin UI

Extension detection system

== Upgrade Notice ==
1.0.0
Initial release of ModPress. No upgrade actions required.