<?php
namespace App\Domain\LeadDistribution\Contracts;
use Illuminate\Support\Collection;

interface DistributionStrategy
{
    public function distribute(
        Collection $managers,
        Collection $leadIds,
    ): array;
}
