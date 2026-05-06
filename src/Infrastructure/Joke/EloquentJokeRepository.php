<?php

namespace Infrastructure\Joke;

use Domain\Joke\Joke;
use Domain\Joke\JokeRepository;
use Infrastructure\Persistence\Models\JokeRecord;

final class EloquentJokeRepository implements JokeRepository
{
    public function save(Joke $joke): void
    {
        JokeRecord::create([
            'external_id' => $joke->externalId,
            'type' => $joke->type,
            'setup' => $joke->setup,
            'punchline' => $joke->punchline,
        ]);
    }

    public function latest(int $limit = 50): array
    {
        return JokeRecord::query()
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'external_id', 'type', 'setup', 'punchline', 'created_at'])
            ->map(fn (JokeRecord $record): array => [
                'id' => $record->id,
                'external_id' => $record->external_id,
                'type' => $record->type,
                'setup' => $record->setup,
                'punchline' => $record->punchline,
                'created_at' => $record->created_at?->toISOString(),
            ])
            ->all();
    }
}
