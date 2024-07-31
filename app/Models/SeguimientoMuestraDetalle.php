<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeguimientoMuestraDetalle extends Model
{
    use HasFactory;
    protected $table = "seguimiento_muestra_detalle";
    protected $primaryKey = 'id';
    protected $fillable = [
        'muestra_id',
        'libro_id',
        'cantidad',
        'evidencia',
        'cantidad_devolucion',
      
    ];
	public $timestamps = true;
}
