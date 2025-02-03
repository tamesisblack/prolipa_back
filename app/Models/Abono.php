<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Abono extends Model
{
    use HasFactory;
    protected $table = 'abono';
    protected $primaryKey = 'abono_id';
    
    protected $fillable = [
        'abono_id',
        'egf_id',
        'abono_facturas',
        'abono_notas',
        'abono_totalFacturas',
        'abono_totalNotas',
        'abono_fecha',
        'abono_tipo',
        'abono_porcentaje',
        'abono_cuenta',
        'abono_documento',
        'abono_cheque_cuenta',
        'abono_cheque_numero',
        'abono_cheque_banco',
        'abono_valor_retencion',
        'abono_empresa',
        'abono_institucion',
        'abono_periodo',
        'user_created',
        'created_at',
        'updated_at',
        'tipo',
        'idPerseo',
        'estadoPerseo',
        'idClientePerseo',
        'clienteCodigoPerseo',
        'abono_concepto',
        'abono_ruc_cliente',
        'usuario_verificador',
        'fecha_verificacion',
        'referencia_retencion_factura',
        'abono_estado',
        'referencia_nota_cred_interna',
    ];
}
