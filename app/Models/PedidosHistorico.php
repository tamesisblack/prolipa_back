<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidosHistorico extends Model
{
    use HasFactory;
    protected $table = "pedidos_historico";
    protected $primaryKey = "id";
    protected $fillable = [
        "id_pedido",
        "estado",
        "fecha_creacion_pedido",
        "fecha_generar_contrato",
        "fecha_aprobacion_anticipo_gerencia",
        "fecha_subir_cheque",
        "fecha_envio_cheque_for_asesor",
        "fecha_orden_firmada",
        "fecha_que_recibe_orden_firmada",

    ];
}
