<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuotasPorCobrar extends Model
{
    use HasFactory;
    protected $table = "mat_cuotas_por_cobrar";
    protected $primaryKey = 'id_cuotas_id';
    protected $fillable = [
        'id_matricula',
        'valor_cuota',
        'valor_pendiente',
        'fecha_a_pagar',
        'img_comprobante',
        'url',
        'comentario',
        'num_cuota',
    ];
    public $timestamps = false;

}
