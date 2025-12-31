<?php

use Illuminate\Support\Facades\Blade;

describe('Service Provider Registration', function () {
    it('registers the cascader blade component', function () {
        $aliases = Blade::getClassComponentAliases();

        expect($aliases)->toHaveKey('cascader');
    });

    it('loads views from the correct namespace', function () {
        $view = view('cascader::cascader');

        expect($view->getPath())->toContain('cascader.blade.php');
    });
});
