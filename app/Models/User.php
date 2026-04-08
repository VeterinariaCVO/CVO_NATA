<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name', 'email', 'password', 'role_id',
        'phone', 'address', 'active',
        'gender', 'birth_date', 'profile_photo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'birth_date'        => 'date',
            'active'            => 'boolean',
        ];
    }


    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function pets()
    {
        return $this->hasMany(Pet::class, 'owner_id');
    }

    public function createdAppointments()
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class, 'veterinarian_id');
    }

    //Helpers de rol

    public function isAdmin(): bool      { return $this->role_id === 1; }
    public function isEmpleado(): bool   { return $this->role_id === 2; }
    public function isCliente(): bool    { return $this->role_id === 3; }
    public function isVeterinario(): bool{ return $this->role_id === 4; }

    public function scopeVeterinarios($query)
{
    return $query->where('role_id', 4)->where('active', true);
}

}

