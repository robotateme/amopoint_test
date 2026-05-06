<?php

namespace App\Http\Controllers;

use Application\Visit\Command\RecordVisit\RecordVisitCommand;
use Application\Visit\Command\RecordVisit\RecordVisitHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VisitController extends Controller
{
    public function store(Request $request, RecordVisitHandler $handler): JsonResponse
    {
        $handler->handle(new RecordVisitCommand(
            ip: $request->ip() ?? '0.0.0.0',
            fingerprint: $request->string('fingerprint')->toString(),
            device: $request->string('device')->toString(),
            userAgent: $request->userAgent(),
            pageUrl: $request->string('page_url')->toString(),
            referrer: $request->string('referrer')->toString(),
        ));

        return response()
            ->json(['ok' => true], 201)
            ->withHeaders($this->corsHeaders());
    }

    public function options(): JsonResponse
    {
        return response()
            ->json([], 204)
            ->withHeaders($this->corsHeaders());
    }

    /**
     * @return array<string, string>
     */
    private function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
        ];
    }
}
