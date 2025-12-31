<?php

use Vlados\Cascader\IconResolver;

beforeEach(function () {
    // Reset to default state before each test
    IconResolver::useFontAwesome();
});

describe('FontAwesome Resolver', function () {
    it('uses solid style by default', function () {
        IconResolver::useFontAwesome();

        $html = IconResolver::resolve('home');

        expect($html)->toContain('fa-solid')
            ->and($html)->toContain('fa-home');
    });

    it('supports regular style', function () {
        IconResolver::useFontAwesome('regular');

        $html = IconResolver::resolve('home');

        expect($html)->toContain('fa-regular')
            ->and($html)->toContain('fa-home');
    });

    it('supports brands style', function () {
        IconResolver::useFontAwesome('brands');

        $html = IconResolver::resolve('github');

        expect($html)->toContain('fa-brands')
            ->and($html)->toContain('fa-github');
    });

    it('applies color styling', function () {
        IconResolver::useFontAwesome();

        $html = IconResolver::resolve('star', '#FF0000');

        expect($html)->toContain('color: #FF0000');
    });

    it('applies size classes', function () {
        IconResolver::useFontAwesome();

        $htmlSm = IconResolver::resolve('star', null, 'sm');
        $htmlLg = IconResolver::resolve('star', null, 'lg');

        expect($htmlSm)->toContain('text-sm')
            ->and($htmlLg)->toContain('text-lg');
    });
});

describe('Custom Resolver', function () {
    it('accepts custom resolver function', function () {
        IconResolver::using(function (string $icon, ?string $color = null, string $size = 'sm') {
            return "<custom-icon name=\"{$icon}\" color=\"{$color}\" size=\"{$size}\"></custom-icon>";
        });

        $html = IconResolver::resolve('test', '#123456', 'md');

        expect($html)->toBe('<custom-icon name="test" color="#123456" size="md"></custom-icon>');
    });

    it('custom resolver receives all parameters', function () {
        $receivedParams = [];

        IconResolver::using(function (string $icon, ?string $color = null, string $size = 'sm') use (&$receivedParams) {
            $receivedParams = compact('icon', 'color', 'size');
            return 'test';
        });

        IconResolver::resolve('my-icon', '#AABBCC', 'xl');

        expect($receivedParams)->toBe([
            'icon' => 'my-icon',
            'color' => '#AABBCC',
            'size' => 'xl',
        ]);
    });
});

describe('Blade FontAwesome Resolver', function () {
    it('generates blade component syntax', function () {
        IconResolver::useBladeFontAwesome();

        $html = IconResolver::resolve('laptop');

        expect($html)->toContain('x-fas-laptop')
            ->or->toContain('fas-laptop');
    });

    it('supports different styles', function () {
        IconResolver::useBladeFontAwesome('far');

        $html = IconResolver::resolve('heart');

        expect($html)->toContain('far-heart')
            ->or->toContain('x-far-heart');
    });
});

describe('Heroicons Resolver', function () {
    it('generates heroicon component syntax', function () {
        IconResolver::useHeroicons();

        $html = IconResolver::resolve('home');

        expect($html)->toContain('heroicon')
            ->and($html)->toContain('home');
    });

    it('supports outline style', function () {
        IconResolver::useHeroicons('outline');

        $html = IconResolver::resolve('home');

        expect($html)->toContain('heroicon-o-home')
            ->or->toContain('x-heroicon-o-home');
    });

    it('supports solid style', function () {
        IconResolver::useHeroicons('solid');

        $html = IconResolver::resolve('home');

        expect($html)->toContain('heroicon-s-home')
            ->or->toContain('x-heroicon-s-home');
    });
});

describe('Blade Icons Resolver', function () {
    it('uses icon name directly as component', function () {
        IconResolver::useBladeIcons();

        $html = IconResolver::resolve('fas-laptop');

        expect($html)->toContain('fas-laptop');
    });
});

describe('Resolver Edge Cases', function () {
    it('handles empty icon name', function () {
        $html = IconResolver::resolve('');

        expect($html)->toBeString();
    });

    it('handles icon names with hyphens', function () {
        $html = IconResolver::resolve('arrow-left');

        expect($html)->toContain('arrow-left');
    });

    it('handles icon names with underscores', function () {
        $html = IconResolver::resolve('arrow_left');

        expect($html)->toContain('arrow_left');
    });

    it('handles null color gracefully', function () {
        $html = IconResolver::resolve('star', null);

        expect($html)->toBeString()
            ->and($html)->not->toContain('null');
    });
});
