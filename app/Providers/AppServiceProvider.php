<?php

namespace App\Providers;

use Application\Auth\JwtTokenService;
use Application\Auth\LoginRateLimiter;
use Application\Auth\StatsCredentials;
use Domain\Joke\JokeProvider;
use Domain\Joke\JokeRepository;
use Domain\Visit\CityResolver;
use Domain\Visit\VisitRepository;
use Domain\Visit\VisitStatisticsCache;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application as FoundationApplication;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Auth\HmacJwtTokenService;
use Infrastructure\Auth\InMemorySlidingWindowLoginRateLimiter;
use Infrastructure\Auth\LaravelStatsCredentials;
use Infrastructure\Auth\RedisSlidingWindowLoginRateLimiter;
use Infrastructure\Cache\LaravelVisitStatisticsCache;
use Infrastructure\Joke\EloquentJokeRepository;
use Infrastructure\Joke\OfficialJokeApiClient;
use Infrastructure\Persistence\LaravelConfigModelResolver;
use Infrastructure\Persistence\ModelResolver;
use Infrastructure\Redis\LuaScriptResolver;
use Infrastructure\Redis\PhpRedisConnection;
use Infrastructure\Redis\RedisConnectionPort;
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
        $this->app->singleton(RedisConnectionPort::class, function (FoundationApplication $app): RedisConnectionPort {
            $config = $app->make(ConfigRepository::class);

            return new PhpRedisConnection(
                $config,
                (string) $config->get('services.stats.rate_limit.redis_connection', 'cache'),
            );
        });
        $this->app->singleton(LuaScriptResolver::class, fn (FoundationApplication $app): LuaScriptResolver => LuaScriptResolver::default(
            $app->make(RedisConnectionPort::class)
        ));
        $this->app->singleton(LoginRateLimiter::class, function (FoundationApplication $app): LoginRateLimiter {
            $config = $app->make(ConfigRepository::class);
            $maxAttempts = (int) $config->get('services.stats.rate_limit.max_attempts', 5);
            $windowSeconds = (int) $config->get('services.stats.rate_limit.window_seconds', 60);

            if ($app->environment() === 'testing') {
                return new InMemorySlidingWindowLoginRateLimiter($maxAttempts, $windowSeconds);
            }

            return new RedisSlidingWindowLoginRateLimiter(
                $app->make(LuaScriptResolver::class),
                $app->make(RedisConnectionPort::class),
                $maxAttempts,
                $windowSeconds,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
