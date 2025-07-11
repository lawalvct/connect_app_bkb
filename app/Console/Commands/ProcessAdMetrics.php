<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAdMetricsJob;
use Illuminate\Console\Command;

class ProcessAdMetrics extends Command
{
    protected $signature = 'ads:process-metrics {ad_id} {--impressions=0} {--clicks=0} {--conversions=0} {--spent=0}';
    protected $description = 'Process advertisement metrics';

    public function handle()
    {
        $adId = $this->argument('ad_id');
        $metrics = [
            'impressions' => $this->option('impressions'),
            'clicks' => $this->option('clicks'),
            'conversions' => $this->option('conversions'),
            'spent' => $this->option('spent'),
        ];

        ProcessAdMetricsJob::dispatch($adId, $metrics);

        $this->info("Metrics processing job dispatched for ad ID: {$adId}");
    }
}
