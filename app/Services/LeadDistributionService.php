<?php


namespace App\Services;

use App\Domain\LeadDistribution\Contracts\DistributionStrategy;
use App\Domain\ManagerLoad;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadHistory;
use App\Models\Manager;
use App\Repositories\LeadRepository;
use App\Repositories\ManagerRepository;
use DB;
use Illuminate\Support\Collection;
use App\Events\LeadAssigned;


final class LeadDistributionService
{
    public function __construct(
        private readonly DistributionStrategy $strategy,
        private readonly LeadRepository       $leadRepository,
        private readonly ManagerRepository    $managerRepository
    )
    {
    }


    private function getNewLeads(): Collection
    {
        return $this->leadRepository->getNewLeads();
    }

    private function getActiveManagerLoads(): Collection
    {
       return $this->managerRepository->getActiveManagerLoads();
    }

    /**
     * @throws Throwable
     */
    public function distribute(): int
    {
        return DB::transaction(function (): int {
            $leads = $this->getNewLeads();

            if ($leads->isEmpty()) {
                return 0;
            }

            $managers = $this->getActiveManagerLoads();

            if ($managers->isEmpty()) {
                return 0;
            }

            $assignments = $this->strategy->distribute(
                $managers,
                $leads->pluck('id'),
            );

            foreach ($leads as $lead) {
                $this->assign(
                    $lead,
                    $assignments[$lead->id],
                );
            }

            return count($assignments);
        });
    }

    private function assign(
        Lead $lead,
        int  $managerId,
    ): void
    {
        $oldManagerId = $lead->manager_id;
        $oldStatus = $lead->status;

        $lead->update([
            'manager_id' => $managerId,
            'status' => LeadStatus::IN_PROGRESS,
        ]);

        LeadHistory::query()->create([
            'lead_id' => $lead->id,
            'old_manager_id' => $oldManagerId,
            'new_manager_id' => $managerId,
            'old_status' => $oldStatus,
            'new_status' => LeadStatus::IN_PROGRESS,
            'created_at' => now(),
        ]);

        DB::afterCommit(
            fn() => LeadAssigned::dispatch(
                $lead->id,
                $managerId,
            )
        );
    }
}

