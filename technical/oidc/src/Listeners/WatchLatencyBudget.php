<?php

namespace Technical\Oidc\Listeners;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Log;

class WatchLatencyBudget
{
    /**
     * Fifteen applications lean on these two routes; a drift shows up here long
     * before anybody reports being unable to sign in. A p95 still needs a load
     * run — this only says when a single request went over.
     */
    public function handle(RequestHandled $event): void
    {
        $budget = config('oidc.performance.budgets_ms')[$event->request->path()] ?? null;
        $startedAt = $event->request->server('REQUEST_TIME_FLOAT');

        if ($budget === null || $startedAt === null) {
            return;
        }

        $elapsed = (microtime(true) - (float) $startedAt) * 1000;

        if ($elapsed <= $budget) {
            return;
        }

        Log::warning('An OIDC route ran past its latency budget.', [
            'route' => $event->request->path(),
            'elapsed_ms' => round($elapsed),
            'budget_ms' => $budget,
            'status' => $event->response->getStatusCode(),
        ]);
    }
}
