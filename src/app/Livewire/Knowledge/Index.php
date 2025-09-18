<?php

namespace App\Livewire\Knowledge;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.knowledge.index')
            ->layout('layouts.app', ['header' => 'Knowledge Hub']);
    }
}
