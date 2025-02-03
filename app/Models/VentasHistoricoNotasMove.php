<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentasHistoricoNotasMove extends Model
{
    use HasFactory;
    protected $table = 'f_venta_historico_notas_cambiadas';
    protected $primaryKey = 'id';
}
