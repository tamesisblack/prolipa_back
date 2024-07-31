<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    use HasFactory;

    protected $table = 'cheque';
    protected $primaryKey = 'chq_id';

    protected $fillable = [
        'ban_codigo',
        'chq_tipo',
        'chq_valor',
        'chq_fecha_cobro',
        'chq_cuenta',
        'chq_estado',
        'chq_referenca',
        'chq_numero',
        'chq_periodo',
        'chq_institucion',
        'chq_id_abono',
        'chq_empresa',
    ];
}
