<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorktimeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'worktime_id',
        'requested_start_time',
        'requested_end_time',
        'requested_break_start',
        'requested_break_end',
        'reason',
        'approval_status',
    ];

    protected $casts = [
        'requested_start_time' => 'datetime:H:i',
        'requested_end_time' => 'datetime:H:i',
        'requested_break_start' => 'datetime:H:i',
        'requested_break_end' => 'datetime:H:i',
    ];

    public function getApprovalStatusLabelAttribute()
    {
        return [
            0 => '承認待ち',
            1 => '承認済み',
        ][$this->approval_status] ?? '不明';
    }

    public function worktime()
    {
        return $this->belongsTo(Worktime::class);
    }

    public function requestBreaks()
    {
        return $this->hasMany(WorktimeRequestBreak::class, 'worktime_request_id');
    }
}