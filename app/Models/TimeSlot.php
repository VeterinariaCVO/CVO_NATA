<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    protected $fillable = [
        'working_day_id',
        'start_time',
        'end_time',
        'status',
        'is_open',
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    public function workingDay()
    {
        return $this->belongsTo(WorkingDay::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function appointment()
    {
        return $this->hasOne(Appointment::class)->whereIn('status', ['pending', 'confirmed', 'in_progress']);
    }
}
