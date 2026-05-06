<?php

namespace Infrastructure\Visit;

use Domain\Visit\CityResolver;
use Illuminate\Support\Facades\Http;
use Throwable;

final class IpApiCityResolver implements CityResolver
{
    public function resolve(string $ip): string
    {
        if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '10.') || str_starts_with($ip, '192.168.')) {
            return 'Local';
        }

        try {
            $payload = Http::timeout(3)
                ->acceptJson()
                ->get("http://ip-api.com/json/{$ip}", ['fields' => 'status,city'])
                ->json();
        } catch (Throwable) {
            return 'Unknown';
        }

        if (! is_array($payload) || ($payload['status'] ?? null) !== 'success') {
            return 'Unknown';
        }

        return trim((string) ($payload['city'] ?? '')) ?: 'Unknown';
    }
}
