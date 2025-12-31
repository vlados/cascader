@props([
    'resolvedOptions' => [],
    'wireModel' => null,
    'placeholder' => 'Select...',
    'selectedText' => null,
    'valueField' => 'id',
    'labelField' => 'name',
    'searchPlaceholder' => 'Search...',
    'clearable' => false,
    'cancelText' => 'Cancel',
    'confirmText' => 'Confirm',
    'size' => 'sm',
    'multiple' => false,
])

@php
    // Match Flux UI select styling
    $sizeClasses = match($size) {
        'xs' => 'h-8 ps-2.5 pe-8 py-1.5 text-sm',
        default => 'h-10 ps-3 pe-10 py-2 text-base sm:text-sm leading-[1.375rem]',
    };
    $iconSize = match($size) {
        'xs' => 'size-4',
        default => 'size-5',
    };
    $clearButtonPosition = match($size) {
        'xs' => 'right-6',
        default => 'right-8',
    };

    $wireModelAttribute = $attributes->wire('model');
    $entangleExpression = $wireModelAttribute->value()
        ? $wireModelAttribute->directive() === 'wire:model.live'
            ? "\$wire.entangle('{$wireModelAttribute->value()}').live"
            : "\$wire.entangle('{$wireModelAttribute->value()}')"
        : 'null';
@endphp

<div
    x-data="cascader({
        options: {{ Js::from($resolvedOptions) }},
        selectedValue: {{ $entangleExpression }},
        valueField: {{ Js::from($valueField) }},
        labelField: {{ Js::from($labelField) }},
        multiple: {{ Js::from($multiple) }}
    })"
    x-ref="cascaderRoot"
    {{ $attributes->except(['wire:model', 'wire:model.live', 'wire:model.blur', 'wire:model.debounce'])->merge(['class' => 'relative']) }}
