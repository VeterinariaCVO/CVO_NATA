<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    protected $fillable = [
        'appointment_id', 'veterinarian_id',
        'weight', 'temperature', 'symptoms', 'diagnosis',
        'treatment', 'prescriptions', 'observations', 'next_visit',
    ];

    protected function casts(): array
    {
        return [
            'weight'      => 'decimal:2',
            'temperature' => 'decimal:1',
            'next_visit'  => 'date',
        ];
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function veterinarian()
    {
        return $this->belongsTo(User::class, 'veterinarian_id');
    }

    public function pet()
    {
        return $this->hasOneThrough(Pet::class, Appointment::class,
            'id', 'id', 'appointment_id', 'pet_id');
    }
}
