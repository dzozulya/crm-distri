<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class SendLeadAssignedNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $leadId,
        public readonly int $managerId,
    ) {
    }

    /** Doing something with event data. In this case store to log... */
    public function handle(): void
    {
        Log::info('Lead assigned', [
            'lead_id' => $this->leadId,
            'manager_id' => $this->managerId,
        ]);
    }
}
