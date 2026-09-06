<?php

namespace App\Repositories;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Support\Collection;

class LeadRepository
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

}
