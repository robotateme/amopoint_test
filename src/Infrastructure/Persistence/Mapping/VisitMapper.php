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
            'fingerprint' => $visit->getFingerprint(),
            'ip' => $visit->getIp(),
            'city' => $visit->getCity(),
            'device' => $visit->getDevice(),
            'user_agent' => $visit->getUserAgent(),
            'page_url' => $visit->getPageUrl(),
            'referrer' => $visit->getReferrer(),
            'created_at' => $visit->getCreatedAt(),
            'updated_at' => $visit->getUpdatedAt(),
        ];
    }
}
