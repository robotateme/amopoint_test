<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Mapping;

use Domain\Joke\Joke;
use Domain\ValueObjects\JokeExternalId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final readonly class JokeMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function attributesFromDomain(Joke $joke): array
    {
        $externalId = new JokeExternalId($joke->externalId);

        return [
            'external_id' => $externalId->value(),
            'type' => $joke->type,
            'setup' => $joke->setup,
            'punchline' => $joke->punchline,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function responseFromModel(Model $record): array
    {
        $externalId = new JokeExternalId((int) $record->getAttribute('external_id'));
        /** @var mixed $createdAt */
        $createdAt = $record->getAttribute('created_at');

        return [
            'id' => $record->getAttribute('id'),
            'external_id' => $externalId->value(),
            'type' => $record->getAttribute('type'),
            'setup' => $record->getAttribute('setup'),
            'punchline' => $record->getAttribute('punchline'),
            'created_at' => $createdAt instanceof Carbon ? $createdAt->toISOString() : null,
        ];
    }
}
