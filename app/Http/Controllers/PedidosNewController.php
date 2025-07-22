<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Repositories\pedidos\PedidosRepository;

class PedidosNewController extends Controller
{
    protected $pedidosRepository = null;
     public function __construct(PedidosRepository $pedidosRepository)
    {
        $this->pedidosRepository = $pedidosRepository;
    }

    public function index(Request $request)
    {
        if ($request->getLibrosXInstitucionesAsesor) {
            return $this->getLibrosXInstitucionesAsesor(
                $request->id_periodo,
                $request->tipo_venta,
                $request->id_asesor,
                $request->fecha_inicio,
                $request->fecha_fin
            );
        }
        if ($request->getLibrosXInstitucionesAsesor_new) {
            return $this->getLibrosXInstitucionesAsesor_new(
                $request->id_periodo,
                $request->tipo_venta,
                $request->id_asesor,
                $request->fecha_inicio,
                $request->fecha_fin
            );
        }
        if ($request->getValoresLibrosContratosInstitucionesAsesor) {
            return $this->getValoresLibrosContratosInstitucionesAsesor($request);
        }
        if ($request->getValoresLibrosContratosInstitucionesAsesor_new) {
            return $this->getValoresLibrosContratosInstitucionesAsesor_new($request);
        }
    }

    public function getLibrosXInstitucionesAsesor($id_periodo, $tipo_venta, $id_asesor, $fecha_inicio = null, $fecha_fin = null)
    {
        $query = $this->tr_getInstitucionesVentaXTipoVentaAsesor($id_periodo, $tipo_venta, $id_asesor, $fecha_inicio, $fecha_fin);
        $id_pedidos = collect($query)->pluck('id_pedido')->implode(',');
        return $this->geAllLibrosxAsesor(0, $id_periodo, 1, $id_pedidos);
    }

    public function getLibrosXInstitucionesAsesor_new($id_periodo, $tipo_venta, $id_asesor, $fecha_inicio = null, $fecha_fin = null)
    {
        $query = $this->tr_getInstitucionesVentaXTipoVentaAsesor($id_periodo, $tipo_venta, $id_asesor, $fecha_inicio, $fecha_fin);
        $id_pedidos = collect($query)->pluck('id_pedido')->implode(',');
        return $this->geAllLibrosxAsesor_new(0, $id_periodo, 1, $id_pedidos);
    }

    public function getValoresLibrosContratosInstitucionesAsesor($request)
    {
        $arrayLibros = json_decode($request->arrayLibros);
        $id_periodo = $request->id_periodo;
        $tipo_venta = $request->tipo_venta;
        $id_asesor = $request->id_asesor;
        $fecha_inicio = $request->fecha_inicio;
        $fecha_fin = $request->fecha_fin;

        $query = $this->tr_getInstitucionesVentaXTipoVentaAsesor($id_periodo, $tipo_venta, $id_asesor, $fecha_inicio, $fecha_fin);
        $datos = [];
        foreach ($query as $key => $item) {
            $validate = $this->obtenerValores($arrayLibros, $item->id_pedido);
            $datos[$key] = [
                'id_pedido' => $item->id_pedido,
                'id_asesor' => $item->id_asesor,
                'asesor' => $item->asesor,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad' => $item->ciudad,
                'fecha_generacion_contrato' => $item->fecha_generacion_contrato,
                'librosFormato' => $validate,
            ];
        }
        return $datos;
    }

    public function getValoresLibrosContratosInstitucionesAsesor_new($request)
    {
        $arrayLibros = json_decode($request->arrayLibros);
        $id_periodo = $request->id_periodo;
        $tipo_venta = $request->tipo_venta;
        $id_asesor = $request->id_asesor;
        $fecha_inicio = $request->fecha_inicio;
        $fecha_fin = $request->fecha_fin;

        $query = $this->tr_getInstitucionesVentaXTipoVentaAsesor($id_periodo, $tipo_venta, $id_asesor, $fecha_inicio, $fecha_fin);
        $datos = [];
        foreach ($query as $key => $item) {
            $validate = $this->obtenerValores_new($arrayLibros, $item->id_pedido);
            $datos[$key] = [
                'id_pedido' => $item->id_pedido,
                'id_asesor' => $item->id_asesor,
                'asesor' => $item->asesor,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad' => $item->ciudad,
                'fecha_generacion_contrato' => $item->fecha_generacion_contrato,
                'librosFormato' => $validate,
            ];
        }
        return $datos;
    }

