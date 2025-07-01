<?php

namespace App\Jobs;

use App\Helpers\AdHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAdMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $adId;
    private $metrics;

    public function __construct($adId, $metrics)
    {
        $this->adId = $adId;
        $this->metrics = $metrics;
    }

    public function handle()
    {
        AdHelper::updateAdMetrics(
            $this->adId,
            $this->metrics['impressions'] ?? 0,
            $this->metrics['clicks'] ?? 0,
            $this->metrics['conversions'] ?? 0,
            $this->metrics['spent'] ?? 0
        );
    }
}
