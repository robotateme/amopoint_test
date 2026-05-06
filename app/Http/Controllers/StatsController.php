<?php

namespace App\Http\Controllers;

use Application\Visit\Query\GetVisitStatistics\GetVisitStatisticsHandler;
use Application\Visit\Query\GetVisitStatistics\GetVisitStatisticsQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class StatsController extends Controller
{
    public function __invoke(Request $request, GetVisitStatisticsHandler $handler): View
    {
        $hours = min(max($request->integer('hours', 24), 1), 168);

        return view('stats.index', [
            'stats' => $handler->handle(new GetVisitStatisticsQuery($hours)),
            'hours' => $hours,
        ]);
    }
}
