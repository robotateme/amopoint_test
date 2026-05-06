<?php

namespace App\Providers;

use Application\Auth\JwtTokenService;
use Application\Auth\StatsCredentials;
use Domain\Joke\JokeProvider;
use Domain\Joke\JokeRepository;
use Domain\Visit\CityResolver;
use Domain\Visit\VisitRepository;
use Domain\Visit\VisitStatisticsCache;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Auth\HmacJwtTokenService;
use Infrastructure\Auth\LaravelStatsCredentials;
use Infrastructure\Cache\LaravelVisitStatisticsCache;
use Infrastructure\Joke\EloquentJokeRepository;
use Infrastructure\Joke\OfficialJokeApiClient;
use Infrastructure\Persistence\LaravelConfigModelResolver;
use Infrastructure\Persistence\ModelResolver;
use Infrastructure\Visit\EloquentVisitRepository;
use Infrastructure\Visit\IpApiCityResolver;

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
        $this->app->bind(ModelResolver::class, LaravelConfigModelResolver::class);
        $this->app->bind(JwtTokenService::class, HmacJwtTokenService::class);
        $this->app->bind(StatsCredentials::class, LaravelStatsCredentials::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
