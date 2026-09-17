<?php

namespace Technical\Oidc\Tests\Feature;

use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\Livewire\PingCounter;
use Tests\TestCase;

class LayerViewsTest extends TestCase
{
    #[Test]
    public function it_serves_a_blade_view_registered_by_the_layer(): void
    {
        $this->withoutVite()
            ->get('/oidc/ping')
            ->assertOk()
            ->assertSee('vue servie depuis la couche technical/oidc');
    }

    #[Test]
    public function it_resolves_the_layer_view_namespace(): void
    {
        $this->assertTrue(view()->exists('oidc::ping'));
    }

    #[Test]
    public function it_renders_a_livewire_component_registered_by_the_layer(): void
    {
        Livewire::test(PingCounter::class)
            ->assertSee('0')
            ->call('increment')
            ->assertSet('count', 1);
    }

    #[Test]
    public function it_mounts_the_layer_livewire_component_from_the_layer_view(): void
    {
        $this->withoutVite()
            ->get('/oidc/ping')
            ->assertSeeLivewire('oidc.ping-counter');
    }
}
