<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bodegas extends Model
{
    use HasFactory;
    protected $table  ='bodegas';

    protected $primaryKey = 'bod_id';
    public $timestamps = false;

    protected $fillable = [
        'bod_id',
        'bod_responsable',
        'bod_nombre',
        'bod_ubicacion',
        'bod_estado',
        'created_at',
        'updated_at',
        'user_created',
    ];

}
