<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkBreak extends Model
{
  use HasFactory;

  protected $table = 'breaks';

  protected $fillable = [
      'worktime_id',
      'break_start',
      'break_end',
  ];

  protected $casts = [
      'break_start' => 'datetime',
      'break_end' => 'datetime',
  ];

  public function worktime()
  {
    return $this->belongsTo(Worktime::class);
  }
}
