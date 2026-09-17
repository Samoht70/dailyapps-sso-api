<?php

namespace Technical\Oidc\Tests\Feature;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\Listeners\WatchLatencyBudget;
use Tests\TestCase;

class LatencyBudgetTest extends TestCase
{
    #[Test]
    public function it_carries_a_budget_for_each_route_the_ecosystem_leans_on(): void
    {
        $budgets = config('oidc.performance.budgets_ms');

        $this->assertSame(300, $budgets['oauth/token']);
        $this->assertArrayHasKey('oauth/authorize', $budgets);
    }

    #[Test]
    public function it_says_nothing_about_a_route_that_carries_no_budget(): void
    {
        Log::spy();

        $this->watch('login');

        Log::shouldNotHaveReceived('warning');
    }

    #[Test]
    public function it_warns_when_a_watched_route_runs_past_its_budget(): void
    {
        Log::spy();

        $this->watch('oauth/token', elapsedMs: config('oidc.performance.budgets_ms')['oauth/token'] + 50);

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => $context['route'] === 'oauth/token'
                && $context['budget_ms'] === 300,
        );
    }

    #[Test]
    public function it_stays_quiet_while_a_watched_route_holds_its_budget(): void
    {
        Log::spy();

        $this->watch('oauth/token', elapsedMs: 10);

        Log::shouldNotHaveReceived('warning');
    }

    private function watch(string $path, int $elapsedMs = 0): void
    {
        $request = Request::create("/{$path}");
        $request->server->set('REQUEST_TIME_FLOAT', microtime(true) - ($elapsedMs / 1000));

        (new WatchLatencyBudget)->handle(new RequestHandled($request, new Response));
    }
}
