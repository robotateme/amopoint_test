<?php

namespace App\Models;

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
}
