<?php

namespace App\Repositories\Contracts;

use App\Enums\LeadStatus;
use App\Models\Lead;

interface LeadHistoryRepositoryInterface
{
    public function createAssignmentHistory(
        Lead $lead,
        ?int $oldManagerId,
        int $newManagerId,
        ?LeadStatus $oldStatus,
        LeadStatus $newStatus,
    ): void;

}
