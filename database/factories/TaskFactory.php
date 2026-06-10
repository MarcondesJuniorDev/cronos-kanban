<?php

namespace Database\Factories;

use App\Models\Column;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'position' => 0,
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+1 month')?->format('Y-m-d'),
            'column_id' => Column::factory(),
            'user_id' => User::factory(),
        ];
    }
}
