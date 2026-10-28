<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class TraceTimeline extends Component
{
    public function __construct(public Collection $traces)
    {
    }

    public function render(): View|Closure|string
    {
        return view('components.trace-timeline');
    }
}
