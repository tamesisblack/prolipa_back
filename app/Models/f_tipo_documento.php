<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class f_tipo_documento extends Model
{
    use HasFactory;
    protected $table  ="f_tipo_documento";

    protected $primaryKey = 'tdo_id';
    // public $timestamps = false;

    protected $fillable = [
        'tdo_id',
        'tdo_nombre',
        'tdo_secuencial_calmed',
        'tdo_letra',
        'tdo_estado',
        'created_at',
        'updated_at',
        'user_created',
    ];
    public function scopeObtenerSecuencia($query, $tdo_nombre)
    {
        return $query->where('tdo_nombre', $tdo_nombre)
                     ->where('tdo_estado', '1')->first();
    }
    public function scopeUpdateSecuencia($query, $tdo_nombre, $empresa, $secuencial)
    {
        $setEmpresa = '';
        // Determinar la columna a actualizar basada en la empresa
        if ($empresa == 1) {
            $setEmpresa = 'tdo_secuencial_Prolipa';
        } elseif ($empresa == 3) {
            $setEmpresa = 'tdo_secuencial_calmed';
        }

        // Si no se encontró una columna válida, retorna false
        if ($setEmpresa === '') {
            return false;
        }

        // Aplicar la actualización a la consulta
        return $query->where('tdo_nombre', $tdo_nombre)
                     ->where('tdo_estado', '1') // Actualiza solo si el estado es 1
                     ->update([$setEmpresa => $secuencial]);
    }
    public static function formatSecuencia($secuencia)
    {
        // Asegúrate de que $secuencia sea un número entero
        $secuencia = (int) $secuencia;

        // Aplicar formato con ceros a la izquierda
        if ($secuencia < 10) {
            return str_pad($secuencia, 7, '0', STR_PAD_LEFT);
        } elseif ($secuencia >= 10 && $secuencia < 1000) {
            return str_pad($secuencia, 6, '0', STR_PAD_LEFT);
        } elseif ($secuencia >= 1000) {
            return str_pad($secuencia, 5, '0', STR_PAD_LEFT);
        }
    }
}
