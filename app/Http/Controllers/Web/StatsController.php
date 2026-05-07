<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Application\Visit\Query\GetVisitStatistics\GetVisitStatisticsHandler;
use Application\Visit\Query\GetVisitStatistics\GetVisitStatisticsQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StatsController extends Controller
{
    public function __invoke(Request $request, GetVisitStatisticsHandler $handler): View|JsonResponse
    {
        $hours = min(max($request->integer('hours', 24), 1), 168);
        $stats = $handler->handle(new GetVisitStatisticsQuery($hours));

        if ($request->expectsJson()) {
            return response()->json([
                'stats' => $stats,
                'hours' => $hours,
            ]);
        }

        return view('stats.index', [
            'stats' => $stats,
            'hours' => $hours,
        ]);
    }
}
