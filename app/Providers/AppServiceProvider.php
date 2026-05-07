<?php

namespace App\Providers;

use Application\Auth\JwtTokenService;
use Application\Auth\LoginRateLimiter;
use Application\Auth\StatsCredentials;
use Domain\Joke\JokeProvider;
use Domain\Visit\CityResolver;
use Domain\Visit\VisitStatisticsCache;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application as FoundationApplication;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Auth\HmacJwtTokenService;
use Infrastructure\Auth\InMemorySlidingWindowLoginRateLimiter;
use Infrastructure\Auth\LaravelStatsCredentials;
use Infrastructure\Auth\RedisSlidingWindowLoginRateLimiter;
use Infrastructure\Cache\LaravelVisitStatisticsCache;
use Infrastructure\Joke\OfficialJokeApiClient;
use Infrastructure\Redis\LuaScriptResolver;
use Infrastructure\Redis\PhpRedisConnection;
use Infrastructure\Redis\RedisConnectionPort;
use Infrastructure\Visit\IpApiCityResolver;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(JokeProvider::class, OfficialJokeApiClient::class);
        $this->app->bind(CityResolver::class, IpApiCityResolver::class);
        $this->app->bind(VisitStatisticsCache::class, LaravelVisitStatisticsCache::class);
        $this->app->bind(JwtTokenService::class, HmacJwtTokenService::class);
        $this->app->bind(StatsCredentials::class, LaravelStatsCredentials::class);
        $this->bindPersistenceRepositories();
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
            $driver = (string) $config->get('services.stats.rate_limit.driver', 'redis');
            $maxAttempts = (int) $config->get('services.stats.rate_limit.max_attempts', 5);
            $windowSeconds = (int) $config->get('services.stats.rate_limit.window_seconds', 60);

            if ($app->environment() === 'testing' || $driver === 'memory') {
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

    private function bindPersistenceRepositories(): void
    {
        $repositories = config('persistence.repositories', []);

        if (! is_array($repositories)) {
            throw new InvalidArgumentException('Persistence repositories mapping must be an array.');
        }

        foreach ($repositories as $contract => $implementation) {
            if (! is_string($contract) || ! is_string($implementation)) {
                throw new InvalidArgumentException('Persistence repository mapping must contain class-string pairs.');
            }

            if (! interface_exists($contract) || ! class_exists($implementation)) {
                throw new InvalidArgumentException("Persistence repository mapping [{$contract}] is invalid.");
            }

            if (! is_a($implementation, $contract, true)) {
                throw new InvalidArgumentException("Persistence repository [{$implementation}] must implement [{$contract}].");
            }

            $this->app->bind($contract, $implementation);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
