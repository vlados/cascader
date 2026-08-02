<?php

namespace Vlados\Cascader\Tests;

use InvalidArgumentException;
use Vlados\Cascader\IconResolver;

class IconResolverTest extends TestCase
{
    public function test_defaults_to_inline_fontawesome(): void
    {
        $this->assertSame(
            '<i class="fa-solid fa-laptop text-sm"></i>',
            IconResolver::resolve('laptop')
        );
    }

    public function test_valid_colors_are_rendered(): void
    {
        $this->assertStringContainsString(
            'style="color: #FF0000"',
            IconResolver::resolve('laptop', '#FF0000')
        );
    }

    public function test_malicious_colors_are_dropped(): void
    {
        $html = IconResolver::resolve('laptop', '" onmouseover="alert(1)');

        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertStringNotContainsString('style=', $html);
    }

    public function test_icon_names_are_escaped_in_inline_tags(): void
    {
        $html = IconResolver::resolve('laptop"><script>alert(1)</script>');

        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_md_size_maps_to_a_real_tailwind_class(): void
    {
        $this->assertStringContainsString('text-base', IconResolver::resolve('laptop', null, 'md'));
    }

    public function test_blade_resolvers_reject_invalid_component_names(): void
    {
        IconResolver::useBladeIcons();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid icon component name');

        IconResolver::resolve('foo {{ config("app.key") }}');
    }

    public function test_blade_injection_attempts_are_rejected(): void
    {
        IconResolver::useHeroicons();

        $this->expectException(InvalidArgumentException::class);

        IconResolver::resolve('x @php echo 1; @endphp');
    }

    public function test_missing_components_throw_a_descriptive_error(): void
    {
        IconResolver::useHeroicons();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to render icon component');

        IconResolver::resolve('this-icon-does-not-exist');
    }

    public function test_custom_resolvers_are_used(): void
    {
        IconResolver::using(fn (string $icon, ?string $color = null, string $size = 'sm') => "custom:{$icon}:{$size}");

        $this->assertSame('custom:laptop:sm', IconResolver::resolve('laptop'));
    }

    public function test_reset_restores_the_default_resolver(): void
    {
        IconResolver::using(fn (string $icon) => 'custom');
        IconResolver::reset();

        $this->assertStringContainsString('fa-solid', IconResolver::resolve('laptop'));
    }
}
