<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Comic::observe(\App\Observers\ComicObserver::class);
        \App\Models\Comment::observe(\App\Observers\CommentObserver::class);
        \App\Models\Tag::observe(\App\Observers\TagObserver::class);

         // Настройка RateLimiter для API
        \Illuminate\Support\Facades\RateLimiter::for('api', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip());
        });

        $this->app->singleton(\Faker\Generator::class, function () {
            $faker = \Faker\Factory::create('ru_RU');
            $faker->addProvider(new \Faker\Provider\ru_RU\Person($faker));
            $faker->addProvider(new \Faker\Provider\ru_RU\Text($faker));
            return $faker;
        });
    }
}
