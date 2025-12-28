<?php

namespace Vlados\Cascader\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Vlados\Cascader\IconResolver;

class Cascader extends Component
{
    public array $resolvedOptions = [];

    public function __construct(
        public array $options = [],
        public ?string $wireModel = null,
        public ?string $placeholder = null,
        public ?string $selectedText = null,
        public string $valueField = 'id',
        public string $labelField = 'name',
        public ?string $searchPlaceholder = null,
        public bool $clearable = false,
        public ?string $cancelText = null,
        public ?string $confirmText = null,
    ) {
        $this->resolvedOptions = $this->resolveIcons($options);
    }

    /**
     * Process options and resolve icons to HTML.
     */
    protected function resolveIcons(array $options): array
    {
        return array_map(function ($option) {
            // Resolve parent icon
            if (!empty($option['icon'])) {
                $option['iconHtml'] = IconResolver::resolve(
                    $option['icon'],
                    $option['color'] ?? null,
                    'sm'
                );
            }

            // Resolve children icons
            if (!empty($option['children'])) {
                $option['children'] = array_map(function ($child) {
                    if (!empty($child['icon'])) {
                        $child['iconHtml'] = IconResolver::resolve(
                            $child['icon'],
                            $child['color'] ?? null,
                            'sm'
                        );
                    }
                    return $child;
                }, $option['children']);
            }

            return $option;
        }, $options);
    }

    public function render(): View
    {
        return view('cascader::cascader');
    }
}
