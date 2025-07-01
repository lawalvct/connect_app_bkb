<?php

namespace App\Events;

use App\Models\Ad;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdApprovedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ad;

    public function __construct(Ad $ad)
    {
        $this->ad = $ad;
    }
}
