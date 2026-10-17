# FaHelper

FaHelper is the dedicated Font Awesome rendering utility for ModPress. It generates consistent HTML for icons using the official Font Awesome CSS class conventions and supports styling, transforms, animations, accessibility attributes, and custom inline styling without requiring callers to build repetitive markup by hand.

## Primary implementation

- [src/Includes/Plugins/FontAwesome/Includes/Functions/Helpers/FAHelper.php](../../Includes/Functions/Helpers/FAHelper.php)

## Typical usage

```php
echo FaHelper::faicon(
    'house',
    [
        'family' => 'classic',
        'style' => 'solid',
        'size' => 'lg',
        'fixed_width' => true,
        'flip' => 'horizontal',
        'rotate' => 90,
        'animation' => 'spin',
        'transform' => 'grow-2 right-4',
        'class' => 'demo-icon',
        'aria_label' => 'Home icon',
        'title' => 'Home',
        'data_test' => 'example',
    ]
);
```

## Example output

```html
<i class="fa-solid fa-house fa-fw fa-lg fa-flip-horizontal fa-rotate-90 fa-spin demo-icon"
    aria-label="Home icon"
    title="Home"
    data-test="example"
    style="--fa-transform: grow-2 right-4;">
</i>
```

## Class name

`FaHelper`

## Public API

- `faicon( string $icon_name, array $options = [] ): string`
- `icon( string $icon_name, array $options = [] ): string`

> `render_icon()` remains available as an internal alias, but normal usage should go through `FaHelper::faicon()`.

## Notes

- `$icon_name` accepts names like `house`, `fa-house`, or `fas fa-house` style values; the helper normalizes them into a single Font Awesome icon class.
- The helper always renders an `<i>` element because that is the standard Font Awesome markup pattern used by the FA CSS library.
- Values are sanitized and escaped with the WordPress-safe helpers available in the runtime, so output remains safe for HTML rendering.

## Supported option keys

### `family`

Accepts `classic` or `brands`. The rendered class is either `fa-solid` / `fa-regular` / `fa-light` / etc. or `fa-brands`.

### `style`

Supported styles for classic families: `solid`, `regular`, `light`, `thin`, `duotone`.

For brand icons, the helper ignores the style and outputs `fa-brands`.

### `size`

Accepts values such as: `2xs`, `xs`, `sm`, `lg`, `xl`, `2xl`, `1x`, `2x`, `3x`, `5x`, `7x`, `10x`.

### `fixed_width`

When `true`, adds `fa-fw`.

### `border`

When `true`, adds `fa-border`.

### `li` / `list_item`

When `true`, adds `fa-li`.

### `pull`

`left` adds `fa-pull-left` and `right` adds `fa-pull-right`.

### `flip`

`horizontal`, `vertical`, or `both` adds the matching `fa-flip-*` class.

### `rotate`

Integer value adds `fa-rotate-N`.

### `animation`

Supported values include: `spin`, `pulse`, `beat`, `bounce`, `fade`, `beat-fade`, `flash`, and `shake`.

### `transform`

Allows custom transform instructions. This can be a raw string such as `grow-2 right-4` or an array such as:

```php
[
    'rotate' => 90,
    'x' => 4,
    'y' => 2,
    'grow' => 2,
    'shrink' => 1,
    'scale' => 1.5,
]
```

The helper turns this into the CSS custom property `--fa-transform`.

### `class` / `classes`

Lets callers append additional CSS classes. Accepts a string or array of classes.

### `color`

Applies a direct CSS color value to the icon.

### `font_size`

Applies a font-size value to the icon.

### `primary_color` / `secondary_color`

Uses the Font Awesome dual-tone color variables, for example `--fa-primary-color` and `--fa-secondary-color`.

### `secondary_opacity` / `opacity`

Applies the matching Font Awesome opacity variables.

### `inline_style`

Appends raw CSS declarations to the generated `style` attribute.

## Accessibility and custom attributes

- `aria_label` maps to `aria-label`
- `aria_hidden` maps to `aria-hidden`
- `title` maps to `title`
- any key beginning with `data_` or `aria_` is converted to an HTML attribute
- custom scalar values are passed through and rendered as HTML attributes

## Example with dual-tone colors and transform

```php
echo FaHelper::faicon(
    'star-half-stroke',
    [
        'style' => 'duotone',
        'size' => '2xl',
        'primary_color' => '#f59e0b',
        'secondary_color' => '#fef3c7',
        'secondary_opacity' => '0.8',
        'transform' => [
            'rotate' => 45,
            'grow' => 2,
        ],
        'class' => 'featured-icon',
        'aria_label' => 'Featured star',
    ]
);
```

## Notes for maintainers

- Keep icon names in the same normal form expected by Font Awesome class names.
- When using custom classes, prefer the official Font Awesome utility classes for consistent sizing, animations, and transforms.
- Use `family` and `style` together to target the right FA family. Brand icons should usually be rendered as `family="brands"`.
