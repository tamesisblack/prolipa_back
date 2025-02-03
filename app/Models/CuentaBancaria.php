<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaBancaria extends Model
{
    use HasFactory;
    protected $table = '1_1_cuenta_pago';
    protected $primaryKey = 'cue_pag_codigo';
    
    protected $fillable = [
        'ban_codigo',
        'cue_pag_numero',
        'cue_pag_descripcion',
        'cue_pag_nombre',
        'cue_pag_secuencial',
        'cue_pag_estado',
        'created_at',
        'updated_at',
        'user_created',
        'cue_pag_tipo_cuenta',
    ];
}
