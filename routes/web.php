<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Web\V1\SnippetController;
use App\Livewire\Folders\Create as FoldersCreate;
use App\Livewire\Folders\Edit as FoldersEdit;
use App\Livewire\Folders\Index as FoldersIndex;
use App\Livewire\Snippets\Create as SnippetsCreate;
use App\Livewire\Snippets\Edit as SnippetsEdit;
use App\Livewire\Snippets\Index as SnippetsIndex;
use App\Livewire\SmartCollections\Create as SmartCollectionsCreate;
use App\Livewire\SmartCollections\Edit as SmartCollectionsEdit;
use App\Livewire\SmartCollections\Index as SmartCollectionsIndex;
use App\Livewire\Tags\Index as TagsIndex;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/shared/snippets/{uuid}', [SnippetController::class, 'publicView'])
    ->middleware('signed')
    ->name('snippets.public');

Route::get('/p/snippets/{uuid}', [SnippetController::class, 'publicOpen'])
    ->name('snippets.publicOpen');

Route::get('locale/{locale}', [LocaleController::class, 'switch'])
    ->whereIn('locale', ['en', 'ru'])
    ->name('locale.switch');

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::get('snippets', SnippetsIndex::class)->name('snippets.index');
    Route::get('snippets/create', SnippetsCreate::class)->name('snippets.create');
    Route::get('snippets/{snippet}/edit', SnippetsEdit::class)->name('snippets.edit');

    Route::get('folders', FoldersIndex::class)->name('folders.index');
    Route::get('folders/create', FoldersCreate::class)->name('folders.create');
    Route::get('folders/{folder}/edit', FoldersEdit::class)->name('folders.edit');

    Route::get('smart-collections', SmartCollectionsIndex::class)->name('smart-collections.index');
    Route::get('smart-collections/create', SmartCollectionsCreate::class)->name('smart-collections.create');
    Route::get('smart-collections/{smartCollection}/edit', SmartCollectionsEdit::class)->name('smart-collections.edit');

    Route::get('tags', TagsIndex::class)->name('tags.index');
});

require __DIR__.'/auth.php';