>
    {{-- Trigger Button --}}
    <div class="relative">
        <button
            type="button"
            @click="openCascader()"
            class="w-full flex items-center justify-between {{ $sizeClasses }} text-left bg-white dark:bg-white/10 border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 rounded-lg shadow-xs text-zinc-700 dark:text-zinc-300 hover:border-zinc-300 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:border-transparent transition-colors"
            :class="{ 'border-zinc-400 dark:border-white/20': open }"
        >
            <span x-show="!selectedText" class="text-zinc-400 dark:text-zinc-400">{{ $placeholder }}</span>
            <span x-show="selectedText" x-text="selectedText" class="text-zinc-700 dark:text-zinc-300 truncate @if($clearable) pr-6 @endif"></span>
            <svg class="{{ $iconSize }} text-zinc-400 shrink-0 absolute right-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        @if($clearable)
            <button
                type="button"
                x-show="selectedValue && (multiple ? selectedValue.length > 0 : true)"
                @click.stop="clear()"
                class="absolute {{ $clearButtonPosition }} top-1/2 -translate-y-1/2 p-1 text-zinc-400 hover:text-zinc-600 transition-colors"
                x-cloak
            >
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        @endif
    </div>

    {{-- Desktop Dropdown (using dialog for top-layer stacking) --}}
    <dialog
        x-ref="desktopDialog"
        @close="open = false; search = '';"
        @click="if ($event.target === $el) { $el.close(); }"
        class="m-0 p-0 border-0 bg-transparent overflow-visible backdrop:bg-transparent"
        :style="`position: fixed; top: ${dropdownPosition.top}px; left: ${dropdownPosition.left}px; min-width: ${dropdownPosition.width}px;`"
    >
        <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg overflow-hidden">
            {{-- Search Input --}}
            <div class="p-2 border-b border-zinc-100 dark:border-zinc-700">
                <div class="relative">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input
                        type="text"
                        x-model="search"
                        x-ref="searchInput"
                        @keydown.escape.stop="if (search) { search = ''; } else { $refs.desktopDialog.close(); }"
                        @keydown.enter.prevent
                        placeholder="{{ $searchPlaceholder }}"
                        class="w-full pl-8 pr-8 py-1.5 text-sm border border-zinc-200 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 rounded-md focus:outline-none focus:ring-1 focus:ring-zinc-400 focus:border-zinc-400"
                    />
                    <button
                        type="button"
                        x-show="search.length > 0"
                        @click="search = ''; $refs.searchInput.focus()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600"
                    >
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Search Results View --}}
            <div x-show="isSearching" class="max-h-72 overflow-y-auto">
                <template x-if="searchResults.length === 0">
                    <div class="px-3 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                        No results found
                    </div>
                </template>
                <template x-for="result in searchResults" :key="getValue(result) + '-search'">
                    <button
                        type="button"
                        @click="selectSearchResult(result)"
                        class="w-full flex items-center gap-2 px-3 py-2.5 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                        :class="{
                            'bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 font-medium': isOptionSelected(result),
                            'text-zinc-700 dark:text-zinc-300': !isOptionSelected(result)
                        }"
                    >
                        @if($multiple)
                            {{-- Checkbox --}}
                            <span class="flex items-center justify-center size-4 border rounded shrink-0 transition-colors"
                                :class="isOptionSelected(result) ? 'bg-teal-500 border-teal-500' : 'border-zinc-300 dark:border-zinc-500'">
                                <svg x-show="isOptionSelected(result)" class="size-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </span>
                        @endif
                        <template x-if="result.iconHtml">
                            <span
                                class="inline-flex items-center justify-center size-6 rounded-full shrink-0"
                                :style="'background-color: ' + (result.color || '#6B7280') + '20'"
                                x-html="result.iconHtml"
                            ></span>
                        </template>
                        <span class="truncate" x-text="result._pathLabels.join(' / ')"></span>
                        @if(!$multiple)
                            <svg x-show="isOptionSelected(result)" class="size-4 text-teal-600 dark:text-teal-400 ml-auto shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </button>
                </template>
            </div>

            {{-- Normal Cascader View (Multi-Level Columns) --}}
            <div x-show="!isSearching" class="flex max-h-72 overflow-x-auto">
                {{-- Dynamic columns based on navigation depth --}}
                <template x-for="(level, levelIndex) in columnCount" :key="'level-' + levelIndex">
                    <div
                        class="min-w-[180px] max-h-72 overflow-y-auto border-r border-zinc-100 dark:border-zinc-700 last:border-r-0 shrink-0"
                        x-show="getOptionsForLevel(levelIndex).length > 0"
                    >
                        <template x-for="option in getOptionsForLevel(levelIndex)" :key="getValue(option)">
                            <button
                                type="button"
                                @click="multiple ? (hasChildren(option) ? navigateToOption(option, levelIndex) : toggleCheckbox(option, levelIndex)) : navigateToOption(option, levelIndex)"
                                @mouseenter="hasChildren(option) ? navigateToOption(option, levelIndex) : null"
                                class="w-full flex items-center justify-between gap-3 px-3 py-2.5 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors whitespace-nowrap"
                                :class="{
                                    'bg-zinc-100 dark:bg-zinc-700 font-medium': isOptionActive(option, levelIndex),
                                    'bg-teal-50 dark:bg-teal-900/30': !multiple && isOptionSelected(option),
                                    'text-zinc-900 dark:text-zinc-100': true,
                                    'cursor-default': hasChildren(option),
                                    'cursor-pointer': !hasChildren(option)
                                }"
                            >
                                <span class="flex items-center gap-2">
                                    @if($multiple)
                                        {{-- Checkbox (only for leaf nodes) --}}
                                        <template x-if="!hasChildren(option)">
                                            <span class="flex items-center justify-center size-4 border rounded shrink-0 transition-colors"
                                                :class="isOptionSelected(option) ? 'bg-teal-500 border-teal-500' : 'border-zinc-300 dark:border-zinc-500'">
                                                <svg x-show="isOptionSelected(option)" class="size-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </template>
                                    @endif
                                    <template x-if="option.iconHtml">
                                        <span
                                            class="inline-flex items-center justify-center size-6 rounded-full shrink-0"
                                            :style="'background-color: ' + (option.color || '#6B7280') + '20'"
                                            x-html="option.iconHtml"
                                        ></span>
                                    </template>
                                    <span x-text="getLabel(option)"></span>
                                </span>
                                {{-- Arrow for parent nodes --}}
                                <svg x-show="hasChildren(option)" class="size-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                                {{-- Checkmark for selected leaf (single-select mode) --}}
                                @if(!$multiple)
                                    <svg x-show="!hasChildren(option) && isOptionSelected(option)" class="size-4 text-teal-600 dark:text-teal-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </dialog>

    {{-- Mobile Bottom Sheet (using dialog for top-layer stacking) --}}
    <dialog
        x-ref="mobileDialog"
        @close="open = false;"
        class="m-0 p-0 border-0 bg-transparent fixed inset-0 w-full h-full max-w-none max-h-none backdrop:bg-black/50"
    >
        <div class="fixed inset-0 flex items-end" @click.self="mobileCancel()">
            {{-- Bottom Sheet --}}
            <div
                x-show="open && isMobile"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="w-full bg-white dark:bg-zinc-800 rounded-t-2xl max-h-[70vh] flex flex-col"
            >
                {{-- Header with Cancel/Confirm --}}
                <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <button
                        type="button"
                        @click="mobileCancel()"
                        class="inline-flex items-center justify-center h-9 px-4 text-sm font-medium rounded-md text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                    >
                        {{ $cancelText }}
                    </button>
                    @if($multiple)
                        <span class="text-sm text-zinc-500 dark:text-zinc-400" x-show="tempSelectedValue && tempSelectedValue.length > 0" x-text="tempSelectedValue.length + ' selected'"></span>
                    @endif
                    <button
                        type="button"
                        @click="mobileConfirm()"
                        class="inline-flex items-center justify-center h-9 px-4 text-sm font-medium rounded-md transition-colors"
                        :class="(multiple ? (tempSelectedValue && tempSelectedValue.length > 0) : tempSelectedValue) ? 'bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500 cursor-not-allowed'"
                        :disabled="multiple ? (!tempSelectedValue || tempSelectedValue.length === 0) : !tempSelectedValue"
                    >
                        {{ $confirmText }}
                    </button>
                </div>

                {{-- Breadcrumb Navigation --}}
                <div class="flex items-center gap-1 px-4 py-2 border-b border-zinc-100 dark:border-zinc-700 overflow-x-auto" x-show="mobilePath.length > 0">
                    {{-- Root button --}}
                    <button
                        type="button"
                        @click="mobileGoToLevel(0)"
                        class="shrink-0 px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                        :class="mobileLevel === 0 ? 'text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-900/30' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
                    >
                        All
                    </button>
                    {{-- Path breadcrumbs --}}
                    <template x-for="(pathItem, pathIndex) in mobilePath" :key="getValue(pathItem)">
                        <div class="flex items-center gap-1">
                            <svg class="size-4 text-zinc-300 dark:text-zinc-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            <button
                                type="button"
                                @click="mobileGoToLevel(pathIndex + 1)"
                                class="shrink-0 px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                                :class="mobileLevel === pathIndex + 1 ? 'text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-900/30' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
                                x-text="getLabel(pathItem)"
                            ></button>
                        </div>
                    </template>
                </div>

                {{-- Options List --}}
                <div class="flex-1 overflow-y-auto">
                    <template x-for="option in mobileOptions" :key="getValue(option)">
                        <button
                            type="button"
                            @click="mobileSelectOption(option)"
                            class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors border-b border-zinc-100 dark:border-zinc-700"
                            :class="{
                                'bg-teal-50 dark:bg-teal-900/30': isMobileTempSelected(option),
                                'text-zinc-900 dark:text-zinc-100': true
                            }"
                        >
                            <span class="flex items-center gap-3">
                                @if($multiple)
                                    {{-- Checkbox (only for leaf nodes) --}}
                                    <template x-if="!hasChildren(option)">
                                        <span class="flex items-center justify-center size-5 border rounded shrink-0 transition-colors"
                                            :class="isMobileTempSelected(option) ? 'bg-teal-500 border-teal-500' : 'border-zinc-300 dark:border-zinc-500'">
                                            <svg x-show="isMobileTempSelected(option)" class="size-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                    </template>
                                @endif
                                <template x-if="option.iconHtml">
                                    <span
                                        class="inline-flex items-center justify-center size-8 rounded-full shrink-0"
                                        :style="'background-color: ' + (option.color || '#6B7280') + '20'"
                                        x-html="option.iconHtml"
                                    ></span>
                                </template>
                                <span class="text-base" x-text="getLabel(option)"></span>
                            </span>
                            {{-- Arrow for parent nodes --}}
                            <template x-if="hasChildren(option)">
                                <svg class="size-5 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </template>
                            {{-- Checkmark for selected leaf (single-select mode) --}}
                            @if(!$multiple)
                                <template x-if="!hasChildren(option) && isMobileTempSelected(option)">
                                    <svg class="size-5 text-teal-600 dark:text-teal-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </template>
                            @endif
                        </button>
                    </template>
                </div>

                {{-- Safe area padding for iOS --}}
                <div class="h-safe-area-inset-bottom bg-white dark:bg-zinc-800"></div>
            </div>
        </div>
    </dialog>
</div>
