<?php

namespace Vlados\Cascader;

use Closure;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use Throwable;

class IconResolver
{
    protected static ?Closure $resolver = null;

    /**
     * Icons already rendered during this request, keyed by component/size/color.
     *
     * @var array<string, string>
     */
    protected static array $rendered = [];

    /**
     * Set a custom icon resolver.
     *
     * The resolver receives the icon name and optional color,
     * and should return the HTML string for the icon.
     */
    public static function using(Closure $resolver): void
    {
        static::$resolver = $resolver;
    }

    /**
     * Use FontAwesome icons (default).
     * Expects icon names like 'laptop', 'user', 'home'.
     * Uses inline <i> tags - no Blade components needed.
     */
    public static function useFontAwesome(string $style = 'solid'): void
    {
        static::$resolver = function (string $icon, ?string $color = null, string $size = 'sm') use ($style) {
            $sizeClass = match ($size) {
                'xs' => 'text-xs',
                'sm' => 'text-sm',
                'md' => 'text-base',
                'lg' => 'text-lg',
                default => 'text-sm',
            };
            $color = static::sanitizeColor($color);
            $colorAttribute = $color ? ' style="color: ' . e($color) . '"' : '';

            return '<i class="fa-' . e($style) . ' fa-' . e($icon) . ' ' . $sizeClass . '"' . $colorAttribute . '></i>';
        };
    }

    /**
     * Use Blade FontAwesome components (blade-fontawesome package).
     * Expects icon names like 'laptop', 'user', 'home'.
     * Renders as <x-fas-laptop />, <x-far-user />, etc.
     */
    public static function useBladeFontAwesome(string $style = 'fas'): void
    {
        static::$resolver = function (string $icon, ?string $color = null, string $size = 'sm') use ($style) {
            return static::renderBladeComponent($style . '-' . $icon, static::sizeClass($size), $color, $icon);
        };
    }

    /**
     * Use Heroicons via Blade components.
     * Expects icon names like 'home', 'user', 'cog'.
     */
    public static function useHeroicons(string $style = 'solid'): void
    {
        static::$resolver = function (string $icon, ?string $color = null, string $size = 'sm') use ($style) {
            $prefix = $style === 'solid' ? 'heroicon-s' : 'heroicon-o';

            return static::renderBladeComponent($prefix . '-' . $icon, static::sizeClass($size), $color, $icon);
        };
    }

    /**
     * Use Blade UI Kit icons.
     * Expects icon names like 'fas-laptop', 'heroicon-o-home'.
     */
    public static function useBladeIcons(): void
    {
        static::$resolver = function (string $icon, ?string $color = null, string $size = 'sm') {
            return static::renderBladeComponent($icon, static::sizeClass($size), $color, $icon);
        };
    }

    protected static function sizeClass(string $size): string
    {
        return match ($size) {
            'xs' => 'size-3',
            'sm' => 'size-4',
            'md' => 'size-5',
            'lg' => 'size-6',
            default => 'size-4',
        };
    }

    /**
     * Colors end up inside a style attribute; anything that could terminate
     * the attribute or the declaration (quotes, semicolons, tags) is rejected.
     */
    protected static function sanitizeColor(?string $color): ?string
    {
        if ($color === null || $color === '') {
            return null;
        }

        return preg_match('/^[a-zA-Z0-9#(),.%\/\s-]+$/', $color) === 1 ? $color : null;
    }

    /**
     * Render a Blade component with proper error handling.
     *
     * The component name, class and style are passed to the template as data —
     * never concatenated into the template source — so option values cannot
     * inject Blade syntax.
     */
    protected static function renderBladeComponent(string $component, string $sizeClass, ?string $color, string $originalIcon): string
    {
        if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]*$/', $component) !== 1) {
            throw new InvalidArgumentException(
                "Cascader: Invalid icon component name '{$component}' (from icon '{$originalIcon}'). " .
                'Icon names may only contain letters, numbers, dots, dashes and underscores.'
            );
        }

        $color = static::sanitizeColor($color);
        $cacheKey = $component . '|' . $sizeClass . '|' . ($color ?? '');

        if (isset(static::$rendered[$cacheKey])) {
            return static::$rendered[$cacheKey];
        }

        try {
            return static::$rendered[$cacheKey] = Blade::render(
                '<x-dynamic-component :component="$component" :class="$class" :style="$style" />',
                [
                    'component' => $component,
                    'class' => $sizeClass,
                    'style' => $color !== null ? "color: {$color}" : false,
                ]
            );
        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                "Cascader: Unable to render icon component '<x-{$component} />'. " .
                "Original icon name: '{$originalIcon}'. " .
                "Make sure the icon exists or configure a different IconResolver. " .
                "Available resolvers: useFontAwesome(), useBladeFontAwesome(), useHeroicons(), useBladeIcons(), or using() for custom.",
                previous: $e
            );
        }
    }

    /**
     * Resolve an icon to HTML.
     */
    public static function resolve(string $icon, ?string $color = null, string $size = 'sm'): string
    {
        if (static::$resolver === null) {
            static::useFontAwesome();
        }

        return (static::$resolver)($icon, $color, $size);
    }

    /**
     * Reset the resolver to default.
     */
    public static function reset(): void
    {
        static::$resolver = null;
        static::$rendered = [];
    }
}
