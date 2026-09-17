<?php

namespace Functional\Users\Tests\Feature;

use Functional\Organizations\Models\Organization;
use Functional\Users\Actions\ChangeOrganizationRole;
use Functional\Users\Actions\DisableUser;
use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Enums\UserStatus;
use Functional\Users\Exceptions\LastAdministrator;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LastAdminTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->client()->create();
    }

    #[Test]
    public function it_refuses_to_disable_the_only_active_administrator(): void
    {
        $administrator = $this->administrator();

        $this->expectException(LastAdministrator::class);

        app(DisableUser::class)($administrator);
    }

    #[Test]
    public function it_answers_that_refusal_with_a_machine_code(): void
    {
        $administrator = $this->administrator();

        try {
            app(DisableUser::class)($administrator);
            $this->fail('Disabling the last administrator should have been refused.');
        } catch (LastAdministrator $refusal) {
            $this->assertSame('last_admin', $refusal->machineCode());
        }
    }

    #[Test]
    public function it_refuses_to_demote_the_only_active_administrator(): void
    {
        $administrator = $this->administrator();

        $this->expectException(LastAdministrator::class);

        app(ChangeOrganizationRole::class)($administrator, OrganizationRole::Member);
    }

    #[Test]
    public function it_disables_an_administrator_once_another_one_stands(): void
    {
        $administrator = $this->administrator();
        $this->administrator();

        $disabled = app(DisableUser::class)($administrator);

        $this->assertSame(UserStatus::Disabled, $disabled->status);
    }

    #[Test]
    public function it_counts_no_invited_administrator_as_standing(): void
    {
        $administrator = $this->administrator();
        User::factory()->for($this->organization)->admin()->invited()->create();

        $this->expectException(LastAdministrator::class);

        app(DisableUser::class)($administrator);
    }

    #[Test]
    public function it_counts_no_administrator_of_another_organization_as_standing(): void
    {
        $administrator = $this->administrator();
        User::factory()->admin()->active()->create();

        $this->expectException(LastAdministrator::class);

        app(DisableUser::class)($administrator);
    }

    #[Test]
    public function it_disables_a_plain_member_without_asking(): void
    {
        $this->administrator();
        $member = User::factory()->for($this->organization)->active()->create();

        $this->assertSame(UserStatus::Disabled, app(DisableUser::class)($member)->status);
    }

    #[Test]
    public function it_demotes_an_administrator_once_another_one_stands(): void
    {
        $administrator = $this->administrator();
        $this->administrator();

        $demoted = app(ChangeOrganizationRole::class)($administrator, OrganizationRole::Member);

        $this->assertSame(OrganizationRole::Member, $demoted->organization_role);
    }

    private function administrator(): User
    {
        return User::factory()->for($this->organization)->admin()->active()->create();
    }
}
