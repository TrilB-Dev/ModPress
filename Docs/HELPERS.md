# Shared Helpers

Reusable helpers live in `src/Includes/Functions/Helpers` and use the namespace `ModPress\Includes\Functions\Helpers`. Prefer these helpers over duplicating WordPress sanitization, authorization, URL, query, form, menu, or response logic.

The helpers are shared infrastructure, but feature-specific rules remain in the owning plugin. Sanitize input before validation or persistence, check capabilities before protected operations, and escape helper output for its final context.

## SanitizationHelper

`SanitizationHelper` provides defensive wrappers around common WordPress sanitizers:

- `text($value, $fallback = '')` sanitizes scalar plain text.
- `textarea($value, $fallback = '')` sanitizes scalar textarea content.
- `key($value, $fallback = '')` returns a sanitized WordPress key or fallback.
- `slug($value, $fallback = '')` returns a sanitized title slug or fallback.
- `integer($value, $fallback = 0)` returns a non-negative integer.
- `integer_range($value, $minimum, $maximum, $fallback)` returns an integer bounded by an inclusive range.
- `one_of($value, $allowed, $fallback)` returns the value only when it is strictly in the allowed list.
- `terms($terms)` accepts a comma-separated string or array and returns unique, non-empty sanitized term names.

```php
$title = SanitizationHelper::text($payload['title'] ?? '');
$status = SanitizationHelper::one_of($payload['status'] ?? '', [ 'draft', 'publish' ], 'draft');
$tags = SanitizationHelper::terms($payload['tags'] ?? []);
```

## RequestHelper

`RequestHelper` reads request-like arrays, unslashes values, and delegates sanitization to `SanitizationHelper`:

- `get($key, $fallback = null)`, `get_text($key, $fallback = '')`, `get_key($key, $fallback = '')`, and `get_integer($key, $fallback = 0)` read from `$_GET`.
- `value($source, $key, $fallback = null)` reads an unmodified value from a supplied array.
- `text($source, $key, $fallback = '')`, `key($source, $key, $fallback = '')`, `slug($source, $key, $fallback = '')`, and `integer($source, $key, $fallback = 0)` read and sanitize supplied values.
- `integer_range($value, $minimum, $maximum, $fallback)` sanitizes a bounded scalar value.
- `array($source, $key, $fallback = [])` returns an unslashed array or fallback.
- `boolean($source, $key, $fallback = false)` parses boolean-like request values.

```php
$page = RequestHelper::get_key('page', 'mspress');
$per_page = RequestHelper::integer_range($request->get_param('per_page'), 1, 100, 20);
$enabled = RequestHelper::boolean([ 'enabled' => $request->get_param('enabled') ], 'enabled');
```

## PermissionHelper

Centralize capability and authentication checks with:

- `can($capability, $object_id = 0)` checks a capability, optionally for an object.
- `can_any($capabilities, $object_id = 0)` succeeds when any supplied capability is granted.
- `can_all($capabilities, $object_id = 0)` succeeds only when every supplied capability is granted.
- `logged_in()` checks whether a user is authenticated.
- `user_id()` returns the current user ID or `0`.

Check permissions before reading protected data or changing state.

## QueryHelper

- `current()` returns the global `WP_Query`, or `null` when it is unavailable.
- `posts($args = [])` creates a reusable `WP_Query` from the supplied arguments.

## PostHelper

`PostHelper` provides null-safe post identity and permalink operations:

- `current()`, `current_id()`, and `current_type()` inspect the current query post.
- `get($post = null)` resolves a `WP_Post`, post ID, or current post to a `WP_Post` or `null`.
- `id($post = null)` returns a resolved post ID or `0`.
- `is_type($post, $post_type)` checks a post type.
- `permalink($post = null)` returns a post permalink or an empty string.

The template may contain plugin-specific post predicates in a derived implementation. Keep those predicates in the owning plugin when post types differ between projects.

## TaxonomyHelper

`TaxonomyHelper` centralizes safe taxonomy lookups and term normalization:

- `terms($taxonomy, $post_id = 0, $limit = 0, $search = '')` returns matching terms, optionally limited to a post, count, or search value.
- `ids($terms)` extracts unique positive term IDs from term objects, IDs, names, arrays, or normalized term input.
- `resolve_ids($terms, $taxonomy, $create = false)` resolves IDs and names, optionally creating missing terms.
- `names($terms)` extracts unique, sanitized term names.

Use `resolve_ids()` with `$create = true` only when the current operation is authorized to create terms.

## ContentHelper

