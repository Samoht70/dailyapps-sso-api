<?php

namespace Functional\Catalog\Tests\Feature;

use Functional\Catalog\Actions\PublishApplication;
use Functional\Catalog\Actions\RetireApplication;
use Functional\Catalog\Enums\ApplicationStatus;
use Functional\Catalog\Exceptions\IllegalApplicationTransition;
use Functional\Catalog\Models\Application;
use Functional\Catalog\Models\ApplicationRole;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Technical\Audit\Enums\SecurityEventType;
use Tests\TestCase;

class ApplicationRetirementTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $account;

    private Application $leaves;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->client()->create();
        $this->account = User::factory()->for($this->organization)->active()->create();

        $this->leaves = Application::factory()->published()->create(['slug' => 'leaves']);
        License::factory()->for($this->organization)->for($this->leaves)->valid()->create();
        ApplicationAccess::factory()->for($this->account)->for($this->leaves)->create();
    }

    #[Test]
    public function it_drops_a_retired_application_out_of_the_rights_answered(): void
    {
        $this->assertSame(['leaves'], $this->reachableApplications());

        app(RetireApplication::class)($this->leaves);

        $this->assertSame([], $this->reachableApplications());
    }

    #[Test]
    public function it_keeps_the_accesses_granted_in_the_past(): void
    {
        app(RetireApplication::class)($this->leaves);

        $this->assertDatabaseHas('application_accesses', [
            'user_id' => $this->account->getKey(),
            'application_id' => $this->leaves->getKey(),
        ]);
    }

    #[Test]
    public function it_gives_the_rights_back_when_the_application_returns_to_the_catalog(): void
    {
        app(RetireApplication::class)($this->leaves);

        app(PublishApplication::class)($this->leaves->fresh());

        $this->assertSame(['leaves'], $this->reachableApplications());
    }

    #[Test]
    public function it_never_sends_a_retired_application_back_to_draft(): void
    {
        $retired = app(RetireApplication::class)($this->leaves);

        $this->expectException(IllegalApplicationTransition::class);

        $retired->retire();
    }

    #[Test]
    public function it_journals_the_retirement(): void
    {
        app(RetireApplication::class)($this->leaves);

        $this->assertDatabaseHas('security_events', [
            'type' => SecurityEventType::ApplicationRetired->value,
        ]);
    }

    #[Test]
    public function it_leaves_the_other_applications_in_the_catalog(): void
    {
        $expenses = Application::factory()->published()->create(['slug' => 'expenses']);
        License::factory()->for($this->organization)->for($expenses)->valid()->create();
        ApplicationAccess::factory()->for($this->account)->for($expenses)->create();

        app(RetireApplication::class)($this->leaves);

        $this->assertSame(['expenses'], $this->reachableApplications());
        $this->assertSame(ApplicationStatus::Published, $expenses->fresh()->status);
    }

    #[Test]
    public function it_detaches_a_deleted_role_from_the_accesses_that_held_it(): void
    {
        $role = ApplicationRole::factory()->for($this->leaves)->create(['key' => 'manager']);
        $access = ApplicationAccess::query()
            ->where('user_id', $this->account->getKey())
            ->where('application_id', $this->leaves->getKey())
            ->sole();
        $access->roles()->attach($role->getKey());

        $role->delete();

        $this->assertSame(0, DB::table('application_access_role')
            ->where('application_role_id', $role->getKey())->count());
        $this->assertDatabaseHas('application_accesses', ['id' => $access->getKey()]);
    }

    #[Test]
    public function it_leaves_the_roles_of_other_accesses_alone_when_one_role_goes(): void
    {
        $manager = ApplicationRole::factory()->for($this->leaves)->create(['key' => 'manager']);
        $user = ApplicationRole::factory()->for($this->leaves)->create(['key' => 'user']);
        $access = ApplicationAccess::query()
            ->where('user_id', $this->account->getKey())
            ->where('application_id', $this->leaves->getKey())
            ->sole();
        $access->roles()->attach([$manager->getKey(), $user->getKey()]);

        $manager->delete();

        $this->assertSame(['user'], $access->fresh()->roles->pluck('key')->all());
    }

    /** @return list<string> */
    private function reachableApplications(): array
    {
        Passport::actingAs($this->account->fresh());

        return $this->getJson('/me/applications')->assertOk()->json('data.*.slug');
    }
}
