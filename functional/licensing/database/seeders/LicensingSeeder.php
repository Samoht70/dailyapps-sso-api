<?php

namespace Functional\Licensing\Database\Seeders;

use Functional\Catalog\Enums\ApplicationStatus;
use Functional\Catalog\Models\Application;
use Functional\Catalog\Models\ApplicationRole;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Enums\OrganizationKind;
use Functional\Organizations\Models\Organization;
use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Enums\UserStatus;
use Functional\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Technical\Oidc\Models\Client;

class LicensingSeeder extends Seeder
{
    private const DEMO_ROLES = [
        'user' => 'Utilisateur',
        'manager' => 'Responsable',
    ];

    public function run(): void
    {
        $acme = $this->organization('Acme');
        $this->organization('Globex');

        $leaves = $this->application('leaves', 'Congés');
        $expenses = $this->application('expenses', 'Notes de frais');

        $this->command?->line('Licence Acme × leaves, 2 sièges');
        $running = License::query()->firstOrCreate(
            ['organization_id' => $acme->getKey(), 'application_id' => $leaves->getKey()],
            ['starts_on' => now()->subMonth(), 'ends_on' => now()->addYear(), 'seats' => 2],
        );

        $this->command?->line('Licence Acme × expenses, expirée');
        License::query()->firstOrCreate(
            ['organization_id' => $acme->getKey(), 'application_id' => $expenses->getKey()],
            ['starts_on' => now()->subYears(2), 'ends_on' => now()->subDay(), 'seats' => 5],
        );

        $entitled = $this->user($acme, 'Camille Acme', 'camille@acme.test', OrganizationRole::Admin);
        $this->user($acme, 'Dominique Acme', 'dominique@acme.test', OrganizationRole::Member);

        $this->command?->line("Accès de {$entitled->email} à leaves");
        ApplicationAccess::query()->firstOrCreate(
            ['user_id' => $entitled->getKey(), 'application_id' => $leaves->getKey()],
            ['granted_at' => now()],
        );

        $this->command?->comment("Jeu de démonstration en place — licence leaves sur {$running->seats} sièges.");
    }

    private function organization(string $name): Organization
    {
        $this->command?->line("Organisation cliente {$name}");

        return Organization::query()->firstOrCreate(
            ['name' => $name],
            ['kind' => OrganizationKind::Client],
        );
    }

    private function application(string $slug, string $name): Application
    {
        $this->command?->line("Application {$slug}, publiée, avec ses rôles");

        $application = Application::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'home_url' => "https://{$slug}.dailyapps.test",
                'status' => ApplicationStatus::Published,
                'oauth_client_id' => $this->client($slug, $name)->getKey(),
            ],
        );

        foreach (self::DEMO_ROLES as $key => $label) {
            ApplicationRole::query()->firstOrCreate(
                ['application_id' => $application->getKey(), 'key' => $key],
                ['label' => $label],
            );
        }

        return $application;
    }

    private function client(string $slug, string $name): Client
    {
        return Client::query()->firstOrCreate(
            ['name' => $name],
            [
                'redirect_uris' => ["https://{$slug}.dailyapps.test/auth/callback"],
                'grant_types' => ['authorization_code', 'refresh_token'],
                'revoked' => false,
            ],
        );
    }

    private function user(Organization $organization, string $name, string $email, OrganizationRole $role): User
    {
        $this->command?->line("Utilisateur {$email}");

        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'organization_id' => $organization->getKey(),
                'name' => $name,
                'password' => Hash::make('password'),
                'status' => UserStatus::Active,
                'organization_role' => $role,
                'email_verified_at' => now(),
            ],
        );
    }
}
