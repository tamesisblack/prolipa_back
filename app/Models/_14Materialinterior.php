<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class _14Materialinterior extends Model
{
    use HasFactory;
    protected $table  = "1_4_cal_material_interior";

    protected $primaryKey = 'mat_in_codigo';
    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'mat_in_codigo',
        'mat_in_nombre',
        'mat_in_gramaje',
        'mat_in_estado',
        'updated_at',
        'user_created',
    ];
}
