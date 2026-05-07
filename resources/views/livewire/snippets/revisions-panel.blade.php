<section class="flex flex-col gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex items-center justify-between gap-2">
        <div>
            <flux:heading size="md">{{ __('snippets.revisions.title') }}</flux:heading>
            <flux:subheading class="mt-0.5 text-sm">{{ __('snippets.revisions.subtitle') }}</flux:subheading>
        </div>
        <flux:button size="sm" variant="ghost" wire:click="loadRevisions" wire:loading.attr="disabled">
            {{ __('snippets.revisions.refresh') }}
        </flux:button>
    </div>

    @if (count($revisions) === 0)
        <div class="rounded-lg border border-dashed border-zinc-300 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
            {{ __('snippets.revisions.empty') }}
        </div>
    @else
        <div class="rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
            <flux:radio.group wire:model.live="diffMode" variant="segmented">
                <flux:radio value="current">{{ __('snippets.revisions.mode_current') }}</flux:radio>
                <flux:radio value="previous">{{ __('snippets.revisions.mode_previous') }}</flux:radio>
            </flux:radio.group>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="max-h-96 overflow-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($revisions as $revision)
                        <button
                            type="button"
                            wire:click="selectRevision({{ $revision['id'] }})"
                            class="flex w-full flex-col items-start gap-1 px-3 py-2 text-left transition {{ $selectedRevisionId === $revision['id'] ? 'bg-zinc-100 dark:bg-zinc-800' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/60' }}"
                        >
                            <div class="flex w-full items-center justify-between gap-2">
                                <span class="text-sm font-medium">v{{ $revision['version'] }}</span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $revision['created_at'] }}</span>
                            </div>
                            <span class="line-clamp-1 text-xs text-zinc-600 dark:text-zinc-300">{{ $revision['title'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-3">
                @if ($selectedRevision)
                    <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold">{{ __('snippets.revisions.version') }} {{ $selectedRevision['version'] }}</span>
                            <flux:modal.trigger name="confirm-revision-rollback">
                                <flux:button
                                    size="sm"
                                    variant="danger"
                                    type="button"
                                    wire:click="confirmRollback({{ $selectedRevision['id'] }})"
                                >
                                    {{ __('snippets.revisions.rollback_to_this') }}
                                </flux:button>
                            </flux:modal.trigger>
                        </div>
                        <div class="space-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                            <div><span class="font-medium">{{ __('snippets.revisions.field_title') }}:</span> {{ $selectedRevision['title'] }}</div>
                            <div><span class="font-medium">{{ __('snippets.revisions.field_language') }}:</span> {{ $selectedRevision['language'] }}</div>
                            <div><span class="font-medium">{{ __('snippets.revisions.field_visibility') }}:</span> {{ $selectedRevision['is_public'] ? __('snippets.revisions.visibility_public') : __('snippets.revisions.visibility_private') }}</div>
                            <div><span class="font-medium">{{ __('snippets.revisions.updated') }}:</span> {{ $selectedRevision['created_at'] }}</div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <pre class="m-0 max-h-72 overflow-auto bg-zinc-950 p-3 text-xs leading-5 text-zinc-100"><code>{{ $selectedRevision['code'] }}</code></pre>
                    </div>

                    @if ($selectedDiff)
                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="mb-2 text-sm font-semibold">
                                {{ $diffMode === 'previous' ? __('snippets.revisions.changes_vs_previous') : __('snippets.revisions.changes_vs_current') }}
                            </div>

                            @if (! ($selectedDiff['has_changes'] ?? false))
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('snippets.revisions.no_diff') }}
                                </div>
                            @else
                                <div class="space-y-2">
                                    @foreach (($selectedDiff['fields'] ?? []) as $field)
                                        @if ($field['changed'] ?? false)
                                            <div class="rounded border border-zinc-200 p-2 text-xs dark:border-zinc-700">
                                                <div class="mb-1 font-medium">{{ __($field['label_key']) }}</div>
                                                <div class="text-zinc-600 dark:text-zinc-400">
                                                    <span class="font-medium">{{ __('snippets.revisions.current') }}:</span>
                                                    @if (($field['label_key'] ?? '') === 'snippets.revisions.field_visibility')
                                                        {{ ($field['current'] ?? '') === 'public' ? __('snippets.revisions.visibility_public') : __('snippets.revisions.visibility_private') }}
                                                    @else
                                                        {{ $field['current'] !== '' ? $field['current'] : '—' }}
                                                    @endif
                                                </div>
                                                <div class="text-zinc-600 dark:text-zinc-400">
                                                    <span class="font-medium">{{ __('snippets.revisions.revision') }}:</span>
                                                    @if (($field['label_key'] ?? '') === 'snippets.revisions.field_visibility')
                                                        {{ ($field['revision'] ?? '') === 'public' ? __('snippets.revisions.visibility_public') : __('snippets.revisions.visibility_private') }}
                                                    @else
                                                        {{ $field['revision'] !== '' ? $field['revision'] : '—' }}
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach

                                    @if (($selectedDiff['code']['changed'] ?? false) && count($selectedDiff['code']['lines'] ?? []) > 0)
                                        <div class="rounded border border-zinc-200 p-2 dark:border-zinc-700">
                                            <div class="mb-2 flex flex-wrap items-center gap-2 text-xs">
                                                <span class="font-medium">{{ __('snippets.revisions.code_diff') }}</span>
                                                <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                    +{{ $selectedDiff['code']['summary']['added'] ?? 0 }}
                                                </span>
                                                <span class="rounded bg-rose-100 px-1.5 py-0.5 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                                    -{{ $selectedDiff['code']['summary']['removed'] ?? 0 }}
                                                </span>
                                                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                                    ~{{ $selectedDiff['code']['summary']['changed'] ?? 0 }}
                                                </span>
                                                <span class="text-zinc-500 dark:text-zinc-400">
                                                    {{ __('snippets.revisions.showing_first', ['count' => min(count($selectedDiff['code']['lines']), 80)]) }}
                                                </span>
                                            </div>
                                            <div class="max-h-64 space-y-2 overflow-auto">
                                                @foreach (array_slice($selectedDiff['code']['lines'], 0, 80) as $line)
                                                    <div class="rounded p-2 text-xs
                                                        {{ $line['type'] === 'added' ? 'bg-emerald-50 dark:bg-emerald-900/20' : '' }}
                                                        {{ $line['type'] === 'removed' ? 'bg-rose-50 dark:bg-rose-900/20' : '' }}
                                                        {{ $line['type'] === 'changed' ? 'bg-amber-50 dark:bg-amber-900/20' : '' }}
                                                    ">
                                                        <div class="mb-1 font-medium">{{ __('snippets.revisions.line') }} {{ $line['line'] }}</div>
                                                        <div class="text-zinc-600 dark:text-zinc-400">
                                                            <span class="font-medium">{{ __('snippets.revisions.current') }}:</span>
                                                            <code class="break-all">{{ $line['current'] !== '' ? $line['current'] : '∅' }}</code>
                                                        </div>
                                                        <div class="text-zinc-600 dark:text-zinc-400">
                                                            <span class="font-medium">{{ __('snippets.revisions.revision') }}:</span>
                                                            <code class="break-all">{{ $line['revision'] !== '' ? $line['revision'] : '∅' }}</code>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="rounded-lg border border-dashed border-zinc-300 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        {{ __('snippets.revisions.select_to_preview') }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    <flux:modal name="confirm-revision-rollback" class="max-w-lg">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('snippets.revisions.rollback_confirm_title') }}</flux:heading>
                <flux:subheading>
                    {{ __('snippets.revisions.rollback_confirm_text') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('snippets.revisions.cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" wire:click="rollback">
                        {{ __('snippets.revisions.confirm_rollback') }}
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
