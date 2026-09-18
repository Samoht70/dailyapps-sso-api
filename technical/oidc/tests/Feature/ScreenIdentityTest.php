<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Organizations\Models\Organization;
use Functional\Users\Actions\InviteUser;
use Functional\Users\Models\User;
use Functional\Users\Notifications\UserInvited;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Technical\Oidc\Livewire\Login;
use Tests\TestCase;

class ScreenIdentityTest extends TestCase
{
    use RefreshDatabase;

    private const VIEWS = __DIR__.'/../../resources/views';

    #[Test]
    public function no_screen_writes_a_control_of_its_own(): void
    {
        foreach ($this->views(self::VIEWS.'/livewire') as $path => $markup) {
            $this->assertDoesNotMatchRegularExpression(
                '/<(input|button|label|select|textarea)[\s>]/',
                $markup,
                "{$path} redefines a control the layer already defines.",
            );
        }
    }

    #[Test]
    public function no_view_but_the_logo_carries_a_colour_of_its_own(): void
    {
        foreach ($this->views(self::VIEWS) as $path => $markup) {
            if (str_contains($path, '/components/brand/')) {
                continue;
            }

            $this->assertDoesNotMatchRegularExpression(
                '/#[0-9a-fA-F]{6}\b|rgba?\(/',
                $markup,
                "{$path} states a colour instead of reaching for a token.",
            );
        }
    }

    #[Test]
    public function every_entry_screen_carries_the_brand(): void
    {
        $paths = [
            '/login',
            '/password/forgot',
            '/password/reset/'.Password::broker()->createToken($this->account()),
            '/invitations/'.$this->invitationToken(),
        ];

        foreach ($paths as $path) {
            $this->withoutVite()
                ->get($path)
                ->assertOk()
                ->assertSee(__('oidc::screens.brand.logo_alt'), escape: false)
                ->assertDontSee('data-shell="app"', escape: false);
        }
    }

    #[Test]
    public function the_profile_stands_on_the_shell_of_the_signed_in_pages(): void
    {
        $this->actingAs($this->account())
            ->withoutVite()
            ->get('/account')
            ->assertOk()
            ->assertSee('data-shell="app"', escape: false)
            ->assertSee(__('oidc::screens.brand.logo_alt'), escape: false);
    }

    #[Test]
    public function the_invitation_speaks_its_own_welcome(): void
    {
        $this->withoutVite()
            ->get('/invitations/'.$this->invitationToken())
            ->assertOk()
            ->assertSee(__('oidc::screens.brand.invitation_headline'))
            ->assertDontSee(__('oidc::screens.brand.headline'));
    }

    #[Test]
    public function an_authentication_failure_stands_above_the_form_and_not_under_the_address(): void
    {
        $component = Livewire::test(Login::class)
            ->set('email', 'nobody@dailyapps.test')
            ->set('password', 'Wrong-Horse-42!')
            ->call('authenticate');

        $component->assertSee(__('oidc::screens.login.error_title'));

        $this->assertStringNotContainsString(
            'id="email-error"',
            $component->html(),
        );
    }

    #[Test]
    public function a_validation_error_stays_under_its_own_field(): void
    {
        $component = Livewire::test(Login::class)
            ->set('email', 'not-an-address')
            ->set('password', 'Wrong-Horse-42!')
            ->call('authenticate');

        $component->assertDontSee(__('oidc::screens.login.error_title'));

        $this->assertStringContainsString('id="email-error"', $component->html());
    }

    /**
     * @return array<string, string>
     */
    private function views(string $directory): array
    {
        $markup = [];

        foreach (glob($directory.'/{,*/,*/*/}*.blade.php', GLOB_BRACE) as $path) {
            $markup[$path] = (string) file_get_contents($path);
        }

        $this->assertNotEmpty($markup, "No view found under {$directory}.");

        return $markup;
    }

    private function account(): User
    {
        return User::factory()->active()->create(['password' => Hash::make('Correct-Horse-42!')]);
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
