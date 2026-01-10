<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\WorktimeRequest;
use App\Models\Worktime;

class WorktimeRequestFactory extends Factory
{
    protected $model = WorktimeRequest::class;

    public function definition()
    {
    return [
        'worktime_id' => Worktime::factory(),
        'requested_start_time' => '2026-01-01 10:00',
        'requested_end_time' => '2026-01-01 19:00',
        'reason' => $this->faker->sentence(),
        'approval_status' => 0,
    ];
    }

}
