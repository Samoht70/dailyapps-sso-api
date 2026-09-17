<?php

namespace Functional\Users\Tests\Feature;

use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsersLayerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_persists_a_user_built_by_the_layer_factory(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', ['id' => $user->getKey(), 'email' => $user->email]);
    }

    #[Test]
    public function it_binds_the_layer_user_model_to_the_default_auth_provider(): void
    {
        $this->assertSame(User::class, config('auth.providers.users.model'));
    }
}
