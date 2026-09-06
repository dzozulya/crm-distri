<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Enums\LeadStatus;
use App\Models\Manager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'manager_id' => null,
            'status' => LeadStatus::NEW,
        ];
    }

    public function assignedTo(Manager $manager): static
    {
        return $this->state(fn () => [
            'manager_id' => $manager->id,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => LeadStatus::IN_PROGRESS,
        ]);
    }

    public function done(): static
    {
        return $this->state(fn () => [
            'status' => LeadStatus::DONE,
        ]);
    }
}
