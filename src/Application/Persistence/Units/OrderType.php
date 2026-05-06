<?php

declare(strict_types=1);

namespace Application\Persistence\Units;

enum OrderType: string
{
    case ASC = 'asc';
    case DESC = 'desc';
}
