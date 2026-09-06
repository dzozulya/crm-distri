<?php

namespace App\Providers;

use App\Domain\LeadDistribution\Contracts\DistributionStrategy;
use App\Domain\LeadDistribution\Strategies\LeastLoadedStrategy;
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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
