# FormFieldHelper

`FormFieldHelper` is the shared HTML renderer for Bootstrap-compatible form controls in ModPress. It lives at `src/Includes/Functions/Helpers/FormFieldHelper.php` and is available through:

```php
use ModPress\Includes\Functions\Helpers\FormFieldHelper;
```

The helper keeps field names, IDs, Bootstrap classes, boolean attributes, selected values, validation state, and user-visible text consistent. It is presentation infrastructure only. The owning feature must still authorize the request, validate submitted values, sanitize data before persistence, and add the appropriate nonce.

TinyMCE is intentionally not part of this core helper. Rich-text editor rendering belongs to the TinyMCE plugin's `TinyMCEHelper`; use that feature helper directly when a TinyMCE editor is required.

## Return and output conventions

Methods return a string containing HTML. Echo the result in a template or compose it with another helper:

```php
echo FormFieldHelper::label( 'project-name', __( 'Project name', 'mspress' ) );
echo FormFieldHelper::text_input( 'project[name]', $name, [ 'id' => 'project-name' ] );
```

The helper escapes values used as HTML text and attributes. It does not make application data safe for database storage or make an operation authorized. Follow the normal WordPress flow:

1. Check the current user's capability and verify a nonce before a protected operation.
2. Read and sanitize submitted values with the appropriate shared sanitization or request helper.
3. Validate business rules and persist the sanitized values.
4. Escape values at output time and use `FormFieldHelper` for the form markup.

## Shared option conventions

Several methods accept an `$options` array. The most common keys are:

