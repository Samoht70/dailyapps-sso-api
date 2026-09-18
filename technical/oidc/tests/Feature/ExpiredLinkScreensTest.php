<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Organizations\Models\Organization;
use Functional\Users\Actions\InviteUser;
use Functional\Users\Models\Invitation;
use Functional\Users\Models\User;
use Functional\Users\Notifications\UserInvited;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCase;

class ExpiredLinkScreensTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_expired_invitation_is_gone_and_says_so(): void
    {
        $token = $this->invitationToken();
        Invitation::query()->where('email', 'camille@acme.test')->update(['expires_at' => now()->subDay()]);

        $this->withoutVite()
            ->get("/invitations/{$token}")
            ->assertStatus(410)
            ->assertSee(__('oidc::screens.invitation.expired'))
            ->assertSee(__('oidc::screens.link_expired.ask_administrator'))
            ->assertSee(__('oidc::screens.brand.logo_alt'), escape: false);
    }

    #[Test]
    public function the_expired_invitation_offers_nothing_the_visitor_could_do(): void
    {
        $token = $this->invitationToken();
        Invitation::query()->where('email', 'camille@acme.test')->update(['expires_at' => now()->subDay()]);

        $body = $this->withoutVite()->get("/invitations/{$token}")->getContent();

        $this->assertStringNotContainsString(route('password.forgot'), $body);
        $this->assertStringNotContainsString('camille@acme.test', $body);
    }

    #[Test]
    public function an_invitation_nobody_issued_answers_exactly_like_an_expired_one(): void
    {
        $issued = $this->invitationToken();
        Invitation::query()->where('email', 'camille@acme.test')->update(['expires_at' => now()->subDay()]);

        foreach ([$issued, Invitation::freshToken()] as $token) {
            $this->withoutVite()
                ->get("/invitations/{$token}")
                ->assertStatus(410)
                ->assertSee(__('oidc::screens.invitation.expired'))
                ->assertDontSee(__('oidc::screens.invitation.heading'));
        }
    }

    #[Test]
    public function a_dead_reset_link_explains_itself_and_offers_a_new_one(): void
    {
        $account = User::factory()->active()->create();
        $token = Password::broker()->createToken($account);
        Password::broker()->deleteToken($account);

        $this->withoutVite()
            ->get("/password/reset/{$token}?email=".urlencode($account->email))
            ->assertOk()
            ->assertSee(__('oidc::screens.reset_password.invalid'))
            ->assertSee(__('oidc::screens.link_expired.request_new'))
            ->assertSee(route('password.forgot'), escape: false);
    }

    #[Test]
    public function an_address_nobody_holds_answers_exactly_like_a_dead_link(): void
    {
        $account = User::factory()->active()->create();
        $token = Password::broker()->createToken($account);
        Password::broker()->deleteToken($account);

        foreach ([$account->email, 'nobody@dailyapps.test'] as $address) {
            $this->withoutVite()
                ->get("/password/reset/{$token}?email=".urlencode($address))
                ->assertOk()
                ->assertSee(__('oidc::screens.reset_password.invalid'))
                ->assertDontSee(__('oidc::screens.reset_password.heading'));
        }
    }

    #[Test]
    public function a_live_reset_link_still_serves_the_form(): void
    {
        $account = User::factory()->active()->create();
        $token = Password::broker()->createToken($account);

        $this->withoutVite()
            ->get("/password/reset/{$token}?email=".urlencode($account->email))
            ->assertOk()
            ->assertSee(__('oidc::screens.reset_password.heading'))
            ->assertDontSee(__('oidc::screens.link_expired.request_new'));
    }

    #[Test]
    public function a_link_stripped_of_its_address_falls_back_to_the_form(): void
    {
        $account = User::factory()->active()->create();
        $token = Password::broker()->createToken($account);
        Password::broker()->deleteToken($account);

        $this->withoutVite()
            ->get("/password/reset/{$token}")
            ->assertOk()
            ->assertSee(__('oidc::screens.reset_password.heading'))
            ->assertDontSee(__('oidc::screens.link_expired.request_new'));
    }

    private function invitationToken(): string
    {
        Notification::fake();

        $organization = Organization::factory()->client()->create();
        app(InviteUser::class)($organization, 'camille@acme.test', 'Camille Acme');

        $token = null;

        Notification::assertSentTo(
            User::query()->where('email', 'camille@acme.test')->sole(),
            UserInvited::class,
            function ($notification) use (&$token): bool {
                $token = (new ReflectionProperty($notification, 'token'))->getValue($notification);

                return true;
            },
        );

        return $token;
    }
}
