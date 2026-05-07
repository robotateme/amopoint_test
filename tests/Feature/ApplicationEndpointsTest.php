<?php

namespace Tests\Feature;

use App\Models\JokeRecord;
use Application\Auth\JwtTokenService;
use Domain\Visit\CityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ApplicationEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_jokes_endpoint_returns_stored_jokes(): void
    {
        JokeRecord::query()->create([
            'external_id' => 10,
            'type' => 'general',
            'setup' => 'Setup',
            'punchline' => 'Punchline',
        ]);

        $this->getJson('/api/jokes')
            ->assertOk()
            ->assertJsonPath('0.external_id', 10)
            ->assertJsonPath('0.setup', 'Setup');
    }

    public function test_visit_endpoint_records_visit(): void
    {
        $this->app->bind(CityResolver::class, fn (): CityResolver => new class implements CityResolver
        {
            public function resolve(string $ip): string
            {
                return 'Simferopol';
            }
        });

        $this->postJson('/api/visits', [
            'fingerprint' => 'browser-1',
            'device' => 'desktop',
            'page_url' => 'https://example.test/page',
            'referrer' => 'https://example.test/',
        ])->assertCreated()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('visits', [
            'fingerprint' => 'browser-1',
            'city' => 'Simferopol',
            'device' => 'desktop',
        ]);
    }

    public function test_stats_page_requires_jwt_auth(): void
    {
        $this->get('/stats')->assertRedirect('/stats/login');

        $token = $this->app->make(JwtTokenService::class)->issue('admin');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->get('/stats')
            ->assertOk()
            ->assertSee('id="stats-app"', false)
            ->assertSee('__VISIT_STATS__');
    }

    public function test_stats_login_issues_jwt_cookie(): void
    {
        $this->post('/stats/login', [
            'login' => 'admin',
            'password' => 'secret',
        ])
            ->assertRedirect('/stats')
            ->assertCookie('stats_token');
    }

    public function test_forwarded_https_proto_is_used_for_vite_asset_urls(): void
    {
        $this->withHeader('X-Forwarded-Proto', 'https')
            ->get('http://amopoint-test.fly.dev/stats/login')
            ->assertOk()
            ->assertSee('href="https://amopoint-test.fly.dev/build/', false)
            ->assertDontSee('href="http://amopoint-test.fly.dev/build/', false);
    }
}
