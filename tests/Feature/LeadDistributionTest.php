<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadDistributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_distributes_new_leads_according_to_current_load(): void
    {
        $managerA = Manager::factory()->create();
        $managerB = Manager::factory()->create();
        $managerC = Manager::factory()->create();

        Lead::factory()
            ->count(12)
            ->assignedTo($managerA)
            ->inProgress()
            ->create();

        Lead::factory()
            ->count(4)
            ->assignedTo($managerB)
            ->inProgress()
            ->create();

        Lead::factory()
            ->count(8)
            ->assignedTo($managerC)
            ->inProgress()
            ->create();

        Lead::factory()
            ->count(9)
            ->create();

        $response = $this->postJson('/api/leads/distribute');

        $response
            ->assertOk()
            ->assertJson([
                'distributed' => 9,
            ]);

        $this->assertSame(
            12,
            Lead::where('manager_id', $managerA->id)->count()
        );

        $this->assertSame(
            11,
            Lead::where('manager_id', $managerB->id)->count()
        );

        $this->assertSame(
            10,
            Lead::where('manager_id', $managerC->id)->count()
        );
    }

    public function test_it_does_not_assign_leads_to_inactive_managers(): void
    {
        $activeManager = Manager::factory()->create();

        $inactiveManager = Manager::factory()
            ->inactive()
            ->create();

        Lead::factory()
            ->count(5)
            ->create();

        $response = $this->postJson('/api/leads/distribute');

        $response
            ->assertOk()
            ->assertJson([
                'distributed' => 5,
            ]);

        $this->assertSame(
            0,
            Lead::where('manager_id', $inactiveManager->id)->count()
        );

        $this->assertSame(
            5,
            Lead::where('manager_id', $activeManager->id)->count()
        );
    }

    public function test_it_does_not_redistribute_already_processed_leads(): void
    {
        $manager = Manager::factory()->create();

        Lead::factory()
            ->count(3)
            ->assignedTo($manager)
            ->inProgress()
            ->create();

        Lead::factory()
            ->count(2)
            ->create();

        $firstResponse = $this->postJson('/api/leads/distribute');

        $firstResponse
            ->assertOk()
            ->assertJson([
                'distributed' => 2,
            ]);

        $secondResponse = $this->postJson('/api/leads/distribute');

        $secondResponse
            ->assertOk()
            ->assertJson([
                'distributed' => 0,
            ]);

        $this->assertSame(
            0,
            Lead::where('status', LeadStatus::NEW)->count()
        );
    }
}
