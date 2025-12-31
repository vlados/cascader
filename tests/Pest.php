<?php

use Vlados\Cascader\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeValidHtml', function () {
    return $this->toBeString()
        ->and($this->value)->toContain('<');
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function sampleOptions(): array
{
    return [
        [
            'id' => 1,
            'name' => 'Electronics',
            'icon' => 'laptop',
            'color' => '#3B82F6',
            'children' => [
                [
                    'id' => 11,
                    'name' => 'Phones',
                    'icon' => 'mobile',
                    'color' => '#3B82F6',
                    'children' => [
                        ['id' => 111, 'name' => 'iPhone', 'icon' => 'apple', 'color' => '#3B82F6'],
                        ['id' => 112, 'name' => 'Android', 'icon' => 'robot', 'color' => '#3B82F6'],
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
            'name' => 'Other',
            'icon' => 'question',
            'color' => '#6B7280',
            'children' => [],
        ],
    ];
}

function deepNestedOptions(): array
{
    return [
        [
            'id' => 1,
            'name' => 'Level 1',
            'children' => [
                [
                    'id' => 2,
                    'name' => 'Level 2',
                    'children' => [
                        [
                            'id' => 3,
                            'name' => 'Level 3',
                            'children' => [
                                [
                                    'id' => 4,
                                    'name' => 'Level 4',
                                    'children' => [
                                        ['id' => 5, 'name' => 'Level 5 Leaf'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
}
