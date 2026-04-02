<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pet extends Model
{
    protected $fillable = [
        'name', 'species', 'breed', 'color', 'special_marks',
        'weight', 'sex', 'age', 'photo_path', 'owner_id', 'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'weight' => 'decimal:2',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
        //
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasManyThrough(MedicalRecord::class, Appointment::class);
    }
}
