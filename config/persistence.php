<?php

use App\Models\JokeRecord;
use App\Models\VisitRecord;
use Application\Visit\VisitRepository;
use Domain\Joke\JokeRepository;
use Infrastructure\Joke\EloquentJokeRepository;
use Infrastructure\Visit\EloquentVisitRepository;

return [
    'models' => [
        'joke' => JokeRecord::class,
        'visit' => VisitRecord::class,
    ],
    'repositories' => [
        JokeRepository::class => EloquentJokeRepository::class,
        VisitRepository::class => EloquentVisitRepository::class,
    ],
];
