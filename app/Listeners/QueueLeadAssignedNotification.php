<?php

namespace App\Listeners;
use App\Events\LeadAssigned;
use App\Jobs\SendLeadAssignedNotification;


final class QueueLeadAssignedNotification
{
    public function handle(LeadAssigned $event): void
    {
        SendLeadAssignedNotification::dispatch(
            $event->leadId,
            $event->managerId,
        );
    }
}
