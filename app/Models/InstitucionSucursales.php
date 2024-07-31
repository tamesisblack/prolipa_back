<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitucionSucursales extends Model
{
    use HasFactory;
    protected $table  ="institucion_sucursales";

    protected $primaryKey = 'isuc_id';
    public $timestamps = false;

    protected $fillable = [
        'isuc_id',
        'isuc_idInstitucion',
        'isuc_ruc',
        'isuc_direccion',
        'isuc_estado',
        'updated_at',
        'user_created',
    ];
}
