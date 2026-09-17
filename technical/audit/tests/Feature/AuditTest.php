<?php

namespace Technical\Audit\Tests\Feature;

use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Audit\Models\SecurityEvent;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_nineteen_events_the_product_must_journal(): void
    {
        $this->assertCount(19, SecurityEventType::cases());
    }

    #[Test]
    public function it_never_lets_an_event_be_modified_after_the_fact(): void
    {
        $this->assertFalse(Schema::hasColumn('security_events', 'updated_at'));
        $this->assertNull(SecurityEvent::UPDATED_AT);
    }

    #[Test]
    public function it_records_an_event_through_the_single_entry_point(): void
    {
        $actor = User::factory()->admin()->create();

        $event = app(RecordSecurityEvent::class)(
            SecurityEventType::AccessGranted,
            actor: $actor,
            payload: ['application' => 'leaves'],
        );

        $this->assertSame(SecurityEventType::AccessGranted, $event->type);
        $this->assertTrue($actor->is($event->actor));
        $this->assertSame(['application' => 'leaves'], $event->payload);
    }

    #[Test]
    public function it_files_an_event_under_the_organization_of_its_actor(): void
    {
        $actor = User::factory()->create();

        $event = app(RecordSecurityEvent::class)(SecurityEventType::PasswordChanged, actor: $actor);

        $this->assertTrue($actor->organization->is($event->organization));
    }

    #[Test]
    public function it_files_an_event_under_the_organization_it_is_given(): void
    {
        $organization = Organization::factory()->client()->create();

        $event = app(RecordSecurityEvent::class)(
            SecurityEventType::OrganizationSuspended,
            organization: $organization,
            subject: $organization,
        );

        $this->assertTrue($organization->is($event->organization));
        $this->assertTrue($organization->is($event->subject));
    }

    #[Test]
    public function it_records_a_failed_authentication_without_an_actor(): void
    {
        $event = app(RecordSecurityEvent::class)(
            SecurityEventType::AuthenticationFailed,
            payload: ['email' => 'unknown@dailyapps.test'],
        );

        $this->assertNull($event->actor_id);
        $this->assertNull($event->organization_id);
        $this->assertDatabaseHas('security_events', ['id' => $event->getKey()]);
    }

    #[Test]
    public function it_keeps_the_journal_for_twelve_months(): void
    {
        $this->assertSame(12, SecurityEvent::RETENTION_MONTHS);

        SecurityEvent::factory()->recordedMonthsAgo(13)->create();
        SecurityEvent::factory()->recordedMonthsAgo(11)->create();

        $this->assertSame(1, (new SecurityEvent)->prunable()->count());
    }

    #[Test]
    public function it_prunes_the_events_past_their_retention(): void
    {
        $expired = SecurityEvent::factory()->recordedMonthsAgo(18)->create();
        $kept = SecurityEvent::factory()->recordedMonthsAgo(1)->create();

        $this->artisan('model:prune', ['--model' => [SecurityEvent::class]])->assertSuccessful();

        $this->assertDatabaseMissing('security_events', ['id' => $expired->getKey()]);
        $this->assertDatabaseHas('security_events', ['id' => $kept->getKey()]);
    }
}