| Key | Meaning |
| --- | --- |
| `id` | Explicit HTML ID. Set this when a label needs a stable `for` target or JavaScript needs a stable selector. |
| `class` | Additional classes for the control or wrapper, depending on the method. Built-in Bootstrap classes are retained. |
| `attributes` | Additional HTML attributes. This is useful when an attribute name does not have a dedicated option. |
| `disabled` | Adds the disabled state where supported. A disabled control is not submitted by the browser. |
| `required` | Adds the HTML required attribute. Server-side validation is still required. |
| `placeholder` | Adds placeholder text to compatible controls. It is not a replacement for a label. |
| `autocomplete` | Sets the browser autocomplete hint. Use `new-password` for secrets that should not be prefilled. |
| `validation` | Adds Bootstrap validation classes and feedback. See [Validation](#validation). |

The helper accepts direct option keys such as `id`, `required`, and `data-*`, as well as an `attributes` array. Use one style consistently for a given control. The `attributes` array is especially useful for custom `aria-*`, `data-*`, and HTML attributes:

```php
echo FormFieldHelper::input(
	'query',
	$query,
	[
		'id'         => 'directory-query',
		'type'       => 'search',
		'attributes' => [
			'aria-describedby' => 'directory-query-help',
			'autocomplete'     => 'off',
		],
	]
);
```

Boolean attributes are rendered without a value. `false` and `null` attributes are omitted. Attribute names and values are escaped by `attributes_to_string()`.

## Text controls

### `input()`

```php
FormFieldHelper::input( string $name, $value = '', array $options = [] ): string
```

Renders an `<input>` with the Bootstrap `form-control` class. Set `type` in `$options`; the default is `text`. Common types include `email`, `url`, `number`, `password`, `search`, `date`, `color`, and `file`. Color inputs also receive Bootstrap's `form-control-color` class. File inputs deliberately do not receive a `value` attribute.

```php
echo FormFieldHelper::input(
	'connection[email]',
	$email,
	[
		'id'           => 'connection-email',
		'type'         => 'email',
		'required'     => true,
		'autocomplete' => 'email',
	]
);
```

Use `attributes` for attributes that are not represented by an option. The field name is supplied separately and becomes the `name` attribute; array names such as `settings[enabled]` are valid.

#### `input()` variables

| Variable | Applies to | Description |
| --- | --- | --- |
| `type` | `$options` | Input type. Defaults to `text`. Use the semantic HTML type such as `email`, `url`, `number`, `date`, `search`, `password`, `color`, `file`, or `range`. |
| `class` | `$options` | Additional control classes. `form-control` is added automatically; color inputs also receive `form-control-color`. |
| `attributes` | `$options` | Associative array of additional HTML attributes, including `aria-*`, `data-*`, and `autocomplete`. |
| `id` | `$options` or `attributes` | Stable control ID for a matching label or JavaScript selector. |
| `placeholder` | `$options` or `attributes` | Placeholder text. Use a visible label as well. |
| `required`, `disabled`, `readonly`, `multiple` | `$options` or `attributes` | Boolean HTML states. Disabled controls are not submitted. |
| `min`, `max`, `step` | `$options` or `attributes` | Numeric/date/range constraints. |
| `minlength`, `maxlength`, `size` | `$options` or `attributes` | Text length or display constraints. |
| `pattern` | `$options` or `attributes` | Client-side regular-expression constraint. |
| `autocomplete`, `inputmode`, `accept`, `capture` | `$options` or `attributes` | Browser input hints and file/media constraints. |
| `list` | `$options` or `attributes` | ID of a related `<datalist>`. `datalist()` sets this automatically. |
| `validation` | `$options` | Validation state and feedback configuration. See [Validation](#validation). |

Any other key in `$options` is forwarded as an HTML attribute after the helper's reserved keys are removed. The helper always sets `type`, `name`, and, except for file inputs, `value` itself.

### `text_input()`

```php
FormFieldHelper::text_input( string $name, string $value = '', array $attributes = [] ): string
```

Convenience wrapper for a text input. It delegates to `input()` with `type` set to `text`.

```php
echo FormFieldHelper::text_input( 'settings[display_name]', $display_name, [ 'id' => 'display-name' ] );
```

Use `input()` when the control needs another input type.

### `textarea()`

```php
FormFieldHelper::textarea( string $name, string $value = '', array $options = [] ): string
```

Renders a Bootstrap `form-control` `<textarea>`. The value is escaped as textarea content, not as a `value` attribute. Options such as `id`, `rows`, `maxlength`, `placeholder`, `required`, and `readonly` are passed to the element.

```php
echo FormFieldHelper::textarea(
	'notes',
	$notes,
	[ 'id' => 'notes', 'rows' => 5, 'maxlength' => 500 ]
);
```

#### `textarea()` variables

`$options` accepts the same general HTML attributes as `input()`, except that `type` and `value` are not rendered as textarea attributes. Common variables are `id`, `class`, `attributes`, `placeholder`, `required`, `disabled`, `readonly`, `autofocus`, `autocomplete`, `dirname`, `maxlength`, `minlength`, `name`, `rows`, `cols`, and `wrap`. `validation` adds Bootstrap validation classes and feedback. The helper always sets `name` and uses `$value` as escaped element content.

## Labels and help text

### `label()`

```php
FormFieldHelper::label( string $for, string $text, array $options = [] ): string
```

Renders a Bootstrap `form-label` associated with the control ID in `$for`. Always provide a matching control ID for inputs that have a visible label.

Supported presentation options:

- `class`: additional label classes.
- `description`: escaped help text rendered as a `form-text` element after the label.
- `tooltip`: escaped tooltip text rendered as an accessible Bootstrap tooltip button.
- `tooltip_type`: `question` (default) or `info`; controls the default Font Awesome icon.
- `tooltip_icon`: a Font Awesome icon name or class list.
- `attributes`: additional label attributes.

```php
echo FormFieldHelper::label(
	'client-secret',
	__( 'Client secret', 'mspress' ),
	[
		'description' => __( 'Leave blank to keep the stored secret.', 'mspress' ),
		'tooltip'    => __( 'The secret is used only for the Graph connection.', 'mspress' ),
	]
);
```

Tooltip markup requires the project's Bootstrap tooltip initialization and Font Awesome styles. The tooltip button includes an accessible `aria-label`; the icon itself is hidden from assistive technology.

### `form_text()`

```php
FormFieldHelper::form_text( string $text, array $options = [] ): string
```

Returns standalone Bootstrap help text in a `<div class="form-text">`. It supports `class`, `id`, and nested `attributes`.

```php
echo FormFieldHelper::form_text( __( 'Use a verified domain or tenant ID.', 'mspress' ), [ 'id' => 'tenant-help' ] );
```

### Descriptions and hidden fields

Route user-visible field help through `FormFieldHelper` rather than writing
Bootstrap help markup in a template. Put help that belongs to a specific
control in `label()` with the `description` option; use `form_text()` for
standalone help text:

```php
echo FormFieldHelper::label(
	'profile-name',
	__( 'Profile name', 'mspress' ),
	[ 'description' => __( 'This name is shown only inside WordPress.', 'mspress' ) ]
);
echo FormFieldHelper::input( 'profile[name]', $name, [ 'id' => 'profile-name' ] );
echo FormFieldHelper::form_text( __( 'Profiles are available to authorized senders.', 'mspress' ) );
```

Render hidden controls with `input()` and set `type` to `hidden`. Hidden
controls are submitted values, not user-facing fields, so they do not receive
visible descriptions:

```php
echo FormFieldHelper::input( 'action', 'mspress_save_settings', [ 'type' => 'hidden' ] );
```

`wp_nonce_field()` remains the correct WordPress API for generating nonce
fields. It is security infrastructure rather than a normal form control and
should remain next to the helper-rendered hidden values.

## Select controls

### `select()`

```php
FormFieldHelper::select( string $name, array $options = [], $selected = [], array $attributes = [] ): string
```

Renders a normal Bootstrap `form-select`. `$selected` may be a scalar or array; values are compared as strings. `$options` supports three shapes:

```php
$options = [
	'user'   => __( 'User', 'mspress' ),
	'shared' => __( 'Shared mailbox', 'mspress' ),
];

$options_with_metadata = [
	'draft' => [ 'label' => __( 'Draft', 'mspress' ), 'disabled' => true ],
];

$options_with_group = [
	[ 'label' => __( 'Mailboxes', 'mspress' ), 'options' => $options ],
];
```

An option definition may contain `value`, `label`, and `disabled`. Other option keys become `data-*` attributes on that `<option>`. Option labels are escaped.

```php
echo FormFieldHelper::select(
	'settings[sender_type]',
	[ 'user' => 'User', 'shared' => 'Shared mailbox' ],
	$current_type,
	[ 'id' => 'sender-type', 'required' => true ]
);
```

#### `select()` variables

The fourth argument, `$attributes`, accepts these select-level variables:

| Variable | Description |
| --- | --- |
| `id`, `class`, `attributes` | Control ID, additional classes, and additional HTML attributes. `form-select` is added automatically. |
| `multiple` | Allows more than one selected option. Use an array name such as `settings[roles][]`. |
| `required`, `disabled`, `autofocus` | Native select states. |
| `size` | Number of visible options when using a list-style select. |
| `form` | Associates the select with a form by ID when it is outside that form. |
| `validation` | Adds `is-valid` or `is-invalid` and feedback. |

Other keys in `$attributes` are forwarded as HTML attributes. The helper reserves `options`, `selected`, `validation`, `class`, and `attributes`, and always sets `name`.

Each entry in the second argument, `$options`, can be a simple `value => label` pair or an array with these variables:

| Variable | Description |
| --- | --- |
| `value` | Submitted option value. Defaults to the array key. |
| `label` | Visible option text. Defaults to `value`. |
| `disabled` | Disables the option. |
| `selected` | Accepted in the definition but selection is controlled by the `$selected` argument. |
| `options` | Child options for an optgroup. An optgroup also accepts `label` and `disabled`. |
| Any other key | Becomes a `data-*` attribute on the option. Keys already beginning with `data-` are preserved. |

### `bootstrap_select()`

```php
FormFieldHelper::bootstrap_select( string $name, array $options = [] ): string
```

Renders a single-select intended for Bootstrap Select. Unlike `select()`, choices are supplied under `data` and the selected value under `selected`:

```php
echo FormFieldHelper::bootstrap_select(
	'settings[default_sender]',
	[
		'data'        => $sender_options,
		'selected'    => $default_sender,
		'id'          => 'default-sender',
		'live_search' => true,
		'show_tick'   => true,
		'placeholder' => __( 'Select a sender', 'mspress' ),
	]
);
```

The helper adds the `selectpicker` class and converts supported settings to `data-*` attributes. Supported settings include `icons_base`, `tick_icon`, `live_search`, `show_selected_tags`, `open_options`, `dropup_auto`, `show_tick`, `selection_indicator`, `live_search_placeholder`, `open_options_text`, `selected_text_format`, `selected_items_style`, `selected_tag_remove_label`, `placeholder`, `width`, `size`, `actions_box`, `max_options`, `live_search_normalize`, and `live_search_style`.

#### `bootstrap_select()` and `bootstrap_multiselect()` variables

| Variable | Description |
| --- | --- |
| `data` | Choice definitions using the same shapes as `select()`; this is not the HTML `data-*` attribute bag. |
| `selected` | Selected scalar or array of values. |
| `id`, `attributes` | Native select ID and additional HTML attributes. |
| `class` | Only `selectpicker`, `dropup`, and `show-tick` are retained by the Bootstrap Select class normalizer. |
| `multiple` | Set automatically by `bootstrap_multiselect()`. |
| `icons_base`, `tick_icon` | Bootstrap Select icon configuration. |
| `live_search`, `show_selected_tags`, `open_options`, `dropup_auto`, `show_tick` | Search, tag, open-menu, drop-up, and tick-mark behavior. |
| `selection_indicator`, `selected_text_format`, `selected_items_style` | Selection display behavior. |
| `live_search_placeholder`, `open_options_text`, `selected_tag_remove_label`, `placeholder` | User-facing Bootstrap Select text. |
| `width`, `size`, `actions_box`, `max_options` | Menu width, menu size, actions toolbar, and selection limit. |
| `live_search_normalize`, `live_search_style` | Search normalization and matching style. |
| `validation` | Bootstrap validation state and feedback. |

Native attributes such as `required`, `disabled`, `autofocus`, `form`, and custom `aria-*` values go in `$options['attributes']` or directly in `$options`. The helper consumes the listed Bootstrap Select variables and converts them to `data-*` attributes.

Only the supported Bootstrap Select classes `selectpicker`, `dropup`, and `show-tick` are retained by the helper's class normalizer. Put application-specific classes on a surrounding element if they are not part of that list.

### `bootstrap_multiselect()`

```php
FormFieldHelper::bootstrap_multiselect( string $name, array $options = [] ): string
```

Renders a Bootstrap Select multi-select. It adds `[]` to `$name` when it is not already present, sets `multiple`, and uses the same `data`, `selected`, and Bootstrap Select settings as `bootstrap_select()`:

```php
echo FormFieldHelper::bootstrap_multiselect(
	'settings[features]',
	[
		'data'      => [ 'graph' => 'Microsoft Graph', 'exchange' => 'Exchange' ],
		'selected'  => $enabled_features,
		'id'        => 'enabled-features',
		'show_tick' => true,
	]
);
```

Pass `settings[features][]` only when that is already the desired name; the helper avoids adding a second suffix.

## Check controls

### `checkbox()`, `radio()`, and `switch()`

```php
FormFieldHelper::checkbox( string $name, string $value = '1', string $label = '', array $options = [] ): string
FormFieldHelper::radio( string $name, string $value, string $label = '', array $options = [] ): string
FormFieldHelper::switch( string $name, string $value = '1', string $label = '', array $options = [] ): string
```

These methods render a Bootstrap `form-check` wrapper, an associated input, and an optional label. `switch()` is a checkbox with `form-switch` and `role="switch"`.

Useful options include:

- `id`: explicit input ID; otherwise one is generated from name and value.
- `checked`: truthy values add the checked attribute.
- `inline`: adds `form-check-inline`.
- `reverse`: adds `form-check-reverse`.
- `switch`: used internally by `switch()`.
- `wrapper_class`: additional classes on the wrapper.
- `class`: additional classes on the input.
- `attributes`: additional input attributes, including `aria-*` and `data-*`.

The `checkbox()`, `radio()`, and `switch()` `$options` variables are:

| Variable | Description |
| --- | --- |
| `id` | Input ID used by the associated label. Generated from name and value when omitted. |
| `class` | Additional input classes. `form-check-input` is added automatically. |
| `attributes` | Additional native input attributes such as `aria-describedby`, `data-*`, `required`, or `autocomplete`. |
| `checked` | Adds the checked state when truthy. |
| `disabled`, `required`, `form` | Native input states and form association. |
| `inline` | Adds `form-check-inline` to the wrapper. |
| `reverse` | Adds `form-check-reverse` to the wrapper. |
| `wrapper_class` | Additional classes on the `.form-check` wrapper. |
| `validation` | Adds validation class and feedback inside the wrapper. |
| `switch` | Internal flag used by `switch()`; it adds `form-switch` and `role="switch"`. |

The `$name`, `$value`, and `$label` arguments supply the input name, submitted value, and escaped visible label. Unchecked checkboxes do not submit a value.

```php
echo FormFieldHelper::switch(
	'settings[enabled]',
	'1',
	__( 'Exchange Integration', 'mspress' ),
	[
		'id'      => 'exchange-enabled',
		'checked' => ! empty( $settings['enabled'] ),
	]
);
echo FormFieldHelper::form_text( __( 'Send WordPress email through Microsoft Graph.', 'mspress' ) );
```

Unchecked checkboxes do not submit a value. When an unchecked state must be persisted explicitly, handle the missing request key in the owning settings or request code.

### `check()`

```php
FormFieldHelper::check( string $type, string $name, string $value, string $label = '', array $options = [] ): string
```

Shared checkbox/radio renderer. `$type` is restricted to `checkbox` and `radio`; unsupported values fall back to `checkbox`. Prefer the named methods unless a shared dynamic renderer genuinely needs `$type`.

## Button controls

### `button()`

```php
FormFieldHelper::button( string $label, array $options = [] ): string
```

Renders a `<button>` by default, or an `<a>` when `href` is supplied. The default button type is `button`, which avoids accidentally submitting a form. Supported button types are `button`, `submit`, and `reset`; unsupported values fall back to `button`.

```php
echo FormFieldHelper::button(
	__( 'Save settings', 'mspress' ),
	[ 'type' => 'submit', 'class' => 'btn-primary' ]
);

echo FormFieldHelper::button(
	__( 'View documentation', 'mspress' ),
	[ 'href' => $documentation_url, 'class' => 'btn-outline-secondary' ]
);
```

The label is escaped as text. Do not pass raw icon HTML as `$label`; use text or a deliberately trusted composition path for icons. Link buttons marked `disabled` receive `aria-disabled="true"` and `tabindex="-1"`, but application code should still prevent the action from being followed.

#### `button()` variables

| Variable | Description |
| --- | --- |
| `href` | Changes the renderer from `<button>` to `<a>`. The URL is emitted as the link's `href`. |
| `type` | `button`, `submit`, or `reset`; invalid values fall back to `button`. Applies to button elements. |
| `class` | Button classes such as `btn-primary`, `btn-outline-secondary`, or `btn-danger`; `btn` is added automatically. |
| `disabled` | Adds the disabled attribute. Link buttons also receive `aria-disabled` and `tabindex=-1`. |
| `attributes` | Additional HTML attributes, including `aria-*`, `data-*`, `title`, and `name`. |
| `value` | Not supported by the renderer; the helper removes this reserved option. Use a dedicated hidden input when a separate submitted value is required. |

The `$label` argument is escaped text, not an HTML content slot.

### `dropdown_button()`

```php
FormFieldHelper::dropdown_button( string $label, array $items = [], array $options = [] ): string
```

Renders a Bootstrap dropdown inside a `btn-group`. The toggle supports `id`, `variant`, `size` (`sm` or `lg`), `class`, `split`, and nested `attributes`. Each item can be a string or an array with `label`, `href`, `active`, `disabled`, `class`, and `attributes`. Use `[ 'divider' => true ]` for a divider and `[ 'header' => '...' ]` for a menu header.

```php
echo FormFieldHelper::dropdown_button(
	__( 'Actions', 'mspress' ),
	[
		[ 'label' => __( 'Import', 'mspress' ), 'href' => $import_url ],
		[ 'divider' => true ],
		[ 'header' => __( 'Other actions', 'mspress' ) ],
		[ 'label' => __( 'Delete', 'mspress' ), 'class' => 'text-danger' ],
	],
	[ 'id' => 'profile-actions', 'variant' => 'secondary' ]
);
```

Labels, headers, and item attributes are escaped. State-changing item buttons still need a nonce and server-side authorization in their owning action.

#### `dropdown_button()` variables

Toggle `$options` support `id`, `class`, `variant`, `size` (`sm` or `lg`), `split`, and `attributes`. The helper always adds the Bootstrap dropdown attributes `type="button"`, `data-bs-toggle="dropdown"`, and `aria-expanded="false"`.

Each `$items` entry can contain:

| Variable | Description |
| --- | --- |
| `label` | Escaped visible item text. |
| `href` | Renders the item as an anchor; without it, the item is a button. |
| `active` | Adds the active class and `aria-current="true"`. |
| `disabled` | Adds disabled and `aria-disabled` states. |
| `class` | Additional menu-item classes. |
| `attributes` | Additional item HTML attributes. |
| `divider` | When truthy, renders a dropdown divider instead of an item. |
| `header` | Renders a dropdown section heading instead of an item. |

### `button_group()` and `button_toolbar()`

```php
FormFieldHelper::button_group( string $name, array $buttons = [], $selected = [], array $options = [] ): string
FormFieldHelper::button_toolbar( array $groups = [], array $options = [] ): string
```

`button_group()` supports `type` values `radio`, `checkbox`, and `button`. Radio and checkbox definitions become hidden Bootstrap `btn-check` inputs paired with labels. Button definitions become regular buttons and may include a nested `dropdown` definition. Checkbox groups append `[]` to the input name by default; set `append_array` to `false` to disable that behavior.

Group options include `id`, `class`, `size` (`sm` or `lg`), `vertical`, `variant`, `button_class`, `role`, `aria_label`, and `validation`. A button definition can override `value`, `label`, `id`, `variant`, `class`, `disabled`, `type`, and `input_attributes`.

```php
echo FormFieldHelper::button_group(
	'settings[mode]',
	[
		'compact'  => [ 'label' => __( 'Compact', 'mspress' ) ],
		'detailed' => [ 'label' => __( 'Detailed', 'mspress' ) ],
	],
	$current_mode,
	[ 'id' => 'display-mode', 'aria_label' => __( 'Display mode', 'mspress' ) ]
);
```

`button_toolbar()` expects already-rendered group strings in `$groups`. It adds `role="toolbar"`, an optional `aria_label`, optional `gap`, and the `btn-toolbar` class. Because groups are supplied as markup, only pass trusted helper output or markup that has been deliberately sanitized.

#### Button group and toolbar variables

`button_group()` group `$options` support `type` (`radio`, `checkbox`, or `button`), `id`, `class`, `size` (`sm` or `lg`), `vertical`, `variant`, `button_class`, `role`, `aria_label`, `append_array`, `attributes`, and `validation`.

For each button definition, use `value`, `label`, `id`, `variant`, `class`, `disabled`, `type`, `input_attributes`, and, for button groups, `dropdown`. `input_attributes` are applied to checkbox/radio inputs; `dropdown` contains the item definitions accepted by `dropdown_button()`.

`button_toolbar()` accepts `id`, `class`, `aria_label`, `gap`, `attributes`, and already-rendered group strings. `gap` becomes the Bootstrap `gap-*` class after being normalized to a non-negative integer.

## Range and datalist controls

### `range()`

```php
FormFieldHelper::range( string $name, $value = 0, array $options = [] ): string
```

Renders a Bootstrap `form-range` input. Standard range attributes such as `min`, `max`, and `step` are passed through. Set `output` to a truthy value to append an `<output>` associated with the generated or supplied `id`:

```php
echo FormFieldHelper::range(
	'settings[retention_days]',
	$retention_days,
	[ 'id' => 'retention-days', 'min' => 1, 'max' => 90, 'output' => true ]
);
```

The helper renders the initial output value only. Updating the output while the user moves the slider requires feature-specific JavaScript.

#### `range()` variables

`$options` supports all relevant `input()` variables plus `min`, `max`, `step`, and `output`. `output` is a helper-only boolean: when true, an `<output>` element is appended and associated with the input ID. The helper forces `type="range"` and adds `form-range`; do not use `type` to change it.

### `datalist()`

```php
FormFieldHelper::datalist( string $name, array $values, $value = '', array $options = [] ): string
```

Renders an input connected to a `<datalist>`. By default, the list ID is derived from the field name; set `list` to provide an explicit ID. Values may be scalars or arrays containing `value` and an optional `label`.

#### `datalist()` variables

`$options` supports the `input()` variables. `list` overrides the generated datalist ID, while `id` identifies the input itself. The `$values` array accepts scalar values or definitions with `value` and `label`; labels are escaped and rendered as option text.

```php
echo FormFieldHelper::datalist(
	'country',
	[
		[ 'value' => 'US', 'label' => 'United States' ],
		[ 'value' => 'GB', 'label' => 'United Kingdom' ],
	],
	$country,
	[ 'id' => 'country', 'list' => 'country-options' ]
);
```

## Validation

Controls that accept `$options` can receive `validation` in either of these forms:

```php
[ 'validation' => 'The value is invalid.' ]
[ 'validation' => [ 'state' => 'invalid', 'message' => 'The value is invalid.' ] ]
```

The string form means invalid. The array form accepts `state` values `valid` or `invalid`, plus `message` and optional `id`. The control receives `is-valid` or `is-invalid`, and the helper appends feedback markup:

```php
echo FormFieldHelper::input(
	'client_id',
	$client_id,
	[
		'id' => 'client-id',
		'validation' => [
			'state'   => 'invalid',
			'message' => __( 'Enter an application ID.', 'mspress' ),
		],
	]
);
```

Set `tooltip` inside the validation array to use Bootstrap's `invalid-tooltip` or `valid-tooltip` class. The helper does not generate `aria-invalid` or `aria-describedby` automatically, so add those attributes when the page's accessibility behavior requires an explicit relationship:

```php
echo FormFieldHelper::input(
	'client_id',
	$client_id,
	[
		'id' => 'client-id',
		'attributes' => [
			'aria-invalid'     => 'true',
			'aria-describedby' => 'client-id-feedback',
		],
		'validation' => [
			'state'   => 'invalid',
			'id'      => 'client-id-feedback',
			'message' => __( 'Enter an application ID.', 'mspress' ),
		],
	]
);
```

`feedback()` can be used directly when a composite layout needs to place feedback separately:

```php
echo FormFieldHelper::feedback( [ 'validation' => [ 'state' => 'valid', 'message' => 'Looks good.' ] ] );
```

## Layout wrappers and forms

### `input_group()`

```php
FormFieldHelper::input_group( string $content, array $options = [] ): string
```

Wraps supplied control markup in an `input-group`. It supports `size`, `class`, nested `attributes`, and `validation`; validation adds `has-validation` and appends feedback. `$content` is intentionally treated as markup, so compose it from trusted helper output:

```php
$content = FormFieldHelper::input( 'domain', $domain, [ 'id' => 'domain' ] );
$content .= FormFieldHelper::button( __( 'Check', 'mspress' ), [ 'type' => 'submit', 'class' => 'btn-secondary' ] );
echo FormFieldHelper::input_group( $content );
```

### `floating()`

```php
FormFieldHelper::floating( string $control, string $label, array $options = [] ): string
```

Wraps a control and label in Bootstrap's `form-floating` layout. Pass the control markup first and set `for` to the control ID. The control must be suitable for floating labels, and the label is rendered after it as required by Bootstrap:

```php
$control = FormFieldHelper::input( 'email', $email, [ 'id' => 'floating-email', 'placeholder' => ' ' ] );
echo FormFieldHelper::floating( $control, __( 'Email address', 'mspress' ), [ 'for' => 'floating-email' ] );
```

### `form_open()` and `form_close()`

```php
FormFieldHelper::form_open( string $action = '', string $method = 'post', array $options = [] ): string
FormFieldHelper::form_close(): string
```

`form_open()` returns an escaped `<form>` opening tag. It supports `class`, nested `attributes`, and `validation`; validation adds Bootstrap's `needs-validation` class. The method is normalized to lowercase, but the owning feature remains responsible for nonces and server-side request handling.

```php
echo FormFieldHelper::form_open(
	admin_url( 'admin-post.php' ),
	'post',
	[
		'class'      => 'needs-confirmation',
		'attributes' => [ 'enctype' => 'multipart/form-data' ],
	]
);
// Render nonce, fields, and submit control here.
echo FormFieldHelper::form_close();
```

## Utility method

### `attributes_to_string()`

```php
FormFieldHelper::attributes_to_string( array $attributes ): string
```

Converts an associative array to escaped HTML attributes. `null` and `false` are omitted, `true` becomes a boolean attribute, and a `true` `data-*` value is rendered as `data-*=\"true\"`. Attribute names are normalized with WordPress `sanitize_key()`.

Use this method only when a feature needs to compose markup that the existing renderers cannot express. Prefer the higher-level helper methods for controls so Bootstrap classes and validation remain consistent.

## Common mistakes

- Passing raw HTML as the label to `button()`. The label is escaped intentionally; use text or a deliberately trusted composition path for icons.
- Passing select choices directly as the second argument to `bootstrap_select()`. Use `data` and `selected`; direct choices belong to `select()`.
- Forgetting that `bootstrap_multiselect()` appends `[]` to the name and expects an array of selected values.
- Using a placeholder instead of a visible label. Placeholders disappear while typing and do not provide a reliable accessible name.
- Assuming `checked`, `required`, or `disabled` replaces server-side validation or authorization.
- Passing untrusted strings as `$content` to `input_group()`, `floating()`, or `button_toolbar()`. Those parameters are composed as markup by design.
- Expecting `range()` output to update automatically. The initial `<output>` is server-rendered; dynamic updates need JavaScript.
- Assuming an unchecked checkbox submits `false`. Browsers omit unchecked checkbox controls entirely, so normalize missing values in the request handler.
