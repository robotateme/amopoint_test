<?php

use App\Models\JokeRecord;
use App\Models\VisitRecord;

return [
    'models' => [
        'joke' => JokeRecord::class,
        'visit' => VisitRecord::class,
    ],
];