- `plain_text($content)` strips shortcodes and HTML from scalar content.
- `word_count($content)` returns a safe word count.
- `reading_time($content, $words_per_minute = 200)` returns reading time in minutes, with a minimum of one minute.
- `excerpt($content, $words = 30)` creates a plain-text excerpt.
- `heading_id($heading, $fallback = 'section')` creates a sanitized slug suitable for a heading ID.

## UrlHelper

Build common administrative URLs consistently:

- `admin_page($page, $args = [])` builds an `admin.php` page URL.
- `admin_action($action, $args = [])` builds an `admin-post.php` action URL.
- `nonce($url, $action)` adds a WordPress nonce to a URL.
- `admin_action_nonce($action, $nonce_action, $args = [])` builds a nonce-protected admin action URL.

Always escape returned URLs at output time with `esc_url()`.

## PermalinkHelper

`PermalinkHelper` is optional. Use it only when the plugin owns tokenized permalink patterns. Replace any content-specific tokens, post types, taxonomies, settings keys, metadata keys, rewrite variables, and filters with the current plugin's equivalents.

- `token_definitions()` returns available translated token descriptions.
- `default_pattern()` returns the default pattern.
- `sanitize_pattern($pattern)` keeps supported tokens and sanitized literal segments.
- `pattern_for_object($object_id = 0)` resolves an object-specific override or configured default. Rename this method and its parameter to match the plugin-owned content model.
- `page_url($page)` returns a full URL for a supported post object.
- `expand($pattern, $page, $parent = null)` expands a pattern into a relative path.
- `rewrite_rule()` registers the feature's rewrite handling.
- `resolve_request($vars)` resolves a requested path.
- `filter_page_permalink($link, $post)` replaces the normal permalink for supported posts.

Sanitize patterns before persisting custom values. Keep override metadata and rewrite variables namespaced to the owning plugin.

## AjaxHelper

Use `AjaxHelper` for common AJAX checks and JSON responses:

- `is_ajax_request()` checks the `DOING_AJAX` flag.
- `request_method()` returns the current uppercase HTTP method.
- `is_method($method)` checks the current HTTP method.
- `has_valid_nonce($action, $field = 'nonce', $request = null)` verifies a nonce from the supplied request or `$_POST`.
- `can($capability, $object_id = 0)` delegates to `PermissionHelper::can()`.
- `authorized($action, $capability = '', $object_id = 0, $field = 'nonce')` combines nonce and optional capability checks.
- `success($data = null, $status_code = 200)` sends a successful JSON response.
- `error($data = null, $status_code = 400)` sends an error JSON response.
- `unauthorized($message = 'You are not authorized to perform this action.', $status_code = 403)` sends a standard authorization error.

These response methods end the request through WordPress JSON response handling.

## AlertHelper

`AlertHelper` renders Bootstrap admin notices:

- `admin_error($message)`, `admin_success($message)`, `admin_warning($message)`, and `admin_info($message)` render a notice immediately.
- `render_admin_notice($message, $type = 'info')` renders a notice for `info`, `success`, `warning`, or `error`.
- `get_admin_notice($message, $type = 'info')` returns the rendered notice markup.

Use `get_admin_notice()` when markup must be returned in an AJAX response or inserted later.

## LoggerHelper

- `write_log($log, $settings = [])` writes strings, arrays, or objects to the PHP debug log when `WP_DEBUG` or the plugin's debug logging setting is enabled.
- `write_console($log, $settings = [])` emits a browser console message when debug or console logging is enabled.

Never log credentials, tokens, secrets, or sensitive request data. Keep the logging setting names owned by the current plugin.

## FormFieldHelper

For the complete API, option contracts, composition patterns, accessibility guidance, and examples, see [the FormFieldHelper reference](Helpers/FORMFIELDHELPER.md).

`FormFieldHelper` renders escaped Bootstrap-compatible form controls and validation markup:

