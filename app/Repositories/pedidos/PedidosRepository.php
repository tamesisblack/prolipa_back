<?php
namespace App\Repositories\pedidos;

use App\Models\Pedidos;
use App\Repositories\BaseRepository;
use DB;
class  PedidosRepository extends BaseRepository
{
    public function __construct(Pedidos $pedidoRepository)
    {
        parent::__construct($pedidoRepository);
    }
    public function getPrecioXLibro($id_serie,$libro_idlibro,$area_idarea,$periodo,$year){
        $precio = 0;
        $query = [];
        if($id_serie == 6){
            $query = DB::SELECT("SELECT f.pvp AS precio
            FROM pedidos_formato f
            WHERE f.id_serie    = '6'
            AND f.id_area       = '69'
            AND f.id_libro      = '$libro_idlibro'
            AND f.id_periodo    = '$periodo'");
        }else{
            $query = DB::SELECT("SELECT ls.*, l.nombrelibro, l.idlibro,
                (
                    SELECT f.pvp AS precio
                    FROM pedidos_formato f
                    WHERE f.id_serie = ls.id_serie
                    AND f.id_area = a.area_idarea
                    AND f.id_periodo = '$periodo'
                )as precio
                FROM libros_series ls
                LEFT JOIN libro l ON ls.idLibro = l.idlibro
                LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
                WHERE ls.id_serie = '$id_serie'
                AND a.area_idarea  = '$area_idarea'
                AND l.Estado_idEstado = '1'
                AND a.estado = '1'
                AND ls.year = '$year'
                LIMIT 1
            ");
        }
        if(count($query) > 0){
            $precio = $query[0]->precio;
        }
        return $precio;
    }
    public function getLibrosNormalesFormato($periodo){
        $series = DB::SELECT("SELECT * FROM series s WHERE s.id_serie != 6"); // omitir plan lector
        $datos  = [];
        foreach($series as $key => $value){
            $query = DB::SELECT("SELECT DISTINCT l.idlibro as libro_id , l.nombrelibro ,l.nombrelibro as nombre_libro ,pf.pvp AS precio,
            ls.codigo_liquidacion as codigo
            FROM area ar
            INNER JOIN asignatura a ON ar.idarea = a.area_idarea
            INNER JOIN libro l ON a.idasignatura = l.asignatura_idasignatura
            INNER JOIN libros_series ls ON l.idlibro = ls.idLibro
            INNER JOIN tipoareas t ON ar.tipoareas_idtipoarea = t.idtipoarea
            INNER JOIN pedidos_formato pf ON pf.id_area = ar.idarea
            WHERE ls.id_serie = ?
            AND pf.id_periodo = ?
            AND pf.id_serie = ?
            AND ar.estado = '1'
            AND a.estado = '1'
            AND pf.pvp <> 0
            AND l.Estado_idEstado = 1
            ORDER BY l.nombrelibro ASC", [$value->id_serie, $periodo, $value->id_serie]);
            $datos[$key] = [
                $query,
            ];
        }
        if(count($datos) == 0){
          return [];
        }
        $resultado = collect($datos)->flatten(10);
        return $resultado;
    }
    public function getLibrosPlanLectorFormato($periodo){
        $libros_plan = DB::SELECT("SELECT DISTINCT l.idlibro as libro_id , l.nombrelibro, l.nombrelibro as nombre_libro ,p.pvp AS precio,
        ls.codigo_liquidacion as codigo
        FROM libro l
        INNER JOIN libros_series ls ON l.idLibro = ls.idLibro
        LEFT JOIN pedidos_formato p ON l.idlibro = p.id_libro AND p.id_periodo = $periodo
        WHERE ls.id_serie = 6
        ORDER BY l.nombrelibro ASC");

        return $libros_plan;
    }
    public function obtenerLibroxPedidoTodo($pedido){
        $val_pedido = DB::SELECT("SELECT DISTINCT pv.*,
        p.descuento, p.id_periodo,
        p.anticipo, p.comision, CONCAT(se.nombre_serie,' ',ar.nombrearea) as serieArea,
        se.nombre_serie
        FROM pedidos_val_area pv
        left join area ar ON  pv.id_area = ar.idarea
        left join series se ON pv.id_serie = se.id_serie
        INNER JOIN pedidos p ON pv.id_pedido = p.id_pedido
        WHERE pv.id_pedido = '$pedido'
        GROUP BY pv.id;
        ");
        if(empty($val_pedido)){
            return $val_pedido;
        }
        $arreglo = [];
        $cont    = 0;
        //obtener solo los alcances activos
        foreach($val_pedido as $k => $tr){
            //Cuando es el pedido original
            $alcance_id = 0;
            $alcance_id = $tr->alcance;
            if($alcance_id == 0){
                $arreglo[$cont] =   (object)[
                    "id"                => $tr->id,
                    "id_pedido"         => $tr->id_pedido,
                    "valor"             => $tr->valor,
                    "id_area"           => $tr->id_area,
                    "tipo_val"          => $tr->tipo_val,
                    "id_serie"          => $tr->id_serie,
                    "year"              => $tr->year,
                    "plan_lector"       => $tr->plan_lector,
                    "alcance"           => $tr->alcance,
                    "descuento"         => $tr->descuento,
                    "id_periodo"        => $tr->id_periodo,
                    "anticipo"          => $tr->anticipo,
                    "comision"          => $tr->comision,
                    "serieArea"         => $tr->serieArea,
                    "nombre_serie"      => $tr->nombre_serie,
                    "alcance"           => $alcance_id
                ];
            }else{
                //validate que el alcance este cerrado o aprobado
                $query = $this->obtenerAlcanceAbiertoXId($alcance_id);
                if(count($query) > 0){
                    $arreglo[$cont] = (object) [
                        "id"                => $tr->id,
                        "id_pedido"         => $tr->id_pedido,
                        "valor"             => $tr->valor,
                        "id_area"           => $tr->id_area,
                        "tipo_val"          => $tr->tipo_val,
                        "id_serie"          => $tr->id_serie,
                        "year"              => $tr->year,
                        "plan_lector"       => $tr->plan_lector,
                        "alcance"           => $tr->alcance,
                        "descuento"         => $tr->descuento,
                        "id_periodo"        => $tr->id_periodo,
                        "anticipo"          => $tr->anticipo,
                        "comision"          => $tr->comision,
                        "serieArea"         => $tr->serieArea,
                        "nombre_serie"      => $tr->nombre_serie,
                        "alcance"           => $alcance_id
                    ];
                }
            }
            $cont++;
        }
        //mostrar el arreglo bien
        $renderSet = [];
        $renderSet = array_values($arreglo);
        if(count($renderSet) == 0){
            return $renderSet;
        }
        $datos = [];
        $contador = 0;
        //return $renderSet;
        foreach($renderSet as $key => $item){
            $valores = [];
            //plan lector
            if($item->plan_lector > 0 ){
                $getPlanlector = DB::SELECT("SELECT l.nombrelibro,l.idlibro, pro.pro_reservar,
                (
                    SELECT f.pvp AS precio
                    FROM pedidos_formato f
                    WHERE f.id_serie = '6'
                    AND f.id_area = '69'
                    AND f.id_libro = '$item->plan_lector'
                    AND f.id_periodo = '$item->id_periodo'
                )as precio, ls.codigo_liquidacion,ls.version,ls.year
                FROM libro l
                left join libros_series ls  on ls.idLibro = l.idlibro
                inner join 1_4_cal_producto pro on ls.codigo_liquidacion=pro.pro_codigo
                WHERE l.idlibro = '$item->plan_lector'
                ");
                $valores = $getPlanlector;
            }else{
                $getLibros = DB::SELECT("SELECT ls.*, l.nombrelibro, l.idlibro, pro.pro_reservar,
                (
                    SELECT f.pvp AS precio
                    FROM pedidos_formato f
                    WHERE f.id_serie = ls.id_serie
                    AND f.id_area = a.area_idarea
                    AND f.id_periodo = '$item->id_periodo'
                )as precio
                FROM libros_series ls
                LEFT JOIN libro l ON ls.idLibro = l.idlibro
                LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
                inner join 1_4_cal_producto pro on ls.codigo_liquidacion=pro.pro_codigo
                WHERE ls.id_serie = '$item->id_serie'
                AND a.area_idarea  = '$item->id_area'
                AND l.Estado_idEstado = '1'
                AND a.estado = '1'
                AND ls.year = '$item->year'
                LIMIT 1
                ");
                $valores = $getLibros;
            }
            $datos[$contador] = (Object)[
                // "id"                => $item->id,
                "id_pedido"         => $item->id_pedido,
                "valor"             => $item->valor,
                "id_area"           => $item->id_area,
                "tipo_val"          => $item->tipo_val,
                "id_serie"          => $item->id_serie,
                "year"              => $item->year,
                "anio"              => $valores[0]->year,
                "version"           => $valores[0]->version,
                "descuento"         => $item->descuento,
                "anticipo"          => $item->anticipo,
                "comision"          => $item->comision,
                "plan_lector"       => $item->plan_lector,
                "serieArea"         => $item->id_serie == 6 ? $item->nombre_serie." ".$valores[0]->nombrelibro : $item->serieArea,
                "idlibro"           => $valores[0]->idlibro,
                "nombrelibro"       => $valores[0]->nombrelibro,
                "precio"            => $valores[0]->precio,
                "stock"             => $valores[0]->pro_reservar,
                // "subtotal"          => $item->valor * $valores[0]->precio,
                "codigo_liquidacion"=> $valores[0]->codigo_liquidacion,
                "alcance"           => $item->alcance,
            ];
            $contador++;
        }
        //si el codigo de liquidacion se repite sumar en el valor
        // Crear un array asociativo para agrupar por codigo_liquidacion
        $grouped = [];

        foreach ($datos as $item) {
            $codigo = $item->codigo_liquidacion;

            if (!isset($grouped[$codigo])) {
                $grouped[$codigo] = $item;
            } else {
                $grouped[$codigo]->valor += $item->valor;
            }
        }

        // Convertir el array asociativo de nuevo a un array indexado
        $result = array_values($grouped);
        //subtotal
        foreach($result as $key => $item){
            $result[$key]->subtotal = $item->valor * $item->precio;
        }
        return $result;
    }
    public function obtenerAlcanceAbiertoXId($id){
        $query = DB::SELECT("SELECT * FROM pedidos_alcance a
        WHERE a.id = '$id'
        AND a.estado_alcance = '1'");
        return $query;
    }
    public function stockReservaProducto($pro_codigo){
        $query = DB::SELECT("SELECT pro_codigo,pro_reservar FROM 1_4_cal_producto WHERE pro_codigo = '$pro_codigo' ");
        return $query;
    }
}
