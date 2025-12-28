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
            $colorStyle = $color ? "color: {$color}" : '';
            return '<i class="fa-' . $style . ' fa-' . e($icon) . ' text-' . $size . '" style="' . $colorStyle . '"></i>';
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
            $sizeClass = match ($size) {
                'xs' => 'size-3',
                'sm' => 'size-4',
                'md' => 'size-5',
                'lg' => 'size-6',
                default => 'size-4',
            };
            $colorStyle = $color ? "style=\"color: {$color}\"" : '';
            $component = $style . '-' . $icon;

            return static::renderBladeComponent($component, $sizeClass, $colorStyle, $icon);
        };
    }

    /**
     * Use Heroicons via Blade components.
     * Expects icon names like 'home', 'user', 'cog'.
     */
    public static function useHeroicons(string $style = 'solid'): void
    {
        static::$resolver = function (string $icon, ?string $color = null, string $size = 'sm') use ($style) {
            $sizeClass = match ($size) {
                'xs' => 'size-3',
                'sm' => 'size-4',
                'md' => 'size-5',
                'lg' => 'size-6',
                default => 'size-4',
            };
            $colorStyle = $color ? "style=\"color: {$color}\"" : '';
            $prefix = $style === 'solid' ? 'heroicon-s' : 'heroicon-o';
            $component = $prefix . '-' . $icon;

            return static::renderBladeComponent($component, $sizeClass, $colorStyle, $icon);
        };
    }

    /**
     * Use Blade UI Kit icons.
     * Expects icon names like 'fas-laptop', 'heroicon-o-home'.
     */
    public static function useBladeIcons(): void
    {
        static::$resolver = function (string $icon, ?string $color = null, string $size = 'sm') {
            $sizeClass = match ($size) {
                'xs' => 'size-3',
                'sm' => 'size-4',
                'md' => 'size-5',
                'lg' => 'size-6',
                default => 'size-4',
            };
            $colorStyle = $color ? "style=\"color: {$color}\"" : '';

            return static::renderBladeComponent($icon, $sizeClass, $colorStyle, $icon);
        };
    }

    /**
     * Render a Blade component with proper error handling.
     */
    protected static function renderBladeComponent(string $component, string $sizeClass, string $colorStyle, string $originalIcon): string
    {
        try {
            return Blade::render('<x-' . $component . ' class="' . $sizeClass . '" ' . $colorStyle . ' />');
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
    }
}