- `input($name, $value = '', $options = [])` renders a configurable input type.
- `text_input($name, $value = '', $attributes = [])` renders a text input.
- `textarea($name, $value = '', $options = [])` renders a textarea.
- `select($name, $options = [], $selected = [], $attributes = [])` renders a select, including option groups.
- `checkbox($name, $value = '1', $label = '', $options = [])`, `radio($name, $value, $label = '', $options = [])`, and `switch($name, $value = '1', $label = '', $options = [])` render check controls.
- `check($type, $name, $value, $label = '', $options = [])` is the shared checkbox/radio renderer.
- `button_group($name, $buttons = [], $selected = [], $options = [])` renders Bootstrap button, checkbox, or radio groups.
- `button($label, $options = [])` renders a button or link styled as a Bootstrap button.
- `dropdown_button($label, $items = [], $options = [])` renders a Bootstrap dropdown button.
- `button_toolbar($groups = [], $options = [])` wraps rendered groups in a Bootstrap toolbar.
- `range($name, $value = 0, $options = [])` renders a range input, optionally with an output element.
- `datalist($name, $values, $value = '', $options = [])` renders an input with a datalist.
- `bootstrap_select($name, $options = [])` renders a Bootstrap Select single-select.
- `bootstrap_multiselect($name, $options = [])` renders a Bootstrap Select multi-select and appends `[]` to the name when needed.
- `label($for, $text, $options = [])` renders a label with optional description and tooltip.
- `form_text($text, $options = [])` renders Bootstrap help text.
- `feedback($options = [])` renders valid or invalid validation feedback.
- `input_group($content, $options = [])` wraps supplied markup in an input group.
- `floating($control, $label, $options = [])` wraps a rendered control in a Bootstrap floating form layout.
- `form_open($action = '', $method = 'post', $options = [])` opens an escaped form element.
- `form_close()` returns the closing form tag.
- `attributes_to_string($attributes)` converts an attribute array to escaped HTML attributes.

Select options may be keyed values, value/label definitions, or optgroup definitions. Form options support escaped attributes, validation data, Bootstrap classes, and presentation options such as `live_search`, `placeholder`, `width`, and `actions_box` for Bootstrap Select.

```php
echo FormFieldHelper::text_input(
	'plugin_title',
	$title,
	[ 'class' => 'form-control-lg', 'required' => true ]
);
```

Prefer these controls over hand-built form HTML so attributes, labels, and validation messages are escaped consistently.

## ShortcodeHelper

Define and register plugin-owned shortcodes through the shared registry:

```php
use ModPress\Includes\Functions\Helpers\ShortcodeHelper;

$definition = ShortcodeHelper::define(
	'my_status',
	static fn( array $attributes, ?string $content, string $tag ): string => esc_html( $attributes['label'] ),
	[ 'label' => 'Ready' ],
	[ 'description' => 'Displays a status label.', 'category' => 'my-plugin' ]
);

ShortcodeHelper::register( $definition );
```

- `define($tag, $callback, $attributes = [], $metadata = [])` creates a shortcode definition.
- `register($definition, $replace = false)` registers one definition.
- `register_many($definitions, $replace = false)` registers multiple definitions and returns registered tags.

Definitions support `tag`, `callback`, `attributes`, `description`, `category`, `enclosing`, and `tinymce`. Shortcode callbacks must return their output.

## LoaderHelper

`LoaderHelper::register_component($component, $hooks)` registers multiple actions or filters for one component. Each definition contains `type`, `hook`, and `callback`, with optional `priority` and `accepted_args` values.

```php
$this->loader->register_component( $this, [
	[ 'type' => 'action', 'hook' => 'init', 'callback' => 'register_content' ],
	[ 'type' => 'filter', 'hook' => 'the_content', 'callback' => 'filter_content' ],
] );
```

Use `action` or `filter` as the type. Missing hook or callback values, and unsupported types, throw `InvalidArgumentException`.

## Admin Menu Helpers

`PAMHelper`, `PASMHelper`, and `PSAMHelper` are placeholders intended to be renamed with the initials of the plugin. Rename the file, class, namespace references, filter constants, and all call sites together.

### PAMHelper

The plugin-initials admin menu helper defines and filters standard WordPress admin menu entries:

- `define($name, $slug, $icon = 'dashicons-admin-generic', $parent = '')` returns a menu definition.
- `filter($menus)` applies the plugin-owned admin menu filter and normalizes the result.
- `get_admin_menu_page_url($slug)` returns an admin page URL.
- `FILTER` contains the plugin-owned admin menu filter name and must use the plugin's slug.

### PASMHelper

The plugin-initials admin sidebar menu helper defines and filters sidebar menu entries:

- `define($name, $slug, $icon, $parent = '', $capability = '')` returns a sidebar menu definition.
- `filter($menus)` applies the plugin-owned sidebar menu filter and normalizes the result.
- `get_url($slug)` returns a sanitized admin sidebar menu URL.
- `FILTER` contains the plugin-owned sidebar menu filter name and must use the plugin's slug.

### PSAMHelper

The plugin-initials sidebar admin menu URL helper provides:

- `get_admin_sidebar_menu_page_url($slug)` returns an admin sidebar menu page URL.

Use the menu helpers only for menus owned by the current plugin. Keep menu slugs, capabilities, filter names, and icon conventions plugin-specific.

## Related Shared Services

`Response` and `Validators` provide consistent REST envelopes and payload validation. `Settings` and `SettingsManager` cover shared settings persistence. Use `PermissionHelper` for authorization rather than duplicating capability checks in those services or feature classes.
