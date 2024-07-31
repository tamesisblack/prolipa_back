<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\f_tipo_documento;
use Illuminate\Http\Request;
use DB;
use App\Models\PedidoGuiaDevolucion;
use App\Models\PedidoGuiaDevolucionDetalle;
use App\Models\PedidoHistoricoActas;
use App\Models\PedidoGuiaTemp;
use App\Models\Pedidos;
use App\Traits\Pedidos\TraitGuiasGeneral;
use Illuminate\Support\Facades\Http;
class GuiasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    use TraitGuiasGeneral;
    //API:get/guias
    public function index(Request $request)
    {
        //listado de guias para devolver
        if($request->listadoGuias){
            return $this->listadoGuias();
        }
        //validar que no solo haya devolucion abierta
        if($request->validarGenerar){
            return $this->validarGenerar($request->asesor_id);
        }
        //listado de devoluciones
        if($request->devolucion){
            return $this->getDevolucionesBodega($request->asesor_id,$request->periodo_id);
        }//listado de guias devueltas
        if($request->detalle){
            return $this->getDetalle($request->id);
        }
        //ver stock guias
        if($request->verStock){
            return $this->verStock($request->id_pedido);
        }
        //stock de las
        if($request->verStockGuiasProlipa){
            return $this->verStockGuiasProlipa($request->id_pedido,$request->acta);
        }
        //dashboard guias bodega
        if($request->datosGuias){
            return $this->datosGuias();
        }
        //api:get/guias?listadoGuiasXEstado=1&estado_entrega=
        //listado por tipo de entrega
        if($request->listadoGuiasXEstado) { return $this->listadoGuiasXEstado($request->estado_entrega); }
    }
    public function get_val_pedidoInfo($pedido){
        $val_pedido = DB::SELECT("SELECT DISTINCT pv.*,
        p.descuento, p.id_periodo,
        p.anticipo, p.comision, CONCAT(se.nombre_serie,' ',ar.nombrearea) as serieArea,
        se.nombre_serie
        FROM pedidos_val_area pv
        left join area ar ON  pv.id_area = ar.idarea
        left join series se ON pv.id_serie = se.id_serie
        INNER JOIN pedidos p ON pv.id_pedido = p.id_pedido
        WHERE pv.id_pedido = '$pedido'
        AND pv.alcance = '0'
        GROUP BY pv.id;
        ");
        $datos = [];
        foreach($val_pedido as $key => $item){
            $valores = [];
            //plan lector
            if($item->plan_lector > 0 ){
                $getPlanlector = DB::SELECT("SELECT l.nombrelibro,l.idlibro,
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
                WHERE l.idlibro = '$item->plan_lector'
                ");
                $valores = $getPlanlector;
            }else{
                $getLibros = DB::SELECT("SELECT ls.*, l.nombrelibro, l.idlibro,
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
                WHERE ls.id_serie = '$item->id_serie'
                AND a.area_idarea  = '$item->id_area'
                AND l.Estado_idEstado = '1'
                AND a.estado = '1'
                AND ls.year = '$item->year'
                LIMIT 1
                ");
                $valores = $getLibros;
            }
            $datos[$key] = [
                "id"                => $item->id,
                "id_pedido"         => $item->id_pedido,
                "valor"             => $item->valor,
                "id_area"           => $item->id_area,
                "tipo_val"          => $item->tipo_val,
                "id_serie"          => $item->id_serie,
                "year"              => $item->year,
                "anio"              => $valores[0]->year,
                "version"           => $valores[0]->version,
                "created_at"        => $item->created_at,
                "updated_at"        => $item->updated_at,
                "descuento"         => $item->descuento,
                "anticipo"          => $item->anticipo,
                "comision"          => $item->comision,
                "plan_lector"       => $item->plan_lector,
                "serieArea"         => $item->id_serie == 6 ? $item->nombre_serie." ".$valores[0]->nombrelibro : $item->serieArea,
                "idlibro"           => $valores[0]->idlibro,
                "nombrelibro"       => $valores[0]->nombrelibro,
                "precio"            => $valores[0]->precio,
                "subtotal"          => $item->valor * $valores[0]->precio,
                "codigo_liquidacion"=> $valores[0]->codigo_liquidacion,
            ];
        }
        return $datos;

    }
    public function verStock($id_pedido){
        try {
            //consultar el stock
            $arregloCodigos = $this->get_val_pedidoInfo($id_pedido);
            $contador = 0;
            $form_data_stock = [];
            foreach($arregloCodigos as $key => $item){
                $codigo         = $arregloCodigos[$contador]["codigo_liquidacion"];
                $codigoFact     = "G".$codigo;
                $nombrelibro    = $arregloCodigos[$contador]["nombrelibro"];
                //get stock
                $getStock       = Http::get('http://186.4.218.168:9095/api/f2_Producto/Busquedaxprocodigo?pro_codigo='.$codigoFact);
                $json_stock     = json_decode($getStock, true);
                $stockAnterior  = $json_stock["producto"][0]["proStock"];
                //post stock
                $valorNew       = $arregloCodigos[$contador]["valor"];
                $nuevoStock     = $stockAnterior - $valorNew;
                $form_data_stock[$contador] = [
                "nombrelibro"    => $nombrelibro,
                "stockAnterior"  => $stockAnterior,
                "valorNew"       => $valorNew,
                "nuevoStock"     => $nuevoStock,
                "codigoFact"     => $codigoFact,
                "codigo"         => $codigo
                ];
                $contador++;
            }
            return $form_data_stock;
        } catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor"];
        }
    }
    //PARA VER EL STOCK INGRESADO DE LA ACTA
    public function verStockGuiasProlipa($id_pedido,$acta){
        try {
            //consultar el stock
            $arregloCodigos = $this->get_val_pedidoInfo($id_pedido);
            $contador = 0;
            $form_data_stock = [];
            $contador = 0;
            foreach($arregloCodigos as $key => $item){
                //variables
                $codigo         = $arregloCodigos[$contador]["codigo_liquidacion"];
                $nombrelibro    = $arregloCodigos[$contador]["nombrelibro"];
                $valorNew       = $arregloCodigos[$contador]["valor"];
                //consulta
                $query = DB::SELECT("SELECT * FROM pedidos_historico_actas pa
                WHERE pa.ven_codigo = '$acta'
                AND pa.pro_codigo = '$codigo'
                LIMIT 1
                ");
                if(empty($query)){
                    $form_data_stock[$contador] = [
                    "nombrelibro"    => $nombrelibro,
                    "stockAnterior"  => "",
                    "valorNew"       => $valorNew,
                    "nuevoStock"     => "",
                    "codigo"         => $codigo
                    ];
                }else{
                    $stockAnterior  = $query[0]->stock_anterior;
                    $nuevo_stock    = $query[0]->nuevo_stock;
                    $form_data_stock[$contador] = [
                    "nombrelibro"    => $nombrelibro,
                    "stockAnterior"  => $stockAnterior,
                    "valorNew"       => $valorNew,
                    "nuevoStock"     => $nuevo_stock,
                    "codigo"         => $codigo
                    ];
                }
                $contador++;
            }
            return $form_data_stock;
            // foreach($arregloCodigos as $key => $item){
            //     $codigo         = $arregloCodigos[$contador]["codigo_liquidacion"];
            //     $codigoFact     = "G".$codigo;
            //     $nombrelibro    = $arregloCodigos[$contador]["nombrelibro"];
            //     //get stock
            //     $getStock       = Http::get('http://186.4.218.168:9095/api/f2_Producto/Busquedaxprocodigo?pro_codigo='.$codigoFact);
            //     $json_stock     = json_decode($getStock, true);
            //     $stockAnterior  = $json_stock["producto"][0]["proStock"];
            //     //post stock
            //     $valorNew       = $arregloCodigos[$contador]["valor"];
            //     $nuevoStock     = $stockAnterior - $valorNew;
            //     $form_data_stock[$contador] = [
            //     "nombrelibro"    => $nombrelibro,
            //     "stockAnterior"  => $stockAnterior,
            //     "valorNew"       => $valorNew,
            //     "nuevoStock"     => $nuevoStock,
            //     "codigoFact"     => $codigoFact,
            //     "codigo"         => $codigo
            //     ];
            //     $contador++;
            // }
            return $form_data_stock;
        } catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor".$ex];
        }
    }
    //guias?datosGuias=yes
    public function datosGuias(){
        $guiasSinDespachar      = 0;
        $guiasDespachadas       = 0;
        $devueltasPendientes    = 0;
        $devueltasAprobadas     = 0;
        $query = Pedidos::Where("tipo","1")->where('estado','1')->select('id_pedido','estado_entrega')->get();
        //guias
        if(count($query) > 0){ $query->map(function($item) use (&$guiasSinDespachar,&$guiasDespachadas){ if($item->estado_entrega == 1){ $guiasSinDespachar++; } if($item->estado_entrega == 2){ $guiasDespachadas++; } }); }
        //devoluciones de guias
        $query2 = PedidoGuiaDevolucion::all();
        if(count($query2) > 0){
            $query2->map(function($item2) use (&$devueltasPendientes,&$devueltasAprobadas) {
                if($item2->estado == 0){ $devueltasPendientes++; }
                if($item2->estado == 1){ $devueltasAprobadas++; }
            });
         }
        return [ "guiasSinDespachar" => $guiasSinDespachar, "guiasDespachadas" => $guiasDespachadas, "devueltasPendientes" => $devueltasPendientes,"devueltasAprobadas" => $devueltasAprobadas ];
    }
    //api:get/>>guias?listadoGuiasXEstado=1&estado_entrega=2
    public function listadoGuiasXEstado($estado_entrega){
        $query = $this->tr_guiasXEstado($estado_entrega);
        //coleccion
        $coleccion = collect($query);
        //ordenar por fecha_entrega_bodega asc
        $coleccion = $coleccion->sortBy('fecha_entrega_bodega');
        return $coleccion->values();
    }
    public function listadoGuias(){
        $query = DB::SELECT("SELECT pd.*,
        CONCAT(u.nombres,' ',u.apellidos) as asesor,
        (
            SELECT SUM(pg.cantidad_devuelta) AS cantidad
            FROM pedidos_guias_devolucion_detalle  pg
            WHERE pg.pedidos_guias_devolucion_id = pd.id

        ) as cantidad_devolver, pe.codigo_contrato,pe.region_idregion,u.iniciales,
        pe.pedido_facturacion, pe.pedido_bodega, pe.pedido_asesor,pe.periodoescolar as periodo
        FROM pedidos_guias_devolucion pd
        LEFT JOIN usuario u ON pd.asesor_id = u.idusuario
        LEFT JOIN periodoescolar pe ON pd.periodo_id = pe.idperiodoescolar

        ORDER BY pd.id DESC
        ");
        return $query;
    }
    public function validarGenerar($asesor_id){
        $query = DB::SELECT("SELECT * FROM pedidos_guias_devolucion p
        WHERE p.asesor_id = '$asesor_id'
        AND p.estado = '0'
        ");
        if(count($query) > 0){
            return ["status" => "0", "message" => "Existe una devolución abierta en algun periodo"];
        }
    }
    public function getDevolucionesBodega($asesor_id,$periodo_id){
        $query = DB::SELECT("SELECT pd.*,
        CONCAT(u.nombres,' ',u.apellidos) as asesor,
        (
            SELECT SUM(pg.cantidad_devuelta) AS cantidad
            FROM pedidos_guias_devolucion_detalle  pg
            WHERE pg.pedidos_guias_devolucion_id = pd.id

        ) as cantidad_devolver
        FROM pedidos_guias_devolucion pd
        LEFT JOIN usuario u ON pd.asesor_id = u.idusuario
        WHERE pd.asesor_id = '$asesor_id'
        AND periodo_id = '$periodo_id'
        ORDER BY pd.id DESC
        ");
        return $query;
    }
    public function getDetalle($id){
        $query = DB::SELECT("SELECT  pg.* , l.nombrelibro
        FROM pedidos_guias_devolucion_detalle pg
        LEFT JOIN libros_series ls ON pg.pro_codigo = ls.codigo_liquidacion
        LEFT JOIN libro l ON ls.idLibro = l.idlibro
        WHERE pg.pedidos_guias_devolucion_id = '$id'
        ORDER BY l.nombrelibro
        ");
        return $query;
    }
     //api:post//guardarDevolucionBDMilton
     public function guardarDevolucionBDMilton(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        try {
            //variables
            $id_pedido            = $request->id_pedido;
            $codigo_contrato      = $request->codigo_contrato;
            //se envía tal código quemado a facturacion debido a proceso anterior requerido
            $cod_fact             = "JARN";
            //codigo de facturacion se va a usar el codigo de asesor
            //$cod_fact             = $request->iniciales;
            //$usuario_fact         = $request->usuario_fact;
            $iniciales            = $request->iniciales;
            $total_venta          = 0;
            $observacion          = "";
            $anticipo             = 0;
            $descuento            = 0;
            $fecha_formato        = date("Y-m-d");
            $region_idregion      = $request->region_idregion;
            $cuenta               = "0";
            $fechaActual          = date("Y-m-d H:i:s");
            //id general de prolipa para los vendedores
            //buscar el id de institucion de prolipa de facturacion
            // $query = DB::SELECT("SELECT * FROM pedidos_asesor_institucion_docente pd
            // WHERE pd.id_asesor = '$request->iniciales'
            // AND pd.id_institucion = '3858'
            // ");
            $query = DB::SELECT("SELECT * FROM pedidos_secuencia s
            WHERE s.id_periodo = '$request->id_periodo'
            AND s.ven_d_codigo = '$request->iniciales'
            AND s.institucion_facturacion = '22926'
            ");
            if(empty($query)){
                return ["status" => "0", "message" => "No esta configurado el id de institucion de prolipa de facturacion"];
            }
            //get secuencia
            $secuencia = Http::get('http://186.4.218.168:9095/api/f_Configuracion');
            $json_secuencia_guia = json_decode($secuencia, true);
            $getSecuencia   = $json_secuencia_guia[22]["conValorNum"];
            ///====migrar
            // $secuencia = $this->tr_obtenerSecuenciaGuia(2);
            // if(empty($secuencia)){ return ["status" => "0", "message" => "No hay secuencia de guias"]; }
            // $getSecuencia           = $secuencia[0]->tdo_secuencial;
            //VARIABLES
            $cod_institucion      = $query[0]->cli_ins_codigo;
            $secuencia = $getSecuencia;
            if( $secuencia < 10 ){
                $format_id_pedido = '000000' . $secuencia;
            }
            if( $secuencia >= 10 && $secuencia < 1000 ){
                $format_id_pedido = '00000' . $secuencia;
            }
            if( $secuencia > 1000 ){
                $format_id_pedido = '0000' . $secuencia;
            }
            $codigo_ven = 'NCI-'.$iniciales . '-'. $format_id_pedido;

            // $codigo_ven = 'NCI-' . $codigo_contrato . '-' .$iniciales . '-'. $format_id_pedido;
            // $codigo_ven = 'NCI-' . $codigo_contrato . '-' .$cod_fact . '-'. $format_id_pedido;
            //===ENVIAR A TABLA DE VENTA DE MILTON LAS GUIAS
            $form_data = [
                'veN_CODIGO'            => $codigo_ven, //codigo formato milton
                'usU_CODIGO'            => strval($cod_fact),
                // 'usU_CODIGO'            => strval($iniciales),
                'veN_D_CODIGO'          => $iniciales, // codigo del asesor
                'clI_INS_CODIGO'        => floatval($cod_institucion),
                'tiP_veN_CODIGO'        => 2, //Venta por lista
                'esT_veN_CODIGO'        => 2, // por defecto
                'veN_OBSERVACION'       => null,
                'veN_VALOR'             => floatval($total_venta),
                'veN_PAGADO'            => 0.00, // por defecto
                'veN_ANTICIPO'          => floatval($anticipo),
                'veN_DESCUENTO'         => floatval($descuento),
                'veN_FECHA'             => $fecha_formato,
                'veN_CONVERTIDO'        => '', // por defecto
                'veN_TRANSPORTE'        => 0.00, // por defecto
                'veN_ESTADO_TRANSPORTE' => false, // por defecto
                'veN_FIRMADO'           => 'DS', // por defecto
                'veN_TEMPORADA'         => $region_idregion == 1 ? 0 :1 ,
                'cueN_NUMERO'           => strval($cuenta)
            ];
            $guias = Http::post('http://186.4.218.168:9095/api/Contrato', $form_data);
            $json_guias = json_decode($guias, true);
            // //ACTUALIZAR VEN CODIGO - FECHA APROBACION-
            $query = "UPDATE `pedidos_guias_devolucion` SET `ven_codigo` = '$codigo_ven', `fecha_aprobacion` = '$fechaActual', `estado` = '1' WHERE `id` = $id_pedido;";
            DB::UPDATE($query);
            //================SAVE DETALLE DE LAS GUIAS======================
            //obtener las guias por libros
            $detalleGuias = $this->getDetalle($request->id_pedido);
            //Si no hay nada en detalle de venta
            if(empty($detalleGuias)){
                return ["status" => "0", "message" => "No hay ningun libro para el detalle de las guias a devolver"];
            }
            //variables
            $iva = 0;
            $precio = 0;
            $descontar =0;
             //GUARDAR DETALLE DE LAS GUIAS
            foreach($detalleGuias as $key => $item){
                $form_data_detalleGuias = [
                    "VEN_CODIGO"            => $codigo_ven,
                    "PRO_CODIGO"            => "G".$item->pro_codigo,
                    "DET_VEN_CANTIDAD"      => intval($item->cantidad_devuelta),
                    "DET_VEN_VALOR_U"       => floatval($precio),
                    "DET_VEN_IVA"           => floatval($iva),
                    "DET_VEN_DESCONTAR"     => intval($descontar),
                    "DET_VEN_INICIO"        => false,
                    "DET_VEN_CANTIDAD_REAL" => intval($item->cantidad_devuelta),
                ];
                $detalle = Http::post('http://186.4.218.168:9095/api/DetalleVenta', $form_data_detalleGuias);
                $json_detalle = json_decode($detalle, true);
            }
            //ACTUALIZAR EL ACTA DE LAS GUIAS
            //post leer y aumentar secuencia + 1
            $form_data_Secuencia = [
                "conCod"        => 23,
                "conNombre"     => "actas",
                "conValorNum"   => $getSecuencia + 1 ,
                "conValorStr"   => null,
            ];
            $post_Secuencia = Http::post('http://186.4.218.168:9095/api/f_Configuracion', $form_data_Secuencia);
            $json_secuencia = json_decode($post_Secuencia, true);
            //MIGRAR
            //f_tipo_documento::Where('tdo_id',2)->update(['tdo_secuencial' => $getSecuencia + 1]);
            //===ACTUALIZAR STOCK========
           return $this->actualizarStockFacturacion($detalleGuias,$codigo_ven);
            //return response()->json(['json_guias' => $json_guias, 'form_data' => $form_data]);
         } catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor"];
        }

    }
    //actualizar stock
    public function actualizarStockFacturacion($arregloCodigos,$codigo_ven){
        foreach($arregloCodigos as $key => $item){
            $form_data_stock = [];
            $codigo         = $item->pro_codigo;
            $codigoFact     = "G".$codigo;
            //get stock
            $getStock       = Http::get('http://186.4.218.168:9095/api/f2_Producto/Busquedaxprocodigo?pro_codigo='.$codigoFact);
            $json_stock     = json_decode($getStock, true);
            $stockAnterior  = $json_stock["producto"][0]["proStock"];
            //post stock
            $valorNew       = $item->cantidad_devuelta;
            $nuevoStock     = $stockAnterior + $valorNew;
            $form_data_stock = [
                "proStock"     => $nuevoStock,
            ];
            //test
            //$postStock = Http::post('http://186.4.218.168:9095/api/f_Producto/ActualizarStockProducto?proCodigo='.$codigoFact,$form_data_stock);
            //prod
            $postStock = Http::post('http://186.4.218.168:9095/api/f2_Producto/ActualizarStockProducto?proCodigo='.$codigoFact,$form_data_stock);
            $json_StockPost = json_decode($postStock, true);
            //save Historico
            $historico = new PedidoHistoricoActas();
            $historico->cantidad        = $valorNew;
            $historico->ven_codigo      = $codigo_ven;
            $historico->pro_codigo      = $codigo;
            $historico->stock_anterior  = $stockAnterior;
            $historico->nuevo_stock     = $nuevoStock;
            //tipo = 0  solicitud; 1 = devolucion;
            $historico->tipo            = 1;
            $historico->save();
        }
        return $codigo_ven;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }
    //api:post/saveDevolucionGuiasBodega
    public function saveDevolucionGuiasBodega(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $detalles  = json_decode($request->data_detalle);
        $asesor_id = $request->asesor_id;
        if($request->id == 0){
            //save devolucion
            $devolucion = new PedidoGuiaDevolucion();
            $devolucion->periodo_id      = $request->periodo_id;
            $devolucion->asesor_id       = $asesor_id;
            $devolucion->save();
        }else{
            $devolucion = PedidoGuiaDevolucion::findOrFail($request->id);
            //validar que el pedido devolucion este abierto
            if($devolucion->estado != 0){
                return ["status" => "0", "message" => "La solicitud de la devolucion no se encuentra activa"];
            }
        }
        foreach($detalles as $key => $item){
            $codigo     = $item->pro_codigo;
            $cantidad   = $item->formato;
            //GUARDAR DETALLE DE ENTREGA
            $this->saveDevolucionDetalle($item,$devolucion);
            //GUARDAR EL STOCK EN BODEGA DE PROLIPA
            //tipo  0 = suma; 1 = dismunuir stock
            //$this->saveStockBodegaProlipa($tipo,$asesor_id,$codigo,$cantidad);
        }
        if($devolucion){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "0", "message" => "No se pudo guardar"];
        }
    }
    public function saveDevolucionDetalle($tr,$devolucion){
        //validar que el libro ya haya sido devuelto
        $validate = DB::SELECT("SELECT * FROM  pedidos_guias_devolucion_detalle
        WHERE pro_codigo = '$tr->pro_codigo'
        AND pedidos_guias_devolucion_id = '$devolucion->id'
        LIMIT 1
        ");
        if(count($validate) > 0){
            $getId = $validate[0]->id;
            $detalle = PedidoGuiaDevolucionDetalle::findOrFail($getId);
        }else{
            $detalle = new PedidoGuiaDevolucionDetalle();
        }
        $detalle->pro_codigo                   = $tr->pro_codigo;
        $detalle->cantidad_devuelta            = $tr->formato;
        $detalle->pedidos_guias_devolucion_id  = $devolucion->id;
        $detalle->save();
    }
    //api:post/eliminarDevolucionGuias
    public function eliminarDevolucionGuias(Request $request){
        $devolucion = PedidoGuiaDevolucion::findOrFail($request->id)->delete();
        DB::DELETE("DELETE FROM pedidos_guias_devolucion_detalle WHERE pedidos_guias_devolucion_id = '$request->id'");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    //api:post/guias/cambiar
    public function changeGuiaSTOCK(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $guias = json_decode($request->data_guias);
        $contador = 0;
        try {
            foreach($guias as $key => $item){
                $form_data_stock = [];
                $codigo         = $item->pro_codigo;
                $codigoFact     = $item->codigoFact;
                //get stock
               // $getStock       = Http::get('http://186.4.218.168:9095/api/f2_Producto/Busquedaxprocodigo?pro_codigo='.$codigo);
                $getStock       = Http::get('http://186.4.218.168:9095/api/f2_Producto/Busquedaxprocodigo?pro_codigo='.$codigoFact);
                $json_stock     = json_decode($getStock, true);
                $stockAnterior  = $json_stock["producto"][0]["proStock"];
                //post stock
                $valorNew       = $item->cantidad;
                $nuevoStock     = $stockAnterior - $valorNew;
                $form_data_stock = [
                    "proStock"     => $nuevoStock,
                ];
                //test
                //$postStock = Http::post('http://186.4.218.168:9095/api/f_Producto/ActualizarStockProducto?proCodigo='.$codigo,$form_data_stock);
                //$postStock = Http::post('http://186.4.218.168:9095/api/f_Producto/ActualizarStockProducto?proCodigo='.$codigoFact,$form_data_stock);
                //prod
                //$postStock = Http::post('http://186.4.218.168:9095/api/f2_Producto/ActualizarStockProducto?proCodigo='.$codigo,$form_data_stock);
                $postStock = Http::post('http://186.4.218.168:9095/api/f2_Producto/ActualizarStockProducto?proCodigo='.$codigoFact,$form_data_stock);
                $json_StockPost = json_decode($postStock, true);

                $historico = new PedidoHistoricoActas();
                $historico->cantidad        = $valorNew;
                $historico->ven_codigo      = "A-S23-FR-000053427";
                $historico->pro_codigo      = $codigo;
                $historico->stock_anterior  = $stockAnterior;
                $historico->nuevo_stock     = $nuevoStock;
                $historico->save();
                if($historico){
                    $contador++;
                }
                //save Historico
                // $historico = new PedidoGuiaTemp();
                // $historico->cantidad        = $valorNew;
                // $historico->pro_codigo      = $codigo;
                // $historico->stock_anterior  = $stockAnterior;
                // $historico->nuevo_stock     = $nuevoStock;
                // $historico->tipo            = $request->tipo;
                // $historico->save();
                // if($historico){
                //     $contador++;
                // }
            }
            return ["cambiados" => $contador];
        } catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor".$ex];
        }
    }
    public function corregirChequeContabilidad(Request $request){
        DB::UPDATE("UPDATE pedidos_historico
        SET `$request->fecha` = null,
        `$request->sendFile` = null,
         `estado` = '$request->estado'
         WHERE `id_pedido` = '$request->id_pedido'
         ");
    }
}
