<?php

namespace App\Providers;

use App\Domain\LeadDistribution\Contracts\DistributionStrategy;
use App\Domain\LeadDistribution\Strategies\LeastLoadedStrategy;
use App\Events\LeadAssigned;
use App\Listeners\QueueLeadAssignedNotification;
use App\Repositories\Contracts\LeadHistoryRepositoryInterface;
use App\Repositories\Contracts\LeadRepositoryInterface;
use App\Repositories\Contracts\ManagerRepositoryInterface;
use App\Repositories\Eloquent\LeadHistoryRepository;
use App\Repositories\Eloquent\LeadRepository;
use App\Repositories\Eloquent\ManagerRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class LeadDistributionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            DistributionStrategy::class,
            LeastLoadedStrategy::class,
        );
        $this->app->bind(
            LeadRepositoryInterface::class,
            LeadRepository::class,
        );

        $this->app->bind(
            ManagerRepositoryInterface::class,
            ManagerRepository::class,
        );

        $this->app->bind(
            LeadHistoryRepositoryInterface::class,
            LeadHistoryRepository::class,
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Event::listen(
            LeadAssigned::class,
            [QueueLeadAssignedNotification::class, 'handle'],
        );
    }
}
