<?php

namespace App\Listeners;
use App\Events\LeadAssigned;



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
