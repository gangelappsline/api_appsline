<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectTask>
 */
class ProjectTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => ProjectTask::STATUS_PENDING,
            'due_date' => now()->addDays(3),
            'assigned_to' => User::factory(),
            'estimated_hours' => fake()->randomFloat(2, 1, 20),
            'priority' => 'medium',
            'created_by' => User::factory(),
        ];
    }

    /** Tarea que se entrega el día actual. */
    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->endOfDay(),
        ]);
    }

    /** Tarea abierta cuya fecha de entrega ya pasó. */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->subDays(2),
        ]);
    }

    /** Tarea completada (por defecto, hoy y a tiempo). */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectTask::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }
}
