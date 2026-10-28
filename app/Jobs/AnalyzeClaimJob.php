<?php

namespace App\Jobs;

use App\Models\EnvironmentalClaim;
use App\Services\GreenwashingAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeClaimJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public EnvironmentalClaim $claim)
    {
    }

    public function handle(GreenwashingAnalyzer $analyzer): void
    {
        if ($this->claim->product_id !== null) {
            $analyzer->analyzeClaim($this->claim);
        }
    }
}
