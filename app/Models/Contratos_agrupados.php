<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contratos_agrupados extends Model
{
    use HasFactory;
    protected $table = "f_contratos_agrupados";
    protected $primaryKey = 'ca_id';
    public $incrementing = false;
    protected $fillable = [
        'ca_descripcion',
        'ca_codigo_agrupado',
        'ca_tipo_pedido',
        'ca_cantidad',
        'ca_estado',
        'created_at',
        'updated_at'
    ];
}
