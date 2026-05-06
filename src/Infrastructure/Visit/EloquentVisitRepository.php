<?php

namespace Infrastructure\Visit;

use Domain\Visit\Visit;
use Domain\Visit\VisitRepository;
use Infrastructure\Persistence\Models\VisitRecord;
use Illuminate\Support\Facades\DB;

final class EloquentVisitRepository implements VisitRepository
{
    public function save(Visit $visit): void
    {
        VisitRecord::create([
            'fingerprint' => $visit->fingerprint,
            'ip' => $visit->ip,
            'city' => $visit->city,
            'device' => $visit->device,
            'user_agent' => $visit->userAgent,
            'page_url' => $visit->pageUrl,
            'referrer' => $visit->referrer,
        ]);
    }

    public function uniqueByHour(int $hours = 24): array
    {
        $driver = DB::connection()->getDriverName();
        $hourExpression = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d %H:00', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD HH24:00')",
            default => "date_format(created_at, '%Y-%m-%d %H:00')",
        };

        return VisitRecord::query()
            ->selectRaw("{$hourExpression} as hour, count(distinct fingerprint) as visits")
            ->where('created_at', '>=', now()->subHours($hours))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn ($row): array => [
                'hour' => (string) $row->hour,
                'visits' => (int) $row->visits,
            ])
            ->all();
    }

    public function uniqueByCity(): array
    {
        return VisitRecord::query()
            ->selectRaw('city, count(distinct fingerprint) as visits')
            ->groupBy('city')
            ->orderByDesc('visits')
            ->get()
            ->map(fn ($row): array => [
                'city' => (string) $row->city,
                'visits' => (int) $row->visits,
            ])
            ->all();
    }
}
