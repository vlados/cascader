# Cascader

A cascading dropdown component for Laravel Livewire with Alpine.js. Inspired by Ant Design's Cascader component.

## Installation

```bash
composer require vlados/cascader
```

## Usage

### Basic Usage

```blade
<x-cascader
    :options="$categories"
    wire-model="category_id"
    placeholder="Select category"
    :selected-text="$selectedCategoryText"
/>
```

### Custom Value and Label Fields

By default, the component uses `id` for values and `name` for labels. You can customize this:

```blade
<x-cascader
    :options="$items"
    wire-model="selected_slug"
    value-field="slug"
    label-field="title"
    placeholder="Select item"
/>
```

### Options Format

The `options` array should be structured with parent items containing a `children` array. The component supports unlimited nesting depth:

```php
$categories = [
    [
        'id' => 1,
        'name' => 'Electronics',
        'icon' => 'laptop',      // FontAwesome icon (optional)
        'color' => '#3B82F6',    // Color for icon background (optional)
        'children' => [
            [
                'id' => 11,
                'name' => 'Phones',
                'icon' => 'mobile',
                'color' => '#3B82F6',
                'children' => [  // Unlimited nesting depth
                    ['id' => 111, 'name' => 'iPhone', 'icon' => 'apple', 'color' => '#3B82F6'],
                    ['id' => 112, 'name' => 'Android', 'icon' => 'android', 'color' => '#3B82F6'],
                ],
            ],
            ['id' => 12, 'name' => 'Tablets', 'icon' => 'tablet', 'color' => '#3B82F6'],
        ],
    ],
    [
        'id' => 2,
        'name' => 'Clothing',
        'icon' => 'shirt',
        'color' => '#10B981',
        'children' => [
            ['id' => 21, 'name' => 'Men', 'icon' => 'person', 'color' => '#10B981'],
            ['id' => 22, 'name' => 'Women', 'icon' => 'person-dress', 'color' => '#10B981'],
        ],
    ],
    [
        'id' => 3,
        'name' => 'Other',  // No children - selectable directly
        'icon' => 'question',
        'color' => '#6B7280',
        'children' => [],
    ],
];
```

### Multi-Select with Checkboxes

Enable multi-select mode to allow selecting multiple leaf items with checkboxes:

```blade
<x-cascader
    :options="$categories"
    wire:model="selected_ids"
    :multiple="true"
    placeholder="Select categories"
/>
```

In multi-select mode:
- Only leaf nodes (items without children) can be selected
- Selected values are stored as an array
- The display shows "X selected" where X is the count
- Checkboxes appear next to selectable items

### Alpine.js Component

If you need to use the Alpine.js component directly without the Blade component:

```blade
<div
    x-data="cascader({
        options: {{ Js::from($categories) }},
        selectedValue: $wire.entangle('category_id'),
        initialText: {{ Js::from($selectedText) }},
        valueField: 'id',
        labelField: 'name'
    })"
>
    <!-- Your custom template -->
</div>
```

### Clearable Selection

Add a clear button to reset the selection:

```blade
<x-cascader
    :options="$categories"
    wire-model="category_id"
    placeholder="Select category"
    :clearable="true"
/>
```

### Mobile Customization

On mobile devices (< 640px), the cascader automatically displays as a bottom sheet. You can customize the button text:

```blade
<x-cascader
    :options="$categories"
    wire-model="category_id"
    placeholder="Select category"
    cancel-text="Cancel"
    confirm-text="Done"
/>
```

### Icon Resolver

The cascader supports flexible icon rendering through a resolver system. By default, it uses FontAwesome, but you can configure it to use Heroicons, Blade Icons, or create a custom resolver.

#### Using FontAwesome with inline `<i>` tags (default)

```php
// In AppServiceProvider::boot()
use Vlados\Cascader\IconResolver;

IconResolver::useFontAwesome(); // solid style (default)
IconResolver::useFontAwesome('regular'); // regular style
```

Options use simple icon names: `['icon' => 'laptop']` → `<i class="fa-solid fa-laptop">`

#### Using Blade FontAwesome (recommended)

For projects using the [blade-fontawesome](https://github.com/owenvoke/blade-fontawesome) package:

```php
IconResolver::useBladeFontAwesome(); // fas style (default)
IconResolver::useBladeFontAwesome('far'); // regular style
```

Options use simple icon names: `['icon' => 'laptop']` → `<x-fas-laptop />`

#### Using Heroicons

```php
IconResolver::useHeroicons(); // solid style
IconResolver::useHeroicons('outline'); // outline style
```

Options use simple icon names: `['icon' => 'home']` → `<x-heroicon-s-home />`

#### Using Blade Icons

For any icon set that follows the Blade Icons convention:

```php
IconResolver::useBladeIcons();
```

With this resolver, pass full component names in your options:
```php
['icon' => 'fas-laptop'] // renders <x-fas-laptop />
['icon' => 'heroicon-o-home'] // renders <x-heroicon-o-home />
```

#### Custom Resolver

Create your own resolver for complete control:

```php
IconResolver::using(function (string $icon, ?string $color = null, string $size = 'sm') {
    // Return HTML string for the icon
    return view('components.my-icon', [
        'name' => $icon,
        'color' => $color,
        'size' => $size,
    ])->render();
});
```

#### Error Handling

If an icon component cannot be found, a descriptive error is thrown:

```
Cascader: Unable to render icon component '<x-fas-missing />'.
Original icon name: 'missing'.
Make sure the icon exists or configure a different IconResolver.
```

### Publishing Assets

To publish the views for customization:

```bash
php artisan vendor:publish --tag=cascader-views
```

To publish the JavaScript:

```bash
php artisan vendor:publish --tag=cascader-scripts
```

## Features

- **Unlimited nesting depth** - Support for multi-level hierarchies (not just parent/child)
- **Multi-select mode** with checkboxes for selecting multiple items
- Multi-column cascading dropdown (desktop) - columns appear dynamically as you navigate
- **Mobile-friendly bottom sheet** with breadcrumb navigation
- **Search/filter** through all levels of options
- Hover to preview children
- Click to select
- **Clearable** selection with optional clear button
- **Flexible icon resolver** (FontAwesome, Heroicons, Blade Icons, or custom)
- Selected value shows full path (e.g., "Electronics / Phones / iPhone")
- Auto-closes on selection or outside click
- Keyboard support (Escape to close)
- Works with Livewire's wire:model
- Configurable value and label fields
- Customizable search placeholder
- Customizable Cancel/Confirm button text (mobile)
- Dark mode support

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- Livewire 3
- Alpine.js 3
- Tailwind CSS
- FontAwesome (for icons)

## License

MIT
