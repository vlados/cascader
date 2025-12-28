<?php

namespace Vlados\Cascader\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Cascader extends Component
{
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
    }

    public function render(): View
    {
        return view('cascader::cascader');
    }
}
