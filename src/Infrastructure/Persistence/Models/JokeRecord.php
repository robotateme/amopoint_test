<?php

namespace Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class JokeRecord extends Model
{
    protected $table = 'jokes';

    protected $fillable = [
        'external_id',
        'type',
        'setup',
        'punchline',
    ];

    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
        ];
    }
}
