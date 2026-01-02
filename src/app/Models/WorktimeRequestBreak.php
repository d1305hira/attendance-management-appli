<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorktimeRequestBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'worktime_request_id',
        'break_start',
        'break_end',
    ];

    protected $casts = [
        'break_start' => 'datetime:H:i',
        'break_end' => 'datetime:H:i',
    ];

    public function request()
    {
        return $this->belongsTo(WorktimeRequest::class, 'worktime_request_id');
    }
}
