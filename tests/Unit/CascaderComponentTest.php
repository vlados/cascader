<?php

use Vlados\Cascader\Components\Cascader;
use Vlados\Cascader\IconResolver;

beforeEach(function () {
    // Reset to default FontAwesome resolver
    IconResolver::useFontAwesome();
});

describe('Cascader Component Initialization', function () {
    it('can be instantiated with default values', function () {
        $cascader = new Cascader();

        expect($cascader->options)->toBe([])
            ->and($cascader->valueField)->toBe('id')
            ->and($cascader->labelField)->toBe('name')
            ->and($cascader->clearable)->toBeFalse()
            ->and($cascader->multiple)->toBeFalse()
            ->and($cascader->size)->toBe('sm');
    });

    it('accepts custom options', function () {
        $options = sampleOptions();
        $cascader = new Cascader(options: $options);

        expect($cascader->resolvedOptions)->toHaveCount(3);
    });

    it('accepts custom value and label fields', function () {
        $cascader = new Cascader(
            valueField: 'slug',
            labelField: 'title'
        );

        expect($cascader->valueField)->toBe('slug')
            ->and($cascader->labelField)->toBe('title');
    });

    it('accepts multiple mode flag', function () {
        $cascader = new Cascader(multiple: true);

        expect($cascader->multiple)->toBeTrue();
    });

    it('accepts size variants', function () {
        $cascaderSm = new Cascader(size: 'sm');
        $cascaderXs = new Cascader(size: 'xs');

        expect($cascaderSm->size)->toBe('sm')
            ->and($cascaderXs->size)->toBe('xs');
    });

    it('accepts clearable flag', function () {
        $cascader = new Cascader(clearable: true);

        expect($cascader->clearable)->toBeTrue();
    });
});

describe('Icon Resolution', function () {
    it('resolves icons for parent options', function () {
        $options = [
            ['id' => 1, 'name' => 'Test', 'icon' => 'home', 'children' => []],
        ];

        $cascader = new Cascader(options: $options);

        expect($cascader->resolvedOptions[0])->toHaveKey('iconHtml')
            ->and($cascader->resolvedOptions[0]['iconHtml'])->toContain('fa-home');
    });

    it('resolves icons for child options', function () {
        $options = [
            [
                'id' => 1,
                'name' => 'Parent',
                'icon' => 'folder',
                'children' => [
                    ['id' => 11, 'name' => 'Child', 'icon' => 'file'],
                ],
            ],
        ];

        $cascader = new Cascader(options: $options);

        expect($cascader->resolvedOptions[0]['children'][0])->toHaveKey('iconHtml')
            ->and($cascader->resolvedOptions[0]['children'][0]['iconHtml'])->toContain('fa-file');
    });

    it('resolves icons recursively for deep nesting', function () {
        $options = deepNestedOptions();
        $options[0]['icon'] = 'level1';
        $options[0]['children'][0]['icon'] = 'level2';
        $options[0]['children'][0]['children'][0]['icon'] = 'level3';
        $options[0]['children'][0]['children'][0]['children'][0]['icon'] = 'level4';
        $options[0]['children'][0]['children'][0]['children'][0]['children'][0]['icon'] = 'level5';

        $cascader = new Cascader(options: $options);

        // Check each level has iconHtml
        expect($cascader->resolvedOptions[0])->toHaveKey('iconHtml');

        $level2 = $cascader->resolvedOptions[0]['children'][0];
        expect($level2)->toHaveKey('iconHtml');

        $level3 = $level2['children'][0];
        expect($level3)->toHaveKey('iconHtml');

        $level4 = $level3['children'][0];
        expect($level4)->toHaveKey('iconHtml');

        $level5 = $level4['children'][0];
        expect($level5)->toHaveKey('iconHtml');
    });

    it('handles options without icons', function () {
        $options = [
            ['id' => 1, 'name' => 'No Icon', 'children' => []],
        ];

        $cascader = new Cascader(options: $options);

        expect($cascader->resolvedOptions[0])->not->toHaveKey('iconHtml');
    });

    it('preserves color in icon resolution', function () {
        $options = [
            ['id' => 1, 'name' => 'Test', 'icon' => 'star', 'color' => '#FF0000', 'children' => []],
        ];

        $cascader = new Cascader(options: $options);

        expect($cascader->resolvedOptions[0]['color'])->toBe('#FF0000');
    });
});

describe('Options Structure', function () {
    it('preserves all option properties', function () {
        $options = [
            [
                'id' => 1,
                'name' => 'Test',
                'slug' => 'test-slug',
                'customField' => 'custom-value',
                'children' => [],
            ],
        ];

        $cascader = new Cascader(options: $options);

        expect($cascader->resolvedOptions[0]['id'])->toBe(1)
            ->and($cascader->resolvedOptions[0]['name'])->toBe('Test')
            ->and($cascader->resolvedOptions[0]['slug'])->toBe('test-slug')
            ->and($cascader->resolvedOptions[0]['customField'])->toBe('custom-value');
    });

    it('handles empty children array', function () {
        $options = [
            ['id' => 1, 'name' => 'Leaf', 'children' => []],
        ];

        $cascader = new Cascader(options: $options);

        expect($cascader->resolvedOptions[0]['children'])->toBe([]);
    });

    it('handles options without children key', function () {
        $options = [
            ['id' => 1, 'name' => 'No Children Key'],
        ];

        $cascader = new Cascader(options: $options);

        expect($cascader->resolvedOptions[0])->not->toHaveKey('children');
    });

    it('handles deeply nested structure', function () {
        $options = deepNestedOptions();

        $cascader = new Cascader(options: $options);

        // Navigate to the deepest level
        $level1 = $cascader->resolvedOptions[0];
        $level2 = $level1['children'][0];
        $level3 = $level2['children'][0];
        $level4 = $level3['children'][0];
        $level5 = $level4['children'][0];

        expect($level5['name'])->toBe('Level 5 Leaf')
            ->and($level5['id'])->toBe(5);
    });

    it('handles multiple root options', function () {
        $options = sampleOptions();

        $cascader = new Cascader(options: $options);

        expect($cascader->resolvedOptions)->toHaveCount(3)
            ->and($cascader->resolvedOptions[0]['name'])->toBe('Electronics')
            ->and($cascader->resolvedOptions[1]['name'])->toBe('Clothing')
            ->and($cascader->resolvedOptions[2]['name'])->toBe('Other');
    });
});

describe('Render Method', function () {
    it('returns a view instance', function () {
        $cascader = new Cascader();
        $view = $cascader->render();

        expect($view)->toBeInstanceOf(\Illuminate\Contracts\View\View::class);
    });

    it('uses the correct view name', function () {
        $cascader = new Cascader();
        $view = $cascader->render();

        expect($view->name())->toBe('cascader::cascader');
    });
});
