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
        public ?string $wireModel = null, // Deprecated: use wire:model instead
        public ?string $placeholder = null,
        public ?string $selectedText = null,
        public string $valueField = 'id',
        public string $labelField = 'name',
        public ?string $searchPlaceholder = null,
        public bool $clearable = false,
        public ?string $cancelText = null,
        public ?string $confirmText = null,
        public string $size = 'sm', // sm (default) or xs
        public bool $multiple = false, // Enable multi-select with checkboxes
    ) {
        $this->resolvedOptions = $this->resolveIcons($options);
    }

    /**
     * Process options and resolve icons to HTML recursively for unlimited levels.
     */
    protected function resolveIcons(array $options): array
    {
        return array_map(function ($option) {
            // Resolve icon for this option
            if (!empty($option['icon'])) {
                $option['iconHtml'] = IconResolver::resolve(
                    $option['icon'],
                    $option['color'] ?? null,
                    'sm'
                );
            }

            // Recursively resolve children icons (supports unlimited depth)
            if (!empty($option['children'])) {
                $option['children'] = $this->resolveIcons($option['children']);
            }

            return $option;
        }, $options);
    }

    public function render(): View
    {
        return view('cascader::cascader');
    }
}
