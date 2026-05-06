<?php

namespace Domain\Visit;

final readonly class Visit
{
    public function __construct(
        public string $fingerprint,
        public string $ip,
        public string $city,
        public string $device,
        public ?string $userAgent,
        public ?string $pageUrl,
        public ?string $referrer,
    ) {}
}
