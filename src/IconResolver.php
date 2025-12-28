<?php

namespace Vlados\Cascader;

use Closure;
use Illuminate\Support\Facades\Blade;

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
     */
    public static function useFontAwesome(string $style = 'solid'): void
    {
        static::$resolver = function (string $icon, ?string $color = null, string $size = 'sm') use ($style) {
            $colorStyle = $color ? "color: {$color}" : '';
            return '<i class="fa-' . $style . ' fa-' . e($icon) . ' text-' . $size . '" style="' . $colorStyle . '"></i>';
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
            $colorStyle = $color ? "color: {$color}" : '';
            $prefix = $style === 'solid' ? 'heroicon-s' : 'heroicon-o';

            return Blade::render('<x-' . $prefix . '-' . $icon . ' class="' . $sizeClass . '" style="' . $colorStyle . '" />');
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
            $colorStyle = $color ? "color: {$color}" : '';

            return Blade::render('<x-' . $icon . ' class="' . $sizeClass . '" style="' . $colorStyle . '" />');
        };
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
