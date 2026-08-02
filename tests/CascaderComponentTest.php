<?php

namespace Vlados\Cascader\Tests;

use Vlados\Cascader\Components\Cascader;

class CascaderComponentTest extends TestCase
{
    public function test_renders_entangle_for_wire_model(): void
    {
        $html = $this->renderDecoded(
            '<x-cascader :options="$options" wire:model="category_id" />',
            ['options' => $this->sampleOptions()]
        );

        $this->assertStringContainsString("\$wire.entangle('category_id')", $html);
        $this->assertStringNotContainsString("entangle('category_id').live", $html);
    }

    public function test_live_modifier_is_detected_in_modifier_chains(): void
    {
        $html = $this->renderDecoded(
            '<x-cascader :options="$options" wire:model.live.debounce.500ms="category_id" />',
            ['options' => $this->sampleOptions()]
        );

        $this->assertStringContainsString("entangle('category_id').live", $html);
    }

    public function test_wire_model_attributes_do_not_leak_onto_the_root_element(): void
    {
        $html = $this->renderDecoded(
            '<x-cascader :options="$options" wire:model.live.debounce.500ms="category_id" />',
            ['options' => $this->sampleOptions()]
        );

        $this->assertStringNotContainsString('wire:model', $html);
    }

    public function test_legacy_wire_model_prop_still_binds(): void
    {
        $html = $this->renderDecoded(
            '<x-cascader :options="$options" wire-model="category_id" />',
            ['options' => $this->sampleOptions()]
        );

        $this->assertStringContainsString("\$wire.entangle('category_id')", $html);
    }

    public function test_renders_null_model_without_binding(): void
    {
        $html = $this->renderDecoded(
            '<x-cascader :options="$options" />',
            ['options' => $this->sampleOptions()]
        );

        $this->assertStringContainsString('modelValue: null', $html);
    }

    public function test_selected_text_is_forwarded_as_initial_text(): void
    {
        $html = $this->renderDecoded(
            '<x-cascader :options="$options" wire:model="category_id" selected-text="Electronics / Phones" />',
            ['options' => $this->sampleOptions()]
        );

        $this->assertStringContainsString('initialText:', $html);
        $this->assertStringContainsString('Electronics', $html);
    }

    public function test_icons_are_resolved_into_the_options_payload(): void
    {
        $component = new Cascader(options: $this->sampleOptions());

        $this->assertStringContainsString('fa-solid fa-laptop', $component->resolvedOptions[0]['iconHtml']);
        $this->assertStringContainsString('fa-solid fa-mobile', $component->resolvedOptions[0]['children'][0]['iconHtml']);
        $this->assertArrayNotHasKey('iconHtml', $component->resolvedOptions[0]['children'][1]);
        $this->assertArrayNotHasKey('iconHtml', $component->resolvedOptions[1]);
    }
}
