<?php

namespace App\Repositories\Eloquent;

use App\Domain\ManagerLoad;
use App\Enums\LeadStatus;
use App\Models\Manager;
use App\Repositories\Contracts\ManagerRepositoryInterface;
use Illuminate\Support\Collection;

class ManagerRepository implements ManagerRepositoryInterface
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
