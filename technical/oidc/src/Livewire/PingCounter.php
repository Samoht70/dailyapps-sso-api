<?php

namespace Technical\Oidc\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class PingCounter extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function render(): View
    {
        return view('oidc::ping-counter');
    }
}
