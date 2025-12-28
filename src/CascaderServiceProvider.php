<?php

namespace Vlados\Cascader;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class CascaderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'cascader');

        Blade::component('cascader', Components\Cascader::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/resources/views' => resource_path('views/vendor/cascader'),
            ], 'cascader-views');

            $this->publishes([
                __DIR__ . '/resources/js' => resource_path('js/vendor/cascader'),
            ], 'cascader-scripts');
        }
    }
}
