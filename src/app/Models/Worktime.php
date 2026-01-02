<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Worktime extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'remarks'
    ];
    protected $casts = [
        'date' => 'datetime',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function getStatusLabelAttribute()
    {
      return [
        0 => '勤務外',
        1 => '出勤中',
        2 => '休憩中',
        3 => '退勤済',
      ][$this->status] ?? '不明';
    }

    public function user()
    {
      return $this->belongsTo(User::class);
    }

    public function breaks()
    {
      return $this->hasMany(WorkBreak::class, 'worktime_id');
    }

    // ✅ 修正申請（複数）
    public function requests()
    {
      return $this->hasMany(WorktimeRequest::class);
    }

    public function latestRequest()
    {
      return $this->hasOne(WorktimeRequest::class)->latestOfMany();
    }
}
