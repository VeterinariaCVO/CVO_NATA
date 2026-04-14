<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkingDay extends Model
{
      protected $fillable = ['date', 'is_open'];

    protected function casts(): array
    {
        return [
            'date'    => 'date',
            'is_open' => 'boolean',
        ];
    }

    public function timeSlots()
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function availableSlots()
    {
        return $this->hasMany(TimeSlot::class)->where('status', 'available');
    }
}
