<?php

use Livewire\Volt\Component;

use function Livewire\Volt\layout;
use function Livewire\Volt\title;

new class extends Component {
    //
};

layout('components.layouts.app');
title(fn () => __('settings.appearance_page_title'));

?>

<div class="flex w-full flex-col gap-4 p-3 sm:p-4 lg:p-6">
    @include('partials.settings-heading')

    <x-settings.layout heading="Appearance" subheading="Update your account's appearance settings">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">Light</flux:radio>
            <flux:radio value="dark" icon="moon">Dark</flux:radio>
            <flux:radio value="system" icon="computer-desktop">System</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</div>
