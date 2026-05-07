<?php

namespace App\Providers;

use App\Repositories\Contracts\FolderRepositoryInterface;
use App\Repositories\Contracts\SmartCollectionRepositoryInterface;
use App\Repositories\Contracts\TagRepositoryInterface;
use App\Repositories\Eloquent\FolderRepository;
use App\Repositories\Eloquent\SmartCollectionRepository;
use App\Repositories\Eloquent\TagRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            TagRepositoryInterface::class,
            TagRepository::class
        );

        $this->app->bind(
            FolderRepositoryInterface::class,
            FolderRepository::class
        );

        $this->app->bind(
            SmartCollectionRepositoryInterface::class,
            SmartCollectionRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
