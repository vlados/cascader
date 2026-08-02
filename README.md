# Cascader

A cascading dropdown component for Laravel Livewire with Alpine.js. Inspired by Ant Design's Cascader component.

On desktop it renders as a two-column dropdown with search; on mobile (< 640px) it becomes a bottom sheet with step-by-step navigation. Light and dark mode are both supported out of the box.

## Requirements

- PHP 8.2+ (Laravel 13 requires PHP 8.3+)
- Laravel 11, 12 or 13
- Livewire 3 or 4
- Alpine.js 3
- Tailwind CSS
- An icon source if you use icons (FontAwesome by default — see [Icon Resolver](#icon-resolver))

## Installation

```bash
composer require vlados/cascader
```

### Register the Alpine component (required)

The Blade component renders `x-data="cascader(...)"`, so the Alpine component must be registered before Alpine starts. Publish the script and import it in your bundle:

```bash
php artisan vendor:publish --tag=cascader-scripts
```

```js
// resources/js/app.js — before Alpine.start()
import { cascader } from './vendor/cascader/cascader';

document.addEventListener('alpine:init', () => {
    Alpine.data('cascader', cascader);
});
```

The script also registers itself automatically when it is loaded on a page where Alpine is available globally (before `alpine:init` fires), so with a plain `<script>` setup no extra code is needed.

### Tailwind

The published views use Tailwind classes. Make sure your Tailwind content configuration covers the package views (or the published copies):

```js
content: [
    // ...
    './vendor/vlados/cascader/src/resources/views/**/*.blade.php',
],
```

## Usage

### Basic usage

```blade
<x-cascader
    :options="$categories"
    wire:model="category_id"
    placeholder="Select category"
/>
```

Use `wire:model.live` (with any modifiers, e.g. `wire:model.live.debounce.500ms`) for live updates. The deprecated `wire-model="category_id"` prop from older versions still works but will be removed in a future release — prefer the standard `wire:model` attribute.

If the bound property already has a value on page load, the component derives the displayed label from the options automatically. You can override it with `selected-text`:

```blade
<x-cascader
    :options="$categories"
    wire:model="category_id"
    selected-text="Electronics / Phones"
/>
```

### Options format

The `options` array is two levels deep — parents with an optional `children` array. A parent **with** children is a navigation node and cannot be selected itself; a parent with no children is selectable directly.

```php
$categories = [
    [
        'id' => 1,
        'name' => 'Electronics',
        'icon' => 'laptop',      // optional, see Icon Resolver
        'color' => '#3B82F6',    // optional icon color / background tint
        'children' => [
            ['id' => 11, 'name' => 'Phones', 'icon' => 'mobile', 'color' => '#3B82F6'],
            ['id' => 12, 'name' => 'Tablets', 'icon' => 'tablet', 'color' => '#3B82F6'],
        ],
    ],
    [
        'id' => 3,
        'name' => 'Other',  // no children — selectable directly
        'icon' => 'question',
        'color' => '#6B7280',
        'children' => [],
    ],
];
```

> **Values must be unique across the whole tree** — parents and children share one value space. If a parent and a child both had `id: 5`, selection highlighting could not tell them apart.

### Custom value and label fields

By default the component reads `id` for values and `name` for labels:

```blade
<x-cascader
    :options="$items"
    wire:model="selected_slug"
    value-field="slug"
    label-field="title"
/>
```

### Clearable selection

```blade
<x-cascader :options="$categories" wire:model="category_id" :clearable="true" />
```

### Sizes

Two sizes are available, matching Flux UI's select: `sm` (default) and `xs`:

```blade
<x-cascader :options="$categories" wire:model="category_id" size="xs" />
```

### Search

The desktop dropdown includes a search box. Results are leaf-only: a query matching a parent lists its children as "Parent / Child" paths, and leaf parents appear directly. Escape clears the search first; pressing Escape again closes the dropdown.

### Mobile customization

On screens narrower than 640px the cascader opens as a bottom sheet with Cancel/Confirm buttons:

```blade
<x-cascader
    :options="$categories"
    wire:model="category_id"
    cancel-text="Cancel"
    confirm-text="Done"
    search-placeholder="Search..."
/>
```

### All props

| Prop | Default | Description |
| --- | --- | --- |
| `options` | `[]` | The option tree (see format above) |
| `wire:model` | — | Livewire binding (attribute, supports `.live` and other modifiers) |
| `placeholder` | `Select...` | Trigger text when nothing is selected |
| `selected-text` | derived | Initial label override for a pre-selected value |
| `value-field` | `id` | Key used for option values |
| `label-field` | `name` | Key used for option labels |
| `search-placeholder` | `Search...` | Search input placeholder |
| `clearable` | `false` | Show a clear button when a value is selected |
| `cancel-text` | `Cancel` | Mobile sheet cancel button |
| `confirm-text` | `Confirm` | Mobile sheet confirm button |
| `size` | `sm` | `sm` or `xs` |

## Icon Resolver

Icons are resolved server-side to HTML through a configurable resolver. The default renders FontAwesome `<i>` tags. Configure a different resolver in `AppServiceProvider::boot()`.

Icon names are validated (letters, numbers, dots, dashes, underscores) and colors are sanitized before rendering — invalid values are rejected or ignored rather than interpolated into markup.

### FontAwesome inline tags (default)

```php
use Vlados\Cascader\IconResolver;

IconResolver::useFontAwesome();          // fa-solid (default)
IconResolver::useFontAwesome('regular'); // fa-regular
```

Options use plain icon names: `['icon' => 'laptop']` → `<i class="fa-solid fa-laptop">`. Requires FontAwesome CSS on the page.

### Blade FontAwesome components

For projects using [blade-fontawesome](https://github.com/owenvoke/blade-fontawesome):

```php
IconResolver::useBladeFontAwesome();      // fas (default)
IconResolver::useBladeFontAwesome('far'); // regular
```

`['icon' => 'laptop']` → `<x-fas-laptop />`

### Heroicons

```php
IconResolver::useHeroicons();          // solid
IconResolver::useHeroicons('outline'); // outline
```

`['icon' => 'home']` → `<x-heroicon-s-home />`

### Blade Icons (any set)

```php
IconResolver::useBladeIcons();
```

Pass full component names: `['icon' => 'heroicon-o-home']` → `<x-heroicon-o-home />`

### Custom resolver

```php
IconResolver::using(function (string $icon, ?string $color = null, string $size = 'sm') {
    return view('components.my-icon', [
        'name' => $icon,
        'color' => $color,
        'size' => $size,
    ])->render();
});
```

The returned HTML is injected with `x-html`, so a custom resolver must escape any untrusted data itself.

### Error handling

If an icon component cannot be rendered, a descriptive `InvalidArgumentException` is thrown:

```
Cascader: Unable to render icon component '<x-fas-missing />'.
Original icon name: 'missing'.
Make sure the icon exists or configure a different IconResolver.
```

## Using the Alpine component directly

If you want your own markup, use the Alpine component without the Blade wrapper:

```blade
<div
    x-data="cascader({
        options: {{ Js::from($categories) }},
        modelValue: $wire.entangle('category_id'),
        initialText: {{ Js::from($selectedText) }},
        valueField: 'id',
        labelField: 'name'
    })"
>
    <!-- Your custom template -->
</div>
```

`selectedValue` is accepted as a deprecated alias for `modelValue`.

## Publishing assets

```bash
php artisan vendor:publish --tag=cascader-views    # Blade views
php artisan vendor:publish --tag=cascader-scripts  # Alpine component
```

## License

MIT
