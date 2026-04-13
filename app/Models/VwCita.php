<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VwCita extends Model
{
    protected $table = 'vw_citas';
    public $timestamps = false;
    protected $primaryKey = 'cita_id';

    protected static function boot() {
        parent::boot();
        static::saving(fn() => false);
    }
}
