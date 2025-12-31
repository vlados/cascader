<?php

use Illuminate\Support\Facades\Blade;
use Vlados\Cascader\IconResolver;

beforeEach(function () {
    IconResolver::useFontAwesome();
});

describe('Blade Component Rendering', function () {
    it('renders the cascader component', function () {
        $options = sampleOptions();

        $html = Blade::render('<x-cascader :options="$options" />', [
            'options' => $options,
        ]);

        expect($html)->toContain('x-data="cascader')
            ->and($html)->toContain('x-ref="cascaderRoot"');
    });

    it('renders with placeholder', function () {
        $html = Blade::render('<x-cascader :options="[]" placeholder="Select a category" />');

        expect($html)->toContain('Select a category');
    });

    it('renders with custom search placeholder', function () {
        $html = Blade::render('<x-cascader :options="[]" search-placeholder="Find items..." />');

        expect($html)->toContain('Find items...');
    });

    it('renders clearable button when enabled', function () {
        $html = Blade::render('<x-cascader :options="[]" :clearable="true" />');

        expect($html)->toContain('@click.stop="clear()"');
    });

    it('does not render clearable button when disabled', function () {
        $html = Blade::render('<x-cascader :options="[]" :clearable="false" />');

        // The clear button should not be in the main trigger area
        expect($html)->not->toContain('clear()');
    });

    it('renders with xs size', function () {
        $html = Blade::render('<x-cascader :options="[]" size="xs" />');

        expect($html)->toContain('h-8');
    });

    it('renders with sm size (default)', function () {
        $html = Blade::render('<x-cascader :options="[]" size="sm" />');

        expect($html)->toContain('h-10');
    });

    it('renders cancel and confirm buttons for mobile', function () {
        $html = Blade::render('<x-cascader :options="[]" cancel-text="Abort" confirm-text="OK" />');

        expect($html)->toContain('Abort')
            ->and($html)->toContain('OK');
    });
});

describe('Multiple Mode Rendering', function () {
    it('renders with multiple mode enabled', function () {
        $html = Blade::render('<x-cascader :options="[]" :multiple="true" />');

        expect($html)->toContain('multiple: true');
    });

    it('renders without multiple mode by default', function () {
        $html = Blade::render('<x-cascader :options="[]" />');

        expect($html)->toContain('multiple: false');
    });

    it('renders checkboxes in multiple mode', function () {
        $options = sampleOptions();

        $html = Blade::render('<x-cascader :options="$options" :multiple="true" />', [
            'options' => $options,
        ]);

        // Check for checkbox styling classes
        expect($html)->toContain('border rounded shrink-0');
    });
});

describe('Options Data', function () {
    it('passes options to Alpine component', function () {
        $options = [
            ['id' => 1, 'name' => 'Test Option', 'children' => []],
        ];

        $html = Blade::render('<x-cascader :options="$options" />', [
            'options' => $options,
        ]);

        expect($html)->toContain('Test Option');
    });

    it('handles nested options', function () {
        $options = sampleOptions();

        $html = Blade::render('<x-cascader :options="$options" />', [
            'options' => $options,
        ]);

        expect($html)->toContain('Electronics')
            ->and($html)->toContain('Phones');
    });

    it('resolves icons in options', function () {
        $options = [
            ['id' => 1, 'name' => 'Home', 'icon' => 'home', 'children' => []],
        ];

        $html = Blade::render('<x-cascader :options="$options" />', [
            'options' => $options,
        ]);

        expect($html)->toContain('iconHtml');
    });
});

describe('Custom Field Names', function () {
    it('accepts custom value field', function () {
        $html = Blade::render('<x-cascader :options="[]" value-field="slug" />');

        expect($html)->toContain("valueField: 'slug'")
            ->or->toContain('valueField: "slug"');
    });

    it('accepts custom label field', function () {
        $html = Blade::render('<x-cascader :options="[]" label-field="title" />');

        expect($html)->toContain("labelField: 'title'")
            ->or->toContain('labelField: "title"');
    });
});

describe('Dialog Elements', function () {
    it('renders desktop dialog', function () {
        $html = Blade::render('<x-cascader :options="[]" />');

        expect($html)->toContain('x-ref="desktopDialog"')
            ->and($html)->toContain('<dialog');
    });

    it('renders mobile dialog', function () {
        $html = Blade::render('<x-cascader :options="[]" />');

        expect($html)->toContain('x-ref="mobileDialog"');
    });

    it('renders search input in desktop dialog', function () {
        $html = Blade::render('<x-cascader :options="[]" />');

        expect($html)->toContain('x-ref="searchInput"')
            ->and($html)->toContain('x-model="search"');
    });
});

describe('Dark Mode Support', function () {
    it('includes dark mode classes', function () {
        $html = Blade::render('<x-cascader :options="[]" />');

        expect($html)->toContain('dark:bg-')
            ->and($html)->toContain('dark:text-')
            ->and($html)->toContain('dark:border-');
    });
});

describe('Multi-Level Column Rendering', function () {
    it('renders dynamic columns template', function () {
        $html = Blade::render('<x-cascader :options="[]" />');

        expect($html)->toContain('columnCount')
            ->and($html)->toContain('getOptionsForLevel');
    });

    it('includes level navigation logic', function () {
        $html = Blade::render('<x-cascader :options="[]" />');

        expect($html)->toContain('navigateToOption')
            ->and($html)->toContain('isOptionActive');
    });
});

describe('Mobile Breadcrumb Navigation', function () {
    it('renders breadcrumb navigation', function () {
        $html = Blade::render('<x-cascader :options="[]" />');

        expect($html)->toContain('mobilePath')
            ->and($html)->toContain('mobileGoToLevel');
    });

    it('renders All button for root navigation', function () {
        $html = Blade::render('<x-cascader :options="[]" />');

        expect($html)->toContain('>All</button>')
            ->or->toContain('>All<');
    });
});
