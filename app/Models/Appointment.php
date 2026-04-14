<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{

    protected $fillable = [
        'pet_id', 'time_slot_id', 'service_id',
        'status', 'is_walk_in', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_walk_in' => 'boolean',
        ];
    }


    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    //Helpers

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
