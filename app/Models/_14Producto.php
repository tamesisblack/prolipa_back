<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class _14Producto extends Model
{
    use HasFactory;
    protected $table  ="1_4_cal_producto";

    protected $primaryKey = 'pro_codigo';
    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'pro_codigo',
        'gru_pro_codigo',
        'pro_nombre',
        'pro_descripcion',
        'pro_iva',
        'pro_valor',
        'pro_descuento',
        'pro_stock',
        'pro_stockCalmed',
        'pro_reservar',
        'pro_deposito',
        'pro_depositoCalmed',
        'pro_costo',
        'pro_peso',
        'pro_estado',
        'user_created'
        // Agrega cualquier otro campo que sea fillable
    ];
    // Define el método como estático
    public static function obtenerProducto($codigo)
    {
        // Verifica si el código no está vacío
        if (empty($codigo)) {
            throw new \InvalidArgumentException('El código del producto no puede estar vacío.');
        }

        // Obtén el producto basado en el código usando self
        $producto = self::where('pro_codigo', $codigo)->first();

        // Opcionalmente, podrías manejar el caso en que el producto no se encuentre
        if (!$producto) {
            throw new \Exception('Producto no encontrado '.$codigo);
        }

        return $producto;
    }
    //update stock producto con scope
    public function scopeUpdateStock($query, $codigo, $empresa, $cantidadReserva ,$cantidadEmpresa,$tipo =0)
    {
        // Verifica si el código no está vacío
        if (empty($codigo)) {
            throw new \InvalidArgumentException('El código del producto no puede estar vacío.');
        }

        // Verifica si la cantidad reserva no es un número
        if (!is_numeric($cantidadEmpresa)) {
            throw new \InvalidArgumentException('La cantidad empresa debe ser un número.');
        }
        // Verifica si la cantidad reserva no es un número
        if (!is_numeric($cantidadReserva)) {
            throw new \InvalidArgumentException('La cantidad reserva debe ser un número.');
        }
        $setEmpresa = '';
        // Determina la columna a actualizar basada en la empresa
        if($tipo == 0){
            if ($empresa == 1) {
                $setEmpresa = 'pro_stock';
            } elseif ($empresa == 3) {
                $setEmpresa = 'pro_stockCalmed';
            }
        }
        //actualizar notas
        if($tipo == 1)
        {
            if ($empresa == 1) {
                $setEmpresa = 'pro_deposito';
            } elseif ($empresa == 3) {
                $setEmpresa = 'pro_depositoCalmed';
            }
        }

        // Aplica la actualización a la consulta
        return $query->where('pro_codigo', $codigo)
                     ->update([ 'pro_reservar' => $cantidadReserva , $setEmpresa => $cantidadEmpresa ]);
    }
    public function scopeUpdateStockNoReserva($query, $codigo, $empresa, $cantidadEmpresa,$tipo =0)
    {
        // Verifica si el código no está vacío
        if (empty($codigo)) {
            throw new \InvalidArgumentException('El código del producto no puede estar vacío.');
        }

        // Verifica si la cantidad reserva no es un número
        if (!is_numeric($cantidadEmpresa)) {
            throw new \InvalidArgumentException('La cantidad empresa debe ser un número.');
        }
       
        $setEmpresa = '';
        // Determina la columna a actualizar basada en la empresa
        if($tipo == 0){
            if ($empresa == 1) {
                $setEmpresa = 'pro_stock';
            } elseif ($empresa == 3) {
                $setEmpresa = 'pro_stockCalmed';
            }
        }
        //actualizar notas
        if($tipo == 1)
        {
            if ($empresa == 1) {
                $setEmpresa = 'pro_deposito';
            } elseif ($empresa == 3) {
                $setEmpresa = 'pro_depositoCalmed';
            }
        }

        // Aplica la actualización a la consulta
        return $query->where('pro_codigo', $codigo)
                     ->update([ $setEmpresa => $cantidadEmpresa ]);
    }
}
