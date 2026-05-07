<?php

namespace Tests\Feature;

use App\Models\VisitRecord;
use Application\Auth\JwtTokenService;
use Domain\Visit\CityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

final class StatsAuthAndCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_login_sets_cookie_that_grants_access_to_dashboard(): void
    {
        $loginResponse = $this->post('/stats/login', [
            'login' => 'admin',
            'password' => 'secret',
        ]);

        $loginResponse
            ->assertRedirect('/stats')
            ->assertCookie('stats_token');

        $cookie = $loginResponse->getCookie('stats_token');
        $this->assertNotNull($cookie);

        $this->withCookie($cookie->getName(), (string) $cookie->getValue())
            ->get('/stats')
            ->assertOk()
            ->assertSee('id="stats-app"', false);
    }

    public function test_stats_rejects_expired_bearer_token(): void
    {
        config()->set('services.stats.jwt_ttl', -60);

        $token = $this->app->make(JwtTokenService::class)->issue('admin');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->get('/stats')
            ->assertRedirect('/stats/login');
    }

    public function test_stats_rejects_invalid_bearer_token_for_json_requests(): void
    {
        $token = $this->app->make(JwtTokenService::class)->issue('admin');
        $invalidToken = substr($token, 0, -1).(str_ends_with($token, 'a') ? 'b' : 'a');

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$invalidToken}",
        ])->get('/stats')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_stats_login_is_rate_limited_with_sliding_window(): void
    {
        config()->set('services.stats.rate_limit.max_attempts', 2);
        config()->set('services.stats.rate_limit.window_seconds', 60);

        $this->post('/stats/login', [
            'login' => 'admin',
            'password' => 'wrong-1',
        ])
            ->assertRedirect()
            ->assertSessionHas('login_error', 'Invalid credentials.');

        $this->post('/stats/login', [
            'login' => 'admin',
            'password' => 'wrong-2',
        ])
            ->assertRedirect()
            ->assertSessionHas('login_error', 'Invalid credentials.');

        $this->post('/stats/login', [
            'login' => 'admin',
            'password' => 'wrong-3',
        ])
            ->assertStatus(429)
            ->assertSessionHas('login_error');
    }

    public function test_successful_login_clears_rate_limit_bucket(): void
    {
        config()->set('services.stats.rate_limit.max_attempts', 2);
        config()->set('services.stats.rate_limit.window_seconds', 60);

        $this->post('/stats/login', [
            'login' => 'admin',
            'password' => 'wrong',
        ])
            ->assertRedirect()
            ->assertSessionHas('login_error', 'Invalid credentials.');

        $this->post('/stats/login', [
            'login' => 'admin',
            'password' => 'secret',
        ])->assertRedirect('/stats');

        $this->post('/stats/login', [
            'login' => 'admin',
            'password' => 'wrong-again',
        ])
            ->assertStatus(302)
            ->assertSessionHas('login_error', 'Invalid credentials.');
    }

    public function test_visit_recording_invalidates_cached_statistics(): void
    {
        VisitRecord::query()->create([
            'fingerprint' => 'seed-alpha',
            'ip' => '198.51.100.1',
            'city' => 'Alpha',
            'device' => 'desktop',
            'user_agent' => 'Test Agent',
            'page_url' => 'https://example.test/alpha',
            'referrer' => 'https://example.test/',
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        $this->app->bind(CityResolver::class, fn (): CityResolver => new class implements CityResolver
        {
            public function resolve(string $ip): string
            {
                return $ip === '203.0.113.99' ? 'Beta' : 'Local';
            }
        });

        $token = $this->app->make(JwtTokenService::class)->issue('admin');

        $initialPayload = $this->statsPayload(
            $this->withHeader('Authorization', "Bearer {$token}")->get('/stats')
        );

        $this->assertSame([
            ['city' => 'Alpha', 'visits' => 1],
        ], $initialPayload['stats']['cities']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
            ->postJson('/api/visits', [
                'fingerprint' => 'seed-beta',
                'device' => 'mobile',
                'page_url' => 'https://example.test/beta',
                'referrer' => 'https://example.test/alpha',
            ])->assertCreated();

        $refreshedPayload = $this->statsPayload(
            $this->withHeader('Authorization', "Bearer {$token}")->get('/stats')
        );

        $cities = $refreshedPayload['stats']['cities'];
        usort($cities, static fn (array $left, array $right): int => strcmp($left['city'], $right['city']));

        $this->assertSame(2, Cache::get('visit-statistics:version'));
        $this->assertSame([
            ['city' => 'Alpha', 'visits' => 1],
            ['city' => 'Beta', 'visits' => 1],
        ], $cities);
    }

    public function test_stats_city_breakdown_uses_requested_hours_window(): void
    {
        VisitRecord::query()->insert([
            'fingerprint' => 'recent-visitor',
            'ip' => '198.51.100.10',
            'city' => 'Recent',
            'device' => 'desktop',
            'user_agent' => 'Test Agent',
            'page_url' => 'https://example.test/recent',
            'referrer' => 'https://example.test/',
            'created_at' => now()->subMinutes(20),
            'updated_at' => now()->subMinutes(20),
        ]);

        VisitRecord::query()->insert([
            'fingerprint' => 'stale-visitor',
            'ip' => '198.51.100.20',
            'city' => 'Stale',
            'device' => 'mobile',
            'user_agent' => 'Test Agent',
            'page_url' => 'https://example.test/stale',
            'referrer' => 'https://example.test/',
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        $token = $this->app->make(JwtTokenService::class)->issue('admin');

        $payload = $this->statsPayload(
            $this->withHeader('Authorization', "Bearer {$token}")->get('/stats?hours=1')
        );

        $this->assertSame([
            ['city' => 'Recent', 'visits' => 1],
        ], $payload['stats']['cities']);
    }

    public function test_stats_total_counts_unique_visitors_globally(): void
    {
        VisitRecord::query()->insert([
            [
                'fingerprint' => 'travelling-visitor',
                'ip' => '198.51.100.10',
                'city' => 'Alpha',
                'device' => 'desktop',
                'user_agent' => 'Test Agent',
                'page_url' => 'https://example.test/alpha',
                'referrer' => 'https://example.test/',
                'created_at' => now()->subMinutes(20),
                'updated_at' => now()->subMinutes(20),
            ],
            [
                'fingerprint' => 'travelling-visitor',
                'ip' => '198.51.100.20',
                'city' => 'Beta',
                'device' => 'desktop',
                'user_agent' => 'Test Agent',
                'page_url' => 'https://example.test/beta',
                'referrer' => 'https://example.test/alpha',
                'created_at' => now()->subMinutes(10),
                'updated_at' => now()->subMinutes(10),
            ],
        ]);

        $token = $this->app->make(JwtTokenService::class)->issue('admin');

        $payload = $this->statsPayload(
            $this->withHeader('Authorization', "Bearer {$token}")->get('/stats?hours=1')
        );

        $this->assertSame(1, $payload['stats']['total']);
        $this->assertSame([
            ['city' => 'Alpha', 'visits' => 1],
            ['city' => 'Beta', 'visits' => 1],
        ], $payload['stats']['cities']);
    }

    /**
     * @param  TestResponse<Response>  $response
     * @return array{
     *     stats: array{
     *         total: int,
     *         hours: array<int, array{hour: string, visits: int}>,
     *         cities: array<int, array{city: string, visits: int}>
     *     },
     *     hours: int
     * }
     */
    private function statsPayload(TestResponse $response): array
    {
        $response->assertOk()->assertSee('__VISIT_STATS__');

        $content = $response->getContent();
        $this->assertIsString($content);

        $matches = [];
        $matched = preg_match('/window\.__VISIT_STATS__ = (\{.*\});/sU', $content, $matches);

        $this->assertSame(1, $matched);
        $this->assertArrayHasKey(1, $matches);

        $rawPayload = $matches[1] ?? null;
        $this->assertIsString($rawPayload);

        $payload = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($payload);

        /** @var array{
         *     stats: array{
         *         total: int,
         *         hours: array<int, array{hour: string, visits: int}>,
         *         cities: array<int, array{city: string, visits: int}>
         *     },
         *     hours: int
         * } $payload
         */
        return $payload;
    }
}
