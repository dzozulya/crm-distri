<?php

namespace App\Repositories\Eloquent;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadHistory;
use App\Repositories\Contracts\LeadHistoryRepositoryInterface;

class LeadHistoryRepository implements LeadHistoryRepositoryInterface
{
    public function createAssignmentHistory(
        Lead $lead,
        ?int $oldManagerId,
        int $newManagerId,
        ?LeadStatus $oldStatus,
        LeadStatus $newStatus,
    ): void {
        LeadHistory::query()->create([
            'lead_id' => $lead->id,
            'old_manager_id' => $oldManagerId,
            'new_manager_id' => $newManagerId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'created_at' => now(),
        ]);
    }
}
