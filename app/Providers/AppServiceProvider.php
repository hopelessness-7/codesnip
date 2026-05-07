<?php

namespace App\Providers;

use App\Repositories\Contracts\TagRepositoryInterface;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
