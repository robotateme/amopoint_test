<?php

namespace Infrastructure\Joke;

use Domain\Joke\Joke;
use Domain\Joke\JokeProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class OfficialJokeApiClient implements JokeProvider
{
    public function random(): Joke
    {
        $payload = Http::timeout(10)
            ->acceptJson()
            ->get('https://official-joke-api.appspot.com/random_joke')
            ->throw()
            ->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Official Joke API returned an invalid response.');
        }

        return new Joke(
            externalId: (int) ($payload['id'] ?? 0),
            type: (string) ($payload['type'] ?? 'unknown'),
            setup: (string) ($payload['setup'] ?? ''),
            punchline: (string) ($payload['punchline'] ?? ''),
        );
    }
}
