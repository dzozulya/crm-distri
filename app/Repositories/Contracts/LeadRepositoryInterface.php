<?php


namespace App\Repositories\Contracts;

use App\Models\Lead;
use Illuminate\Support\Collection;

interface LeadRepositoryInterface
{
    public function getNewLeads(): Collection;

    public function assignToManager(Lead $lead, int $managerId): void;
}
