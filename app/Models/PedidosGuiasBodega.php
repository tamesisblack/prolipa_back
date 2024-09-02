<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidosGuiasBodega extends Model
{
    use HasFactory;
    protected $table = "pedidos_guias_bodega";
    public static function obtenerProducto($codigo,$asesor_id)
    {
        // Verifica si el código no está vacío
        if (empty($codigo)) {
            throw new \InvalidArgumentException('El código del producto no puede estar vacío.');
        }

        // Obtén el producto basado en el código usando self
        $producto = self::where('pro_codigo', $codigo)->where('asesor_id',$asesor_id)->first();

        // Opcionalmente, podrías manejar el caso en que el producto no se encuentre
        if (!$producto) {
            throw new \Exception('Producto no encontrado '.$codigo);
        }

        return $producto;
    }
    public function scopeUpdateStock($query,$codigo,$asesor_id, $cantidad)
    {
        // Verifica si el código no está vacío
        if (empty($codigo)) {
            throw new \InvalidArgumentException('El código del producto no puede estar vacío.');
        }
        return $query->where('pro_codigo', $codigo)
                    ->where('asesor_id',$asesor_id)
                    ->update(['pro_stock' => $cantidad]);
    }

}