    public function tr_getInstitucionesVentaXTipoVentaAsesor($id_periodo, $tipo_venta, $id_asesor, $fecha_inicio = null, $fecha_fin = null)
    {
        $query = DB::table('pedidos as p')
            ->selectRaw('p.id_pedido, p.contrato_generado, p.id_asesor, 
                        CONCAT(u.nombres, " ", u.apellidos) as asesor, 
                        i.nombreInstitucion, c.nombre as ciudad,
                        p.fecha_generacion_contrato')
            ->leftJoin('usuario as u', 'p.id_asesor', '=', 'u.idusuario')
            ->leftJoin('institucion as i', 'p.id_institucion', '=', 'i.idInstitucion')
            ->leftJoin('ciudad as c', 'i.ciudad_id', '=', 'c.idciudad')
            ->where('p.estado', '1')
            ->where('p.id_periodo', $id_periodo)
            ->whereNotNull('p.contrato_generado');

        if ($tipo_venta == 3) {
            $query->whereIn('p.tipo_venta', ['1', '2']);
        } elseif ($tipo_venta == 1 || $tipo_venta == 2) {
            $query->where('p.tipo_venta', $tipo_venta);
        }

        if ($id_asesor !== 'all') {
            $query->where('p.id_asesor', $id_asesor);
        }

        if ($fecha_inicio && $fecha_fin) {
            $query->whereBetween('p.fecha_generacion_contrato', [$fecha_inicio, $fecha_fin]);
        }

        return $query->orderByDesc('p.id_pedido')->get();
    }
    public function geAllLibrosxAsesor($asesor_id,$periodo_id,$tipo = null,$parametro1=null){
        $val_pedido = [];
        //por asesor
        if($tipo == null){
            $val_pedido = DB::SELECT("SELECT pv.valor,
            pv.id_area, pv.tipo_val, pv.id_serie, pv.year,pv.plan_lector,pv.alcance,
            p.id_periodo,
            CONCAT(se.nombre_serie,' ',ar.nombrearea) as serieArea,
            se.nombre_serie
            FROM pedidos_val_area pv
            LEFT JOIN area ar ON  pv.id_area = ar.idarea
            LEFT JOIN series se ON pv.id_serie = se.id_serie
            LEFT JOIN pedidos p ON pv.id_pedido = p.id_pedido
            LEFT JOIN usuario u ON p.id_asesor = u.idusuario
            WHERE p.id_asesor = '$asesor_id'
            AND p.id_periodo  = '$periodo_id'
            AND p.tipo        = '1'
            AND p.estado      = '1'
            And p.estado_entrega = '2'
            GROUP BY pv.id
            ");
        }
        //por varios ids de pedido
        if($tipo == 1){
            //quitar las comas y convertir en array
            $ids = explode(",",$parametro1);
            $val_pedido = DB::table('pedidos_val_area as pv')
            ->selectRaw('pv.valor, pv.id_area, pv.tipo_val, pv.id_serie, pv.year, pv.plan_lector, pv.alcance,
                        p.id_periodo,
                        CONCAT(se.nombre_serie, " ", ar.nombrearea) as serieArea,
                        se.nombre_serie')
            ->leftJoin('area as ar', 'pv.id_area', '=', 'ar.idarea')
            ->leftJoin('series as se', 'pv.id_serie', '=', 'se.id_serie')
            ->leftJoin('pedidos as p', 'pv.id_pedido', '=', 'p.id_pedido')
            ->leftJoin('usuario as u', 'p.id_asesor', '=', 'u.idusuario')
            ->whereIn('p.id_pedido', $ids)
            ->where('p.tipo', '0')
            ->where('p.estado', '1')
            ->where('p.id_periodo',$periodo_id)
            ->groupBy('pv.id')
            ->get();
        }
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
                    "valor"             => $tr->valor,
                    "id_area"           => $tr->id_area,
                    "tipo_val"          => $tr->tipo_val,
                    "id_serie"          => $tr->id_serie,
                    "year"              => $tr->year,
                    "plan_lector"       => $tr->plan_lector,
                    "id_periodo"        => $tr->id_periodo,
                    "serieArea"         => $tr->serieArea,
                    "nombre_serie"      => $tr->nombre_serie,
                    "alcance"           => $tr->alcance,
                    "alcance"           => $alcance_id
                ];
            }else{
                //validate que el alcance este cerrado o aprobado
                $query = $this->getAlcanceAbiertoXId($alcance_id);
                if(count($query) > 0){
                    $arreglo[$cont] = (object) [
                        "valor"             => $tr->valor,
                        "id_area"           => $tr->id_area,
                        "tipo_val"          => $tr->tipo_val,
                        "id_serie"          => $tr->id_serie,
                        "year"              => $tr->year,
                        "plan_lector"       => $tr->plan_lector,
                        "id_periodo"        => $tr->id_periodo,
                        "serieArea"         => $tr->serieArea,
                        "nombre_serie"      => $tr->nombre_serie,
                        "alcance"           => $tr->alcance,
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
                $getPlanlector = DB::SELECT("SELECT l.nombrelibro,l.idlibro,pro.pro_reservar, l.descripcionlibro,
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
                $getLibros = DB::SELECT("SELECT ls.*, l.nombrelibro, l.idlibro,pro.pro_reservar, l.descripcionlibro,
                (
                    SELECT f.pvp AS precio
                    FROM pedidos_formato f
                    WHERE f.id_serie = ls.id_serie
                    AND f.id_area = a.area_idarea
                    AND f.id_periodo = '$item->id_periodo'
                )as precio
                FROM libros_series ls
                LEFT JOIN libro l ON ls.idLibro = l.idlibro
                inner join 1_4_cal_producto pro on ls.codigo_liquidacion=pro.pro_codigo
                LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
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
                "id_area"           => $item->id_area,
                "valor"             => $item->valor,
                // "tipo_val"          => $item->tipo_val,
                "id_serie"          => $item->id_serie,
                "year"              => $item->year,
                // "anio"              => $valores[0]->year,
                // "version"           => $valores[0]->version,
                // "plan_lector"       => $item->plan_lector,
                "serieArea"         => $item->id_serie == 6 ? $item->nombre_serie." ".$valores[0]->nombrelibro : $item->serieArea,
                "libro_id"          => $valores[0]->idlibro,
                "nombrelibro"       => $valores[0]->nombrelibro,
                "nombre_serie"      => $item->nombre_serie,
                "precio"            => $valores[0]->precio,
                "codigo"            => $valores[0]->codigo_liquidacion,
                "stock"             => $valores[0]->pro_reservar,
                "descripcion"       => $valores[0]->descripcionlibro,
            ];
            $contador++;
        }
           //si el codigo de liquidacion se repite sumar en el valor
        // Crear un array asociativo para agrupar por codigo_liquidacion
        $grouped = [];

        foreach ($datos as $item) {
            $codigo = $item->codigo;

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
        //array unicos con array unique
        // $resultado  = [];
        // $resultado  = array_unique($datos, SORT_REGULAR);
        // $coleccion  = collect($resultado);
        // return $coleccion->values();
    }
    public function getAlcanceAbiertoXId($id){
        $query = DB::SELECT("SELECT * FROM pedidos_alcance a
        WHERE a.id = '$id'
        AND a.estado_alcance = '1'");
        return $query;
    }
     public function geAllLibrosxAsesor_new($asesor_id,$periodo_id,$tipo = null,$parametro1=null){
        $val_pedido = [];
        //por asesor
        if($tipo == null){
            $val_pedido = DB::SELECT("SELECT pv.valor,
            pv.id_area, pv.tipo_val, pv.id_serie, pv.year,pv.plan_lector,pv.alcance,
            p.id_periodo,
            CONCAT(se.nombre_serie,' ',ar.nombrearea) as serieArea,
            se.nombre_serie
            FROM pedidos_val_area pv
            LEFT JOIN area ar ON  pv.id_area = ar.idarea
            LEFT JOIN series se ON pv.id_serie = se.id_serie
            LEFT JOIN pedidos p ON pv.id_pedido = p.id_pedido
            LEFT JOIN usuario u ON p.id_asesor = u.idusuario
            WHERE p.id_asesor = '$asesor_id'
            AND p.id_periodo  = '$periodo_id'
            AND p.tipo        = '1'
            AND p.estado      = '1'
            And p.estado_entrega = '2'
            GROUP BY pv.id
            ");
        }
        //por varios ids de pedido
        if($tipo == 1){
            //quitar las comas y convertir en array
            $ids = explode(",",$parametro1);
            $val_pedido = DB::table('pedidos_val_area_new as pv')
            ->selectRaw('pv.pvn_cantidad as valor,
                        CASE
                            WHEN se.id_serie = 6 THEN l.idlibro
                            ELSE ar.idarea
                        END as id_area,
                        se.id_serie,
                        CASE
                            WHEN se.id_serie = 6 THEN 0
                            ELSE ls.year
                        END as year,
                        CASE
                            WHEN se.id_serie = 6 THEN l.idlibro
                            ELSE 0
                        END as plan_lector,
                        pv.pvn_tipo as alcance,
                        p.id_periodo,
                        CASE
                            WHEN se.id_serie = 6 THEN NULL
                            ELSE CONCAT(se.nombre_serie, " ", ar.nombrearea)
                        END as serieArea,
                        se.nombre_serie,
                        ls.codigo_liquidacion as codigo,
                        l.nombrelibro,
                        l.idlibro,
                        l.descripcionlibro')  // Añadimos el campo plan_lector
            ->leftJoin('libro as l', 'pv.idlibro', '=', 'l.idlibro')
            ->leftJoin('libros_series as ls', 'pv.idlibro', '=', 'ls.idLibro')
            ->leftJoin('asignatura as asi', 'l.asignatura_idasignatura', '=', 'asi.idasignatura')
            ->leftJoin('area as ar', 'asi.area_idarea', '=', 'ar.idarea')
            ->leftJoin('series as se', 'ls.id_serie', '=', 'se.id_serie')
            ->leftJoin('pedidos as p', 'pv.id_pedido', '=', 'p.id_pedido')
            ->leftJoin('usuario as u', 'p.id_asesor', '=', 'u.idusuario')
            ->whereIn('p.id_pedido', $ids)
            ->where('p.tipo', '0')
            ->where('p.estado', '1')
            ->where('p.id_periodo', $periodo_id)
            ->groupBy('pv.pvn_id')
            ->get();
            // return $val_pedido;
        }
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
                    "valor"             => $tr->valor,
                    "id_area"           => $tr->id_area,
                    "id_serie"          => $tr->id_serie,
                    "year"              => $tr->year,
                    "plan_lector"       => $tr->plan_lector,
                    "id_periodo"        => $tr->id_periodo,
                    "serieArea"         => $tr->serieArea,
                    "nombre_serie"      => $tr->nombre_serie,
                    "codigo"            => $tr->codigo,
                    "idlibro"           => $tr->idlibro,
                    "nombrelibro"       => $tr->nombrelibro,
                    "descripcionlibro"  => $tr->descripcionlibro,
                    "alcance"           => $tr->alcance,
                    "alcance"           => $alcance_id,

                ];
            }else{
                //validate que el alcance este cerrado o aprobado
                $query = $this->getAlcanceAbiertoXId($alcance_id);
                if(count($query) > 0){
                    $arreglo[$cont] = (object) [
                        "valor"             => $tr->valor,
                        "id_area"           => $tr->id_area,
                        "id_serie"          => $tr->id_serie,
                        "year"              => $tr->year,
                        "plan_lector"       => $tr->plan_lector,
                        "id_periodo"        => $tr->id_periodo,
                        "serieArea"         => $tr->serieArea,
                        "nombre_serie"      => $tr->nombre_serie,
                        "codigo"            => $tr->codigo,
                        "idlibro"           => $tr->idlibro,
                        "nombrelibro"       => $tr->nombrelibro,
                        "descripcionlibro"  => $tr->descripcionlibro,
                        "alcance"           => $tr->alcance,
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
        // return $renderSet;
        $datos = [];
        $contador = 0;
        //return $renderSet;
        foreach($renderSet as $item){
            $valores = [];
            $pfn_pvp_result = (float) DB::table('pedidos_formato_new')
            ->where('idperiodoescolar', $item->id_periodo)
            ->where('idlibro', $item->idlibro)
            ->value('pfn_pvp');

            // Obtener los valores de pro_stock y pro_deposito
            $stock_producto = DB::table('1_4_cal_producto')
            ->where('pro_codigo', $item->codigo)
            ->select('pro_reservar')
            ->first();
            $datos[$contador] = (Object)[
                "id_area"           => $item->id_area,
                "valor"             => $item->valor,
                // "tipo_val"          => $item->tipo_val,
                "id_serie"          => $item->id_serie,
                "year"              => $item->year,
                // "anio"              => $valores[0]->year,
                // "version"           => $valores[0]->version,
                // "plan_lector"       => $item->plan_lector,
                "serieArea"         => $item->id_serie == 6 ? $item->nombre_serie." ".$item->nombrelibro : $item->serieArea,
                "libro_id"          => $item->idlibro,
                "nombrelibro"       => $item->nombrelibro,
                "nombre_serie"      => $item->nombre_serie,
                "precio"            => $pfn_pvp_result,
                "codigo"            => $item->codigo,
                "stock"             => $stock_producto->pro_reservar,
                "descripcion"       => $item->descripcionlibro,
            ];
            $contador++;
        }
           //si el codigo de liquidacion se repite sumar en el valor
        // Crear un array asociativo para agrupar por codigo_liquidacion
        $grouped = [];

        foreach ($datos as $item) {
            $codigo = $item->codigo;

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
        //array unicos con array unique
        // $resultado  = [];
        // $resultado  = array_unique($datos, SORT_REGULAR);
        // $coleccion  = collect($resultado);
        // return $coleccion->values();
    }

    public function obtenerValores($arrayLibros,$id_pedido){
        $validate               = [];
        $libroSolicitados       = [];
        $libroSolicitados       = $this->pedidosRepository->obtenerLibroxPedidoTodo($id_pedido);
        foreach($arrayLibros as $key =>  $item){
            $validate[$key] = $this->validarIfExistsLibro($item,$libroSolicitados);
        }
        return $validate;
    }

     public function obtenerValores_new($arrayLibros,$id_pedido){
        // return $arrayLibros;
        $validate               = [];
        $libroSolicitados       = [];
        $libroSolicitados       = $this->pedidosRepository->obtenerLibroxPedidoTodo_new($id_pedido);
        foreach($arrayLibros as $key =>  $item){
            $validate[$key] = $this->validarIfExistsLibro($item,$libroSolicitados);
        }
        return $validate;
    }
     public function validarIfExistsLibro($Objectlibro,$libroSolicitados){
        //buscar el idLibro en el array de libros solicitados
        $resultado  = [];
        $coleccion  = collect($libroSolicitados);
        $libro = $coleccion->where('idlibro',$Objectlibro->libro_id)->first();
        if($libro){
            $resultado = [
                'libro_id'      => $Objectlibro->libro_id,
                'nombrelibro'   => $Objectlibro->nombrelibro,
                'valor'         => $libro->valor,
                "codigo"        => $Objectlibro->codigo,
                "precio"        => $Objectlibro->precio,
            ];
        }
        else{
            $resultado = [
                'libro_id'      => $Objectlibro->libro_id,
                'nombrelibro'   => $Objectlibro->nombrelibro,
                'valor'         => 0,
                "codigo"        => $Objectlibro->codigo,
                "precio"        => $Objectlibro->precio,
            ];
        }
        return $resultado;
    }



    public function store(Request $request)
    {
        if($request->getValoresLibrosContratosInstitucionesAsesor)      { return $this->getValoresLibrosContratosInstitucionesAsesor($request); }
        if($request->getValoresLibrosContratosInstitucionesAsesor_new)  { return $this->getValoresLibrosContratosInstitucionesAsesor_new($request); }
    }
}