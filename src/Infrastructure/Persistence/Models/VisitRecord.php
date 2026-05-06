<?php

namespace Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class VisitRecord extends Model
{
    protected $table = 'visits';

    protected $fillable = [
        'fingerprint',
        'ip',
        'city',
        'device',
        'user_agent',
        'page_url',
        'referrer',
    ];
}
