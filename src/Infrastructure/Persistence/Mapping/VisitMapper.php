<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Mapping;

use Domain\Visit\Visit;

final readonly class VisitMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function attributesFromDomain(Visit $visit): array
    {
        return [
            'fingerprint' => $visit->fingerprint,
            'ip' => $visit->ip,
            'city' => $visit->city,
            'device' => $visit->device,
            'user_agent' => $visit->userAgent,
            'page_url' => $visit->pageUrl,
            'referrer' => $visit->referrer,
        ];
    }
}
