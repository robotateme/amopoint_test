<?php

namespace Domain\Visit;

use JsonSerializable;

final readonly class Visit implements JsonSerializable
{
    public function __construct(
        private string $fingerprint,
        private string $ip,
        private string $city,
        private string $device,
        private ?string $userAgent,
        private ?string $pageUrl,
        private ?string $referrer,
        private ?string $createdAt,
        private ?string $updatedAt,
    ) {}

    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getDevice(): string
    {
        return $this->device;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getPageUrl(): ?string
    {
        return $this->pageUrl;
    }

    public function getReferrer(): ?string
    {
        return $this->referrer;
    }

    public function jsonSerialize(): array
    {
        return [
            'fingerprint' => $this->fingerprint,
            'ip' => $this->ip,
            'city' => $this->city,
            'device' => $this->device,
            'userAgent' => $this->userAgent,
            'pageUrl' => $this->pageUrl,
            'referrer' => $this->referrer,
        ];
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
}
