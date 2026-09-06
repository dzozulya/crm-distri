<?php

namespace App\Domain\LeadDistribution\Strategies;

use App\Domain\LeadDistribution\Contracts\DistributionStrategy;


use App\Domain\ManagerLoad;
use Illuminate\Support\Collection;

final class LeastLoadedStrategy implements DistributionStrategy
{
    public function distribute(
        Collection $managers,
        Collection $leadIds,
    ): array
    {
        if ($managers->isEmpty()) {
            return [];
        }

        $assignments = [];

        foreach ($leadIds as $leadId) {
            /** @var ManagerLoad $manager */
            $manager = $managers
                ->sortBy([
                    ['load', 'asc'],
                    ['managerId', 'asc'],
                ])
                ->first();

            $assignments[$leadId] = $manager->managerId;

            $manager->increment();
        }

        return $assignments;
    }
}
