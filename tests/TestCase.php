<?php

namespace Vlados\Cascader\Tests;

use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Vlados\Cascader\CascaderServiceProvider;
use Vlados\Cascader\IconResolver;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        IconResolver::reset();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            CascaderServiceProvider::class,
        ];
    }

    /**
     * Render and decode entities — attribute values are entity-encoded in the
     * html source but the browser decodes them, so assertions compare against
     * what Alpine actually evaluates.
     */
    protected function renderDecoded(string $template, array $data = []): string
    {
        return html_entity_decode(
            \Illuminate\Support\Facades\Blade::render($template, $data),
            ENT_QUOTES
        );
    }

    protected function sampleOptions(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Electronics',
                'icon' => 'laptop',
                'color' => '#3B82F6',
                'children' => [
                    ['id' => 11, 'name' => 'Phones', 'icon' => 'mobile', 'color' => '#3B82F6'],
                    ['id' => 12, 'name' => 'Tablets'],
                ],
            ],
            [
                'id' => 3,
                'name' => 'Other',
                'children' => [],
            ],
        ];
    }
}
