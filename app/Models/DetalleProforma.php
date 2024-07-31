<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleProforma extends Model
{
    use HasFactory;
    protected $table = "f_detalle_proforma";
    protected $primaryKey = 'det_prof_id';
    protected $fillable = [
        'det_prof_id',
        'prof_id',
        'pro_codigo',
        'det_prof_cantidad',
        'det_prof_valor_u',
        'det_prof_estado',
    ];
	public $timestamps = false;

}
