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
        'abono_facturas',
        'abono_notas',
        'bono_totalFacturas',
        'abono_totalNotas',
        'abono_ fecha',
        'abono_tipo',
        'abono_empresa',
        'abono_cheque_banco',
        'abono_porcentaje',
        'abono_valor_retencion',
        'abono_cuenta',
        'abono_documento',
        'abono_institucion',
        'abono_periodo',
        'created_at',
        'updated_at',
        'user_created',
        'idClientePerseo',
        'clienteCodigoPerseo',
    ];
}
