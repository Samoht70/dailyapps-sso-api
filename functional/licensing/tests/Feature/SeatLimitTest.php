<?php

namespace Functional\Licensing\Tests\Feature;

use Functional\Catalog\Models\Application;
use Functional\Licensing\Actions\GrantApplicationAccess;
use Functional\Licensing\Exceptions\NoValidLicense;
use Functional\Licensing\Exceptions\SeatsExhausted;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeatLimitTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->client()->create();
        $this->application = Application::factory()->published()->create(['slug' => 'leaves']);
    }

    #[Test]
    public function it_grants_an_access_while_a_seat_is_free(): void
    {
        $this->license(seats: 2);

        $access = $this->grant($this->account());

        $this->assertNotNull($access->granted_at);
        $this->assertDatabaseHas('application_accesses', ['id' => $access->getKey()]);
    }

    #[Test]
    public function it_refuses_the_grant_that_would_go_past_the_seats_paid_for(): void
    {
        $this->license(seats: 1);
        $this->grant($this->account());

        $this->expectException(SeatsExhausted::class);

        $this->grant($this->account());
    }

    #[Test]
    public function it_answers_the_seat_refusal_with_a_machine_code(): void
    {
        $this->license(seats: 1);
        $this->grant($this->account());

        try {
            $this->grant($this->account());
            $this->fail('The grant should have been refused.');
        } catch (SeatsExhausted $refusal) {
            $this->assertSame('seats_exhausted', $refusal->machineCode());
        }
    }

    #[Test]
    public function it_lets_only_one_of_two_grants_take_the_last_seat(): void
    {
        $this->license(seats: 1);
        $first = $this->account();
        $second = $this->account();

        $this->grant($first);

        $this->expectException(SeatsExhausted::class);

        $this->grant($second);
    }

    #[Test]
    public function it_frees_the_last_seat_when_its_holder_is_disabled(): void
    {
        $this->license(seats: 1);
        $holder = $this->account();
        $this->grant($holder);

        $holder->state()->disable();

        $this->assertNotNull($this->grant($this->account()));
    }

    #[Test]
    public function it_refuses_a_grant_when_the_organization_holds_no_valid_licence(): void
    {
        $this->expectException(NoValidLicense::class);

        $this->grant($this->account());
    }

    #[Test]
    public function it_refuses_a_grant_on_an_expired_licence(): void
    {
        License::factory()->for($this->organization)->for($this->application)->expired()->create();

        $this->expectException(NoValidLicense::class);

        $this->grant($this->account());
    }

    #[Test]
    public function it_grants_the_same_access_twice_without_taking_a_second_seat(): void
    {
        $this->license(seats: 1);
        $account = $this->account();

        $first = $this->grant($account);
        $second = $this->grant($account);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, $this->license(seats: 1)->occupiedSeats());
    }

    private function license(int $seats): License
    {
        return License::query()->updateOrCreate(
            [
                'organization_id' => $this->organization->getKey(),
                'application_id' => $this->application->getKey(),
            ],
            ['starts_on' => now()->subMonth(), 'ends_on' => now()->addYear(), 'seats' => $seats],
        );
    }

    private function account(): User
    {
        return User::factory()->for($this->organization)->active()->create();
    }

    private function grant(User $account): ApplicationAccess
    {
        return app(GrantApplicationAccess::class)($account, $this->application);
    }
}
