<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Manager;
use App\Models\Lead;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $managerA = Manager::factory()->create([
            'name' => 'Manager A',
        ]);

        $managerB = Manager::factory()->create([
            'name' => 'Manager B',
        ]);

        $managerC = Manager::factory()->create([
            'name' => 'Manager C',
        ]);

        Manager::factory()
            ->inactive()
            ->create([
                'name' => 'Inactive Manager',
            ]);

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
    }
}
