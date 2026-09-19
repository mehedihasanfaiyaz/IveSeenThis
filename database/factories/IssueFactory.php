<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Issue> */
class IssueFactory extends Factory
{
    protected $model = Issue::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(5),
            'occurred_on' => fake()->date(),
            'environment' => 'Laravel / MySQL',
            'problem' => fake()->paragraph(),
            'error_logs' => fake()->optional()->sentence(),
            'root_cause' => fake()->optional()->paragraph(),
            'dont_do_again' => fake()->optional()->sentence(),
            'tags' => ['laravel', 'debugging'],
        ];
    }
}
