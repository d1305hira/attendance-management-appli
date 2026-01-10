<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Worktime;
use App\Models\User;

class WorktimeFactory extends Factory
{
    protected $model = Worktime::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'date' => $this->faker->dateTime(),
            'start_time' => $this->faker->dateTime(),
            'end_time' => $this->faker->dateTime(),
            'status' => 0,
        ];
    }
}
