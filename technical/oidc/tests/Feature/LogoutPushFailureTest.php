<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Catalog\Enums\ApplicationStatus;
use Functional\Catalog\Models\Application;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Audit\Models\SecurityEvent;
use Technical\Oidc\Enums\LogoutReason;
use Technical\Oidc\Jobs\PushBackchannelLogout;
use Technical\Oidc\Models\Client;
use Technical\Oidc\Models\SsoSession;
use Technical\Oidc\Models\SsoSessionParticipant;
use Tests\TestCase;

class LogoutPushFailureTest extends TestCase
{
    use RefreshDatabase;

    private SsoSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->client()->create();
        $account = User::factory()->for($organization)->active()->create();
        $this->session = SsoSession::factory()->for($account)->create();
    }

    #[Test]
    public function it_retries_a_push_that_the_application_refused(): void
    {
        Http::fake(['*' => Http::response('', 500)]);
        $participant = $this->session->admit($this->application('leaves', 'https://leaves.dailyapps.test/logout'));

        $this->expectException(RequestException::class);

        $this->push($participant);
    }

    #[Test]
    public function it_leaves_the_participant_unstamped_while_the_push_has_not_gone_through(): void
    {
        Http::fake(['*' => Http::response('', 500)]);
        $participant = $this->session->admit($this->application('leaves', 'https://leaves.dailyapps.test/logout'));

        try {
            $this->push($participant);
        } catch (RequestException) {
            // the queue is what retries; here only the outcome matters
        }

        $this->assertTrue($participant->fresh()->awaitsLogoutPush());
    }

    #[Test]
    public function it_journals_a_push_that_never_went_through(): void
    {
        $participant = $this->session->admit($this->application('leaves', 'https://leaves.dailyapps.test/logout'));

        (new PushBackchannelLogout($participant, LogoutReason::UserDisabled))
            ->failed(new \RuntimeException('Connection refused'));

        $event = SecurityEvent::query()
            ->where('type', SecurityEventType::LogoutPushFailed)
            ->sole();

        $this->assertSame('leaves', $event->payload['application']);
        $this->assertSame($this->session->getKey(), $event->payload['sid']);
        $this->assertSame('Connection refused', $event->payload['failure']);
    }

    #[Test]
    public function it_warns_the_reachable_application_even_when_another_one_is_down(): void
    {
        Http::fake([
            'https://leaves.dailyapps.test/*' => Http::response('', 500),
            'https://expenses.dailyapps.test/*' => Http::response('', 204),
        ]);

        $down = $this->session->admit($this->application('leaves', 'https://leaves.dailyapps.test/logout'));
        $reachable = $this->session->admit($this->application('expenses', 'https://expenses.dailyapps.test/logout'));

        try {
            $this->push($down);
        } catch (RequestException) {
            // one unreachable application holds up its own retries and nobody else's
        }

        $this->push($reachable);

        $this->assertTrue($down->fresh()->awaitsLogoutPush());
        $this->assertFalse($reachable->fresh()->awaitsLogoutPush());
    }

    #[Test]
    public function it_says_out_loud_that_an_application_declaring_no_address_is_never_told(): void
    {
        Http::fake();
        $participant = $this->session->admit($this->application('payroll', null));

        $this->push($participant);

        Http::assertNothingSent();

        $event = SecurityEvent::query()
            ->where('type', SecurityEventType::LogoutPushFailed)
            ->sole();

        $this->assertSame('no_backchannel_logout_url', $event->payload['failure']);
        $this->assertSame('payroll', $event->payload['application']);
    }

    #[Test]
    public function it_leaves_such_an_application_holding_only_by_its_token_lifetime(): void
    {
        Http::fake();
        $participant = $this->session->admit($this->application('payroll', null));

        $this->push($participant);

        $this->assertTrue($participant->fresh()->awaitsLogoutPush());
    }

    private function push(SsoSessionParticipant $participant): void
    {
        app()->call([new PushBackchannelLogout($participant, LogoutReason::UserDisabled), 'handle']);
    }

    private function application(string $slug, ?string $backchannelLogoutUrl): Application
    {
        return Application::query()->create([
            'slug' => $slug,
            'name' => ucfirst($slug),
            'home_url' => "https://{$slug}.dailyapps.test",
            'backchannel_logout_url' => $backchannelLogoutUrl,
            'status' => ApplicationStatus::Published,
            'oauth_client_id' => Client::factory()->asPublic()->create()->getKey(),
        ]);
    }
}
