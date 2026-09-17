<?php

namespace Technical\Oidc\Tests\Feature;

use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\Livewire\Login;
use Tests\TestCase;

class LayerViewsTest extends TestCase
{
    /**
     * The point the T009 spike settled: a layer can serve Blade views and
     * Livewire components, which the OSDD package documents nowhere. The five
     * real screens now stand as that proof, so the ping scaffold is gone.
     */
    #[Test]
    public function it_serves_a_blade_view_registered_by_the_layer(): void
    {
        $this->withoutVite()
            ->get('/login')
            ->assertOk()
            ->assertSee(__('oidc::screens.login.heading'));
    }

    #[Test]
    public function it_resolves_the_layer_view_namespace(): void
    {
        $this->assertTrue(view()->exists('oidc::livewire.login'));
        $this->assertTrue(view()->exists('oidc::components.layouts.screen'));
    }

    #[Test]
    public function it_renders_a_livewire_component_registered_by_the_layer(): void
    {
        Livewire::test(Login::class)
            ->assertOk()
            ->assertSee(__('oidc::screens.login.submit'));
    }

    #[Test]
    public function it_mounts_the_layer_livewire_component_from_a_layer_route(): void
    {
        $this->withoutVite()->get('/login')->assertSeeLivewire(Login::class);
    }
}
