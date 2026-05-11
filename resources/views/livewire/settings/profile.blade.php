<?php

use App\Models\User;
use App\Modules\AI\Providers\OllamaProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

use function Livewire\Volt\layout;
use function Livewire\Volt\title;

new class extends Component {
    public string $name = '';

    public string $email = '';

    public string $githubToken = '';

    public string $ollamaModel = '';

    /** @var list<string> */
    public array $ollamaModels = [];

    /** @var list<string> */
    public array $ollamaRunningModels = [];

    public bool $hasGithubToken = false;

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->ollamaModel = (string) ($user->ollama_model ?? config('openai.ollama.model'));
        $this->syncGithubTokenFlag();
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    public function refreshOllamaModels(): void
    {
        $provider = new OllamaProvider([
            'host' => (string) config('openai.ollama.host'),
            'model' => (string) config('openai.ollama.model'),
        ]);

        $this->ollamaModels = $provider->listLocalModelNames();
        $this->ollamaRunningModels = $provider->listRunningModelNames();

        if ($this->ollamaModels === []) {
            $this->dispatch('app-toast', type: 'warning', message: __('settings.ollama_models_empty'));
        } else {
            $this->dispatch('app-toast', type: 'success', message: __('settings.ollama_models_refreshed'));
        }
    }

    public function saveGithubToken(): void
    {
        $this->validate([
            'githubToken' => ['required', 'string', 'min:20', 'max:2048'],
        ]);

        $user = Auth::user();
        $user->github_personal_access_token = trim($this->githubToken);
        $user->save();

        $this->githubToken = '';
        $this->syncGithubTokenFlag();

        $this->dispatch('app-toast', type: 'success', message: __('settings.github_token_saved'));
    }

    public function clearGithubToken(): void
    {
        $user = Auth::user();
        $user->github_personal_access_token = null;
        $user->save();

        $this->syncGithubTokenFlag();

        $this->dispatch('app-toast', type: 'success', message: __('settings.github_token_removed'));
    }

    public function saveOllamaModel(): void
    {
        $this->validate([
            'ollamaModel' => ['required', 'string', 'max:191'],
        ]);

        $user = Auth::user();
        $trimmed = trim($this->ollamaModel);
        $user->ollama_model = $trimmed === '' ? null : $trimmed;
        $user->save();

        $this->ollamaModel = (string) ($user->ollama_model ?? config('openai.ollama.model'));

        $this->dispatch('app-toast', type: 'success', message: __('settings.ollama_model_saved'));
    }

    public function resetOllamaModelToDefault(): void
    {
        $user = Auth::user();
        $user->ollama_model = null;
        $user->save();

        $this->ollamaModel = (string) config('openai.ollama.model');

        $this->dispatch('app-toast', type: 'success', message: __('settings.ollama_model_reset'));
    }

    private function syncGithubTokenFlag(): void
    {
        $token = Auth::user()->github_personal_access_token ?? null;
        $this->hasGithubToken = is_string($token) && trim($token) !== '';
    }
};

layout('components.layouts.app');
title(fn () => __('settings.profile_page_title'));

?>

<div class="flex w-full flex-col gap-4 p-3 sm:p-4 lg:p-6">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('settings.profile_heading')" :subheading="__('settings.profile_subheading')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('settings.field_name')" type="text" name="name" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('settings.field_email')" type="email" name="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                    <div>
                        <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">
                            {{ __('Your email address is unverified.') }}

                            <button
                                wire:click.prevent="resendVerificationNotification"
                                type="button"
                                class="rounded-md text-sm text-zinc-600 underline hover:text-zinc-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-zinc-400 dark:hover:text-zinc-100"
                            >
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('settings.save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('settings.saved') }}
                </x-action-message>
            </div>
        </form>

        <flux:separator class="my-8" />

        <div class="space-y-4">
            <flux:heading size="md">{{ __('settings.integrations_heading') }}</flux:heading>
            <flux:subheading size="sm">{{ __('settings.integrations_subheading') }}</flux:subheading>

            <div class="mt-4 space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('settings.github_heading') }}</flux:heading>
                <flux:subheading size="sm" class="text-zinc-600 dark:text-zinc-400">{{ __('settings.github_subheading') }}</flux:subheading>

                @if ($hasGithubToken)
                    <div class="rounded border border-emerald-700/40 bg-emerald-900/25 px-3 py-2 text-sm text-emerald-200">
                        {{ __('settings.github_token_present') }}
                    </div>
                @else
                    <div class="rounded border border-amber-700/40 bg-amber-900/25 px-3 py-2 text-sm text-amber-100">
                        {{ __('settings.github_token_missing') }}
                    </div>
                @endif

                <flux:input
                    wire:model="githubToken"
                    type="password"
                    :label="__('settings.github_token_label')"
                    :placeholder="__('settings.github_token_placeholder')"
                />

                <div class="flex flex-wrap gap-2">
                    <flux:button size="sm" variant="primary" type="button" wire:click="saveGithubToken">{{ __('settings.github_token_save') }}</flux:button>
                    @if ($hasGithubToken)
                        <flux:button size="sm" variant="ghost" type="button" wire:click="clearGithubToken">{{ __('settings.github_token_remove') }}</flux:button>
                    @endif
                </div>
            </div>

            <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('settings.ollama_heading') }}</flux:heading>
                <flux:subheading size="sm" class="text-zinc-600 dark:text-zinc-400">{{ __('settings.ollama_subheading') }}</flux:subheading>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button size="sm" variant="ghost" type="button" wire:click="refreshOllamaModels">{{ __('settings.ollama_refresh_models') }}</flux:button>
                    <span class="text-xs text-zinc-500">{{ __('settings.ollama_default_hint', ['model' => config('openai.ollama.model')]) }}</span>
                </div>

                @if ($ollamaRunningModels !== [])
                    <div class="max-h-32 overflow-y-auto rounded border border-emerald-800/40 bg-emerald-950/30 p-2 text-xs">
                        <span class="mb-1 block font-medium text-emerald-200">{{ __('settings.ollama_running_models') }}</span>
                        <ul class="list-inside list-disc space-y-0.5 text-emerald-100/90">
                            @foreach ($ollamaRunningModels as $m)
                                <li>{{ $m }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($ollamaModels !== [])
                    <div class="max-h-40 overflow-y-auto rounded border border-zinc-200 p-2 text-xs dark:border-zinc-700">
                        <span class="mb-1 block font-medium text-zinc-700 dark:text-zinc-300">{{ __('settings.ollama_installed_models') }}</span>
                        <ul class="list-inside list-disc space-y-0.5 text-zinc-600 dark:text-zinc-400">
                            @foreach ($ollamaModels as $m)
                                <li>{{ $m }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <flux:input
                    wire:model="ollamaModel"
                    list="ollama-model-suggestions"
                    :label="__('settings.ollama_model_label')"
                    :placeholder="__('settings.ollama_model_placeholder')"
                />
                <datalist id="ollama-model-suggestions">
                    @foreach ($ollamaModels as $m)
                        <option value="{{ $m }}"></option>
                    @endforeach
                </datalist>

                <div class="flex flex-wrap gap-2">
                    <flux:button size="sm" variant="primary" type="button" wire:click="saveOllamaModel">{{ __('settings.ollama_model_save') }}</flux:button>
                    <flux:button size="sm" variant="ghost" type="button" wire:click="resetOllamaModelToDefault">{{ __('settings.ollama_model_reset') }}</flux:button>
                </div>
            </div>
        </div>

        <flux:separator class="my-8" />

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</div>
