<?php

namespace App\Providers;

use Domain\Joke\JokeProvider;
use Domain\Joke\JokeRepository;
use Domain\Visit\CityResolver;
use Domain\Visit\VisitRepository;
use Domain\Visit\VisitStatisticsCache;
use Infrastructure\Joke\EloquentJokeRepository;
use Infrastructure\Joke\OfficialJokeApiClient;
use Infrastructure\Cache\LaravelVisitStatisticsCache;
use Infrastructure\Visit\EloquentVisitRepository;
use Infrastructure\Visit\IpApiCityResolver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(JokeProvider::class, OfficialJokeApiClient::class);
        $this->app->bind(JokeRepository::class, EloquentJokeRepository::class);
        $this->app->bind(CityResolver::class, IpApiCityResolver::class);
        $this->app->bind(VisitRepository::class, EloquentVisitRepository::class);
        $this->app->bind(VisitStatisticsCache::class, LaravelVisitStatisticsCache::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
