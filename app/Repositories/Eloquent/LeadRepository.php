<?php

namespace App\Repositories\Eloquent;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Repositories\Contracts\LeadRepositoryInterface;
use Illuminate\Support\Collection;

class LeadRepository implements LeadRepositoryInterface
{

    public function getNewLeads(): Collection
    {
        return Lead::query()
            ->where('status', LeadStatus::NEW)
            ->whereNull('manager_id')
            ->orderBy('id')
            ->lock('FOR UPDATE SKIP LOCKED')
            ->get();
    }

    public function assignToManager(
        Lead $lead,
        int $managerId,
    ): void {
        $lead->update([
            'manager_id' => $managerId,
            'status' => LeadStatus::IN_PROGRESS,
        ]);
    }
}
