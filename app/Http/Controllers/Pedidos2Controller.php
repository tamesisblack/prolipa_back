<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CodigosLibros;
use App\Models\Models\Pedidos\PedidosDocumentosLiq;
use App\Models\Institucion;
use App\Models\User;
use App\Models\Usuario;
use App\Models\Ventas;
use App\Models\Verificacion;
use App\Repositories\Codigos\CodigosRepository;
use App\Repositories\pedidos\PedidosRepository;
use App\Traits\Pedidos\TraitPedidosGeneral;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class Pedidos2Controller extends Controller
{
    use TraitPedidosGeneral;
    protected $pedidosRepository = null;
    private $codigosRepository;
    public function __construct(PedidosRepository $pedidosRepository,CodigosRepository $codigosRepository)
    {
        $this->pedidosRepository = $pedidosRepository;
        $this->codigosRepository = $codigosRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //pedidos2/pedidos
    public function index(Request $request)
    {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        if($request->getReportePedidos)             { return $this->getReportePedidos($request); }
        if($request->getReportePedidos_new)         { return $this->getReportePedidos_new($request); }
        if($request->getLibrosFormato)              { return $this->getLibrosFormato($request->periodo_id); }
        if($request->getLibrosFormato_new)          { return $this->getLibrosFormato_new($request->periodo_id); }
        if($request->geAllLibrosxAsesorEscuelas)    { return $this->geAllLibrosxAsesorEscuelas($request->asesor_id,$request->periodo_id); }
        if($request->geAllGuiasxAsesor)             { return $this->geAllGuiasxAsesor($request->asesor_id,$request->periodo_id); }
        if($request->geAllLibrosxAsesor)            { return $this->geAllLibrosxAsesor($request->asesor_id,$request->periodo_id); }
        //api:get/pedidos2/pedidos?getAsesoresPedidos=1
        if($request->getAsesoresPedidos)            { return $this->getAsesoresPedidos(); }
        if($request->getAsesoresVentasPeriodo)      { return $this->getAsesoresVentasPeriodo($request->id_periodo); }
        if($request->getInstitucionesDespacho)      { return $this->getInstitucionesDespacho($request); }
        if($request->getLibrosXDespacho)            { return $this->getLibrosXDespacho($request); }
        if($request->getLibrosXDespacho_new)        { return $this->getLibrosXDespacho_new($request); }
        if($request->getLibrosXInstituciones)       { return $this->getLibrosXInstituciones($request->id_periodo,$request->tipo_venta); }
        if($request->getLibrosXInstitucionesAsesor) { return $this->getLibrosXInstitucionesAsesor($request->id_periodo,$request->tipo_venta,$request->id_asesor); }
        if($request->getLibrosXInstitucionesAsesor_new) { return $this->getLibrosXInstitucionesAsesor_new($request->id_periodo,$request->tipo_venta,$request->id_asesor); }
        if($request->getLibrosXPerido) { return $this->getLibrosXPerido($request->id_periodo); }
        if($request->formatoPrecioXPeriodo)         { return $this->formatoPrecioXPeriodo($request); }

        if($request->getproStockReserva)            { return $this->getproStockReserva($request); }
        if($request->getPuntosVentaDespachadosComparativo) { return $this->getPuntosVentaDespachadosComparativo($request); }
        if($request->getPuntosVentaDespachados)     { return $this->getPuntosVentaDespachados($request); }
        if($request->getpuntosVentaDespachadosFacturacion) { return $this->getpuntosVentaDespachadosFacturacion($request); }
        if($request->getDatosPuntosAllVenta)        { return $this->getDatosPuntosAllVenta($request); }
        if($request->getDatosPuntosVenta)           { return $this->getDatosPuntosVenta($request); }
        if($request->getDatosClient)                { return $this->getDatosClient($request); }
        if($request->getDatosClientes)              { return $this->getDatosClientes($request); }
        if($request->getDatosDespachoProforma)      { return $this->getDatosDespachoProforma($request); }
        if($request->InformacionAgrupado)           { return $this->InformacionAgrupado($request->codigo); }
        if($request->informacionInstitucionPerseo)  { return $this->informacionInstitucionPerseo($request); }
        if($request->getReporteFacturacionAsesores) { return $this->getReporteFacturacionAsesores($request); }
        if($request->getReporteFacturacionAsesores_new) { return $this->getReporteFacturacionAsesores_new($request); }
        if($request->getReporteFacturadoXAsesores)  { return $this->getReporteFacturadoXAsesores($request); }
        if($request->getReporteFacturadoXAsesores_new)  { return $this->getReporteFacturadoXAsesores_new($request); }
        if($request->getInfoFacturadoXyear)         { return $this->getInfoFacturadoXyear($request); }
        if($request->getInfoVendidoXyear)           { return $this->getInfoVendidoXyear($request); }
        if($request->getInfoVendidoFacturadoXyear)  { return $this->getInfoVendidoFacturadoXyear($request); }
        if($request->getCobradoXyear)               { return $this->getCobradoXyear($request); }

    }
    //API:GET/pedidos2/pedidos?getReportePedidos=1&periodo_id=26&ifContratos=1
    public function getReportePedidos($request) {
        // 1. Validar que el periodo_id esté presente
        $periodo_id = $request->periodo_id;
        $ifContratos = $request->ifContratos;

        if (!$periodo_id) {
            return ["status" => "0", "message" => "Falta el periodo_id"];
        }

        // 2. Obtener los pedidos
        if ($ifContratos == 1) {
            // Con contratos
            $getPedidos = DB::SELECT("
                SELECT p.*
                FROM pedidos p
                WHERE p.id_periodo = ?
                AND p.estado = '1'
                AND p.tipo = '0'
                AND p.contrato_generado IS NOT NULL
            ", [$periodo_id]);
        } else {
            // Sin contratos
            $getPedidos = DB::SELECT("
                SELECT p.*
                FROM pedidos p
                WHERE p.id_periodo = ?
                AND p.estado = '1'
                AND p.tipo = '0'
                AND p.contrato_generado IS NULL
            ", [$periodo_id]);
        }

        $arrayDetalles = [];

        // 3. Recorrer los pedidos y obtener los detalles de cada uno
        foreach ($getPedidos as $key => $item10) {
            $pedido = $item10->id_pedido;
            $libroSolicitados = $this->pedidosRepository->obtenerLibroxPedidoTodo($pedido);
            $arrayDetalles[$key] = $libroSolicitados;
        }

        $agrupado = [];
        $arrayDetalles = collect($arrayDetalles)->flatten(10);

        // 4. Agrupar los datos por código de liquidación
        foreach ($arrayDetalles as $detalle) {
            $codigo_liquidacion = $detalle->codigo_liquidacion;

            if (isset($agrupado[$codigo_liquidacion])) {
                $agrupado[$codigo_liquidacion]['valor'] += $detalle->valor;
                $agrupado[$codigo_liquidacion]['total'] += $detalle->valor * $detalle->precio;

                if (empty($agrupado[$codigo_liquidacion]['nombrelibro'])) {
                    $agrupado[$codigo_liquidacion]['nombrelibro'] = $detalle->nombrelibro;
                }
            } else {
                $agrupado[$codigo_liquidacion] = [
                    'codigo_liquidacion' => $codigo_liquidacion,
                    'valor' => $detalle->valor,
                    'nombrelibro' => $detalle->nombrelibro,
                    'precio' => $detalle->precio,
                    'total' => $detalle->valor * $detalle->precio,
                ];
            }
        }

        // 5. Retornar los datos agrupados
        return array_values($agrupado);
    }

    //API:GET/pedidos2/pedidos?getReportePedidos_new=1&periodo_id=26&ifContratos=1
    public function getReportePedidos_new($request) {
        // 1. Validar que el periodo_id esté presente
        $periodo_id = $request->periodo_id;
        $ifContratos = $request->ifContratos;

        if (!$periodo_id) {
            return ["status" => "0", "message" => "Falta el periodo_id"];
        }

        // 2. Obtener los pedidos
        if ($ifContratos == 1) {
            // Con contratos
            $getPedidos = DB::SELECT("
                SELECT p.*
                FROM pedidos p
                WHERE p.id_periodo = ?
                AND p.estado = '1'
                AND p.tipo = '0'
                AND p.contrato_generado IS NOT NULL
            ", [$periodo_id]);
        } else {
            // Sin contratos
            $getPedidos = DB::SELECT("
                SELECT p.*
                FROM pedidos p
                WHERE p.id_periodo = ?
                AND p.estado = '1'
                AND p.tipo = '0'
                AND p.contrato_generado IS NULL
            ", [$periodo_id]);
        }

        $arrayDetalles = [];

        // 3. Recorrer los pedidos y obtener los detalles de cada uno
        foreach ($getPedidos as $key => $item10) {
            $pedido = $item10->id_pedido;
            $libroSolicitados = $this->pedidosRepository->obtenerLibroxPedidoTodo_new($pedido);
            $arrayDetalles[$key] = $libroSolicitados;
        }

        $agrupado = [];
        $arrayDetalles = collect($arrayDetalles)->flatten(10);

        // 4. Agrupar los datos por código de liquidación
        foreach ($arrayDetalles as $detalle) {
            $codigo_liquidacion = $detalle->codigo_liquidacion;

            if (isset($agrupado[$codigo_liquidacion])) {
                $agrupado[$codigo_liquidacion]['valor'] += $detalle->valor;
                $agrupado[$codigo_liquidacion]['total'] += $detalle->valor * $detalle->precio;

                if (empty($agrupado[$codigo_liquidacion]['nombrelibro'])) {
                    $agrupado[$codigo_liquidacion]['nombrelibro'] = $detalle->nombrelibro;
                }
            } else {
                $agrupado[$codigo_liquidacion] = [
                    'codigo_liquidacion' => $codigo_liquidacion,
                    'valor' => $detalle->valor,
                    'nombrelibro' => $detalle->nombrelibro,
                    'precio' => $detalle->precio,
                    'total' => $detalle->valor * $detalle->precio,
                ];
            }
        }

        // 5. Retornar los datos agrupados
        return array_values($agrupado);
    }



    //API:GET/pedidos2/pedidos?getLibrosFormato=yes&periodo_id=22
    /**
     * Get the libros formato for a given periodo.
     *
     * @param  string  $periodo
     * @return \Illuminate\Support\Collection
     */
    public function getLibrosFormato($periodo){
        $librosNormales = [];
        $librosPlan     = [];
        $resultado      = [];
        $librosNormales = $this->pedidosRepository->getLibrosNormalesFormato($periodo);
        $librosPlan     = $this->pedidosRepository->getLibrosPlanLectorFormato($periodo);
        //unir los dos arreglos
        $resultado      = array_merge(array($librosNormales),array($librosPlan));
        $coleccion      = collect($resultado)->flatten(10);
        return $coleccion;
    }
    //api:get/pedidos2/pedidos?getInstitucionesDespacho=1
    public function getInstitucionesDespacho($request){
        $id_periodo = $request->id_periodo;
        $query      = $this->tr_getInstitucionesDespacho($id_periodo);
        $datos = [];
        //sacar el id de pedido de cada ca_codigo_agrupado
        foreach($query as $key => $item){
            $datos[$key] = [
                'ca_codigo_agrupado' => $item->ca_codigo_agrupado,
                'id_periodo'        => $item->id_periodo,
                'codigo_contrato'   => $item->codigo_contrato,
                'pedidos'           => $this->tr_pedidosXDespacho($item->ca_codigo_agrupado,$id_periodo),
                'preproformas'      => $this->tr_getPreproformas($item->ca_codigo_agrupado),
                'datoAgrupado'      => $this->tr_getAgrupado($item->ca_codigo_agrupado),
                'Instituciones'     => $this->tr_getPreproformasInstitucion($item->ca_codigo_agrupado),
                'documentos'        => $this->tr_getDocumentos($item->ca_codigo_agrupado),
                'ruc_ci'            => $this->tr_getDocumentosRuc($item->ca_codigo_agrupado),
                'ca_descripcion'    => $item->ca_descripcion,
                'ca_tipo_pedido'    => $item->ca_tipo_pedido,
                'ca_id'             => $item->ca_id,
            ];
        }
        //que sean unicos
        $datos = collect($datos)->unique('ca_codigo_agrupado')->values();
        return $datos;
    }
    public function editarInstitucionDespacho(Request $request){
        $institucion = Institucion::findOrFail($request->idInstitucion);
        $institucion->ruc = $request->ruc;
        $institucion->telefonoInstitucion = $request->telefonoInstitucion;
        $institucion->email = $request->email;
        $institucion->direccionInstitucion = $request->direccionInstitucion;
        $institucion->save();
        if ($institucion->wasRecentlyCreated || $institucion->wasChanged()) {
            return "Se guardó correctamente";
        } else {
            return "No se pudo guardar/actualizar";
        }
    }
    //api:post/marcarComoAnuladoPerseoPrefactura
    public function marcarComoAnuladoPerseoPrefactura(Request $request){
        try {
            // Obtener datos del request
            $ven_codigo = $request->ven_codigo;
            $id_empresa = $request->id_empresa;

            // Realizar la actualización
            Ventas::where('ven_codigo', $ven_codigo)
                ->where('id_empresa', $id_empresa)
                ->update(['anuladoEnPerseo' => '1']);

            // Devolver una respuesta de éxito
            return response()->json([
                "status" => "1",
                "message" => "Se marcó como anulado correctamente"
            ]);
        } catch (\Exception $e) {
            // Manejar cualquier otra excepción
            return response()->json([
                "status" => "0",
                "message" => "Error inesperado: " . $e->getMessage()
            ], 200);
        }
    }
    //API:GET/pedidos2/pedidos?getLibrosXDespacho=yes&id_pedidos=1,2,3
    public function getLibrosXDespacho($request){
        $query = $this->geAllLibrosxAsesor(0,$request->id_periodo,1,$request->id_pedidos);
        return $query;
    }

    //API:GET/pedidos2/pedidos?getLibrosXInstituciones=yes&id_periodo=23&tipo_venta=1
    public function getLibrosXInstituciones($id_periodo,$tipo_venta){
        $query = $this->tr_getInstitucionesVentaXTipoVenta($id_periodo,$tipo_venta);
        $id_pedidos = "";
        //crear un coleccion y pluck de id_pedido
        $id_pedidos = collect($query)->pluck('id_pedido');
        //convertir en string
        $id_pedidos = implode(",",$id_pedidos->toArray());
        $query = $this->geAllLibrosxAsesor(0,$id_periodo,1,$id_pedidos);
        return $query;
    }
    //API:GET/pedidos2/pedidos?getLibrosXInstitucionesAsesor=yes&id_periodo=23&tipo_venta=1&id_asesor=1
    public function getLibrosXInstitucionesAsesor($id_periodo,$tipo_venta,$asesor){
        $query = $this->tr_getInstitucionesVentaXTipoVentaAsesor($id_periodo,$tipo_venta,$asesor);
        $id_pedidos = "";
        //crear un coleccion y pluck de id_pedido
        $id_pedidos = collect($query)->pluck('id_pedido');
        //convertir en string
        $id_pedidos = implode(",",$id_pedidos->toArray());
        $query = $this->geAllLibrosxAsesor(0,$id_periodo,1,$id_pedidos);
        return $query;
    }
    //API:GET/pedidos2/pedidos?getLibrosXPerido=yes&id_periodo=22
    public function getLibrosXPerido($id_periodo){
        $query = $this->tr_getInstitucionesPeriodo($id_periodo);
        $id_pedidos = "";
        //crear un coleccion y pluck de id_pedido
        $id_pedidos = collect($query)->pluck('id_pedido');
        //convertir en string
        $id_pedidos = implode(",",$id_pedidos->toArray());
        $query2 = $this->geAllLibrosxAsesor(0,$id_periodo,1,$id_pedidos);
        return $query2;
    }
    //api:get/pedidos2/pedidos?formatoPrecioXPeriodo=1&periodo_id=28&nuevo=1
    public function formatoPrecioXPeriodo(Request $request){
        $periodo_id = $request->periodo_id;
        $nuevo      = $request->nuevo;
        if($nuevo == 1){
            $query = DB::SELECT("SELECT * FROM pedidos_formato_new p
            WHERE p.idperiodoescolar = '$periodo_id'
            AND p.pfn_estado = 1
            ");
        }else{
            $query = DB::SELECT("SELECT * FROM pedidos_formato p
            WHERE p.id_periodo = '$periodo_id';
            ");
        }
        return $query;
    }
    //API:GET/pedidos2/pedidos?getproStockReserva=yes
    public function getproStockReserva($pro_codigo){
        $query = DB::SELECT("SELECT pro_codigo,pro_reservar FROM 1_4_cal_producto WHERE pro_codigo = '$pro_codigo' ");
        return $query;
    }
    //API:GET/pedidos2/pedidos?getPuntosVentaDespachados=1&periodo=25&idusuario=1&tipoVenta=2
    public function getPuntosVentaDespachados($request){
        $periodo   = $request->periodo;
        $idusuario = $request->idusuario;
        $tipoVenta = $request->input('tipoVenta', 2);

        $clave = "getPuntosVentaDespachados".$periodo.$idusuario.$tipoVenta;
        if (Cache::has($clave)) {
            $response = Cache::get($clave);
        } else {
            //directa
            if($tipoVenta == '1'){ $query     = $this->tr_getPuntosVentasDirectasDespachos($periodo); }
            //lista
            if($tipoVenta == '2'){ $query     = $this->tr_getPuntosVentasDespachos($periodo); }
            $response = $query;
            Cache::put($clave,$response);
        }
        return $response;
    }
     //API:GET/pedidos2/pedidos?getPuntosVentaDespachadosComparativo=1&periodo=25&idusuario=1
     public function getPuntosVentaDespachadosComparativo($request){
        $periodo   = $request->periodo;
        $idusuario = $request->idusuario;

        // $clave = "getPuntosVentaDespachadosComparativo".$periodo.$idusuario;
        // if (Cache::has($clave)) {
        //     $response = Cache::get($clave);
        // } else {
            $filtro   = 0;
            $query     = $this->tr_getPuntosVentasDespachos($periodo);
            foreach($query as $key => $item){
                $libros = [];
                $libros = $this->codigosRepository->getCodigosBodega($filtro,$periodo,$item->venta_lista_institucion);
                if(count($libros) > 0){
                    $query[$key]->totalCantidadBodegaPuntoVenta = count($libros);
                    $query[$key]->totalValorBodegaPuntoVenta    = collect($libros)->sum('precio_total');
                }else{
                    $query[$key]->totalCantidadBodegaPuntoVenta = 0;
                    $query[$key]->totalValorBodegaPuntoVenta    = 0;
                }
            }
            return $query;
            //$response = $query;
        //     Cache::put($clave,$response);
        // }
        // return $response;
    }
    //API:GET/pedidos2/pedidos?getpuntosVentaDespachadosFacturacion=1&periodo=25&idusuario=1
    public function getpuntosVentaDespachadosFacturacion($request){
        $periodo   = $request->periodo;
        $idusuario = $request->idusuario;
        $clave = "getpuntosVentaDespachadosFacturacion".$periodo.$idusuario;
        if (Cache::has($clave)) {
            $response = Cache::get($clave);
        } else {
            $query     = $this->tr_puntosVentaDespachadosFacturacion($periodo);
            $response = $query;
            Cache::put($clave,$response);
        }
        return $response;
    }
    //API:GET/pedidos2/pedidos?getDatosPuntosAllVenta=1&busqueda=prolipa
    public function getDatosPuntosAllVenta($request){
        $busqueda   = $request->busqueda;
        $query      = $this->tr_getPuntosVenta($busqueda);
        return $query;
    }
    //API:GET/pedidos2/pedidos?getDatosPuntosVenta=1&busqueda=prolipa&id_periodo=22
    public function getDatosPuntosVenta($request){
        $busqueda   = $request->busqueda;
        $id_periodo = $request->id_periodo;
        $getPeriodo = DB::table("periodoescolar")
        ->where('idperiodoescolar',$id_periodo)
        ->get();
        $region = $getPeriodo[0]->region_idregion;
        $query = $this->tr_getPuntosVentaRegion($busqueda,$region,$id_periodo);
        //traer datos de la tabla f_formulario_proforma por id_periodo
        foreach($query as $key => $item){ $query[$key]->datosInstitucion = DB::SELECT("SELECT * FROM f_formulario_proforma fp WHERE fp.idInstitucion = '$item->idInstitucion' AND fp.idperiodoescolar = '$id_periodo'"); }
        return $query;
    }
    //API:GET/pedidos2/pedidos?getDatosClient=1&busqueda=prolipa
    public function getDatosClient($request){
        $busqueda = $request->busqueda;
        $query = $this->tr_getCliente($busqueda);
        return $query;
    }
    //API:GET/pedidos2/pedidos?getDatosClientes=1&busqueda=prolipa
    public function getDatosClientes($request){
        $busqueda = $request->busqueda;
        $query = $this->tr_getClientes($busqueda);
        return $query;
    }
    //API:GET/pedidos2/pedidos?getDatosDespachoProforma=1&idProforma=P-ER-0000022&id_periodo=22
    public function getDatosDespachoProforma($request){
        $idProforma = $request->idProforma;
        $query = $this->tr_getDespachoProforma($idProforma);
        //traer datos de la tabla f_formulario_proforma por id_periodo
        foreach($query as $key => $item){ $query[$key]->datosInstitucion = DB::SELECT("SELECT * FROM f_formulario_proforma fp WHERE fp.idInstitucion = '$item->id_ins_depacho' AND fp.idperiodoescolar = '$request->id_periodo'"); }
        return $query;
    }
    //API:GET/pedidos2/pedidos?InformacionAgrupado=1&codigo=AG-44
    public function InformacionAgrupado($codigo){
        $query = $this->tr_getInformacionAgrupado($codigo);
        //traer la primera proforma con estado diferente a 3
        $query[0]->proformas = DB::SELECT("SELECT p.*, i.nombreInstitucion, c.nombre AS ciudad,
        CONCAT(u.nombres, ' ', u.apellidos) AS cliente, u.email as emailCliente
        FROM f_proforma p
        LEFT JOIN institucion i ON p.id_ins_depacho = i.idInstitucion
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        LEFT JOIN usuario u ON p.ven_cliente = u.idusuario
        WHERE p.idPuntoventa = ?
        AND p.prof_estado <> '0'
        LIMIT 1",[$codigo]);
        if(count($query[0]->proformas) > 0){
            $id_ins_depacho = $query[0]->proformas[0]->id_ins_depacho;
            $id_periodo     = $query[0]->id_periodo;
            $query[0]->proformas[0]->datosInstitucion = DB::SELECT("SELECT * FROM f_formulario_proforma fp WHERE fp.idInstitucion = '$id_ins_depacho' AND fp.idperiodoescolar = '$id_periodo'");
        }
        return $query;
    }
    //API:GET/pedidos2/pedidos?informacionInstitucionPerseo=1&institucion_id=981
    public function informacionInstitucionPerseo($request){
        $institucion_id = $request->institucion_id;
        $query = $this->tr_getInformacionInstitucionPerseo($institucion_id);
        return $query;
    }
    //API:GET/pedidos2/pedidos?getReporteFacturacionAsesores=1&periodo_id=25
    public function getReporteFacturacionAsesores($request){


        $periodo_id = $request->periodo_id;
        $query      = $this->tr_getAsesoresFacturacionXPeriodo($periodo_id);

        foreach($query as $key => $item){
            $getInstituciones       = $this->tr_InstitucionesDespachadosFacturacionAsesor($periodo_id,$item->asesor_id);
            // Convertir a colección en caso de que no lo sea
            $getInstituciones       = collect($getInstituciones);
            // $item->instituciones    = $getInstituciones;
            // Usar pluck para obtener solo los institucion_id
            $getInstitucionesId     = $getInstituciones->pluck('institucion_id');
            // $item->institucionesId  = $getInstitucionesId;
            //====libros====
            //prolipa
            $datosRequest = (Object)[
                "periodo"               => $periodo_id,
                "empresa"               => 1,
                "variasInstituciones"   => 1,
                "getInstitucionesId"    => $getInstitucionesId
            ];
            $getLibrosProlipa           = $this->tr_metodoFacturacion($datosRequest);
            //calmed
            $datosRequest = (Object)[
                "periodo"               => $periodo_id,
                "empresa"               => 3,
                "variasInstituciones"   => 1,
                "getInstitucionesId"    => $getInstitucionesId
            ];
            $getLibrosCalmed            = $this->tr_metodoFacturacion($datosRequest);
            $item->librosProlipa        = $getLibrosProlipa;
            $item->librosCalmed         = $getLibrosCalmed;
            //sumar cantidad de prolipa y calmed
            $item->totalLibrosProlipa    = collect($item->librosProlipa)->sum('cantidad');
            $item->totalLibrosCalmed     = collect($item->librosCalmed)->sum('cantidad');
            //sumar precio_total
            $item->totalPrecioProlipa    = collect($item->librosProlipa)->sum('precio_total');
            $item->totalPrecioCalmed     = collect($item->librosCalmed)->sum('precio_total');
        }
        return $query;

    }
    public function getReporteFacturadoXAsesores($request){
        $periodo_id = $request->periodo_id;
        $query      = $this->tr_getAsesoresFacturadoXPeriodo($periodo_id);

        foreach($query as $key => $item){
            $getInstituciones       = $this->tr_InstitucionesDespachadosFacturadoAsesor($periodo_id,$item->asesor_id);
            // Convertir a colección en caso de que no lo sea
            $getInstituciones       = collect($getInstituciones);
            // $item->instituciones    = $getInstituciones;
            // Usar pluck para obtener solo los institucion_id
            $getInstitucionesId     = $getInstituciones->pluck('institucion_id');
            // $item->institucionesId  = $getInstitucionesId;
            //====libros====
            //prolipa
            $datosRequest = (Object)[
                "periodo"               => $periodo_id,
                "empresa"               => 1,
                "variasInstituciones"   => 1,
                "getInstitucionesId"    => $getInstitucionesId,
                "tipo"                  => $request->tipo
            ];
            $getLibrosProlipa           = $this->tr_metodoFacturado($datosRequest);
            //calmed
            $datosRequest = (Object)[
                "periodo"               => $periodo_id,
                "empresa"               => 3,
                "variasInstituciones"   => 1,
                "getInstitucionesId"    => $getInstitucionesId,
                "tipo"                  => $request->tipo
            ];
            $getLibrosCalmed            = $this->tr_metodoFacturado($datosRequest);
            $item->librosProlipa        = $getLibrosProlipa;
            $item->librosCalmed         = $getLibrosCalmed;
            //sumar cantidad de prolipa y calmed
            $item->totalLibrosProlipa    = collect($item->librosProlipa)->sum('cantidad');
            $item->totalLibrosCalmed     = collect($item->librosCalmed)->sum('cantidad');
            //sumar precio_total
            $item->totalPrecioProlipa    = collect($item->librosProlipa)->sum('precio_total');
            $item->totalPrecioCalmed     = collect($item->librosCalmed)->sum('precio_total');
        }
        return $query;

    }

    public function getInfoFacturadoXyear($request) {
        $year = $request->year;

        $ventas = DB::table('f_venta_agrupado')
            ->whereYear('ven_fecha', $year)
            ->get();

        $ventas = collect($ventas);
        $result = [
            'datosProlipa' => [
                'detalles'      => [],
                'totalDetalles' => 0,
                'ventaBruta'    => 0,
                'ventaNeta'     => 0,
                'descuento'     => 0,
            ],
            'datosCalmed' => [
                'detalles'      => [],
                'totalDetalles' => 0,
                'ventaBruta'    => 0,
                'ventaNeta'     => 0,
                'descuento'     => 0,
            ],
        ];

        foreach ($ventas as $venta) {
            // Obtener detalles de la venta
            $detalles = DB::table('f_detalle_venta_agrupado AS fda')
                ->join('libros_series AS ls', 'ls.codigo_liquidacion', '=', 'fda.pro_codigo')
                ->where('fda.id_factura', $venta->id_factura)
                ->where('fda.id_empresa', $venta->id_empresa)
                ->select('ls.nombre', 'fda.*')
                ->get();

            $detalles = collect($detalles);

            // Calcular descuento si es necesario
            $descuento = $venta->ven_descuento ?? 0;

            if ($venta->id_empresa == 1) {
                $result['datosProlipa']['ventaBruta']    += $venta->ven_subtotal;
                $result['datosProlipa']['ventaNeta']     += $venta->ven_valor;
                $result['datosProlipa']['descuento']     += $descuento;
            }elseif ($venta->id_empresa == 3) {
                $result['datosCalmed']['ventaBruta']    += $venta->ven_subtotal;
                $result['datosCalmed']['ventaNeta']     += $venta->ven_valor;
                $result['datosCalmed']['descuento']     += $descuento;
            }
            // Procesar detalles según la empresa
            foreach ($detalles as $detalle) {
                $libro              = $detalle->nombre;
                $pro_codigo         = $detalle->pro_codigo;
                $cantidad           = $detalle->det_ven_cantidad;
                $precio_unitario    = $detalle->det_ven_valor_u;

                if ($venta->id_empresa == 1) { // Prolipa
                    if (!isset($result['datosProlipa']['detalles'][$pro_codigo])) {
                        $result['datosProlipa']['detalles'][$pro_codigo] = [
                            'libro'             => $libro,
                            'pro_codigo'        => $pro_codigo,
                            'cantidad'          => 0,
                            'precio_unitario'   => $precio_unitario,
                            'total'             => 0,
                        ];
                    }

                    $result['datosProlipa']['detalles'][$pro_codigo]['cantidad'] += $cantidad;
                    $result['datosProlipa']['detalles'][$pro_codigo]['total']    += round($cantidad * $precio_unitario, 2);

                    // Sumar totales de Prolipa
                    $result['datosProlipa']['totalDetalles'] += $cantidad;

                } elseif ($venta->id_empresa == 3) { // Calmed
                    if (!isset($result['datosCalmed']['detalles'][$pro_codigo])) {
                        $result['datosCalmed']['detalles'][$pro_codigo] = [
                            'libro'             => $libro,
                            'pro_codigo'        => $pro_codigo,
                            'cantidad'          => 0,
                            'precio_unitario'   => $precio_unitario,
                            'total'             => 0,
                        ];
                    }

                    $result['datosCalmed']['detalles'][$pro_codigo]['cantidad'] += $cantidad;
                    $result['datosCalmed']['detalles'][$pro_codigo]['total']    += round($cantidad * $precio_unitario, 2);

                    // Sumar totales de Calmed
                    $result['datosCalmed']['totalDetalles'] += $cantidad;
                }
            }
        }

        $result['datosProlipa']['ventaBruta'] = round($result['datosProlipa']['ventaBruta'], 2);
        $result['datosProlipa']['ventaNeta']  = round($result['datosProlipa']['ventaNeta'], 2);
        $result['datosProlipa']['descuento']  = round($result['datosProlipa']['descuento'], 2);

        $result['datosCalmed']['ventaBruta']  = round($result['datosCalmed']['ventaBruta'], 2);
        $result['datosCalmed']['ventaNeta']   = round($result['datosCalmed']['ventaNeta'], 2);
        $result['datosCalmed']['descuento']   = round($result['datosCalmed']['descuento'], 2);

        $result['datosProlipa']['detalles']   = array_values($result['datosProlipa']['detalles']);
        $result['datosCalmed']['detalles']    = array_values($result['datosCalmed']['detalles']);

        return $result;
    }

    public function getInfoVendidoXyear($request) {
        $year = $request->year;

        $ventas = DB::table('f_venta')
            ->where('est_ven_codigo','<>', 3)
            ->where('idtipodoc', '<>', 16)
            ->whereYear('ven_fecha', $year)
            ->get();

        $ventas = collect($ventas);
        $result = [
            'datosProlipa' => [
                'totalDetalles' => 0,
                'ventaBruta'    => 0,
                'ventaNeta'     => 0,
                'descuento'     => 0,
                'documentos'    => [
                    'facturas' => [
                        'detalles' => [],
                        'totalDetalles' => 0,
                        'ventaBruta' => 0,
                        'ventaNeta' => 0,
                        'descuento' => 0,
                    ],
                    'actas' => [
                        'detalles' => [],
                        'totalDetalles' => 0,
                        'ventaBruta' => 0,
                        'ventaNeta' => 0,
                        'descuento' => 0,
                    ],
                    'notas' => [
                        'detalles' => [],
                        'totalDetalles' => 0,
                        'ventaBruta' => 0,
                        'ventaNeta' => 0,
                        'descuento' => 0,
                    ],
                ],
            ],
            'datosCalmed' => [
                'totalDetalles' => 0,
                'ventaBruta'    => 0,
                'ventaNeta'     => 0,
                'descuento'     => 0,
                'documentos'    => [
                    'facturas' => [
                        'detalles' => [],
                        'totalDetalles' => 0,
                        'ventaBruta' => 0,
                        'ventaNeta' => 0,
                        'descuento' => 0,
                    ],
                    'actas' => [
                        'detalles' => [],
                        'totalDetalles' => 0,
                        'ventaBruta' => 0,
                        'ventaNeta' => 0,
                        'descuento' => 0,
                    ],
                    'notas' => [
                        'detalles' => [],
                        'totalDetalles' => 0,
                        'ventaBruta' => 0,
                        'ventaNeta' => 0,
                        'descuento' => 0,
                    ],
                ],
            ],
        ];

        foreach ($ventas as $venta) {
            // Obtener detalles de la venta
            $detalles = DB::table('f_detalle_venta AS fda')
                ->join('libros_series AS ls', 'ls.codigo_liquidacion', '=', 'fda.pro_codigo')
                ->where('fda.ven_codigo', $venta->ven_codigo)
                ->where('fda.id_empresa', $venta->id_empresa)
                ->select('ls.nombre', 'fda.*')
                ->get();

            $detalles = collect($detalles);

            // Calcular descuento si es necesario
            $descuento = $venta->ven_descuento ?? 0;

            // Definir el tipo de documento
            $tipoDocumento = '';
            switch ($venta->idtipodoc) {
                case 1:
                    $tipoDocumento = 'facturas';
                    break;
                case 2:
                    $tipoDocumento = 'actas';
                    break;
                case 3:
                case 4:
                    $tipoDocumento = 'notas';
                    break;
            }

            // Procesar según la empresa
            if ($venta->id_empresa == 1) {
                $result['datosProlipa']['ventaBruta'] += $venta->ven_subtotal;
                $result['datosProlipa']['ventaNeta'] += $venta->ven_valor;
                $result['datosProlipa']['descuento'] += $descuento;
                $result['datosProlipa']['documentos'][$tipoDocumento]['ventaBruta'] += round($venta->ven_subtotal, 2);
                $result['datosProlipa']['documentos'][$tipoDocumento]['ventaNeta'] += round($venta->ven_valor, 2);
                $result['datosProlipa']['documentos'][$tipoDocumento]['descuento'] += round($descuento, 2);
            } elseif ($venta->id_empresa == 3) {
                $result['datosCalmed']['ventaBruta'] += $venta->ven_subtotal;
                $result['datosCalmed']['ventaNeta'] += $venta->ven_valor;
                $result['datosCalmed']['descuento'] += $descuento;
                $result['datosCalmed']['documentos'][$tipoDocumento]['ventaBruta'] += round($venta->ven_subtotal, 2);
                $result['datosCalmed']['documentos'][$tipoDocumento]['ventaNeta'] += round($venta->ven_valor, 2);
                $result['datosCalmed']['documentos'][$tipoDocumento]['descuento'] += round($descuento, 2);
            }

            // Procesar detalles
            foreach ($detalles as $detalle) {
                $libro = $detalle->nombre;
                $pro_codigo = $detalle->pro_codigo;
                $cantidad = $detalle->det_ven_cantidad;
                $precio_unitario = $detalle->det_ven_valor_u;

                // Sumar totales de Prolipa
                if ($venta->id_empresa == 1) {
                    if (!isset($result['datosProlipa']['documentos'][$tipoDocumento]['detalles'][$pro_codigo])) {
                        $result['datosProlipa']['documentos'][$tipoDocumento]['detalles'][$pro_codigo] = [
                            'libro'             => $libro,
                            'pro_codigo'        => $pro_codigo,
                            'cantidad'          => 0,
                            'precio_unitario'   => $precio_unitario,
                            'total'             => 0,
                        ];
                    }

                    $result['datosProlipa']['documentos'][$tipoDocumento]['detalles'][$pro_codigo]['cantidad'] += $cantidad;
                    $result['datosProlipa']['documentos'][$tipoDocumento]['detalles'][$pro_codigo]['total'] += round($cantidad * $precio_unitario, 2);
                    $result['datosProlipa']['documentos'][$tipoDocumento]['totalDetalles'] += $cantidad;
                    // $result['datosProlipa']['documentos'][$tipoDocumento]['ventaBruta'] += round($cantidad * $precio_unitario, 2);

                // Sumar totales de Calmed
                } elseif ($venta->id_empresa == 3) {
                    if (!isset($result['datosCalmed']['documentos'][$tipoDocumento]['detalles'][$pro_codigo])) {
                        $result['datosCalmed']['documentos'][$tipoDocumento]['detalles'][$pro_codigo] = [
                            'libro'             => $libro,
                            'pro_codigo'        => $pro_codigo,
                            'cantidad'          => 0,
                            'precio_unitario'   => $precio_unitario,
                            'total'             => 0,
                        ];
                    }

                    $result['datosCalmed']['documentos'][$tipoDocumento]['detalles'][$pro_codigo]['cantidad'] += $cantidad;
                    $result['datosCalmed']['documentos'][$tipoDocumento]['detalles'][$pro_codigo]['total'] += round($cantidad * $precio_unitario, 2);
                    $result['datosCalmed']['documentos'][$tipoDocumento]['totalDetalles'] += $cantidad;
                    // $result['datosCalmed']['documentos'][$tipoDocumento]['ventaBruta'] += round($cantidad * $precio_unitario, 2);
                }
            }
        }

        // Redondear totales
        $result['datosProlipa']['ventaBruta'] = round($result['datosProlipa']['ventaBruta'], 2);
        $result['datosProlipa']['ventaNeta']  = round($result['datosProlipa']['ventaNeta'], 2);
        $result['datosProlipa']['descuento']  = round($result['datosProlipa']['descuento'], 2);

        $result['datosCalmed']['ventaBruta']  = round($result['datosCalmed']['ventaBruta'], 2);
        $result['datosCalmed']['ventaNeta']   = round($result['datosCalmed']['ventaNeta'], 2);
        $result['datosCalmed']['descuento']   = round($result['datosCalmed']['descuento'], 2);

        $result['datosProlipa']['documentos']['facturas']['detalles'] = array_values($result['datosProlipa']['documentos']['facturas']['detalles']);
        $result['datosProlipa']['documentos']['actas']['detalles'] = array_values($result['datosProlipa']['documentos']['actas']['detalles']);
        $result['datosProlipa']['documentos']['notas']['detalles'] = array_values($result['datosProlipa']['documentos']['notas']['detalles']);

        $result['datosCalmed']['documentos']['facturas']['detalles'] = array_values($result['datosCalmed']['documentos']['facturas']['detalles']);
        $result['datosCalmed']['documentos']['actas']['detalles'] = array_values($result['datosCalmed']['documentos']['actas']['detalles']);
        $result['datosCalmed']['documentos']['notas']['detalles'] = array_values($result['datosCalmed']['documentos']['notas']['detalles']);

        return $result;
    }

    public function getInfoVendidoFacturadoXyear($request) {
        $year = $request->year;
        $infoVendido = $this->getInfoVendidoXyear($request);
        $infoFacturado = $this->getInfoFacturadoXyear($request);

        foreach ($infoVendido['datosProlipa']['documentos']['facturas']['detalles'] as &$facturaDetalle) {
            $pro_codigo = $facturaDetalle['pro_codigo'];
            $encontrado = false;

            foreach ($infoFacturado['datosProlipa']['detalles'] as $facturadoDetalle) {
                if ($facturadoDetalle['pro_codigo'] == $pro_codigo) {
                    $facturaDetalle['cantidad_facturada'] = $facturadoDetalle['cantidad'] ?? 0;
                    $facturaDetalle['total_pendiente'] = round(($facturaDetalle['cantidad'] - $facturaDetalle['cantidad_facturada']) * $facturaDetalle['precio_unitario'], 2) ?? 0;
                    $encontrado = true;
                    break;
                }
            }

            if (!$encontrado) {
                $facturaDetalle['cantidad_facturada'] = 0;
                $facturaDetalle['total_pendiente'] = round($facturaDetalle['cantidad'] * $facturaDetalle['precio_unitario'], 2);
            }
        }

        foreach (['actas', 'notas'] as $tipoDocumento) {
            foreach ($infoVendido['datosProlipa']['documentos'][$tipoDocumento]['detalles'] as &$detalle) {
                $detalle['cantidad_facturada'] = 0;
                $detalle['total_pendiente'] = 0;
            }
        }

        foreach ($infoVendido['datosCalmed']['documentos']['facturas']['detalles'] as &$facturaDetalle) {
            $pro_codigo = $facturaDetalle['pro_codigo'];
            $encontrado = false;

            foreach ($infoFacturado['datosCalmed']['detalles'] as $facturadoDetalle) {
                if ($facturadoDetalle['pro_codigo'] == $pro_codigo) {
                    $facturaDetalle['cantidad_facturada'] = $facturadoDetalle['cantidad'];
                    $facturaDetalle['total_pendiente'] = round(($facturaDetalle['cantidad'] - $facturaDetalle['cantidad_facturada']) * $facturaDetalle['precio_unitario'], 2);
                    $encontrado = true;
                    break;
                }
            }

            if (!$encontrado) {
                $facturaDetalle['cantidad_facturada'] = 0;
                $facturaDetalle['total_pendiente'] = round($facturaDetalle['cantidad'] * $facturaDetalle['precio_unitario'], 2);
            }
        }

        foreach (['actas', 'notas'] as $tipoDocumento) {
            foreach ($infoVendido['datosCalmed']['documentos'][$tipoDocumento]['detalles'] as &$detalle) {
                $detalle['cantidad_facturada'] = 0;
                $detalle['total_pendiente'] = 0;
            }
        }

        return $infoVendido;
    }

    public function getCobradoXyear($request) {
        $year = $request->year;

        $abonos = DB::table('abono as a')
            ->join('1_1_cuenta_pago as cp', 'cp.cue_pag_codigo', '=', 'a.abono_cuenta')
            ->select('cp.cue_pag_numero', 'cp.cue_pag_nombre', 'a.*')
            ->where('a.abono_estado', 0)
            ->where('a.abono_periodo', '<>',20)
            ->whereYear('a.abono_fecha', $year)
            ->get();

        foreach ($abonos as $key => $value) {
            $institucion = DB::table('f_venta as v')
                ->join('institucion as i', 'i.idInstitucion', '=', 'v.institucion_id')
                ->select('i.idInstitucion', 'i.nombreInstitucion')
                ->where('v.ruc_cliente', $value->abono_ruc_cliente)
                ->first();
                $abonos[$key]->institucion = $institucion->nombreInstitucion;
        }

        return response()->json($abonos);
    }


    //API:GET/pedidos2/pedidos?geAllLibrosxAsesorEscuelas=1&asesor_id=4179&periodo_id=24
    public function geAllLibrosxAsesorEscuelas($asesor_id,$periodo_id){
        //traer las escuelas del asesor
        $query = $this->tr_institucionesAsesorPedidos($periodo_id,$asesor_id);
        //traer los libros por escuela
        foreach($query as $key => $item){
            //request
            $request                 = (Object)[ "escuela_pedido"        => $item->id_institucion ];
            $query[$key]->libros     = $this->tr_getLibrosAsesores($periodo_id,$item->id_asesor,$request);
        }
        return $query;
    }
    //geAllGuiasxAsesor
    //API:GET/pedidos2/pedidos?geAllGuiasxAsesor=1&asesor_id=4179&periodo_id=24
    public function geAllGuiasxAsesor($asesor_id,$periodo_id){
        $request                 = (Object)[ "guiasAsesor"   => $asesor_id ];
        $query                   = $this->tr_getLibrosAsesores($periodo_id,$asesor_id,$request);
        //buscar en la tabla codigoslibros el campo libro_id con el libro_id del array buscar cuantos codigos tiene el asesor_id con asesor_id parametro
        $libros = [];
        $query2 = DB::SELECT("SELECT * FROM codigoslibros c
        WHERE c.asesor_id = ?
        AND c.bc_periodo = ?
        AND c.prueba_diagnostica = '0'
        ",[$asesor_id,$periodo_id]);
        return $query2;
        // foreach($query as $key => $item){

        //     $query[$key]->cantidadBodega = count($query2);
        //     // $libros[$key] = $item->libro_id;
        // }
        // return $query;
    }
    public function getlibrosxPeriodo($asesor_id,$periodo_id,$tipo = null,$parametro1=null)
    {

    }
    //API:GET/pedidos2/pedidos?geAllLibrosxAsesor=yes&asesor_id=4179&periodo_id=22
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
    //API:POST/pedidos2/pedidos
    public function store(Request $request)
    {
        if($request->getValoresLibrosContratos)                         { return $this->getValoresLibrosContratos($request->asesor_id,$request->periodo_id,$request); }
        if($request->getValoresLibrosContratosDespacho)                 { return $this->getValoresLibrosContratosDespacho($request); }
        if($request->getValoresLibrosContratosDespacho_new)             { return $this->getValoresLibrosContratosDespacho_new($request); }
        if($request->getValoresLibrosContratosInstituciones)            { return $this->getValoresLibrosContratosInstituciones($request); }
        if($request->getValoresLibrosContratosInstitucionesAsesor)      { return $this->getValoresLibrosContratosInstitucionesAsesor($request); }
        if($request->getValoresLibrosContratosInstitucionesAsesor_new)  { return $this->getValoresLibrosContratosInstitucionesAsesor_new($request); }
        if($request->crearUsuario)                                      { return $this->crearUsuario($request); }
        if($request->updateClienteInstitucion)                          { return $this->updateClienteInstitucion($request); }
    }
      //API:POST/pedidos2/pedidos?getValoresLibrosContratos
      public function getValoresLibrosContratos($asesor_id,$periodo_id,$request){
        $arrayLibros = [];
        $arrayLibros = json_decode($request->arrayLibros);
        // $arrayLibros = $this->geAllLibrosxAsesor($asesor_id,$periodo_id);
        $query = DB::SELECT("SELECT p.id_pedido, p.contrato_generado,
            i.nombreInstitucion, c.nombre as ciudad
            FROM pedidos p
            LEFT JOIN institucion i ON p.id_institucion = i.idInstitucion
            LEFT JOIN ciudad c ON c.idciudad = i.ciudad_id
            WHERE p.id_asesor = ?
            AND p.estado = '1'
            AND p.tipo = '0'
            AND p.contrato_generado IS NOT NULL
            AND p.id_periodo = ?
        ",[$asesor_id,$periodo_id]);
        $datos = [];
        foreach($query as $key => $item){
            $validate               = [];
            $validate               = $this->obtenerValores($arrayLibros,$item->id_pedido);
            $datos[$key] = [
                'id_pedido'         => $item->id_pedido,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad'            => $item->ciudad,
                'librosFormato'     => $validate,
            ];
        }
         return $datos;
    }
    //API:POST/pedidos2/pedidos?getValoresLibrosContratosDespacho
    public function getValoresLibrosContratosDespacho($request){
        $arrayLibros = [];
        $arrayLibros = json_decode($request->arrayLibros);
        $ca_codigo_agrupado = $request->id_despacho;
        $id_periodo  = $request->id_periodo;
        $query = $this->tr_pedidosXDespacho($ca_codigo_agrupado,$id_periodo);
        $datos = [];
        foreach($query as $key => $item){
            $validate               = [];
            $validate               = $this->obtenerValores($arrayLibros,$item->id_pedido);
            $datos[$key] = [
                'id_pedido'         => $item->id_pedido,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad'            => $item->ciudad,
                'librosFormato'     => $validate,
            ];
        }
         return $datos;
    }
    //API:POST/pedidos2/pedidos?getValoresLibrosContratosInstituciones
    public function getValoresLibrosContratosInstituciones($request){
        $arrayLibros = [];
        $arrayLibros = json_decode($request->arrayLibros);
        $id_periodo  = $request->id_periodo;
        $tipo_venta  = $request->tipo_venta;
        $query = $this->tr_getInstitucionesVentaXTipoVenta($id_periodo,$tipo_venta);
        // $datos = [];
        foreach($query as $key => $item){
            $validate               = [];
            $validate               = $this->obtenerValores($arrayLibros,$item->id_pedido);
            $datos[$key] = [
                'id_pedido'         => $item->id_pedido,
                'id_asesor'         => $item->id_asesor,
                'asesor'            => $item->asesor,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad'            => $item->ciudad,
                'librosFormato'     => $validate,
            ];
        }
         return $datos;
    }
    //API:POST/pedidos2/pedidos?getValoresLibrosContratosInstitucionesAsesor
    public function getValoresLibrosContratosInstitucionesAsesor($request){
        $arrayLibros = [];
        $arrayLibros = json_decode($request->arrayLibros);
        $id_periodo  = $request->id_periodo;
        $tipo_venta  = $request->tipo_venta;
        $id_asesor   = $request->id_asesor;
        $query = $this->tr_getInstitucionesVentaXTipoVentaAsesor($id_periodo,$tipo_venta,$id_asesor);
        $datos = [];
        foreach($query as $key => $item){
            $validate               = [];
            $validate               = $this->obtenerValores($arrayLibros,$item->id_pedido);
            $datos[$key] = [
                'id_pedido'         => $item->id_pedido,
                'id_asesor'         => $item->id_asesor,
                'asesor'            => $item->asesor,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad'            => $item->ciudad,
                'librosFormato'     => $validate,
            ];
        }
         return $datos;
    }
    //API:POST/pedidos2/pedidos/crearUsuario=1
    public function crearUsuario($request){
        $password                           = sha1(md5($request->cedula));
        $email                              = $request->email;
        //si el email es nulo o vacio guardar la cedula en el email
        if($email == null || $email == ""){
            $email = $request->cedula;
        }
        $user                               = new User();
        $user->cedula                       = $request->cedula;
        $user->nombres                      = $request->nombres;
        $user->apellidos                    = $request->apellidos;
        $user->name_usuario                 = $email;
        $user->password                     = $password;
        $user->email                        = $email;
        $user->id_group                     = $request->id_grupo;
        $user->institucion_idInstitucion    = $request->institucion;
        $user->estado_idEstado              = 1;
        $user->idcreadorusuario             = $request->user_created;
        $user->telefono                     = $request->telefono;
        $user->save();
        $query = DB::SELECT("SELECT u.idusuario,u.cedula,u.nombres,u.apellidos,u.email,u.telefono,
          CONCAT_WS(' ', u.nombres, u.apellidos) AS usuario
        FROM usuario u
        WHERE u.cedula = '$request->cedula'
        ");
        return $query;
    }
    //API:POST/pedidos2/pedidos/updateClienteInstitucion=1
    public function updateClienteInstitucion($request){
        $institucion                                = Institucion::findOrFail($request->id_institucion);
        $empresa                                    = $request->empresa;
        if($empresa == 1){
            $institucion->idrepresentante_prolipa   = $request->idusuario;
            $institucion->cliente_perseo_prolipa    = $request->clientePerseo;
        }
        if($empresa == 3){
            $institucion->idrepresentante_calmed    = $request->idusuario;
            $institucion->cliente_perseo_calmed     = $request->clientePerseo;
        }
        $institucion->save();
        if($institucion){
            return ["status" => "1", "message" => "Se actualizó correctamente"];
        }else{
            return ["status" => "0", "message" => "No se pudo actualizar"];
        }
    }
    //api:get/pedidosDespacho?ca_codigo_agrupado=1265&id_periodo=23
    public function pedidosDespacho(Request $request){
        $ca_codigo_agrupado = $request->id_despacho;
        $id_periodo  = $request->id_periodo;
        $arrayLibros = [];
        //get id_pedidos de despacho
        $query       = $this->tr_pedidosXDespacho($ca_codigo_agrupado,$id_periodo);
        if(empty($query)){
            return ["status" => "0", "message" => "No se encontraron pedidos en el despacho"];
        }
        $arrayIds    = [];
        //guardar en un array los id de pedidos
        foreach($query as $key => $item){
            if($item->contrato_generado!=null){
                $arrayIds[] = (String)$item->id_pedido;
            }
         }
        if(empty($arrayIds)){
            //response codigo 404
            return ["status" => "0", "message" => "No se encontraron pedidos para este despacho"];
        }
        //convierte en string el array
        $arrayIds = implode(",",$arrayIds);
        $arrayLibros = $this->geAllLibrosxAsesor(0,$id_periodo,1,$arrayIds);
        $ca_codigo_agrupado = $request->id_despacho;
        $id_periodo  = $request->id_periodo;
        $query = $this->tr_pedidosXDespacho($ca_codigo_agrupado,$id_periodo);
        $datos = [];
        foreach($query as $key => $item){
            $validate               = [];
            if($item->contrato_generado!=null){
                $validate               = $this->obtenerValores($arrayLibros,$item->id_pedido);
            }
            $datos[$key] = [
                'id_pedido'         => $item->id_pedido,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad'            => $item->ciudad,
                'librosFormato'     => $validate,
            ];
        }
        //del arreglo datos voy a recorrer el arrayLibros y sumar la propiedad valor que esta en librosFormato
        $resultado = [];
        foreach($arrayLibros as $key => $item){
            //del array de datos en la propiedad librosFormato voy a filtrar por el libro_id y sumar el valor
            //y sumar el valor
            $total = 0;
            foreach($datos as $k => $tr){
                $libro = collect($tr['librosFormato'])->where('libro_id',$item->libro_id)->first();
                if($libro){
                    $total += $libro['valor'];

                }
            }
            $resultado[$key] = [
                "nombrelibro"        => $item->nombrelibro,
                "nombre_serie"       => $item->nombre_serie,
                "precio"             => $item->precio,
                "codigo_liquidacion" => $item->codigo,
                "libro_id"           => $item->libro_id,
                "valor"              => $total,
                "stock"              => $item->stock,
                "cantidad"           => 0,
                "id_serie"           => $item->id_serie,
                "descripcion"        => $item->descripcion,
            ];
        }
        return $resultado;
    }
    public function obtenerValores($arrayLibros,$id_pedido){
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
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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

    //INICIO METODOS JEYSON
    public function getLibrosFormato_new($periodo){
        $librosNormales = [];
        $librosPlan     = [];
        $resultado      = [];
        $librosNormales = $this->pedidosRepository->getLibrosNormalesFormato_new($periodo);
        $librosPlan     = $this->pedidosRepository->getLibrosPlanLectorFormato_new($periodo);
        //unir los dos arreglos
        $resultado      = array_merge(array($librosNormales),array($librosPlan));
        $coleccion      = collect($resultado)->flatten(10);
        return $coleccion;
    }

    public function getLibrosXDespacho_new($request){
        $query = $this->geAllLibrosxAsesor_new(0,$request->id_periodo,1,$request->id_pedidos);
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

    //API:POST/pedidos2/pedidos?getValoresLibrosContratosDespacho_new
    public function getValoresLibrosContratosDespacho_new($request){
        $arrayLibros = [];
        $arrayLibros = json_decode($request->arrayLibros);
        $ca_codigo_agrupado = $request->id_despacho;
        $id_periodo  = $request->id_periodo;
        $query = $this->tr_pedidosXDespacho($ca_codigo_agrupado,$id_periodo);
        $datos = [];
        foreach($query as $key => $item){
            $validate               = [];
            $validate               = $this->obtenerValores_new($arrayLibros,$item->id_pedido);
            $datos[$key] = [
                'id_pedido'         => $item->id_pedido,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad'            => $item->ciudad,
                'librosFormato'     => $validate,
            ];
        }
         return $datos;
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

    //api:get/pedidosDespacho_new?ca_codigo_agrupado=1265&id_periodo=23
    public function pedidosDespacho_new(Request $request){
        $ca_codigo_agrupado = $request->id_despacho;
        $id_periodo  = $request->id_periodo;
        $arrayLibros = [];
        //get id_pedidos de despacho
        $query       = $this->tr_pedidosXDespacho($ca_codigo_agrupado,$id_periodo);
        // return $query;
        if(empty($query)){
            return ["status" => "0", "message" => "No se encontraron pedidos en el despacho"];
        }
        $arrayIds    = [];
        //guardar en un array los id de pedidos
        foreach($query as $key => $item){
            if($item->contrato_generado!=null){
                $arrayIds[] = (String)$item->id_pedido;
            }
         }
        if(empty($arrayIds)){
            //response codigo 404
            return ["status" => "0", "message" => "No se encontraron pedidos para este despacho"];
        }
        //convierte en string el array
        $arrayIds = implode(",",$arrayIds);
        $arrayLibros = $this->geAllLibrosxAsesor_new(0,$id_periodo,1,$arrayIds);
        $ca_codigo_agrupado = $request->id_despacho;
        $id_periodo  = $request->id_periodo;
        $query = $this->tr_pedidosXDespacho($ca_codigo_agrupado,$id_periodo);
        $datos = [];
        foreach($query as $key => $item){
            $validate               = [];
            if($item->contrato_generado!=null){
                $validate               = $this->obtenerValores_new($arrayLibros,$item->id_pedido);
            }
            $datos[$key] = [
                'id_pedido'         => $item->id_pedido,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad'            => $item->ciudad,
                'librosFormato'     => $validate,
            ];
        }
        //del arreglo datos voy a recorrer el arrayLibros y sumar la propiedad valor que esta en librosFormato
        $resultado = [];
        foreach($arrayLibros as $key => $item){
            //del array de datos en la propiedad librosFormato voy a filtrar por el libro_id y sumar el valor
            //y sumar el valor
            $total = 0;
            foreach($datos as $k => $tr){
                $libro = collect($tr['librosFormato'])->where('libro_id',$item->libro_id)->first();
                if($libro){
                    $total += $libro['valor'];

                }
            }
            $resultado[$key] = [
                "nombrelibro"        => $item->nombrelibro,
                "nombre_serie"       => $item->nombre_serie,
                "precio"             => $item->precio,
                "codigo_liquidacion" => $item->codigo,
                "libro_id"           => $item->libro_id,
                "valor"              => $total,
                "stock"              => $item->stock,
                "cantidad"           => 0,
                "id_serie"           => $item->id_serie,
                "descripcion"        => $item->descripcion,
            ];
        }
        return $resultado;
    }

    //API:GET/pedidos2/pedidos?getLibrosXInstitucionesAsesor_new=yes&id_periodo=23&tipo_venta=1&id_asesor=1
    public function getLibrosXInstitucionesAsesor_new($id_periodo,$tipo_venta,$asesor){
        $query = $this->tr_getInstitucionesVentaXTipoVentaAsesor($id_periodo,$tipo_venta,$asesor);
        $id_pedidos = "";
        //crear un coleccion y pluck de id_pedido
        $id_pedidos = collect($query)->pluck('id_pedido');
        //convertir en string
        $id_pedidos = implode(",",$id_pedidos->toArray());
        $query = $this->geAllLibrosxAsesor_new(0,$id_periodo,1,$id_pedidos);
        return $query;
    }

    //API:POST/pedidos2/pedidos?getValoresLibrosContratosInstitucionesAsesor_new
    public function getValoresLibrosContratosInstitucionesAsesor_new($request){
        $arrayLibros = [];
        $arrayLibros = json_decode($request->arrayLibros);
        $id_periodo  = $request->id_periodo;
        $tipo_venta  = $request->tipo_venta;
        $id_asesor   = $request->id_asesor;
        $query = $this->tr_getInstitucionesVentaXTipoVentaAsesor($id_periodo,$tipo_venta,$id_asesor);
        $datos = [];
        foreach($query as $key => $item){
            $validate               = [];
            $validate               = $this->obtenerValores_new($arrayLibros,$item->id_pedido);
            $datos[$key] = [
                'id_pedido'         => $item->id_pedido,
                'id_asesor'         => $item->id_asesor,
                'asesor'            => $item->asesor,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad'            => $item->ciudad,
                'librosFormato'     => $validate,
            ];
        }
         return $datos;
    }

    //API:GET/pedidos2/pedidos?getReporteFacturacionAsesores_new=1&periodo_id=25
    public function getReporteFacturacionAsesores_new($request){


        $periodo_id = $request->periodo_id;
        $query      = $this->tr_getAsesoresFacturacionXPeriodo($periodo_id);

        foreach($query as $key => $item){
            $getInstituciones       = $this->tr_InstitucionesDespachadosFacturacionAsesor($periodo_id,$item->asesor_id);
            // Convertir a colección en caso de que no lo sea
            $getInstituciones       = collect($getInstituciones);
            // $item->instituciones    = $getInstituciones;
            // Usar pluck para obtener solo los institucion_id
            $getInstitucionesId     = $getInstituciones->pluck('institucion_id');
            // $item->institucionesId  = $getInstitucionesId;
            //====libros====
            //prolipa
            $datosRequest = (Object)[
                "periodo"               => $periodo_id,
                "empresa"               => 1,
                "variasInstituciones"   => 1,
                "getInstitucionesId"    => $getInstitucionesId
            ];
            $getLibrosProlipa           = $this->tr_metodoFacturacion_new($datosRequest);
            //calmed
            $datosRequest = (Object)[
                "periodo"               => $periodo_id,
                "empresa"               => 3,
                "variasInstituciones"   => 1,
                "getInstitucionesId"    => $getInstitucionesId
            ];
            $getLibrosCalmed            = $this->tr_metodoFacturacion_new($datosRequest);
            $item->librosProlipa        = $getLibrosProlipa;
            $item->librosCalmed         = $getLibrosCalmed;
            //sumar cantidad de prolipa y calmed
            $item->totalLibrosProlipa    = collect($item->librosProlipa)->sum('cantidad');
            $item->totalLibrosCalmed     = collect($item->librosCalmed)->sum('cantidad');
            //sumar precio_total
            $item->totalPrecioProlipa    = collect($item->librosProlipa)->sum('precio_total');
            $item->totalPrecioCalmed     = collect($item->librosCalmed)->sum('precio_total');
        }
        return $query;

    }

    public function getReporteFacturadoXAsesores_new($request){
        $periodo_id = $request->periodo_id;
        $query      = $this->tr_getAsesoresFacturadoXPeriodo($periodo_id);

        foreach($query as $key => $item){
            $getInstituciones       = $this->tr_InstitucionesDespachadosFacturadoAsesor($periodo_id,$item->asesor_id);
            // Convertir a colección en caso de que no lo sea
            $getInstituciones       = collect($getInstituciones);
            // $item->instituciones    = $getInstituciones;
            // Usar pluck para obtener solo los institucion_id
            $getInstitucionesId     = $getInstituciones->pluck('institucion_id');
            // $item->institucionesId  = $getInstitucionesId;
            //====libros====
            //prolipa
            $datosRequest = (Object)[
                "periodo"               => $periodo_id,
                "empresa"               => 1,
                "variasInstituciones"   => 1,
                "getInstitucionesId"    => $getInstitucionesId,
                "tipo"                  => $request->tipo
            ];
            $getLibrosProlipa           = $this->tr_metodoFacturado_new($datosRequest);
            //calmed
            $datosRequest = (Object)[
                "periodo"               => $periodo_id,
                "empresa"               => 3,
                "variasInstituciones"   => 1,
                "getInstitucionesId"    => $getInstitucionesId,
                "tipo"                  => $request->tipo
            ];
            $getLibrosCalmed            = $this->tr_metodoFacturado_new($datosRequest);
            $item->librosProlipa        = $getLibrosProlipa;
            $item->librosCalmed         = $getLibrosCalmed;
            //sumar cantidad de prolipa y calmed
            $item->totalLibrosProlipa    = collect($item->librosProlipa)->sum('cantidad');
            $item->totalLibrosCalmed     = collect($item->librosCalmed)->sum('cantidad');
            //sumar precio_total
            $item->totalPrecioProlipa    = collect($item->librosProlipa)->sum('precio_total');
            $item->totalPrecioCalmed     = collect($item->librosCalmed)->sum('precio_total');
        }
        return $query;

    }

    public function Get_Estado_Venta(){
        $query = DB::SELECT("SELECT * FROM `1_4_estado_venta` WHERE est_ven_codigo NOT IN (5, 6, 7, 8, 9, 11, 13, 14, 15)");
        return $query;
    }
    //FIN METODOS JEYSON

}
