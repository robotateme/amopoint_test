<?php

namespace Application\Visit\Command\RecordVisit;

final readonly class RecordVisitCommand
{
    public function __construct(
        public string $ip,
        public ?string $fingerprint,
        public ?string $device,
        public ?string $userAgent,
        public ?string $pageUrl,
        public ?string $referrer,
    ) {}
}
