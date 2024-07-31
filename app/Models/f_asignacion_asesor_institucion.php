<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class f_asignacion_asesor_institucion extends Model
{
    use HasFactory;
    protected $table  ="f_asesor_institucion";

    protected $primaryKey = 'asin_id';
    // public $timestamps = false;

    protected $fillable = [
        'asin_id',
        'asin_idInstitucion',
        'asin_idusuario',
        'asin_estado',
        'created_at',
        'updated_at',
        'user_created',
    ];
}
