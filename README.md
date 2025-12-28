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

The `options` array should be structured with parent items containing a `children` array:

```php
$categories = [
    [
        'id' => 1,
        'name' => 'Electronics',
        'icon' => 'laptop',      // FontAwesome icon (optional)
        'color' => '#3B82F6',    // Color for icon background (optional)
        'children' => [
            ['id' => 11, 'name' => 'Phones', 'icon' => 'mobile', 'color' => '#3B82F6'],
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

- Two-column cascading dropdown (desktop)
- **Mobile-friendly bottom sheet** with step-by-step navigation
- **Search/filter** through all options
- Hover to preview children
- Click to select
- **Clearable** selection with optional clear button
- Icons and colors support (FontAwesome)
- Selected value shows "Parent / Child" format
- Auto-closes on selection or outside click
- Keyboard support (Escape to close)
- Works with Livewire's wire:model
- Configurable value and label fields
- Customizable search placeholder
- Customizable Cancel/Confirm button text (mobile)

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- Livewire 3
- Alpine.js 3
- Tailwind CSS
- FontAwesome (for icons)

## License

MIT
