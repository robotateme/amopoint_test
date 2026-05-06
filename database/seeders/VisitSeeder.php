<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class VisitSeeder extends Seeder
{
    /**
     * Seed test visits for the statistics dashboard.
     */
    public function run(): void
    {
        DB::table('visits')
            ->where('fingerprint', 'like', 'seed-visitor-%')
            ->delete();

        $cities = [
            'Simferopol',
            'Moscow',
            'Saint Petersburg',
            'Kazan',
            'Novosibirsk',
            'Local',
        ];

        $devices = ['desktop', 'mobile', 'tablet'];
        $pages = [
            'https://example.test/',
            'https://example.test/catalog',
            'https://example.test/pricing',
            'https://example.test/blog/laravel-dashboard',
            'https://example.test/contacts',
        ];
        $referrers = [
            'https://google.com/',
            'https://yandex.ru/',
            'https://github.com/',
            '',
        ];

        $rows = [];
        $now = now();

        for ($hour = 23; $hour >= 0; $hour--) {
            $visitsInHour = 4 + (($hour * 7) % 9);

            for ($index = 0; $index < $visitsInHour; $index++) {
                $visitorNumber = (($hour * 13) + $index) % 64;
                $createdAt = $now->copy()
                    ->subHours($hour)
                    ->subMinutes(($index * 5) % 55)
                    ->subSeconds(($index * 11) % 60);

                $rows[] = [
                    'fingerprint' => sprintf('seed-visitor-%02d', $visitorNumber),
                    'ip' => sprintf('10.10.%d.%d', intdiv($visitorNumber, 254), ($visitorNumber % 254) + 1),
                    'city' => $cities[($visitorNumber + $hour) % count($cities)],
                    'device' => $devices[($visitorNumber + $index) % count($devices)],
                    'user_agent' => 'Seeder Browser/1.0',
                    'page_url' => $pages[($hour + $index) % count($pages)],
                    'referrer' => $referrers[($hour + $index) % count($referrers)],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
        }

        DB::table('visits')->insert($rows);
    }
}
