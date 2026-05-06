<?php

namespace Infrastructure\Auth;

use Application\Auth\JwtTokenService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;
use JsonException;

final readonly class HmacJwtTokenService implements JwtTokenService
{
    public function __construct(
        private ConfigRepository $config,
    ) {}

    public function issue(string $subject): string
    {
        $now = time();
        $payload = [
            'iss' => (string) $this->config->get('app.url', 'http://localhost'),
            'sub' => $subject,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + ((int) $this->config->get('services.stats.jwt_ttl', 3600)),
            'jti' => (string) Str::uuid(),
        ];

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode($this->jsonEncode($header)),
            $this->base64UrlEncode($this->jsonEncode($payload)),
        ];
        $segments[] = $this->sign(implode('.', $segments));

        return implode('.', $segments);
    }

    public function validate(string $token): ?string
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $signature] = $parts;
        $expectedSignature = $this->sign("{$encodedHeader}.{$encodedPayload}");

        if (! hash_equals($expectedSignature, $signature)) {
            return null;
        }

        try {
            $header = $this->jsonDecode($this->base64UrlDecode($encodedHeader));
            $payload = $this->jsonDecode($this->base64UrlDecode($encodedPayload));
        } catch (JsonException) {
            return null;
        }

        if (($header['alg'] ?? null) !== 'HS256' || ($header['typ'] ?? null) !== 'JWT') {
            return null;
        }

        $now = time();

        if (! isset($payload['sub'], $payload['exp'], $payload['nbf'])) {
            return null;
        }

        if ((int) $payload['nbf'] > $now || (int) $payload['exp'] <= $now) {
            return null;
        }

        return is_string($payload['sub']) ? $payload['sub'] : null;
    }

    private function sign(string $payload): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $payload, $this->secret(), true));
    }

    private function secret(): string
    {
        $secret = (string) $this->config->get('services.stats.jwt_secret', '');

        if ($secret === '') {
            $secret = (string) $this->config->get('app.key', '');
        }

        return $secret;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }

    /**
     * @param  array<string, mixed>  $value
     *
     * @throws JsonException
     */
    private function jsonEncode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws JsonException
     */
    private function jsonDecode(string $value): array
    {
        /** @var mixed $decoded */
        $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }
}
