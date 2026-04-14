<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{

    protected $fillable = ['working_day_id', 'start_time', 'end_time', 'status'];

    public function workingDay()
    {
        return $this->belongsTo(WorkingDay::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
