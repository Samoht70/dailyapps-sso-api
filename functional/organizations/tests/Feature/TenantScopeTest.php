<?php

namespace Functional\Organizations\Tests\Feature;

use Functional\Organizations\Enums\OrganizationKind;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Technical\Permissions\Database\Seeders\PermissionsSeeder;
use Technical\Permissions\Enums\Permission;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    private Organization $acme;

    private Organization $globex;

    private User $acmeAdministrator;

    private User $globexMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $this->acme = Organization::factory()->client()->create(['name' => 'Acme']);
        $this->globex = Organization::factory()->client()->create(['name' => 'Globex']);

        $this->acmeAdministrator = User::factory()->for($this->acme)->admin()->active()->create();
        $this->globexMember = User::factory()->for($this->globex)->active()->create();
    }

    #[Test]
    public function it_shows_an_administrator_only_the_accounts_of_their_own_organization(): void
    {
        Passport::actingAs($this->acmeAdministrator);

        $ids = $this->postJson('/users/search', ['search' => []])->assertOk()->json('data.*.id');

        $this->assertContains($this->acmeAdministrator->getKey(), $ids);
        $this->assertNotContains($this->globexMember->getKey(), $ids);
    }

    #[Test]
    public function it_shows_an_administrator_only_their_own_organization(): void
    {
        Passport::actingAs($this->acmeAdministrator);

        $ids = $this->postJson('/organizations/search', ['search' => []])->assertOk()->json('data.*.id');

        $this->assertSame([$this->acme->getKey()], $ids);
    }

    #[Test]
    public function it_refuses_an_administrator_writing_on_another_organization(): void
    {
        Passport::actingAs($this->acmeAdministrator);

        $this->postJson('/users/mutate', [
            'mutate' => [[
                'operation' => 'update',
                'key' => $this->globexMember->getKey(),
                'attributes' => ['name' => 'Renommé de force'],
            ]],
        ])->assertForbidden();

        $this->assertSame($this->globexMember->name, $this->globexMember->fresh()->name);
    }

    #[Test]
    public function it_refuses_an_administrator_writing_on_another_organization_record(): void
    {
        Passport::actingAs($this->acmeAdministrator);

        $this->postJson('/organizations/mutate', [
            'mutate' => [[
                'operation' => 'update',
                'key' => $this->globex->getKey(),
                'attributes' => ['name' => 'Renommé de force'],
            ]],
        ])->assertForbidden();

        $this->assertSame('Globex', $this->globex->fresh()->name);
    }

    #[Test]
    public function it_refuses_a_plain_member_writing_on_their_own_organization(): void
    {
        Passport::actingAs($this->globexMember);

        $this->postJson('/organizations/mutate', [
            'mutate' => [[
                'operation' => 'update',
                'key' => $this->globex->getKey(),
                'attributes' => ['name' => 'Renommé par un membre'],
            ]],
        ])->assertForbidden();
    }

    #[Test]
    public function it_shows_the_operator_every_organization(): void
    {
        Passport::actingAs($this->operator());

        $ids = $this->postJson('/organizations/search', ['search' => []])->assertOk()->json('data.*.id');

        $this->assertContains($this->acme->getKey(), $ids);
        $this->assertContains($this->globex->getKey(), $ids);
    }

    #[Test]
    public function it_refuses_an_operator_account_without_the_permission(): void
    {
        $operator = Organization::query()->firstOrCreate(
            ['kind' => OrganizationKind::Operator],
            ['name' => 'DailyApps'],
        );

        Passport::actingAs(User::factory()->for($operator)->admin()->active()->create());

        $ids = $this->postJson('/organizations/search', ['search' => []])->assertOk()->json('data.*.id');

        $this->assertNotContains($this->globex->getKey(), $ids);
    }

    private function operator(): User
    {
        $operator = Organization::query()->firstOrCreate(
            ['kind' => OrganizationKind::Operator],
            ['name' => 'DailyApps'],
        );

        $account = User::factory()->for($operator)->admin()->active()->create();
        $account->syncPermissions(Permission::names());

        return $account->fresh();
    }
}
