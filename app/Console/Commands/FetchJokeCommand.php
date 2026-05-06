<?php

namespace App\Console\Commands;

use Application\Joke\FetchJoke\FetchJokeCommand as FetchJoke;
use Application\Joke\FetchJoke\FetchJokeHandler;
use Illuminate\Console\Command;

final class FetchJokeCommand extends Command
{
    protected $signature = 'jokes:fetch';

    protected $description = 'Fetch a random joke from Official Joke API and store it.';

    public function handle(FetchJokeHandler $handler): int
    {
        $joke = $handler->handle(new FetchJoke());

        $this->info(sprintf('Stored joke #%d: %s', $joke->externalId, $joke->setup));

        return self::SUCCESS;
    }
}
