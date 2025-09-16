<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Closure;

class Card extends Component
{
    public function render(): View|Closure|string
    {
        return view('components.card');
    }
}
