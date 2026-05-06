<?php

namespace Domain\Joke;

interface JokeProvider
{
    public function random(): Joke;
}
