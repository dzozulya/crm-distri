<?php

namespace App\Services;

use App\Domain\LeadDistribution\Contracts\DistributionStrategy;
use App\Enums\LeadStatus;
use App\Repositories\Eloquent\LeadHistoryRepository;
use App\Repositories\Eloquent\LeadRepository;
use App\Repositories\Eloquent\ManagerRepository;

use App\Events\LeadAssigned;
use Illuminate\Support\Facades\DB;

final readonly class LeadDistributionService
{
    public function __construct(
        private LeadRepository        $leadRepository,
        private ManagerRepository     $managerRepository,
        private LeadHistoryRepository $leadHistoryRepository,
        private DistributionStrategy  $strategy,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function distribute(): int
    {
        return DB::transaction(function (): int {
            $leads = $this->leadRepository->getNewLeads();

            if ($leads->isEmpty()) {
                return 0;
            }

            $managers = $this->managerRepository
                ->getActiveManagerLoads();

            if ($managers->isEmpty()) {
                return 0;
            }

            $assignments = $this->strategy->distribute(
                $managers,
                $leads->pluck('id'),
            );

            foreach ($leads as $lead) {
                $managerId = $assignments[$lead->id];

                $oldManagerId = $lead->manager_id;
                $oldStatus = $lead->status;

                $this->leadRepository->assignToManager(
                    $lead,
                    $managerId,
                );

                $this->leadHistoryRepository->createAssignmentHistory(
                    $lead,
                    $oldManagerId,
                    $managerId,
                    $oldStatus,
                    LeadStatus::IN_PROGRESS,
                );

                DB::afterCommit(
                    fn () => LeadAssigned::dispatch(
                        $lead->id,
                        $managerId,
                    )
                );
            }

            return count($assignments);
        });
    }
}
