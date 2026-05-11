<div class="flex w-full flex-col gap-4 p-3 sm:p-4 lg:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="lg" level="1">{{ __('snippets.edit.title') }}</flux:heading>
            <flux:subheading class="mt-0.5 text-sm">{{ __('snippets.edit.subtitle') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button size="sm" variant="ghost" wire:click="generateShareLink" wire:loading.attr="disabled">
                {{ __('snippets.edit.share_signed') }}
            </flux:button>
            @if ($publicPageUrl)
                <flux:button size="sm" variant="ghost" :href="$publicPageUrl" target="_blank" rel="noopener">{{ __('snippets.edit.share_public') }}</flux:button>
            @endif
        </div>
    </div>

    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-4">
            @if ($shareUrl)
                <flux:callout variant="success" icon="check-circle" :heading="__('snippets.edit.share_heading')">
                    <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                        <code class="max-w-full flex-1 overflow-x-auto rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-800">{{ $shareUrl }}</code>
                        <flux:button size="sm" variant="primary" type="button" x-on:click="navigator.clipboard.writeText({{ json_encode($shareUrl) }})">{{ __('snippets.edit.copy') }}</flux:button>
                    </div>
                </flux:callout>
            @endif

            <form wire:submit="save" class="flex flex-col gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:input size="sm" wire:model="title" :label="__('snippets.edit.title_label')" required />

                <flux:select size="sm" wire:model.live="language" :label="__('snippets.edit.language_label')" required>
                    @foreach ($languages as $lang)
                        <flux:select.option value="{{ $lang->value }}">{{ __('languages.'.$lang->value) }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid gap-1.5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ __('snippets.edit.code_label') }}</span>
                        <div class="flex flex-wrap gap-1.5">
                            <flux:button size="xs" variant="ghost" type="button" x-on:click="window.CodeSnipEditor?.format('{{ $this->getId() }}')">Format</flux:button>
                            <flux:button size="xs" variant="ghost" type="button" x-on:click="window.CodeSnipEditor?.toggleWrap('{{ $this->getId() }}')">Wrap</flux:button>
                            <flux:button size="xs" variant="ghost" type="button" x-on:click="window.CodeSnipEditor?.copy('{{ $this->getId() }}')">Copy</flux:button>
                            <span class="inline-flex items-center rounded bg-zinc-100 px-2 py-1 text-[11px] text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Ctrl/Cmd+S</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 rounded border border-zinc-200 p-2 dark:border-zinc-700">
                        <flux:button size="xs" variant="ghost" type="button" wire:click="generateAiSummary" wire:loading.attr="disabled">
                            {{ __('snippets.ai.summary_btn') }}
                        </flux:button>
                        <flux:button size="xs" variant="ghost" type="button" wire:click="generateAiExplanation" wire:loading.attr="disabled">
                            {{ __('snippets.ai.explain_btn') }}
                        </flux:button>
                        <flux:button size="xs" variant="ghost" type="button" wire:click="generateAiTest" wire:loading.attr="disabled">
                            {{ __('snippets.ai.test_btn') }}
                        </flux:button>
                    </div>
                    <div
                        wire:key="cm-{{ $language }}-{{ $editorRenderKey }}"
                        wire:ignore
                        class="min-h-[min(62vh,460px)] overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700"
                        x-data
                        x-init="
                            queueMicrotask(() => {
                                if (! window.CodeSnipEditor) return;
                                window.CodeSnipEditor.mount($refs.cm, '{{ $this->getId() }}', { property: 'code', language: @js($language), autosave: true, autosaveDelay: 2000 });
                            });
                        "
                        x-ref="cm"
                    ></div>
                </div>

                @if ($aiSummary !== '' || $aiExplanation !== '' || $aiGeneratedTest !== '')
                    <div class="grid gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        @if ($aiSummary !== '')
                            <div class="grid gap-1">
                                <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('snippets.ai.summary_title') }}</h4>
                                <pre class="max-h-56 overflow-auto whitespace-pre-wrap break-words rounded border border-zinc-200 bg-zinc-50 p-2 text-xs leading-5 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">{{ $aiSummary }}</pre>
                            </div>
                        @endif

                        @if ($aiExplanation !== '')
                            <div class="grid gap-1">
                                <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('snippets.ai.explain_title') }}</h4>
                                <pre class="max-h-72 overflow-auto whitespace-pre-wrap break-words rounded border border-zinc-200 bg-zinc-50 p-2 text-xs leading-5 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">{{ $aiExplanation }}</pre>
                            </div>
                        @endif

                        @if ($aiGeneratedTest !== '')
                            <div class="grid gap-1">
                                <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('snippets.ai.test_title') }}</h4>
                                <pre class="max-h-72 overflow-auto whitespace-pre-wrap break-words rounded border border-zinc-200 bg-zinc-50 p-2 text-xs leading-5 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">{{ $aiGeneratedTest }}</pre>
                            </div>
                        @endif
                    </div>
                @endif

                <flux:input
                    size="sm"
                    wire:model="tagsInput"
                    :label="__('snippets.edit.tags_label')"
                    :description="__('snippets.edit.tags_desc')"
                    :placeholder="__('snippets.edit.tags_ph')"
                />

                <div class="grid gap-2">
                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ __('snippets.edit.folders_label') }}</span>
                    @if ($folders->isEmpty())
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('snippets.edit.folders_empty') }}</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($folders as $folder)
                                <label class="inline-flex items-center gap-1.5 rounded border border-zinc-200 px-2 py-1 text-xs dark:border-zinc-700">
                                    <input type="checkbox" wire:model="folderIds" value="{{ $folder->id }}" class="rounded border-zinc-300">
                                    <span>{{ $folder->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <flux:checkbox wire:model="is_public" :label="__('snippets.edit.public_label')" />

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button size="sm" variant="primary" type="submit" wire:loading.attr="disabled">{{ __('snippets.edit.save') }}</flux:button>
                    <flux:button size="sm" variant="ghost" :href="route('snippets.index')" wire:navigate type="button">{{ __('snippets.edit.back') }}</flux:button>
                    <flux:spacer />
                    <flux:button size="sm" variant="danger" type="button" wire:click="deleteSnippet" wire:confirm="{{ __('snippets.edit.delete_confirm') }}">
                        {{ __('snippets.edit.delete') }}
                    </flux:button>
                </div>
            </form>

        </div>

        <div class="w-full">
            <livewire:snippets.revisions-panel :snippet-id="$snippet->id" />
        </div>
    </div>
</div>
