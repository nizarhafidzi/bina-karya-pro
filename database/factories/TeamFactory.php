<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'slug' => $this->faker->unique()->slug,
            // Kita buat user baru sebagai owner tim ini
            'user_id' => User::factory(), 
            'max_users' => 5,
        ];
    }
}