# Changelog

All notable changes to this package are documented here. Releases before this file existed (up to v0.4.0) have no changelog entries.

## [Unreleased]

### Fixed

- **`wire:model` binding was completely broken** — the Blade template passed `modelValue` to the Alpine component, which still expected the old `selectedValue`/`initialText` params, so the entangle object was discarded and selections never synced to Livewire. The Alpine component now owns a single `modelValue` (with `selectedValue` as a deprecated alias), derives the trigger label from the options, and follows server-side value changes. ([#1](https://github.com/vlados/cascader/issues/1))
- **Blade injection and XSS in `IconResolver`** — icon names and colors were concatenated into the template string passed to `Blade::render()` and into `style` attributes. Blade components now render through `x-dynamic-component` with values passed as data, icon names are validated, and colors are sanitized. Rendered icons are also memoized per request. ([#2](https://github.com/vlados/cascader/issues/2))
- Escape in the search box no longer closes the whole dropdown — it clears the search first, and only closes when the search is already empty. ([#3](https://github.com/vlados/cascader/issues/3))
- `wire:model` modifier chains (e.g. `wire:model.live.debounce.500ms`) are detected correctly and no longer leak onto the root element. ([#4](https://github.com/vlados/cascader/issues/4))
- Dark mode now covers the desktop dropdown, search input, option columns and the mobile bottom sheet — previously only the trigger button had dark styles. ([#5](https://github.com/vlados/cascader/issues/5))
- Search results are leaf-only: parents with children appear as "Parent / Child" paths instead of being directly selectable, matching the normal view's rules. ([#6](https://github.com/vlados/cascader/issues/6))
- Typing in the search box no longer crashes when an option lacks its label field; `labelField` now takes priority over a hard-coded `label` key; values like `0` can be selected and confirmed. ([#7](https://github.com/vlados/cascader/issues/7))
- Window `resize`/`scroll` listeners are removed on component destroy, fixing a leak with `wire:navigate`. ([#8](https://github.com/vlados/cascader/issues/8))
- Stale hover state after closing the dialog with Escape, invalid icon backgrounds for non-hex colors (now via `color-mix()`), and the wrong dialog remaining open when crossing the mobile breakpoint. ([#9](https://github.com/vlados/cascader/issues/9))

### Changed

- The `selected-text` prop works again and the deprecated `wire-model` prop binds again as a fallback; both had been silently ignored. Prefer the standard `wire:model` attribute. ([#1](https://github.com/vlados/cascader/issues/1))
- The Alpine component registers itself on `alpine:init` when Alpine has not started yet. ([#11](https://github.com/vlados/cascader/issues/11))
- `composer.json` declares `illuminate/view`/`illuminate/contracts`, real author metadata and Packagist keywords. ([#10](https://github.com/vlados/cascader/issues/10))

### Added

- Test suite (orchestra/testbench + PHPUnit) and a GitHub Actions workflow covering PHP 8.2–8.4 with Laravel 11–13. ([#12](https://github.com/vlados/cascader/issues/12))
- This changelog. ([#12](https://github.com/vlados/cascader/issues/12))
- Rewritten README covering the current API, the required Alpine registration step, and updated requirements. ([#11](https://github.com/vlados/cascader/issues/11))
