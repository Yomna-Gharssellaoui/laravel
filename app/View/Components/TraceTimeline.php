<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class TraceTimeline extends Component
{
    /** @param bool $showRisk show the delay risk predicted by the ML model (M3) */
    public function __construct(public Collection $traces, public bool $showRisk = false) {}

    public function render(): View|Closure|string
    {
        return view('components.trace-timeline');
    }
}
