<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\WorktimeRequestBreak;
use App\Models\WorktimeRequest;

class WorktimeRequestBreakFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
    return [
        'worktime_request_id' => WorktimeRequest::factory(),
        'break_start' => '2026-01-01 13:00',
        'break_end' => '2026-01-01 14:00',
        ];
    }

}
