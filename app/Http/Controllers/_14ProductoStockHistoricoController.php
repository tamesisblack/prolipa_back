<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\_14ProductoStockHistorico;
use Illuminate\Http\Request;

class _14ProductoStockHistoricoController extends Controller
{
    public function GetProductoStockHistorico() {
        // Obtiene los registros de la tabla histórica
        $query = DB::SELECT("SELECT cpsh.*, concat(u.nombres, ' ', u.apellidos) as nombreeditor 
        from `1_4_cal_producto_stock_historico` cpsh
        left join usuario u on cpsh.user_created = u.idusuario 
        order by cpsh.psh_id asc ");
    
        // Procesa los resultados para reemplazar las barras invertidas en los valores JSON
        // $resultados = array_map(function ($registro) {
        //     $registro->psh_old_values = str_replace('\\', ' ', $registro->psh_old_values);
        //     $registro->psh_new_values = str_replace('\\', ' ', $registro->psh_new_values);
        //     return $registro;
        // }, $query);
    
        return $query;
    }

    public function GetProductoStockHistoricoxFecha(Request $request) {
        // Obtiene las fechas desde el request
        $fecha_desde = $request->input('fecha_created_desde');
        $fecha_hasta = $request->input('fecha_created_hasta');
    
        // Ajustar las fechas para incluir todo el día
        // Si fecha_hasta no tiene hora, agrega '23:59:59'
        if (strlen($fecha_hasta) === 10) { // Si solo es una fecha sin hora
            $fecha_hasta .= ' 23:59:59'; // Extiende a final del día
        }
    
        // Realiza la consulta asegurando que incluye registros de todo el día
        $query = DB::select("
            SELECT cpsh.*, concat(u.nombres, ' ', u.apellidos) as nombreeditor
            FROM `1_4_cal_producto_stock_historico` cpsh
            LEFT JOIN usuario u ON cpsh.user_created = u.idusuario
            WHERE cpsh.created_at >= ? AND cpsh.created_at <= ?
            ORDER BY cpsh.psh_id ASC", [$fecha_desde, $fecha_hasta]);
    
        return $query;
    }
}
