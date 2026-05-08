<div class="flex w-full flex-col gap-4 p-3 sm:p-4 lg:p-6">
    <div>
        <flux:heading size="lg" level="1">{{ __('snippet_templates.edit_title') }}</flux:heading>
    </div>

    <form wire:submit="save" class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input size="sm" wire:model="name" :label="__('snippet_templates.field_name')" required />
        <flux:input size="sm" wire:model="description" :label="__('snippet_templates.field_description')" />
        <flux:select size="sm" wire:model="language" :label="__('snippet_templates.field_language')">
            @foreach ($languages as $lang)
                <flux:select.option value="{{ $lang->value }}">{{ __('languages.'.$lang->value) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input size="sm" wire:model="titleTemplate" :label="__('snippet_templates.field_title_template')" required />
        <flux:textarea rows="12" wire:model="codeTemplate" :label="__('snippet_templates.field_code_template')" required />
        <flux:input
            size="sm"
            wire:model="defaultTagsInput"
            :label="__('snippet_templates.field_default_tags')"
            :placeholder="__('snippet_templates.default_tags_hint')"
        />
        <flux:checkbox wire:model="isFavorite" :label="__('snippet_templates.field_favorite')" />

        <div class="text-xs text-zinc-500 dark:text-zinc-400">
            {{ __('snippet_templates.variables_hint') }}
        </div>

        <div class="flex gap-2">
            <flux:button size="sm" variant="primary" type="submit">{{ __('snippet_templates.save') }}</flux:button>
            <flux:button size="sm" variant="ghost" :href="route('snippet-templates.index')" wire:navigate>{{ __('snippet_templates.cancel') }}</flux:button>
        </div>
    </form>
</div>
