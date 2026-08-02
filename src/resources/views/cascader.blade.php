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
    $entangleExpression = 'null';

    if ($wireModelAttribute->value()) {
        $entangleExpression = $wireModelAttribute->hasModifier('live')
            ? "\$wire.entangle('{$wireModelAttribute->value()}').live"
            : "\$wire.entangle('{$wireModelAttribute->value()}')";
    } elseif ($wireModel) {
        // Legacy wire-model prop (deprecated) — kept working for back-compat.
        $entangleExpression = "\$wire.entangle('{$wireModel}')";
    }
@endphp

<div
    x-data="cascader({
        options: {{ Js::from($resolvedOptions) }},
        modelValue: {{ $entangleExpression }},
        initialText: {{ Js::from($selectedText) }},
        valueField: {{ Js::from($valueField) }},
        labelField: {{ Js::from($labelField) }}
    })"
    x-ref="cascaderRoot"
    {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'relative']) }}
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
                x-show="modelValue"
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
        @close="onDesktopDialogClose()"
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
                        @keydown.escape.prevent.stop="if (search) { search = ''; } else { $refs.desktopDialog.close(); }"
                        @keydown.enter.prevent
                        placeholder="{{ $searchPlaceholder }}"
                        class="w-full pl-8 pr-8 py-1.5 text-sm bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 placeholder-zinc-400 dark:placeholder-zinc-500 border border-zinc-200 dark:border-zinc-600 rounded-md focus:outline-none focus:ring-1 focus:ring-zinc-400 dark:focus:ring-zinc-500 focus:border-zinc-400 dark:focus:border-zinc-500"
                    />
                    <button
                        type="button"
                        x-show="search.length > 0"
                        @click="search = ''; $refs.searchInput.focus()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
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
                <template x-for="result in searchResults" :key="(result._isParent ? 'p:' : 'c:' + result._parentLabel + ':') + getValue(result)">
                    <button
                        type="button"
                        @click="selectSearchResult(result)"
                        class="w-full flex items-center gap-2 px-3 py-2.5 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors"
                        :class="{
                            'bg-teal-50 dark:bg-teal-500/10 text-teal-700 dark:text-teal-400 font-medium': modelValue === getValue(result),
                            'text-zinc-700 dark:text-zinc-300': modelValue !== getValue(result)
                        }"
                    >
                        <template x-if="result.iconHtml">
                            <span
                                class="inline-flex items-center justify-center size-6 rounded-full shrink-0"
                                :style="{ backgroundColor: iconBackground(result.color) }"
                                x-html="result.iconHtml"
                            ></span>
                        </template>
                        <span class="truncate" x-text="result._parentLabel ? result._parentLabel + ' / ' + getLabel(result) : getLabel(result)"></span>
                        <svg x-show="modelValue === getValue(result)" class="size-4 text-teal-600 dark:text-teal-400 ml-auto shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </template>
            </div>

            {{-- Normal Cascader View --}}
            <div x-show="!isSearching" class="flex">
                {{-- Parent Options Column --}}
                <div class="min-w-max max-h-72 overflow-y-auto border-r border-zinc-100 dark:border-zinc-700">
                    <template x-for="parent in options" :key="getValue(parent)">
                        <button
                            type="button"
                            @click="selectParent(parent)"
                            @mouseenter="hoverParent(parent)"
                            class="w-full flex items-center justify-between gap-3 px-3 py-2.5 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors whitespace-nowrap"
                            :class="{
                                'bg-zinc-100 dark:bg-zinc-700 font-medium': getValue(hoveredParent) === getValue(parent) || (!hoveredParent && selectedParentValue === getValue(parent)),
                                'text-zinc-900 dark:text-zinc-100': true,
                                'cursor-default': parent.children && parent.children.length > 0,
                                'cursor-pointer': !parent.children || parent.children.length === 0
                            }"
                        >
                            <span class="flex items-center gap-2">
                                <template x-if="parent.iconHtml">
                                    <span
                                        class="inline-flex items-center justify-center size-6 rounded-full shrink-0"
                                        :style="{ backgroundColor: iconBackground(parent.color) }"
                                        x-html="parent.iconHtml"
                                    ></span>
                                </template>
                                <span x-text="getLabel(parent)"></span>
                            </span>
                            <svg x-show="parent.children && parent.children.length > 0" class="size-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </template>
                </div>

                {{-- Child Options Column --}}
                <div
                    x-show="currentChildren.length > 0"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    class="max-h-72 overflow-y-auto flex-1"
                >
                    <div class="min-w-max">
                        <template x-for="child in currentChildren" :key="getValue(child)">
                            <button
                                type="button"
                                @click="selectChild(child)"
                                class="w-full flex items-center gap-2 px-3 py-2.5 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors whitespace-nowrap"
                                :class="{
                                    'bg-teal-50 dark:bg-teal-500/10 text-teal-700 dark:text-teal-400 font-medium': modelValue === getValue(child),
                                    'text-zinc-700 dark:text-zinc-300': modelValue !== getValue(child)
                                }"
                            >
                                <template x-if="child.iconHtml">
                                    <span
                                        class="inline-flex items-center justify-center size-6 rounded-full shrink-0"
                                        :style="{ backgroundColor: iconBackground(child.color) }"
                                        x-html="child.iconHtml"
                                    ></span>
                                </template>
                                <span x-text="getLabel(child)"></span>
                                <svg x-show="modelValue === getValue(child)" class="size-4 text-teal-600 dark:text-teal-400 ml-auto shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>
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
                    <button
                        type="button"
                        @click="mobileConfirm()"
                        class="inline-flex items-center justify-center h-9 px-4 text-sm font-medium rounded-md transition-colors"
                        :class="hasTempSelection ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500 cursor-not-allowed'"
                        :disabled="!hasTempSelection"
                    >
                        {{ $confirmText }}
                    </button>
                </div>

                {{-- Tabs / Breadcrumb --}}
                <div class="flex items-center gap-1 px-4 py-2 border-b border-zinc-100 dark:border-zinc-700 overflow-x-auto">
                    {{-- Parent tab (when viewing children) --}}
                    <template x-if="mobileSelectedParent">
                        <button
                            type="button"
                            @click="mobileGoToParents()"
                            class="shrink-0 px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                            :class="{
                                'text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-500/10': mobileLevel === 0,
                                'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200': mobileLevel !== 0
                            }"
                            x-text="getLabel(mobileSelectedParent)"
                        ></button>
                    </template>
                    {{-- Child tab (when child is selected) --}}
                    <template x-if="mobileSelectedParent && hasTempSelection && tempSelectedValue !== getValue(mobileSelectedParent)">
                        <div class="flex items-center gap-1">
                            <svg class="size-4 text-zinc-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            <span
                                class="shrink-0 px-3 py-1.5 text-sm font-medium rounded-md text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-500/10"
                                x-text="mobileSelectedChildLabel"
                            ></span>
                        </div>
                    </template>
                </div>

                {{-- Options List --}}
                <div class="flex-1 overflow-y-auto">
                    {{-- Level 0: Parents --}}
                    <div x-show="mobileLevel === 0">
                        <template x-for="parent in options" :key="getValue(parent)">
                            <button
                                type="button"
                                @click="mobileSelectParent(parent)"
                                class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors border-b border-zinc-100 dark:border-zinc-700"
                                :class="{
                                    'bg-teal-50 dark:bg-teal-500/10': tempSelectedValue === getValue(parent),
                                    'text-zinc-900 dark:text-zinc-100': true
                                }"
                            >
                                <span class="flex items-center gap-3">
                                    <template x-if="parent.iconHtml">
                                        <span
                                            class="inline-flex items-center justify-center size-8 rounded-full shrink-0"
                                            :style="{ backgroundColor: iconBackground(parent.color) }"
                                            x-html="parent.iconHtml"
                                        ></span>
                                    </template>
                                    <span class="text-base" x-text="getLabel(parent)"></span>
                                </span>
                                <template x-if="parent.children && parent.children.length > 0">
                                    <svg class="size-5 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </template>
                                <template x-if="(!parent.children || parent.children.length === 0) && tempSelectedValue === getValue(parent)">
                                    <svg class="size-5 text-teal-600 dark:text-teal-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </template>
                            </button>
                        </template>
                    </div>

                    {{-- Level 1: Children --}}
                    <div x-show="mobileLevel === 1">
                        <template x-for="child in mobileChildren" :key="getValue(child)">
                            <button
                                type="button"
                                @click="mobileSelectChild(child)"
                                class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors border-b border-zinc-100 dark:border-zinc-700"
                                :class="{
                                    'bg-teal-50 dark:bg-teal-500/10': tempSelectedValue === getValue(child),
                                    'text-zinc-900 dark:text-zinc-100': true
                                }"
                            >
                                <span class="flex items-center gap-3">
                                    <template x-if="child.iconHtml">
                                        <span
                                            class="inline-flex items-center justify-center size-8 rounded-full shrink-0"
                                            :style="{ backgroundColor: iconBackground(child.color) }"
                                            x-html="child.iconHtml"
                                        ></span>
                                    </template>
                                    <span class="text-base" x-text="getLabel(child)"></span>
                                </span>
                                <svg x-show="tempSelectedValue === getValue(child)" class="size-5 text-teal-600 dark:text-teal-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Safe area padding for iOS --}}
                <div class="h-safe-area-inset-bottom bg-white dark:bg-zinc-800"></div>
            </div>
        </div>
    </dialog>
</div>
