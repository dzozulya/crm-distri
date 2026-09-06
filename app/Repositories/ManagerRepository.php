<?php

namespace App\Repositories;

use App\Domain\ManagerLoad;
use App\Enums\LeadStatus;
use App\Models\Manager;
use Illuminate\Support\Collection;

class ManagerRepository
{
    public function getActiveManagerLoads(): Collection
    {
        return Manager::query()
            ->where('is_active', true)
            ->withCount([
                'leads as open_leads_count' => function ($query): void {
                    $query->whereIn('status', [
                        LeadStatus::NEW,
                        LeadStatus::IN_PROGRESS,
                    ]);
                },
            ])
            ->orderBy('id')
            ->get()
            ->map(
                fn(Manager $manager) => new ManagerLoad(
                    managerId: $manager->id,
                    load: (int)$manager->open_leads_count,
                )
            );

    }

}
