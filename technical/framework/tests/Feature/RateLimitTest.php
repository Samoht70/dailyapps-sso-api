<?php

namespace Technical\Framework\Tests\Feature;

use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('api');

        parent::tearDown();
    }

    #[Test]
    public function it_lets_a_caller_through_while_it_stays_under_the_limit(): void
    {
        Passport::actingAs(User::factory()->active()->create());

        $this->getJson('/me')->assertOk()->assertHeader('X-RateLimit-Limit', $this->limit());
    }

    #[Test]
    public function it_answers_too_many_requests_once_the_limit_is_passed(): void
    {
        $account = User::factory()->active()->create();
        Passport::actingAs($account);

        for ($call = 0; $call < $this->limit(); $call++) {
            $this->getJson('/me');
        }

        $this->getJson('/me')->assertStatus(429);
    }

    #[Test]
    public function it_counts_each_caller_on_its_own(): void
    {
        $exhausted = User::factory()->active()->create();
        Passport::actingAs($exhausted);

        for ($call = 0; $call <= $this->limit(); $call++) {
            $this->getJson('/me');
        }

        $this->app['auth']->forgetGuards();
        Passport::actingAs(User::factory()->active()->create());

        $this->getJson('/me')->assertOk();
    }

    #[Test]
    public function it_carries_the_limit_in_the_layer_configuration(): void
    {
        $this->assertIsInt(config('framework.rate_limits.api_per_minute'));
    }

    private function limit(): int
    {
        return config('framework.rate_limits.api_per_minute');
    }
}
