<?php

namespace Application\Joke\Command\FetchJoke;

use Domain\Joke\Joke;
use Domain\Joke\JokeProvider;
use Domain\Joke\JokeRepository;

final readonly class FetchJokeHandler
{
    public function __construct(
        private JokeProvider $provider,
        private JokeRepository $repository,
    ) {}

    public function handle(FetchJokeCommand $command): Joke
    {
        $joke = $this->provider->random();
        $this->repository->save($joke);

        return $joke;
    }
}
