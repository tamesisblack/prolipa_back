<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\_14Producto;
use App\Models\CodigosLibros;
use App\Models\CodigosLibrosDevolucionHeader;
use App\Models\CodigosLibrosDevolucionHeaderFacturador;
use App\Models\CodigosLibrosDevolucionSon;
use App\Models\CodigosLibrosDevolucionSonFacturador;
use App\Models\DetalleVentas;
use App\Models\f_tipo_documento;
use App\Models\LibroSerie;
use App\Models\Periodo;
use App\Models\Ventas;
use App\Repositories\Codigos\CodigosRepository;
use App\Repositories\Facturacion\DevolucionRepository;
use App\Repositories\Facturacion\ProformaRepository;
use App\Repositories\pedidos\PedidosRepository;
use App\Traits\Codigos\TraitCodigosGeneral;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class DevolucionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    use TraitCodigosGeneral;
    protected $proformaRepository;
    protected $pedidosRepository;
    protected $devolucionRepository;
    protected $codigosRepository;
    public function __construct(ProformaRepository $proformaRepository, PedidosRepository $pedidosRepository , DevolucionRepository  $devolucionRepository, CodigosRepository $codigosRepository)
    {
        $this->proformaRepository    = $proformaRepository;
        $this->pedidosRepository     = $pedidosRepository;
        $this->devolucionRepository  = $devolucionRepository;
        $this->codigosRepository     = $codigosRepository;
    }
    //API:GET/devoluciones
    public function index(Request $request)
    {
        if($request->listadoProformasAgrupadas)             { return $this->listadoProformasAgrupadas($request); }
        if($request->filtroDocumentosDevueltos)             { return $this->filtroDocumentosDevueltos($request); }
        if($request->getCodigosDevolucionTodos)             { return $this->getCodigosDevolucionTodos($request); }
        if($request->getCodigosxDocumentoDevolucion)        { return $this->getCodigosxDocumentoDevolucion($request); }
        if($request->historicoDevolucionPreFacturasClientes){ return $this->historicoDevolucionPreFacturasClientes($request); }
        if($request->historicoDevolucionPreFacturas)        { return $this->historicoDevolucionPreFacturas($request); }
        if($request->getDocumentosDevolucion)               { return $this->getDocumentosDevolucion($request); }
        if($request->getDevolucionSon)                      { return $this->getDevolucionSon($request); }
        if($request->getDocumentosFinalizados)              { return $this->getDocumentosFinalizados($request); }
        if($request->getDetalleVentaXPrefactura)            { return $this->getDetalleVentaXPrefactura($request); }
        if($request->getCodigosCombosDocumentoDevolucion)   { return $this->getCodigosCombosDocumentoDevolucion($request); }
        if($request->generateCombos)                        { return $this->generateCombos($request); }
       if($request->todoDevolucionCliente)                  { return $this->todoDevolucionCliente($request); }
       if($request->devolucionDetalle)                      { return $this->devolucionDetalle($request); }
       if($request->CargarDevolucion)                       { return $this->CargarDevolucion($request); }
       if($request->CargarDocumentos)                       { return $this->CargarDocumentos($request); }
       if($request->CargarDocumentosDetalles)               { return $this->CargarDocumentosDetalles($request); }
       if($request->CargarDocumentosDetallesGuias)          { return $this->CargarDocumentosDetallesGuias($request); }
       if($request->CargarDetallesDocumentos)               { return $this->CargarDetallesDocumentos($request); }
       if($request->documentoExiste)                        { return $this->verificarDocumento($request); }
        if($request->documentoParaSinEmpresa)               { return $this->documentoParaSinEmpresa($request); }
        if($request->InstitucionesCambio)               { return $this->InstitucionesCambio($request); }


    }
    //api:get/devoluciones?listadoProformasAgrupadas=1&institucion=1620
    public function listadoProformasAgrupadas(Request $request)
    {
        $institucion                = $request->input('institucion');
        $getProformas               = $this->proformaRepository->listadoProformasAgrupadas($institucion);
        if(empty($getProformas))    { return []; }
        foreach($getProformas as $key => $item){
            $query = DB::SELECT("SELECT * FROM f_venta_agrupado v
            WHERE v.id_factura = ?
            AND v.estadoPerseo = '1'
            AND v.id_empresa = ?
            ",[$item->id_factura,$item->id_empresa]);
            if(count($query) > 0){
                $getProformas[$key]->ifPedidoPerseo = 1;
            }else{
                $getProformas[$key]->ifPedidoPerseo = 0;
            }
            //cantidad de combos
            $query = $this->devolucionRepository->detallePrefactura($item->ven_codigo,$item->id_empresa,$item->institucion_id,1);
            if(count($query) > 0){
                $getProformas[$key]->combos = count($query);
            }else{
                $getProformas[$key]->combos = 0;
            }
        }
        $resultado = collect($getProformas);
        //filtrar por ifPedidoPerseo igual a 0
        $resultado = $resultado->where('ifPedidoPerseo','0')->values();
        return $resultado;
    }
    //api:get/devoluciones?getCodigosDevolucionTodos=1&idDevolucion=41
    public function getCodigosDevolucionTodos($request)
    {
        $idDevolucion = $request->input('idDevolucion');
        $getCodigosDevolucionTodos = CodigosLibrosDevolucionSon::query()
        ->leftJoin('libros_series as ls', 'codigoslibros_devolucion_son.pro_codigo', '=', 'ls.codigo_liquidacion')
        ->where('codigoslibros_devolucion_id',$idDevolucion)
        ->select('codigoslibros_devolucion_son.codigo','codigoslibros_devolucion_son.combo','codigoslibros_devolucion_son.codigo_combo',
        'codigoslibros_devolucion_son.pro_codigo','codigoslibros_devolucion_son.factura','codigoslibros_devolucion_son.documento',
        'codigoslibros_devolucion_son.id_empresa','codigoslibros_devolucion_son.estado','ls.nombre as nombrelibro')
        ->get();
        //si es id_empresa null colocar sin empresa si es 1 colocar Prolipa si es 3 Calmed
        foreach ($getCodigosDevolucionTodos as $key => $value) {
            if($value->id_empresa == null){
                $getCodigosDevolucionTodos[$key]->descripcion_empresa= 'Sin empresa';
            }elseif($value->id_empresa == 1){
                $getCodigosDevolucionTodos[$key]->descripcion_empresa= 'Prolipa';
            }elseif($value->id_empresa == 3){
                $getCodigosDevolucionTodos[$key]->descripcion_empresa= 'Calmed';
            }
            //validar estado si es 0 es creado, 1 es revisado, 2 es finalizado
            if($value->estado == 0){
                $getCodigosDevolucionTodos[$key]->estadoCodigoDoc = 'Creado';
            }elseif($value->estado == 1){
                $getCodigosDevolucionTodos[$key]->estadoCodigoDoc = 'Revisado';
            }elseif($value->estado == 2){
                $getCodigosDevolucionTodos[$key]->estadoCodigoDoc = 'Finalizado';
            }
        }
        return $getCodigosDevolucionTodos;
    }
    //api:get/devoluciones?filtroDocumentosDevueltos=1&fechaInicio=2024-10-01&fechaFin=2024-10-06
    public function filtroDocumentosDevueltos(Request $request)
    {
        $fechaInicio    = $request->input('fechaInicio');
        $fechaFin       = $request->input('fechaFin');
        $id_cliente     = $request->input('id_cliente');
        $revisados      = $request->input('revisados');
        $finalizados    = $request->input('finalizados');
        $documentos     = $request->input('documentos');

        if ($fechaFin) {
            $fechaFin = date('Y-m-d', strtotime($fechaFin)) . ' 23:59:59';
        }

        $getDocumentos = CodigosLibrosDevolucionHeader::with([
            'institucion:idInstitucion,nombreInstitucion',
            'usuario:idusuario,nombres,apellidos',
            'usuarioRevision:idusuario,nombres,apellidos',
            'usuarioFinalizacion:idusuario,nombres,apellidos',
            'usuarioIntercambio:idusuario,nombres,apellidos',
            'periodo',
            'devolucionSon' => function ($query) {
                $query->where('prueba_diagnostico', '0')
                ->where('tipo_codigo', '=', '0');
            },
            'devolucionSonSinEmpresa' => function ($query) {
                $query->whereNull('id_empresa');
            }
        ])
        ->when($fechaInicio && $fechaFin, function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->orderBy('created_at', 'desc');
        })
        ->when($id_cliente, function ($query) use ($id_cliente) {
            $query->where('id_cliente', $id_cliente)
            ->orderBy('created_at', 'desc');
        })
        ->when($revisados, function ($query) use ($revisados) {
            $query->where('estado', 1)
            ->orderBy('created_at', 'desc');
        })
        ->when($finalizados, function ($query) use ($finalizados) {
            $query->where('estado', 2)
            ->orderBy('updated_at', 'desc');
        })
        ->when($documentos, function ($query) use ($documentos) {
            $query->where('codigo_devolucion', 'like', '%' . $documentos . '%');
        })
        ->get()
        ->map(function ($documento) {
            $hijos = null;
            $cantidadCreados        = 0;
            $cantidadSonRevisados   = 0;
            $cantidadSonFinalizados = 0;
            $cantidadCombos         = 0;
            //obtener codigoslibros_devolucion_son creados , revisados y finalizados la cantidad
            $hijos = DB::SELECT("
                SELECT
                    COUNT(CASE WHEN codigoslibros_devolucion_son.estado = 0 THEN 1 END) AS cantidadCreados,
                    COUNT(CASE WHEN codigoslibros_devolucion_son.estado = 1 THEN 1 END) AS cantidadSonRevisados,
                    COUNT(CASE WHEN codigoslibros_devolucion_son.estado = 2 THEN 1 END) AS cantidadSonFinalizados,

                    COUNT(CASE WHEN codigoslibros_devolucion_son.combo IS NOT NULL THEN 1 END) AS cantidadCombos
                FROM codigoslibros_devolucion_son
                WHERE codigoslibros_devolucion_id = ?
                AND codigoslibros_devolucion_son.estado IN (0, 1, 2)
                AND codigoslibros_devolucion_son.tipo_codigo = '0'
            ", [$documento->id]);

            if (!empty($hijos) && isset($hijos[0])) {
                $cantidadCreados             = $hijos[0]->cantidadCreados;
                $cantidadSonRevisados        = $hijos[0]->cantidadSonRevisados;
                $cantidadSonFinalizados      = $hijos[0]->cantidadSonFinalizados;
                $cantidadCombos              = $hijos[0]->cantidadCombos;
            } else {
                // Valores predeterminados si no hay resultados
                $cantidadCreados            = 0;
                $cantidadSonRevisados       = 0;
                $cantidadSonFinalizados     = 0;
                $cantidadCombos             = 0;
            }
            return [
                'id'                        => $documento->id,
                'id_cliente'                => $documento->id_cliente,
                'periodo_id'                => $documento->periodo_id,
                'codigo_devolucion'         => $documento->codigo_devolucion,
                'observacion'               => $documento->observacion,
                'codigo_nota_credito'       => $documento->codigo_nota_credito,
                'created_at'                => $documento->created_at_formatted,
                'estado'                    => $documento->estado,
                'fecha_revisado'            => $documento->fecha_revisado,
                'fecha_finalizacion'        => $documento->fecha_finalizacion,
                'institucion'               => $documento->institucion,
                'cantidadCajas'             => $documento->cantidadCajas,
                'cantidadPaquetes'          => $documento->cantidadPaquetes,
                'tipo_importacion'          => $documento->tipo_importacion,
                'usuario'                   => [
                    'nombres'               => $documento->usuario->nombres ?? null,
                    'apellidos'             => $documento->usuario->apellidos ?? null
                ],
                'usuario_revision'          => [
                    'nombres'               => $documento->usuarioRevision->nombres ?? null,
                    'apellidos'             => $documento->usuarioRevision->apellidos ?? null
                ],
                'usuario_finalizacion'      => [
                    'nombres'               => $documento->usuarioFinalizacion->nombres ?? null,
                    'apellidos'             => $documento->usuarioFinalizacion->apellidos ?? null
                ],
                'cantidadCreados'           => $cantidadCreados,
                'cantidadSonRevisados'      => $cantidadSonRevisados,
                'cantidadSonFinalizados'    => $cantidadSonFinalizados,
                'cantidadHijos'             => count($documento->devolucionSon),
                'cantidadHijosSInempresa'   => count($documento->devolucionSonSinEmpresa),
                'periodo'                   => $documento->periodo->periodoescolar,
                'cantidadCombos'            => $cantidadCombos,
                'combo_estado'              => $documento->combo_estado,
                'fecha_intercambio_cliente'         => $documento->fecha_intercambio_cliente,
                'user_intercambio_cliente'          => $documento->user_intercambio_cliente,
                'intercambio_cliente'               => $documento->intercambio_cliente,
                'observacion_intercambio_cliente'   => $documento->observacion_intercambio_cliente,
                'usuario_intercambio'      => [
                    'nombres'               => $documento->usuarioIntercambio->nombres ?? null,
                    'apellidos'             => $documento->usuarioIntercambio->apellidos ?? null
                ],
            ];
        });

        return $getDocumentos;
    }

    //api:get/devoluciones?getCodigosxDocumentoDevolucion=1&id_documento=3
    public function getCodigosxDocumentoDevolucion(Request $request)
    {
        $id_documento = $request->input('id_documento');
        $revisados    = $request->input('revisados');
        $finalizados  = $request->input('finalizados');
        $agrupar      = $request->input('agrupar');
        $porcliente   = $request->input('porcliente');
        $getCodigos = CodigosLibrosDevolucionSon::query()
        ->leftJoin('codigoslibros', 'codigoslibros.codigo', '=', 'codigoslibros_devolucion_son.codigo')
        ->leftJoin('libro', 'libro.idlibro', '=', 'codigoslibros_devolucion_son.id_libro')
        ->leftJoin('libros_series', 'libros_series.idLibro', '=', 'libro.idlibro')
        ->leftJoin('empresas', 'empresas.id', '=', 'codigoslibros_devolucion_son.id_empresa')
        ->leftJoin('institucion', 'institucion.idInstitucion', '=', 'codigoslibros_devolucion_son.id_cliente')
        ->leftJoin('codigoslibros_devolucion_header', 'codigoslibros_devolucion_header.id', '=', 'codigoslibros_devolucion_son.codigoslibros_devolucion_id')
        ->leftJoin('periodoescolar', 'periodoescolar.idperiodoescolar', '=', 'codigoslibros_devolucion_son.id_periodo')
        ->leftJoin('1_4_cal_producto','1_4_cal_producto.pro_codigo','codigoslibros_devolucion_son.pro_codigo')
        // Aquí añadimos el join con f_venta
        ->leftJoin('f_venta', function($join) {
            $join->on('f_venta.id_empresa', '=', 'codigoslibros_devolucion_son.id_empresa')
                ->on('f_venta.ven_codigo', '=', 'codigoslibros_devolucion_son.documento');
        })
        ->where('codigoslibros_devolucion_id', $id_documento)
        ->where('codigoslibros_devolucion_son.prueba_diagnostico', '0')
        ->when($revisados, function ($query) use ($revisados) {
            $query->where('codigoslibros_devolucion_son.estado', 1);
        })
        ->when($finalizados, function ($query) use ($finalizados) {
            $query->where('codigoslibros_devolucion_son.estado', 2);
        })
        ->select(
            'codigoslibros_devolucion_son.*',
            'codigoslibros.estado_liquidacion',
            'codigoslibros.liquidado_regalado',
            'codigoslibros.estado as estadoActualCodigo',
            'codigoslibros.documento_devolucion',
            // 'libro.nombrelibro',
            'libros_series.codigo_liquidacion',
            'empresas.descripcion_corta',
            'institucion.nombreInstitucion',
            'institucion.idInstitucion as id_cliente',
            'periodoescolar.periodoescolar',
            //codigoslibros_devolucion_header
            'codigoslibros_devolucion_header.codigo_devolucion',
            // Agrega aquí los campos de f_venta que necesites
            'f_venta.ven_desc_por',
            '1_4_cal_producto.codigos_combos',
            Db::raw('CONCAT(libro.nombrelibro,
                      CASE
                          WHEN 1_4_cal_producto.codigos_combos IS NOT NULL AND 1_4_cal_producto.codigos_combos != ""
                          THEN CONCAT(" ( ", 1_4_cal_producto.codigos_combos , " )")
                          ELSE ""
                      END) as nombrelibro')
        )

        ->get();


        if ($agrupar == 1) {
            // Agrupar por nombre libro y contar cuántas veces se repite
            $resultado = collect($getCodigos)->groupBy('nombrelibro')->map(function ($item) {
                return [
                    'nombrelibro' => $item[0]->nombrelibro,
                    'codigo'      => $item[0]->codigo_liquidacion,
                    'cantidad'    => count($item),
                ];
            })->values();
        } else {
            $resultado = $getCodigos;
            if($request->sinCombos){
                //filtrar tipo_codigo = 0
                $resultado = collect($resultado)->filter(function ($item) {
                    return $item->tipo_codigo == 0;
                })->values();
            }

            if($porcliente){
                //Agrupar por cliente, usando "Sin cliente" para id_cliente 0 o null
                $agrupados = $resultado->groupBy(function ($item) {
                    return $item->id_cliente ?: 'sin_cliente';  // 'sin_cliente' como clave para id_cliente 0 o null
                })
                ->map(function ($items, $key) {
                    return [
                        'id_cliente'       => $key == 'sin_cliente' ? 0 : $key,
                        'nombreInstitucion' => $key == 'sin_cliente' ? 'Sin cliente' : $items[0]->nombreInstitucion,
                        'data'             => $items
                    ];
                })->values();
                $resultado = $agrupados;
            }
        }

        return $resultado;
    }
    //api:get/devoluciones?historicoDevolucionPreFacturasClientes=yes
    public function historicoDevolucionPreFacturasClientes(Request $request)
    {
        $query = DB::SELECT("SELECT DISTINCT s.id_cliente,
        i.nombreInstitucion
        FROM codigoslibros_devolucion_son s
        LEFT JOIN empresas e ON s.id_empresa = e.id
        LEFT JOIN codigoslibros_devolucion_header ch ON s.codigoslibros_devolucion_id = ch.id
        LEFT JOIN periodoescolar p ON ch.periodo_id = p.idperiodoescolar
        LEFT JOIN institucion i ON ch.id_cliente = i.idInstitucion
        WHERE s.estado <> '0'
        AND s.prueba_diagnostico = '0'
        ORDER BY i.nombreInstitucion ASC
        ");
        return $query;
    }
    //api:get/devoluciones?historicoDevolucionPreFacturas=yes&id_cliente=1
    public function historicoDevolucionPreFacturas(Request $request)
    {
        $id_cliente = $request->input('id_cliente');
        $query = DB::SELECT("SELECT s.*, e.descripcion_corta,
        i.nombreInstitucion, p.periodoescolar,
        ch.observacion,ch.fecha_revisado,ch.codigo_devolucion
        FROM codigoslibros_devolucion_son s
        LEFT JOIN empresas e ON s.id_empresa = e.id
        LEFT JOIN codigoslibros_devolucion_header ch ON s.codigoslibros_devolucion_id = ch.id
        LEFT JOIN periodoescolar p ON ch.periodo_id = p.idperiodoescolar
        LEFT JOIN institucion i ON ch.id_cliente = i.idInstitucion
        WHERE s.estado <> '0'
        AND s.prueba_diagnostico = '0'
        AND s.id_cliente = ?
        ORDER BY s.id desc
        ",[$id_cliente]);
        return $query;
    }
    //api:get/devoluciones?getDocumentosDevolucion=1&creadas=1
    public function getDocumentosDevolucion(Request $request)
    {
        $creadas = $request->input('creadas');
        $results = DB::table('codigoslibros_devolucion_header as h')
            ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'h.id_cliente')
            ->leftJoin('periodoescolar as p','p.idperiodoescolar','=','h.periodo_id')
            ->select(
                'h.*',
                'i.nombreInstitucion',
                'p.region_idregion as region',
                DB::raw("CONCAT(h.codigo_devolucion, ' - ', i.nombreInstitucion) AS documento_cliente")
            )
            ->when($creadas, function ($query) {
                $query->where('h.estado', '0');
            })
            ->get();

        return $results;
    }
    //api:get/devoluciones?getDevolucionSon=1&id_cliente=1&id_periodo=25
    public function getDevolucionSon($request)
    {
        $query = $this->devolucionRepository->devolucionCliente($request->id_cliente,$request->id_periodo);
        return $query;
    }
    //api:get/devoluciones?getDocumentosFinalizados=1&idDocumentoPadre=40
    public function getDocumentosFinalizados($request){
        $idDocumentoPadre = $request->idDocumentoPadre;
        $query = DB::SELECT("SELECT * FROM codigoslibros_devolucion_header_facturador hh
            WHERE hh.codigoslibros_devolucion_header_id = ?
        ",[$idDocumentoPadre]);
        return $query;
    }
    //api:get/devoluciones?getDetalleVentaXPrefactura=1&ven_codigo=PF-P-S24-FR-0000369&empresa=1&institucion=1631
    public function getDetalleVentaXPrefactura($request){
        $query = $this->devolucionRepository->detallePrefactura($request->ven_codigo,$request->empresa,$request->institucion);
        return $query;
    }
    //api:get/devoluciones?getCodigosCombosDocumentoDevolucion=1&id_documento=3
    public function getCodigosCombosDocumentoDevolucion($request){
        $id_documento   = $request->input('id_documento');
        $query          = $this->devolucionRepository->getCodigosCombosDocumentoDevolucion($id_documento);
        foreach ($query as $key => $item) {
            $det_ven_cantidad   = 0;
            $det_ven_dev        = 0;
            $detalle = DB::table('f_detalle_venta')
            ->where('ven_codigo', $item->documento)
            ->where('id_empresa', $item->id_empresa)
            ->where('pro_codigo', $item->pro_codigo)
            ->first();
            if($detalle){
                $det_ven_cantidad = $detalle->det_ven_cantidad;
                $det_ven_dev      = $detalle->det_ven_dev;
            }
            $query[$key]->det_ven_cantidad = $det_ven_cantidad;
            $query[$key]->det_ven_dev      = $det_ven_dev;
        }
        return $query;
    }
    //api:get/devoluciones?generateCombos=1&id_devolucion=119
    public function generateCombos($request)
    {
        try {
            $arrayCombosNoDisponibles = [];
            $contador = 0;
            //transacaccion
            DB::beginTransaction();
            // Validar entrada
            $id_devolucion = $request->input('id_devolucion');
            if (!$id_devolucion) {
                return response()->json(['status' => '0', 'message' => 'El id_devolucion es requerido.'], 200);
            }
            //obtener padre
            $padre = CodigosLibrosDevolucionHeader::where('id', $id_devolucion)->first();
            if(!$padre){
                return response()->json(['status' => '0', 'message' => 'No se pudo obtener el padre del documento.'], 200);
            }
            $estadoPadre = $padre->estado;
            //si es estado 2 es finalizado y ya no se debe hacer nada
            if($estadoPadre == 2){
                return;
            }
            $id_cliente = $padre->id_cliente;
            $id_periodo = $padre->periodo_id;
            // Obtener datos
            $getCombos = CodigosLibrosDevolucionSon::query()
                ->where('codigoslibros_devolucion_id', $id_devolucion)
                ->where('tipo_codigo', '0')
                ->whereNotNull('id_empresa') // Filtramos por id_empresa no nulo
                ->whereNotNull('combo') // Filtramos por combo no nulo
                ->whereNotNull('codigo_combo')
                ->select('pro_codigo', 'combo', 'codigo_combo', 'factura', 'documento', 'id_empresa')
                ->get();
            // Agrupar por combo y id_empresa
            $result = $getCombos->groupBy(function ($item) {
                return $item->combo . '-' . $item->id_empresa;
            })->map(function ($items, $key) {
                [$combo, $id_empresa] = explode('-', $key);

                // Agrupar por codigo_combo y construir los hijos
                $hijos = $items->groupBy('codigo_combo')->map(function ($subItems, $codigoCombo) {
                    $subhijos = $subItems->map(function ($item) {
                        return [
                            'pro_codigo' => $item->pro_codigo,
                            'factura' => $item->factura,
                            'documento' => $item->documento,
                        ];
                    })->toArray();

                    return [
                        'codigo_combo' => $codigoCombo,
                        'cantidad_subhijos' => count($subhijos),
                        'subhijos' => $subhijos,
                    ];
                })->values()->toArray();
                //filtrar codigo_combo diferente de vacio o nulo
                $hijos = array_filter($hijos, function ($item) {
                    return !empty($item['codigo_combo']);
                });

                return [
                    'combo' => $combo,
                    'id_empresa' => $id_empresa,
                    'cantidad_hijos' => count($hijos), // Número total de hijos
                    'hijos' => $hijos,
                ];
            })->values()->toArray();
            //GUARDAR EN TABLA CODIGOS LIBROS SON
            foreach($result as $key => $item){
                //buscar el combo en la tabla libro series
                $id_empresa                                 = $item['id_empresa'];
                $combo                                      = $item['combo'];
                $cantidadNecesaria                          = $item['cantidad_hijos'];
                //validar si el combo ya esta creado no crear
                $validateCreate                              = $this->devolucionRepository->validateComboCreado($combo,$id_empresa,$id_devolucion);
                if($validateCreate->count() > 0)             { continue; }
                $getLibro = LibroSerie::where('codigo_liquidacion', $combo)->first();
                //si no existe el combo mando una alerta
                if(!$getLibro)                              { return response()->json(['status' => '0', 'message' => 'No se pudo obtener el combo '.$combo], 200); }
                $id_libro                                   = $getLibro->idLibro;
                //obtener disponibilidad del combo en alguna prefactura
                $getDisponibilidadPrefactura                = $this->devolucionRepository->prefacturaLibreXCodigo($id_empresa,$id_cliente,$id_periodo,$combo,$cantidadNecesaria);
                if($getDisponibilidadPrefactura->count() == 0){
                    $mensajeError                            = 'No se pudo obtener la disponibilidad del combo '.$combo;
                    $item['mensajeError']                    = $mensajeError;
                    $arrayCombosNoDisponibles[]              = $item;
                    continue;
                }
                //si existe disponibilidad guardar combo
                $datosCombo                                 = $getDisponibilidadPrefactura[0];
                $guardarCombo                               = new CodigosLibrosDevolucionSon();
                $guardarCombo->codigoslibros_devolucion_id  = $id_devolucion;
                $guardarCombo->id_empresa                   = $id_empresa;
                $guardarCombo->pro_codigo                   = $combo;
                $guardarCombo->combo_cantidad               = $cantidadNecesaria;
                $guardarCombo->tipo_codigo                  = 1;
                $guardarCombo->id_cliente                   = $id_cliente;
                $guardarCombo->id_periodo                   = $id_periodo;
                $guardarCombo->id_libro                     = $id_libro;
                $guardarCombo->precio                       = $datosCombo->det_ven_valor_u;
                $guardarCombo->documento                    = $datosCombo->ven_codigo;
                $guardarCombo->save();
                if($guardarCombo){
                    $contador++;
                }
            }
            if(count($arrayCombosNoDisponibles) > 0){
               //arrayCombosNoDisponibles si es id_empresa 1 es Prolipa 3 es calmed
                foreach($arrayCombosNoDisponibles as $key => $value){
                    if($value['id_empresa'] == 1){
                        $arrayCombosNoDisponibles[$key]['descripcion_empresa']= 'Prolipa';
                    }
                    if($value['id_empresa'] == 3){
                        $arrayCombosNoDisponibles[$key]['descripcion_empresa']= 'Calmed';
                    }
                }
            }
            DB::commit();
            return [
                "contador" => $contador,
                "noDisponibles" => $arrayCombosNoDisponibles
            ];
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '0', 'message' => 'Error al generar combos: ' . $e->getMessage()], 500);
        }
    }



    //api:get/devoluciones?todoDevolucionCliente=yes
    public function todoDevolucionCliente($request)
    {
        $query = DB::SELECT("SELECT
                    i.nombreInstitucion AS cliente,
                    cl.*,
                    (SELECT COUNT(*) FROM codigoslibros_devolucion_son ch WHERE ch.codigoslibros_devolucion_id = cl.id) AS total_codigos_son,
                    (SELECT GROUP_CONCAT(DISTINCT ch.documento SEPARATOR ', ')
                    FROM codigoslibros_devolucion_son ch
                    WHERE ch.codigoslibros_devolucion_id = cl.id) AS documentos
                FROM
                    codigoslibros_devolucion_header cl
                INNER JOIN
                    institucion i ON i.idInstitucion = cl.id_cliente
                WHERE
                    cl.id_cliente LIKE '%$request->cliente%'");
        return $query;
    }
    //api:get/devoluciones?devolucionDetalle=yes
    public function devolucionDetalle(Request $request)
    {
        $query = DB::SELECT("SELECT ch.*, i.nombreInstitucion AS cliente, ls.id_serie, ls.nombre, a.area_idarea, ls.year
            FROM codigoslibros_devolucion_son AS ch
            LEFT JOIN codigoslibros_devolucion_header AS cl ON ch.codigoslibros_devolucion_id = cl.id
            LEFT JOIN institucion AS i ON i.idInstitucion = ch.id_cliente
            LEFT JOIN libros_series ls ON ls.idLibro = ch.id_libro
            LEFT JOIN libro l ON l.idlibro = ch.id_libro
            LEFT JOIN asignatura a ON a.idasignatura = l.asignatura_idasignatura
            WHERE cl.codigo_devolucion = '$request->busqueda'
            AND ch.prueba_diagnostico = 0");
        foreach ($query as $key => $item) {

            //Precio por cada item
            $precio = $this->pedidosRepository->getPrecioXLibro($item->id_serie, $item->id_libro, $item->area_idarea, $item->id_periodo, $item->year);

            //Añadir el precio
            $query[$key]->precio = $precio ?? 0;
        }
        return $query;
    }
    public function CargarDevolucion(Request $request)
    {
        // Obtener y decodificar los datos de la solicitud
        $datosDevolucion = json_decode($request->query('datosDevolucion'), true);

        // Validar que se haya decodificado correctamente
        if (!is_array($datosDevolucion)) {
            return response()->json(['error' => 'Invalid data'], 400);
        }

        $resultados = [];

        foreach ($datosDevolucion as $devolucion) {
            // Verificar que cada devolución tenga el campo 'codigo_devolucion'
            if (!isset($devolucion['codigo_devolucion'])) {
                return response()->json(['error' => 'codigo_devolucion is required'], 400);
            }

            $codigoDevolucion = $devolucion['codigo_devolucion'];

            // Consulta SQL
            $query = DB::SELECT("SELECT ch.*, i.nombreInstitucion AS cliente, ls.id_serie, ls.nombre, a.area_idarea, ls.year
                FROM codigoslibros_devolucion_son AS ch
                LEFT JOIN codigoslibros_devolucion_header AS cl ON ch.codigoslibros_devolucion_id = cl.id
                LEFT JOIN institucion AS i ON i.idInstitucion = ch.id_cliente
                LEFT JOIN libros_series ls ON ls.idLibro = ch.id_libro
                LEFT JOIN libro l ON l.idlibro = ch.id_libro
                LEFT JOIN asignatura a ON a.idasignatura = l.asignatura_idasignatura
                WHERE cl.codigo_devolucion = ?
                AND ch.prueba_diagnostico = 0", [$codigoDevolucion]);

            // Procesar cada ítem de la consulta
            foreach ($query as $item) {
                // Obtener precio por cada ítem
                $precio = $this->pedidosRepository->getPrecioXLibro($item->id_serie, $item->id_libro, $item->area_idarea, $item->id_periodo, $item->year);
                // Añadir el precio al ítem, asignando 0 si no se encuentra
                $item->precio = $precio ?? 0; // Asignar 0 si $precio es null
            }

            // Agregar resultados de la consulta actual a los resultados generales
            $resultados = array_merge($resultados, $query);
        }

        return response()->json($resultados);
    }


    public function CargarDocumentos(Request $request){
        $query = DB::SELECT("SELECT
            ins.ruc AS rucPuntoVenta, em.nombre AS empresa,
            CONCAT(usa.nombres, ' ', usa.apellidos) AS cliente, fv.ven_codigo,
            fv.ven_fecha, fv.user_created, fv.ven_valor, ins.nombreInstitucion, ins.direccionInstitucion, ins.telefonoInstitucion as telefono, ins.asesor_id,
            CONCAT(u.nombres, ' ', u.apellidos) AS asesor,
            usa.nombres, usa.apellidos, fpr.prof_observacion, fpr.idPuntoventa,
            COUNT(DISTINCT dfv.pro_codigo) AS item, CONCAT(us.nombres, ' ', us.apellidos) AS responsable,
            (SELECT SUM(det_ven_cantidad) FROM f_detalle_venta WHERE ven_codigo = fv.ven_codigo AND id_empresa = fv.id_empresa) AS libros,
            fv.ruc_cliente AS cedula, usa.email, usa.telefono as telefono_cliente ,fv.idtipodoc, em.id AS empresa_id, fv.ven_tipo_inst, fv.ven_idproforma, fv.ven_observacion,
            fv.ven_subtotal, fv.ven_desc_por, fv.ven_descuento, fv.ven_iva, fv.ven_transporte, fv.ven_p_libros_obsequios,fv.institucion_id,
            fv.doc_intercambio, fv.fecha_intercambio
            FROM f_venta fv
            LEFT JOIN f_proforma fpr ON fpr.prof_id = fv.ven_idproforma
            LEFT JOIN p_libros_obsequios plo ON plo.id = fv.ven_p_libros_obsequios
            LEFT JOIN empresas em ON fpr.emp_id = em.id OR fv.id_empresa = em.id
            LEFT JOIN pedidos pe ON fpr.idPuntoventa = pe.ca_codigo_agrupado
            LEFT JOIN institucion ins ON fpr.id_ins_depacho = ins.idInstitucion OR fv.institucion_id = ins.idInstitucion
            LEFT JOIN usuario usa ON fpr.ven_cliente = usa.idusuario OR fv.ruc_cliente = usa.cedula
            INNER JOIN usuario us ON fv.user_created = us.idusuario
            LEFT JOIN usuario u ON ins.asesor_id = u.idusuario
            LEFT JOIN f_detalle_venta dfv ON fv.ven_codigo = dfv.ven_codigo AND fv.id_empresa = dfv.id_empresa
            WHERE fv.ven_codigo = '$request->documentos' and fv.id_empresa = '$request->empresa'
            GROUP BY fv.ven_codigo, fv.ven_fecha,
                ins.ruc, em.nombre, usa.nombres, usa.apellidos, fpr.prof_observacion,
                fpr.idPuntoventa, u.nombres, u.apellidos, fv.user_created, fv.ven_valor, ins.nombreInstitucion,
                ins.direccionInstitucion, ins.telefonoInstitucion,  ins.asesor_id,
                fv.id_empresa, fv.ruc_cliente, usa.email, usa.telefono, em.id, fv.ven_tipo_inst, fv.ven_idproforma,
                fv.ven_observacion,fv.ven_subtotal, fv.ven_desc_por, fv.ven_descuento, fv.ven_iva, fv.ven_transporte
            ORDER BY fv.ven_fecha DESC;
            ");
      return $query;
    }
    public function CargarDocumentosDetalles(Request $request){
        $query = DB::SELECT("SELECT dv.det_ven_codigo, dv.pro_codigo, dv.det_ven_dev, dv.det_ven_cantidad, dv.det_ven_valor_u,dv.detalle_notaCreditInterna,
            l.descripcionlibro, ls.nombre, s.nombre_serie, ls.id_serie
            FROM f_detalle_venta AS dv
            LEFT JOIN f_venta AS fv ON dv.ven_codigo=fv.ven_codigo
            LEFT JOIN libros_series AS ls ON dv.pro_codigo=ls.codigo_liquidacion
            LEFT JOIN series AS s ON ls.id_serie=s.id_serie
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            WHERE dv.ven_codigo='$request->codigo'
            AND dv.id_empresa=fv.id_empresa
            AND fv.id_empresa= $request->empresa
            ORDER BY dv.pro_codigo");
      return $query;
    }
    public function CargarDocumentosDetallesGuias(Request $request){
        $query = DB::SELECT("SELECT dv.det_ven_codigo, dv.pro_codigo as codigo_liquidacion,
        dv.det_ven_dev, dv.det_ven_cantidad as valor, dv.det_ven_valor_u,
        ls.pro_nombre as nombrelibro
        FROM f_detalle_venta AS dv
        LEFT JOIN f_venta AS fv ON dv.ven_codigo=fv.ven_codigo
        LEFT JOIN 1_4_cal_producto AS ls ON dv.pro_codigo=ls.pro_codigo
        WHERE dv.ven_codigo='$request->codigo'
        AND dv.id_empresa=fv.id_empresa
        AND fv.id_empresa= $request->empresa
        ORDER BY dv.pro_codigo");
        return $query;
    }
    public function CargarDetallesDocumentos(Request $request) {
        // Decodifica el JSON recibido en el parámetro 'documentos'
        $datosDevolucionDocuemntos = json_decode($request->query('documentos'), true);

        // Verifica que el array no esté vacío
        if (empty($datosDevolucionDocuemntos)) {
            return response()->json([], 400); // Devuelve un error si no hay documentos
        }

        // Usa implode para construir la lista de códigos en la consulta
        $codigos = implode(',', array_map('intval', $datosDevolucionDocuemntos)); // Asegúrate de que sean enteros para evitar inyecciones SQL

        // Prepara la consulta
        $query = DB::SELECT("
            SELECT fv.ven_codigo, fv.tip_ven_codigo, fv.ven_idproforma, fv.ven_tipo_inst,
                   fv.ven_valor, fv.ven_subtotal, fv.ven_desc_por, fv.ven_descuento,
                   fv.ven_fecha, fv.institucion_id, fv.ven_cliente, fv.ruc_cliente
            FROM f_venta fv
            WHERE fv.ven_codigo IN ($codigos)"); // Usa IN para buscar múltiples códigos

        // Retorna los resultados como respuesta JSON
        return response()->json($query);
    }

    public function verificarDocumento(Request $request)
    {
        $documento = $request->query('documento');
        $clienteId = $request->query('cliente');

        // Verificar si el documento existe y no está anulado para el cliente correspondiente
        $resultado = DB::table('f_venta')
            ->where('ven_codigo', $documento)
            ->where('institucion_id', $clienteId) // Asegurarse que el documento pertenezca al cliente
            ->where('est_ven_codigo', '<>', 3) // Asegurarse de que el estado no sea "anulado"
            ->first();

        if ($resultado) {
            $resultadoDevuelto = DB::table('codigoslibros_devolucion_header')
            ->where('codigo_nota_credito', $documento)
            ->first();
            if ($resultadoDevuelto) {
                return response()->json(['existe' => true, 'anulado' => false, 'devuelto' => true]);
            }else{
                return response()->json(['existe' => true, 'anulado' => false, 'devuelto' => false]);
            }
        } else {
            // Comprobar si el documento está anulado para el cliente correspondiente
            $resultadoAnulado = DB::table('f_venta')
                ->where('ven_codigo', $documento)
                ->where('institucion_id', $clienteId) // Verificar cliente
                ->where('est_ven_codigo', 3) // Comprobando estado anulado
                ->first();

            if ($resultadoAnulado) {
                return response()->json(['existe' => true, 'anulado' => true]);
            }

            // El documento no existe
            return response()->json(['existe' => false, 'anulado' => false]);
        }
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
    //api:post/devoluciones
    public function store(Request $request)
    {
        if($request->validateBeforeCreate)      { return $this->validateBeforeCreate($request); }
        if($request->devolverDocumentoBodega)   { return $this->devolverDocumentoBodega($request); }
        if($request->updateDocumentoDevolucion) { return $this->updateDocumentoDevolucion($request); }
        if($request->guardarComboDocumento)     { return $this->guardarComboDocumento($request); }
        if($request->guardarDevolucionCombos)   { return $this->guardarDevolucionCombos($request); }
        if($request->actualizarDatosDevolucion) { return $this->actualizarDatosDevolucion($request); }
        if($request->returnToReview)            { return $this->returnToReview($request); }
        if($request->CambioClienteDevolucion)   { return $this->CambioClienteDevolucion($request); }
    }
    //api:post/validateBeforeCreate=1
    public function validateBeforeCreate($request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos = json_decode($request->data_codigos);
        $codigosNoCambiados     = [];
        $codigosConLiquidacion  = [];
        $codigoNoExiste         = [];
        $codigosSinCodigoUnion  = [];
        $porcentaje             = 0;
        $contador               = 0;
        $contadorNoCambiado     = 0;
        $contadorNoexiste       = 0;
        try{
            DB::beginTransaction();
                ///===PROCESO===
                foreach($codigos as $key => $item){
                    //validar si el codigo existe
                    $validar                        = $this->getCodigos($item->codigo,0);
                    $ingreso                        = 0;
                    $ifsetProforma                  = 0;
                    $ifErrorProforma                = 0;
                    $messageProforma                = "";
                    $datosProforma                  = [];
                    $messageIngreso                 = "";
                    //valida que el codigo existe
                    if(count($validar)>0){

                        $codigo_union               = $validar[0]->codigo_union;
                        //validar si el codigo se encuentra liquidado
                        $ifLiquidado                = $validar[0]->estado_liquidacion;
                        //codigo de combo
                        $ifCombo                    = $validar[0]->combo;
                        //codigo de factura
                        $ifFactura                  = $validar[0]->factura;
                        //tipo_venta
                        $ifTipoVenta                = $validar[0]->venta_estado;
                        //codigo_paquete
                        $ifcodigo_paquete           = $validar[0]->codigo_paquete;
                        //para ver si es codigo regalado no este liquidado
                        $ifliquidado_regalado       = $validar[0]->liquidado_regalado;
                        //para ver la empresa de la proforma
                        $ifproforma_empresa         = $validar[0]->proforma_empresa;
                        //para ver el estado devuelto proforma
                        $ifdevuelto_proforma        = $validar[0]->devuelto_proforma;
                        ///para ver el codigo de proforma
                        $ifcodigo_proforma          = $validar[0]->codigo_proforma;
                        //codigo de liquidacion
                        $ifcodigo_liquidacion       = $validar[0]->codigo_liquidacion;
                        //ADMINISTRADOR (SI PUEDE DEVOLVER AUNQUE LA INSTITUCION SEA DIFERENTE)
                        $EstatusProceso             = false;
                        if($request->dLiquidado ==  '1'){
                            //VALIDACION AUNQUE ESTE LIQUIDADO
                            if($ifLiquidado == '0' || $ifLiquidado == '1' || $ifLiquidado == '2' || $ifLiquidado == '4') $EstatusProceso = true;
                        }else{
                            //VALIDACION QUE NO SEA LIQUIDADO
                            if(($ifLiquidado == '1' || $ifLiquidado == '2' || $ifLiquidado == '4') && $ifliquidado_regalado == '0' ) $EstatusProceso = true;
                        }
                        if(!$EstatusProceso){
                            //codigo A
                            if($ifLiquidado == 0){ $messageIngreso = CodigosLibros::CODIGO_LIQUIDADO; }
                            if($ifLiquidado == 3){ $messageIngreso = CodigosLibros::CODIGO_DEVUELTO;  }
                            if($ifLiquidado == 4){ $messageIngreso = CodigosLibros::CODIGO_GUIA;      }
                            if($ifliquidado_regalado == 1){ $messageIngreso = CodigosLibros::CODIGO_LIQUIDADO_REGALADO; }
                        }
                        //SI ES COMBO NO SE VALIDA PORQUE SE COLOCA DESDE EL MODULO DE COMBOS
                        //====PROFORMA============================================
                        //ifdevuelto_proforma => 0 => nada; 1 => devuelta antes del enviar el pedido; 2 => enviada despues de enviar al pedido
                        if($ifproforma_empresa > 0 && $ifdevuelto_proforma != 1 && ($ifCombo == null || $ifCombo == "")){
                            $datosProforma     = $this->codigosRepository->validateProforma($ifcodigo_proforma,$ifproforma_empresa,$ifcodigo_liquidacion);
                            $ifErrorProforma   = $datosProforma["ifErrorProforma"];
                            $messageIngreso    = $datosProforma["messageProforma"];
                            $ifsetProforma     = $datosProforma["ifsetProforma"];
                            if($ifsetProforma  == 1 && $ifErrorProforma == 0)   { $EstatusProceso = true; }
                            else                                                { $EstatusProceso = false; }
                        }
                        //SI CUMPLE LA VALIDACION
                        if($EstatusProceso){
                            //VALIDAR CODIGOS QUE NO TENGA CODIGO UNION
                            if($codigo_union != null || $codigo_union != ""){
                                //devolucion
                                $datos = (object) [
                                    "codigo"             => $item->codigo,
                                    "codigo_union"       => $codigo_union,
                                    "ifsetProforma"      => $ifsetProforma,
                                    "codigo_liquidacion" => $ifcodigo_liquidacion,
                                    "proforma_empresa"   => $ifproforma_empresa,
                                    "codigo_proforma"    => $ifcodigo_proforma,
                                ];
                                $getIngreso         =  $this->codigosRepository->validacionPrefacturaCodigo($datos);
                                $ingreso            = $getIngreso["ingreso"];
                                $messageIngreso     = $getIngreso["message"];
                                //si ingresa correctamente
                                if($ingreso == 1){
                                    $porcentaje++;
                                }
                                else{
                                    $codigosNoCambiados[$contadorNoCambiado] = [
                                        "codigo"        => $item->codigo,
                                        "mensaje"       => $messageIngreso
                                    ];
                                    $contadorNoCambiado++;
                                }
                            }
                            //ACTUALIZAR CODIGO SIN UNION
                            else{
                                $datos = (object) [
                                    "codigo"             => $item->codigo,
                                    "codigo_union"       => 0,
                                    "ifsetProforma"      => $ifsetProforma,
                                    "codigo_liquidacion" => $ifcodigo_liquidacion,
                                    "proforma_empresa"   => $ifproforma_empresa,
                                    "codigo_proforma"    => $ifcodigo_proforma,
                                ];
                                $getIngreso         =  $this->codigosRepository->validacionPrefacturaCodigo($datos);
                                $ingreso            = $getIngreso["ingreso"];
                                $messageIngreso     = $getIngreso["message"];
                                if($ingreso == 1){
                                    $porcentaje++;

                                }
                                else{
                                    $codigosNoCambiados[$contadorNoCambiado] = [
                                        "codigo"        => $item->codigo,
                                        "mensaje"       => $messageIngreso
                                    ];
                                    $contadorNoCambiado++;
                                }
                            }
                        }
                        //SI NO CUMPLE LA VALIDACION
                        else{
                            $codigosNoCambiados[$contadorNoCambiado] = [
                                "codigo"        => $item->codigo,
                                "mensaje"       => $messageIngreso
                            ];
                            $contadorNoCambiado++;
                            //admin
                            // if($ifErrorProforma == 1)               { $validar[0]->errorProforma = 1; $validar[0]->mensajeErrorProforma   = $messageProforma; }
                            // $codigosConLiquidacion[]                = $validar[0];
                            // $contador++;
                        }
                    }else{
                        $codigoNoExiste[$contadorNoexiste] = [ "codigo" => $item->codigo ];
                        $contadorNoexiste++;
                    }
                }

                DB::commit();
                return [
                    "cambiados"             => $porcentaje,
                    "codigosNoCambiados"    => $codigosNoCambiados,
                    "codigosConLiquidacion" => $codigosConLiquidacion,
                    "codigoNoExiste"        => $codigoNoExiste,
                    "codigosSinCodigoUnion" => $codigosSinCodigoUnion,
                ];
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                "status"  => 0,
                'message' => $e->getMessage()
            ], 200);
        }
    }
    //api:post/devoluciones?devolverDocumentoBodega=1
    public function devolverDocumentoBodega($request){
        $codigos                = json_decode($request->data_codigos);
        $codigosABuscar         = array_column($codigos, 'codigo');
        CodigosLibros::whereIn('codigo', $codigosABuscar)
            ->update([
                'estado_liquidacion' => 3
            ]);
    }
    //api:post/devoluciones?updateDocumentoDevolucion=1
    public function updateDocumentoDevolucion($request) {
        try {
            $arrayEmpresas                                  = json_decode($request->arrayEmpresas);
            $iniciales                                      = $request->iniciales;
            $id_cliente                                     = $request->id_cliente;
            $periodo_id                                     = $request->periodo_id;
            $id_documento                                   = $request->id_documento;
            $id_usuario                                     = $request->input('id_usuario');

            if($iniciales == null || $iniciales == "" || $iniciales == "null"){ return ["status" => "0", "message" => "No se pudo obtener la iniciales del usuario"]; }
            $codigo_contrato                                = Periodo::where('idperiodoescolar', $periodo_id)->value('codigo_contrato');
            if (!$codigo_contrato)                          { return ["status" => "0", "message" => "No se pudo obtener el codigo del contrato"]; }
            // Transacción
            DB::beginTransaction();
            //eliminar los hijos
            CodigosLibrosDevolucionSonFacturador::where('codigoslibros_devolucion_header_id', $id_documento)->delete();
            //CREAR DOCUMENTO DE DEVOLUCION FACTURADOR
            foreach($arrayEmpresas as $key => $item) {
                $ifInsertar                                 = 0;
                $id_empresa                                 = $item->id_empresa;
                $id_devolucion                              = 0;
                $secuencia                                  = 0;
                $letraE                                     = '';
                $getSecuencia                               = f_tipo_documento::obtenerSecuencia("DEVOLUCION-CODIGO-FACTURACION");
                if(!$getSecuencia)                          { return ["status" => "0", "message" => "No se pudo obtener la secuencia de guias"]; }
                $letra                                      = $getSecuencia->tdo_letra;
                if($id_empresa == 0)                        { $secuencia                      = $getSecuencia->tdo_secuencial_SinEmpresa; $letraE = 'SE'; }
                if($id_empresa == 1)                        { $secuencia                      = $getSecuencia->tdo_secuencial_Prolipa; $letraE = 'P'; }
                if($id_empresa == 3)                        { $secuencia                      = $getSecuencia->tdo_secuencial_calmed; $letraE = 'GC'; }
                $secuencia                                  = $secuencia + 1;
                $format_id_pedido                           = f_tipo_documento::formatSecuencia($secuencia);
                $codigo_ven                                 = $letra.'-'.$letraE.'-'. $codigo_contrato .'-'. $iniciales.'-'. $format_id_pedido;
                //ACTUALIZAR LA SECUENCIA
                $tipoDocumento                              = f_tipo_documento::find(15); // Encuentra el registro con tdo_id = 15
                if ($tipoDocumento) {
                    if($id_empresa == 0)                    { $tipoDocumento->tdo_secuencial_SinEmpresa = $secuencia; }
                    if($id_empresa == 1)                    { $tipoDocumento->tdo_secuencial_Prolipa    = $secuencia; }
                    if($id_empresa == 3)                    { $tipoDocumento->tdo_secuencial_calmed     = $secuencia; }
                    //validar si mi codigoslibros_devolucion_header_id ya existe y tiene la misma empresa no creo
                    $validarDocumento                        = CodigosLibrosDevolucionHeaderFacturador::where('codigoslibros_devolucion_header_id', $id_documento)->where('id_empresa', $id_empresa)->first();
                    if($validarDocumento)                    { $ifInsertar = 0; } //No creo nada
                    else                                     { $tipoDocumento->save(); $ifInsertar = 1; } //guardar secuencia

                    $devolucion                             = new CodigosLibrosDevolucionHeaderFacturador();
                    $devolucion->ven_codigo                 = $codigo_ven;
                    $devolucion->id_empresa                 = $id_empresa;
                    //0 porque el cliente ahora se guarda en cada codigo individual
                    $devolucion->id_cliente                 = $id_cliente;
                    $devolucion->user_created               = $id_usuario;
                    $devolucion->periodo_id                 = $periodo_id;
                    $devolucion->codigoslibros_devolucion_header_id = $id_documento;
                    //validar si mi codigoslibros_devolucion_header_id ya existe y tiene la misma empresa no creo
                    if($ifInsertar == 0)                     { $id_devolucion         = $validarDocumento->id; }
                    else                                     { $devolucion->save();   $id_devolucion = $devolucion->id; }
                    if($id_devolucion == 0)                  { return ["status" => "0", "message" => "No se pudo guardar el documento de devolución"]; }

                    foreach($item->arrayCodigos as $key => $item2) {
                        //GUARDAR HIJOS DEL DOCUMENTO FACTURADOR
                        $datos = new stdClass();
                        $datos->documentoPadre                      = $id_devolucion;
                        $datos->codigoslibros_devolucion_header_id  = $id_documento;
                        $datos->id_empresa                          = $id_empresa ?? 0;
                        $datos->pro_codigo                          = $item2->codigo;
                        $datos->cantidad                            = $item2->cantidad;
                        $datos->precio                              = $item2->precio;
                        $datos->descuento                           = $item2->ven_desc_por ?? 0;
                        $datos->observacion_codigo                  = $item2->observacion_codigo;
                        $this->devolucionRepository->save_son_devolucion_facturador($datos);
                    }
                }else {
                    return ["status" => "0", "message" => "No se pudo obtener la secuencia de guias"];
                }
            }

            //ACTUALIZAR TABLA PADRE
            $updateData['estado'] = 2;
            $updateData['user_created_finalizado']  = $id_usuario;
            $updateData['fecha_finalizacion']       = now();


            // Actualizar el encabezado
            CodigosLibrosDevolucionHeader::where('id', $id_documento)->update($updateData);

            // Actualizar la tabla codigoslibros_devolucion_son
            CodigosLibrosDevolucionSon::where('codigoslibros_devolucion_id', $id_documento)
                ->where('estado', '=', '1')
                ->update(['estado' =>  2]);
            //GUARDAR EN TABLA FACTURADOR


            // Confirmar transacción
            DB::commit();
            return response()->json(['message' => 'Documento actualizado correctamente']);

        } catch (\Exception $e) {
            // Rollback de transacción
            DB::rollBack();
            // Puedes registrar el error aquí
            return response()->json(['status' => '0', 'message' => 'Error al actualizar el documento: ' . $e->getMessage()], 200);
        }
    }

    //api:post/devoluciones?guardarComboDocumento=1
    public function guardarComboDocumento($request)
    {
        $idDevolucion = $request->input('id_devolucion');
        $pro_codigo   = $request->input('pro_codigo');
        $id_cliente   = $request->input('id_cliente');
        $id_empresa   = $request->input('id_empresa');
        $documento    = $request->input('documento');
        $id_periodo   = $request->input('id_periodo');
        $id_libro     = $request->input('id_libro');
        $precio       = $request->input('precio');

        try{
            //validar en el codigoslibros_devolucion_header si es estado 2 mostrar que ya no se puede agregar combos porque el documento ya finalizo
            $validarDocumento = CodigosLibrosDevolucionHeader::where('id', $idDevolucion)
            ->first();
            if($validarDocumento->estado == 2){
                return ["status" => "0", "message" => "El documento ya finalizo, no se puede agregar combos"];
            }
            //validar si existe el codigoslibros_devolucion_id , id_empresa y pro_codigo
            $validar = CodigosLibrosDevolucionSon::where('codigoslibros_devolucion_id',$idDevolucion)
            ->where('id_empresa',$id_empresa)
            ->where('documento',$documento)
            ->where('pro_codigo',$pro_codigo)->first();
            if($validar){
                return ["status" => "0", "message" => "El combo $pro_codigo ya existe para este documento"];
            }
            // Buscar y actualizar o crear
            CodigosLibrosDevolucionSon::updateOrCreate(
                [
                    'documento'  => $documento,  // Condición para evitar duplicados
                    'pro_codigo' => $pro_codigo,
                    'id_empresa' => $id_empresa
                ],
                [
                    'codigoslibros_devolucion_id'   => $idDevolucion, // Si no existe, crea con este valor
                    'tipo_codigo'                   => 1,
                    'id_cliente'                    => $id_cliente,
                    'id_periodo'                    => $id_periodo,
                    'id_libro'                      => $id_libro,
                    'precio'                        => $precio,
                ]
            );

            return response()->json(['status' => '1', 'message' => 'Se guardó correctamente']);
        }catch(\Exception $e){
            return response()->json(['status' => '0', 'message' => $e->getMessage()]);
        }
    }
    //api:post/devoluciones?guardarDevolucionCombos=1
    public function guardarDevolucionCombos($request)
    {
        try{
            //transaccion
            DB::beginTransaction();

            $idDevolucion = $request->input('id_devolucion');
            $contador     = 0;
            $id_usuario   = $request->input('id_usuario');
            $arrayCombos  = json_decode($request->input('arrayCombos'));
            //validar que el documento no este finalizado
            $validarDocumento = CodigosLibrosDevolucionHeader::where('id', $idDevolucion)
            ->first();
            if($validarDocumento->estado == 2){
                return response()->json(['status' => '0', 'message' => 'El documento ya finalizo, no se puede agregar mas devoluciones']);
            }
            foreach($arrayCombos as $key => $item){
                $idSon                  = $item->id;
                $cantidadPendiente      = $item->combo_cantidad;
                $prefactura             = $item->documento;
                $id_empresa             = $item->id_empresa;
                $pro_codigo             = $item->pro_codigo;
                //obtener el valor de la devolucion
                $getDevolucion          = DetalleVentas::getLibroDetalle($prefactura, $id_empresa, $pro_codigo);
                $documento              = Ventas::where('ven_codigo', $prefactura)->where('id_empresa', $id_empresa)->first();
                //idtipodoc => 1 es prefactura; 2 = notas
                $idtipodoc = $documento->idtipodoc;
                $documentoPrefactura    = $idtipodoc == 1 ? 0 : 1;
                $det_ven_dev            = $getDevolucion[0]->det_ven_dev;
                $nuevoValorDevolucion   = $det_ven_dev + $cantidadPendiente;
                //actualizar el valor de la devolucion
                $result                  = DetalleVentas::updateDevolucion($prefactura, $id_empresa, $pro_codigo, $nuevoValorDevolucion);
                //actualizar el stock
                //get stock
                $getStock                   = _14Producto::obtenerProducto($pro_codigo);
                $stockAnteriorReserva       = $getStock->pro_reservar;
                //prolipa
                if($id_empresa == 1)  {
                    //si es documento de prefactura
                    if($documentoPrefactura == 0)  { $stockEmpresa  = $getStock->pro_stock; }
                    //si es documento de notas
                    if($documentoPrefactura == 1)  { $stockEmpresa  = $getStock->pro_deposito; }
                }
                //calmed
                if($id_empresa == 3)  {
                    //si es documento de prefactura
                    if($documentoPrefactura == 0)  { $stockEmpresa  = $getStock->pro_stockCalmed; }
                    //si es documento de notas
                    if($documentoPrefactura == 1)  { $stockEmpresa  = $getStock->pro_depositoCalmed; }
                }
                $nuevoStockReserva          = $stockAnteriorReserva + $cantidadPendiente;
                $nuevoStockEmpresa          = $stockEmpresa + $cantidadPendiente;
                //actualizar stock en la tabla de productos
                _14Producto::updateStock($pro_codigo,$id_empresa,$nuevoStockReserva,$nuevoStockEmpresa,$documentoPrefactura);
                //actualizar la tabla codigoslibros devolucion son
                $hijo = CodigosLibrosDevolucionSon::find($idSon);
                if($hijo){
                    $combo_cantidad_devuelta        = $hijo->combo_cantidad_devuelta + $cantidadPendiente;
                    $hijo->estado                   = 1;
                    $hijo->combo_cantidad           = 0;
                    $hijo->combo_cantidad_devuelta  = $combo_cantidad_devuelta;
                    $hijo->user_created             = $id_usuario;
                    $hijo->save();
                    //si se guarddo correctamente
                    if($hijo->wasChanged()){
                        $contador++;
                        //actualizar la tabla padre codigoslibros header a estado 1
                        $header = CodigosLibrosDevolucionHeader::find($idDevolucion);
                        if($header){
                            $header->combo_estado = 1;
                            $header->save();
                        }
                    }
                }
            }
            DB::commit();
            return [
                "guardados" => $contador,
            ];
        }catch(\Exception $e){
            DB::rollback();
            return response()->json(['status' => '0', 'message' => $e->getMessage()]);
        }
    }
    //api:post/devoluciones?returnToReview=1
    public function returnToReview($request)
    {
        try {
            DB::beginTransaction();

            $id_documento = $request->input('id_documento');
            $documento = CodigosLibrosDevolucionHeader::find($id_documento);

            if (!$documento) {
                return response()->json(['status' => '0', 'message' => 'No se pudo obtener el documento']);
            }

            if ($documento->estado != '2') {
                return response()->json(['status' => '0', 'message' => 'Solo se puede devolver documentos que estén finalizados']);
            }

            $documento->estado = 1;
            $documento->save();

            if (!$documento->wasChanged()) {
                return response()->json(['status' => '0', 'message' => 'No se pudo actualizar']);
            }

            // Actualizar CodigosLibrosDevolucionSon
            CodigosLibrosDevolucionSon::where('codigoslibros_devolucion_id', $id_documento)
                ->where('estado', '2')
                ->update(['estado' => 1]);

            // Eliminar registros relacionados
            $facturadores = CodigosLibrosDevolucionHeaderFacturador::where('codigoslibros_devolucion_header_id', $id_documento)->get();

            foreach ($facturadores as $facturador) {
                CodigosLibrosDevolucionSonFacturador::where('codigoslibros_devolucion_header_facturador_id', $facturador->id)->delete();
            }

            DB::commit();
            return response()->json(['status' => '1', 'message' => 'Se devolvió correctamente']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '0', 'message' => $e->getMessage()]);
        }
    }

    //api:post/metodosEliminarDevolucion
    public function metodosEliminarDevolucion(Request $request){
        if($request->eliminarDocumentoDevolucion)   { return $this->eliminarDocumentoDevolucion($request); }
        if($request->eliminarItemDevolucion)        { return $this->eliminarItemDevolucion($request); }
    }
    //api:post/devoluciones?eliminarDocumentoDevolucion=1
    public function eliminarDocumentoDevolucion($request)
    {
        try {
            DB::beginTransaction();
            $id_documento = $request->input('id_documento');

            // Validar que el id_documento tenga estado 0 o 1, si no, no se puede eliminar porque ya fue finalizado
            $documento = CodigosLibrosDevolucionHeader::find($id_documento);
            if ($documento->estado == 1) {
                return response()->json(['status' => '0', 'message' => 'No se puede eliminar un revisado por que los codigos ya fueron devueltos'], 200);
            }
            if ($documento->estado == 2) {
                return response()->json(['status' => '0', 'message' => 'No se puede eliminar un documento finalizado'], 200);
            }
            //combo_estado si es 1 es que ya se hizo una devolucion de combos
            if ($documento->combo_estado == 1) {
                return response()->json(['status' => '0', 'message' => 'No se puede eliminar un documento que ya tiene una devolución de combos'], 200);
            }
            // Primero encontrar los hijos, traer los códigos, y en la tabla CodigosLibros actualizar el documento_devolucion a null
            $codigos = CodigosLibrosDevolucionSon::where('codigoslibros_devolucion_id', $id_documento)->get();
            foreach ($codigos as $codigo) {
                CodigosLibros::where('codigo', $codigo->codigo)->update(['documento_devolucion' => null,'permitir_devolver_nota' => 0]);
            }

            // Eliminar los hijos
            CodigosLibrosDevolucionSon::where('codigoslibros_devolucion_id', $id_documento)->delete();

            // Eliminar el documento principal
            $documento = CodigosLibrosDevolucionHeader::find($id_documento);
            if ($documento) {
                $documento->delete();
                DB::commit(); // Confirmar la transacción
                return response()->json(['message' => 'Documento eliminado correctamente']);
            } else {
                return response()->json(['message' => 'Documento no encontrado'], 404);
            }
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir la transacción en caso de error
            return response()->json(['status' => '0', 'message' => 'Error al eliminar el documento: ' . $e->getMessage()], 200);
        }
    }
    //api:post/metodosEliminarDevolucion?eliminarItemDevolucion=1
    public function eliminarItemDevolucion($request)
    {
        //para los combos
        try {
            DB::beginTransaction();
            $id_documento = $request->input('id_documento');
            $idDocumentoSon = $request->input('id_documento_son');
            //validar si el documento ya fue finalizado no se puede eliminar
            $validarDocumento = CodigosLibrosDevolucionHeader::where('id', $id_documento)
            ->first();
            if($validarDocumento->estado == 2){
                return response()->json(['status' => '0', 'message' => 'El documento ya finalizo, no se puede eliminar']);
            }
            //eliminar el combo
            $query = CodigosLibrosDevolucionSon::where('id', $idDocumentoSon)
            ->first();
            if($query){
                $query->delete();
                DB::commit(); // Confirmar la transacción
                return response()->json(['status' => '1', 'message' => 'Se elimino correctamente']);
            }
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir la transacción en caso de error
            return response()->json(['status' => '0', 'message' => 'Error al eliminar el documento: ' . $e->getMessage()], 200);
        }
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

    public function Post_modificar_cabecera_devolucion(Request $request)
    {
        DB::beginTransaction();
        try {
            // Buscar el devolucionedicioncabecera por su codigo_devolucion
            $devolucionedicioncabecera = CodigosLibrosDevolucionHeader::where('codigo_devolucion', $request->codigo_devolucion)->first();

            // Verificar si el registro existe
            if (!$devolucionedicioncabecera) {
                DB::rollback();
                return response()->json(["status" => "0", 'message' => 'No tiene id de devolucion'], 404);
            }

            // Asignar los datos del devolucionedicioncabecera
            $devolucionedicioncabecera->observacion = $request->observacion;
            $devolucionedicioncabecera->cantidadCajas = $request->cantidadCajas;
            $devolucionedicioncabecera->cantidadPaquetes = $request->cantidadPaquetes;
            $devolucionedicioncabecera->user_edit_cabecera = $request->user_edit_cabecera;
            $devolucionedicioncabecera->updated_at = now();

            // Guardar el devolucionedicioncabecera
            $devolucionedicioncabecera->save();

            // Verificar si el producto se guardó correctamente
            if ($devolucionedicioncabecera->wasChanged()) {
                DB::commit();
                return response()->json(["status" => "1", "message" => "Se guardó correctamente"]);
            } else {
                DB::rollback();
                return response()->json(["status" => "0", "message" => "No se pudo actualizar"]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["status" => "0", 'message' => 'Error al actualizar los datos: ' . $e->getMessage()], 500);
        }
    }
    //METODOS FACTURADOR DE DEVOLUCION
    //api:get/metodosGetDevolucionFacturador
    public function metodosGetDevolucionFacturador(Request $request){
        if($request->getListadoDocumentosFacturador) { return $this->listadoDocumentosFacturador($request); }
        //mostrar detalles facturador
        if($request->getFacturadorDocumentoDetalles) { return $this->facturadorDetalles($request); }
    }
    //api:get/metodosGetDevolucionFacturador?getListadoDocumentosFacturador=1
    public function listadoDocumentosFacturador($request) {
        $query = DB::SELECT("SELECT
            hh.*,
            h.codigo_devolucion,
            h.created_at AS fecha_creacionPadre,
            h.fecha_finalizacion as fecha_finalizacionPadre,
            h.cantidadCajas,
            h.cantidadPaquetes,
            h.observacion,
            p.periodoescolar,
            i.nombreInstitucion,
            CONCAT(u.nombres, ' ', u.apellidos) AS editor,
            CONCAT(ucp.nombres, ' ', ucp.apellidos) AS creadorPadre,
            CASE
                WHEN hh.id_empresa = 0 THEN 'Sin empresa'
                ELSE e.nombre
            END AS empresa,
            (
                SELECT count(csf.id) as cantidadCodigos  FROM codigoslibros_devolucion_son_facturador as csf
                WHERE csf.codigoslibros_devolucion_header_facturador_id = hh.id
            )as cantidadCodigos
        FROM codigoslibros_devolucion_header_facturador hh
        LEFT JOIN codigoslibros_devolucion_header h ON hh.codigoslibros_devolucion_header_id = h.id
        LEFT JOIN periodoescolar p ON hh.periodo_id = p.idperiodoescolar
        LEFT JOIN institucion i ON hh.id_cliente = i.idInstitucion
        LEFT JOIN usuario u ON u.idusuario = hh.user_created
        LEFT JOIN empresas e ON hh.id_empresa = e.id
        LEFT JOIN usuario ucp ON h.user_created = ucp.idusuario
        ORDER BY hh.created_at DESC
        ");
        return $query;
    }
    //api:get/metodosGetDevolucionFacturador?getFacturadorDocumentoDetalles=1&id_documento=1
    public function facturadorDetalles($request) {
        $id_documento = $request->input('id_documento');
        $query = DB::SELECT("SELECT
            ss.*,
                ls.nombre AS nombrelibro,
                CASE
                    WHEN ss.id_empresa = 0 THEN 'Sin empresa'
                    ELSE e.descripcion_corta
                END AS empresa
            FROM codigoslibros_devolucion_son_facturador ss
            LEFT JOIN libros_series ls ON ss.pro_codigo = ls.codigo_liquidacion
            LEFT JOIN empresas e ON ss.id_empresa = e.id
            WHERE ss.codigoslibros_devolucion_header_facturador_id = ?
        ", [$id_documento]);

        return $query;
    }

    //DOCUMENTOS DE DEVOLUCION SIN EMPRESA
    public function documentoParaSinEmpresa(Request $request)
    {
        $institucion = $request->institucion;
        $empresa = $request->empresa;
        $itemsParaBuscar = $request->itemsBusqueda;

        // Decodificar JSON si los items vienen como cadena
        if (is_string($itemsParaBuscar)) {
            $itemsParaBuscar = json_decode($itemsParaBuscar, true);
        }

        // Validación básica de inputs
        if (!$institucion || empty($itemsParaBuscar)) {
            return response()->json(['message' => 'Institución o items inválidos.']);
        }

        $resultados = [];

        // Iterar sobre los items a buscar
        foreach ($itemsParaBuscar as $item) {
            $codigo = $item['codigo'];
            $cantidadSolicitada = $item['cantidad'];
            $cantidadRestante = $cantidadSolicitada; // Mantiene el contador de cuánto falta por devolver
            $cantidadDevuelta = 0; // Mantiene el contador de lo que ya se ha devuelto
            $devolucionesPorDocumento = []; // Guarda las devoluciones por cada documento

            // Obtener todos los documentos asociados a la institución y la empresa
            $documentos = DB::table('f_venta as fv')
                ->where('fv.institucion_id', $institucion)
                ->where('fv.id_empresa', $empresa)
                ->where('fv.est_ven_codigo','<>',3) // Excluir documentos anulados (estado 3)
                ->where('fv.idtipodoc', '<>', 16) // Excluir documentos notas de crédito
                // ->whereNot(function ($query) {
                //     $query->whereIn('fv.idtipodoc', [3, 4])
                //         ->whereNotNull('fv.doc_intercambio');
                // }) // Excluir documentos con intercambio
                ->where(function ($query) {
                    // Validamos que no se cumpla la condición: idtipodoc es 3 o 4 y doc_intercambio no es nulo
                    $query->whereNotIn('fv.idtipodoc', [3, 4])  // Excluye idtipodoc 3 o 4
                          ->orWhereNull('fv.doc_intercambio');  // O donde doc_intercambio sea nulo
                })
                ->select('fv.ven_codigo', 'fv.id_empresa')
                ->get();

            // Iterar sobre los documentos encontrados
            foreach ($documentos as $documento) {
                // Obtener detalles de cada documento
                $detallesDocumentos = DB::table('f_detalle_venta as fdv')
                    ->join('f_venta as fv', function ($join) {
                        $join->on('fv.ven_codigo', '=', 'fdv.ven_codigo')
                            ->on('fv.id_empresa', '=', 'fdv.id_empresa');
                    })
                    ->where('fv.est_ven_codigo', '<>', 3) // Filtrar por el estado del documento
                    ->where('fdv.ven_codigo', $documento->ven_codigo) // Filtrar por el código de venta
                    ->where('fdv.id_empresa', $empresa) // Filtrar por la empresa
                    ->select('fdv.pro_codigo', 'fdv.det_ven_cantidad', 'fdv.det_ven_dev')
                    ->orderBy('fdv.det_ven_cantidad', 'desc') // Ordenar por la cantidad disponible de mayor a menor
                    ->get();

                // Iterar sobre cada detalle de los documentos
                foreach ($detallesDocumentos as $detalle) {
                    // Verificar si el código del producto coincide
                    if ($detalle->pro_codigo == $codigo) {
                        // Calcular la cantidad disponible para ese producto
                        $cantidadDisponible = $detalle->det_ven_cantidad - $detalle->det_ven_dev;

                        // Si hay cantidad disponible para devolver
                        if ($cantidadDisponible > 0) {
                            // Verificar cuántos podemos devolver
                            $cantidadDevolucion = min($cantidadRestante, $cantidadDisponible);

                            // Reducir la cantidad restante por devolver
                            $cantidadRestante -= $cantidadDevolucion;
                            $cantidadDevuelta += $cantidadDevolucion; // Incrementar la cantidad devuelta

                            // Almacenar la devolución en este documento
                            $devolucionesPorDocumento[] = [
                                'ven_codigo' => $documento->ven_codigo,
                                'cantidadDevolucion' => $cantidadDevolucion,
                                'cantidadDisponible' => $cantidadDisponible
                            ];

                            // Si ya se ha cubierto la cantidad solicitada, salimos del ciclo
                            if ($cantidadRestante <= 0) {
                                break 2;  // Rompe los dos foreachs (detalles y items)
                            }
                        }
                    }
                }
            }

            // Agregar el resultado de las devoluciones a la lista de resultados
            $resultados[] = [
                'codigo' => $codigo,
                'cantidadSolicitada' => $cantidadSolicitada,
                'cantidadDevuelta' => $cantidadDevuelta, // Total devuelto
                'cantidadPendiente' => $cantidadRestante, // Cantidad pendiente por devolver
                'detallesDevolucion' => $devolucionesPorDocumento
            ];
        }

        // Devolver los resultados con las cantidades devueltas y pendientes
        return response()->json([
            'message' => 'Documentos encontrados',
            'data' => $resultados
        ]);
    }

    //METODO ACTUALIZAR DATOS DE DEVOLUCION
    public function actualizarDatosDevolucion(Request $request)
    {
        // Datos recibidos desde el front
        $datosFiltrados = $request->input('datosFiltrados');  // Este es el arreglo de datos que se recibe del front
        $empresaId = $request->input('empresaId');
        $iDdocumentoDev = $request->input('iDdocumentoDev');
        $documentoDev = $request->input('documentoDev');
        $usuario = $request->input('usuarioModificador');

        $modificados = [
            'codigoslibros' => [],
            'f_detalle_venta' => [],
            'codigoslibros_devolucion_son' => [],
            'stock_producto' => [],
            'usuario_modificador' => [],

        ];

        try {
            // Comienza la transacción
            DB::beginTransaction();
            // Iterar sobre cada libro en los datos filtrados
            foreach ($datosFiltrados as $libro) {
                // Actualizar tabla codigoslibros
                $modificados['codigoslibros'] = array_merge($modificados['codigoslibros'], $this->actualizarCodigosLibros($libro, $empresaId, $documentoDev));

                // Actualizar tabla f_detalle_venta
                $modificados['f_detalle_venta'] = array_merge($modificados['f_detalle_venta'], $this->actualizarDetalleVenta($libro, $empresaId));
                
                // Actualizar tabla codigoslibros_devolucion_son
                $modificados['codigoslibros_devolucion_son'] = array_merge($modificados['codigoslibros_devolucion_son'], $this->actualizarCodigosLibrosDevolucionSon($libro, $empresaId, $iDdocumentoDev, $documentoDev));

                // Actualizar stock de productos
                $modificados['stock_producto'] = array_merge($modificados['stock_producto'], $this->actualizarStockProducto($libro, $empresaId));

                // Actualizar Usuario Modificador sin empresa
                $modificados['usuario_modificador'] = array_merge($modificados['usuario_modificador'], $this->actualizarUsuarioModificador($usuario, $empresaId, $iDdocumentoDev, $documentoDev));

            }

            // Si todo ha ido bien, hacer commit de la transacción
            DB::commit();

            return response()->json([
                'message' => 'Datos actualizados correctamente',
                'modificados' => $modificados,
                'status' => '0'
            ]);

        } catch (\Exception $e) {
            // En caso de error, hacer rollback y lanzar la excepción
            DB::rollBack();

            // Loguear el error para ayudar a la depuración
            \Log::error('Error al actualizar datos de devolución: ' . $e->getMessage());

            return response()->json([
                'message' => 'Hubo un error al actualizar los datos.',
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    private function actualizarUsuarioModificador($usuario, $empresaId, $iDdocumentoDev, $documentoDev)
    {
        $modificados = [];

        // Buscar el registro en usuarios
        $usuarioModificador = DB::table('codigoslibros_devolucion_header')
            ->where('id', $iDdocumentoDev)
            ->where('codigo_devolucion', $documentoDev)
            ->first();

        if ($usuarioModificador) {
            // Guardar los datos modificados antes de hacer el update
            $modificados[] = [
                'documento' => $usuarioModificador->codigo_devolucion,
            ];

            // Realizar la actualización en la tabla usuarios
            DB::table('codigoslibros_devolucion_header')
                ->where('id', $usuarioModificador->id)
                ->where('codigo_devolucion', $usuarioModificador->codigo_devolucion)
                ->update([
                    'user_dev_sin_empresa' => $usuario
                ]);
        }

        return $modificados;
    }

    private function actualizarCodigosLibros($libro, $empresaId, $documentoDev)
    {
        $modificados = [];

        // Iterar sobre cada 'codigo' y 'codigo_union' en 'libro['codigos']'
        foreach ($libro['codigos'] as $codigoData) {
            $codigo = $codigoData['codigo']; // Obtener el código normal
            $codigoUnion = $codigoData['codigo_union']; // Obtener el código unión

            // Ahora iteramos sobre 'detallesDevolucion' de cada libro
            foreach ($libro['detallesDevolucion'] as $devolucion) {
                $cantidadDevolucion = $devolucion['cantidadDevolucion'];

                // Buscar el registro en 'codigoslibros' usando 'codigo' y 'codigo_union' (relación directa)
                $codigoLibros = DB::table('codigoslibros')
                    ->where('codigo', $codigo)
                    ->where('codigo_union', $codigoUnion)
                    ->where('documento_devolucion', $documentoDev)
                    ->whereNull('codigo_proforma')  // Solo actualiza si codigo_proforma es null
                    ->whereNull('proforma_empresa')  // Solo actualiza si proforma_empresa es null
                    ->where('devuelto_proforma', 0)  // Solo actualiza si devuelto_proforma es 0
                    ->where('estado_liquidacion', 3)  // Solo actualiza si estado_liquidacion es 3 **devuelto**
                    ->first();

                // Buscar el registro en 'codigoslibros' usando 'codigo_union' y 'codigo' (relación inversa)
                $codigoLibrosUnion = DB::table('codigoslibros')
                    ->where('codigo', $codigoUnion)
                    ->where('codigo_union', $codigo)
                    ->where('documento_devolucion', $documentoDev)
                    ->whereNull('codigo_proforma')  // Solo actualiza si codigo_proforma es null
                    ->whereNull('proforma_empresa')  // Solo actualiza si proforma_empresa es null
                    ->where('devuelto_proforma', 0)  // Solo actualiza si devuelto_proforma es 0
                    ->where('estado_liquidacion', 3)  // Solo actualiza si estado_liquidacion es 3 **devuelto**
                    ->first();


                // Si encontramos el registro con la relación directa
                if ($codigoLibros) {
                    // Guardar los datos modificados antes de hacer el update
                    $modificados[] = [
                        'codigo' => $codigoLibros->codigo,
                        'codigo_union' => $codigoLibros->codigo_union,
                        'codigo_proforma' => $codigoLibros->codigo_proforma,
                        'devuelto_proforma' => $codigoLibros->devuelto_proforma,
                        'documento_devolucion' => $codigoLibros->documento_devolucion
                    ];

                    // Realizar la actualización en la tabla 'codigoslibros'
                    DB::table('codigoslibros')
                        ->where('codigo', $codigoLibros->codigo)
                        ->where('codigo_union', $codigoLibros->codigo_union)
                        ->update([
                            'codigo_proforma' => $devolucion['ven_codigo'],
                            'proforma_empresa' => $empresaId,
                            'documento_devolucion' => $documentoDev,
                            'devuelto_proforma' => 1,
                    ]);
                }

                // Si encontramos el registro con la relación inversa
                if ($codigoLibrosUnion) {
                    // Guardar los datos modificados antes de hacer el update
                    $modificados[] = [
                        'codigo' => $codigoLibrosUnion->codigo,
                        'codigo_union' => $codigoLibrosUnion->codigo_union,
                        'codigo_proforma' => $codigoLibrosUnion->codigo_proforma,
                        'devuelto_proforma' => $codigoLibrosUnion->devuelto_proforma,
                        'documento_devolucion' => $codigoLibrosUnion->documento_devolucion
                    ];

                    // Realizar la actualización en la tabla 'codigoslibros'
                    DB::table('codigoslibros')
                        ->where('codigo', $codigoLibrosUnion->codigo)
                        ->where('codigo_union', $codigoLibrosUnion->codigo_union)
                        ->update([
                            'codigo_proforma' => $devolucion['ven_codigo'],
                            'proforma_empresa' => $empresaId,
                            'documento_devolucion' => $documentoDev,
                            'devuelto_proforma' => 1,
                    ]);
                }
            }
        }

        return $modificados;
    }

    private function actualizarDetalleVenta($libro, $empresaId)
    {
        $modificados = [];

        foreach ($libro['detallesDevolucion'] as $devolucion) {
            $venCodigo = $devolucion['ven_codigo'];
            $cantidadDevolucion = $devolucion['cantidadDevolucion'];
            $codigo = $libro['codigo'];

            // Buscar el registro en f_detalle_venta
            $detalleVenta = DB::table('f_detalle_venta')
                ->where('ven_codigo', $venCodigo)
                ->where('id_empresa', $empresaId)
                ->where('pro_codigo', $codigo)
                ->first();

            if ($detalleVenta) {
                // Guardar los datos modificados antes de hacer el update
                $modificados[] = [
                    'ven_codigo' => $detalleVenta->ven_codigo,
                    'det_ven_codigo' => $detalleVenta->det_ven_codigo,
                    'det_ven_dev' => $detalleVenta->det_ven_dev
                ];

                // Calcular nueva cantidad de devolución
                $nuevaCantidadDevolucion = $detalleVenta->det_ven_dev + $cantidadDevolucion;

                // Realizar la actualización
                DB::table('f_detalle_venta')
                    ->where('det_ven_codigo', $detalleVenta->det_ven_codigo)
                    ->update([
                        'det_ven_dev' => $nuevaCantidadDevolucion
                    ]);
            }
        }

        return $modificados;
    }

    // private function actualizarCodigosLibrosDevolucionSon($libro, $empresaId, $iDdocumentoDev, $documentoDev)
    // {
    //     $modificados = []; // Para guardar los registros modificados

    //     // Iteramos sobre los detallesDevolucion del libro
    //     $codigosFiltrados = []; // Inicializamos el arreglo de códigos filtrados

    //     // foreach ($libro['codigos'] as $codigo) {
    //     //     return $codigo['codigo'];
    //     //     //CLI-5APRFMZ
    //     //     //ICLI
    //     //     // Verificamos si el código pertenece al 'codigoAfectado'
    //     //     if (strpos($codigo['codigo'], $libro['codigo']) !== false) {
    //     //         $codigosFiltrados[] = $codigo;
    //     //     }
    //     // }
    //     $indiceCodigo = 0; // Inicializamos el índice del código

    //     foreach ($libro['detallesDevolucion'] as $devolucion) {
    //         $venCodigo = $devolucion['ven_codigo']; // El ven_codigo que está asociado con la devolución
    //         $cantidadDevolucion = $devolucion['cantidadDevolucion']; // Cantidad de devoluciones

    //         for ($i = 0; $i < $cantidadDevolucion; $i++) {
    //             if ($indiceCodigo >= count($codigosFiltrados)) {
    //                 break; // Si se ha agotado la lista de códigos, salimos del ciclo
    //             }

    //             $codigo = $codigosFiltrados[$indiceCodigo];

    //             // Realizamos la actualización en la base de datos para cada código
    //             DB::table('codigoslibros_devolucion_son')
    //                 ->where('codigoslibros_devolucion_id', $iDdocumentoDev)
    //                 ->where('codigo', $codigo['codigo'])
    //                 ->where('codigo_union', $codigo['codigo_union'])
    //                 ->whereNull('id_empresa') // Solo actualizamos si 'id_empresa' es null
    //                 ->update([
    //                     'id_empresa' => $empresaId,
    //                     'documento' => $venCodigo, // Asignamos el ven_codigo correcto
    //                 ]);

    //             // Guardamos los registros modificados para retornarlos después
    //             $modificados[] = [
    //                 'codigo' => $codigo['codigo'],
    //                 'codigo_union' => $codigo['codigo_union'],
    //                 'documento' => $venCodigo,
    //             ];

    //             $indiceCodigo++; // Incrementamos el índice del código
    //         }
    //     }

    //     return $modificados; // Devolvemos los registros modificados
    // }
    private function actualizarCodigosLibrosDevolucionSon($libro, $empresaId, $iDdocumentoDev, $documentoDev)
    {
        $modificados = []; // Para guardar los registros modificados
        $indiceCodigo = 0; // Inicializamos el índice del código

        foreach ($libro['detallesDevolucion'] as $devolucion) {
            $venCodigo = $devolucion['ven_codigo']; // El ven_codigo que está asociado con la devolución
            $cantidadDevolucion = $devolucion['cantidadDevolucion']; // Cantidad de devoluciones

            for ($i = 0; $i < $cantidadDevolucion; $i++) {
                // Aquí es donde usábamos los $codigosFiltrados, pero lo hemos eliminado
                if ($indiceCodigo >= count($libro['codigos'])) {
                    break; // Si no hay más códigos, salimos del ciclo
                }

                $codigo = $libro['codigos'][$indiceCodigo]; // Usamos directamente los codigos del libro

                // Realizamos la actualización en la base de datos para cada código
                DB::table('codigoslibros_devolucion_son')
                    ->where('codigoslibros_devolucion_id', $iDdocumentoDev)
                    ->where('codigo', $codigo['codigo'])
                    ->where('codigo_union', $codigo['codigo_union'])
                    ->whereNull('id_empresa') // Solo actualizamos si 'id_empresa' es null
                    ->update([
                        'id_empresa' => $empresaId,
                        'documento' => $venCodigo, // Asignamos el ven_codigo correcto
                    ]);

                // Guardamos los registros modificados para retornarlos después
                $modificados[] = [
                    'codigo' => $codigo['codigo'],
                    'codigo_union' => $codigo['codigo_union'],
                    'documento' => $venCodigo,
                ];

                $indiceCodigo++; // Incrementamos el índice del código
            }
        }

        return $modificados; // Devolvemos los registros modificados
    }

    private function actualizarStockProducto($libro, $empresaId)
    {
        $modificados = [];

        // Primero, consultamos la venta (f_venta) para obtener la información necesaria
        $detallesDevolucion = $libro['detallesDevolucion'];
        foreach ($detallesDevolucion as $devolucion) {
            $cantidad = $devolucion['cantidadDevolucion'];
            $producto = $libro['codigo'];
            $venCodigo = $devolucion['ven_codigo'];

            // Obtener la venta relacionada
            $venta = DB::table('f_venta')
                ->where('ven_codigo', $venCodigo)
                ->where('id_empresa', $empresaId)
                ->first();

            if ($venta) {
                // Ahora consultamos el producto desde la tabla 1_4_cal_producto
                $productoActual = DB::table('1_4_cal_producto')
                    ->where('pro_codigo', $producto) // Tomamos el código del producto enviado
                    ->first();

                if ($productoActual) {

                    $updateData = [];

                    // Sumar cantidad al stock dependiendo del tipo de documento
                    if ($venta->idtipodoc == 1 && $venta->id_empresa == 1) {
                        $updateData['pro_stock'] = $productoActual->pro_stock + $cantidad;
                    } elseif (in_array($venta->idtipodoc, [2, 3, 4]) && $venta->id_empresa == 1) {
                        $updateData['pro_deposito'] = $productoActual->pro_deposito + $cantidad;
                    } elseif ($venta->idtipodoc == 1 && $venta->id_empresa == 3) {
                        $updateData['pro_stockCalmed'] = $productoActual->pro_stockCalmed + $cantidad;
                    } elseif (in_array($venta->idtipodoc, [2, 3, 4]) && $venta->id_empresa == 3) {
                        $updateData['pro_depositoCalmed'] = $productoActual->pro_depositoCalmed + $cantidad;
                    }

                    // Siempre se suma la cantidad a pro_reservar
                    $updateData['pro_reservar'] = $productoActual->pro_reservar + $cantidad;

                    // Realizamos el update en la tabla 1_4_cal_producto
                    DB::table('1_4_cal_producto')
                        ->where('pro_codigo', $producto)
                        ->update($updateData);

                    // Guardamos los productos modificados para respuesta
                    $modificados[] = [
                        'pro_codigo' => $producto,
                        'modificados' => $updateData
                    ];
                }
            }
        }


        return $modificados;
    }

    public function CambioClienteDevolucion(Request $request)
    {
        $request->validate([
            'codigo_devolucion' => 'required|string',
            'nuevo_id_cliente' => 'required|integer',
            'usuario' => 'required|integer'
        ]);
    
        $codigoDevolucion = $request->codigo_devolucion;
        $nuevoIdCliente = $request->nuevo_id_cliente;
        $usuario = $request->usuario;
        $observacion = $request->observacion;
    
        try {
            DB::transaction(function () use ($codigoDevolucion, $nuevoIdCliente, $usuario, $observacion) {

                $f_ventaValidacion = DB::table('f_venta')
                    ->where('institucion_id', $nuevoIdCliente)
                    ->where('est_ven_codigo', '<>', 3)
                    ->count();

                if ($f_ventaValidacion <= 0) {
                    throw new \Exception('No existe documento de venta para el cliente.');
                }

                // Buscar la cabecera de devolución activa
                $header = CodigosLibrosDevolucionHeader::where('codigo_devolucion', $codigoDevolucion)
                    ->where('estado', 1)
                    ->first();
    
                if (!$header) {
                    throw new \Exception('No se encontró el código de devolución.');
                }
    
                $header->id_cliente = $nuevoIdCliente;
                $header->intercambio_cliente = 1;
                $header->fecha_intercambio_cliente = now();
                $header->user_intercambio_cliente = $usuario;
                $header->observacion_intercambio_cliente = $observacion;
                if (!$header->save()) {
                    throw new \Exception('Error al actualizar la cabecera de devolución.');
                }
    
                // Actualizar en codigoslibros_devolucion_son
                $updatedSon = CodigosLibrosDevolucionSon::where('codigoslibros_devolucion_id', $header->id)
                    ->update(['id_cliente' => $nuevoIdCliente]);
    
                // Actualizar en codigoslibros_devolucion_header_facturador solo si existen registros
                if (CodigosLibrosDevolucionHeaderFacturador::where('codigoslibros_devolucion_header_id', $header->id)->exists()) {
                    CodigosLibrosDevolucionHeaderFacturador::where('codigoslibros_devolucion_header_id', $header->id)
                        ->update(['id_cliente' => $nuevoIdCliente]);
                }
    
                // Validar que la actualización en `codigoslibros_devolucion_son` se realizó correctamente
                if ($updatedSon === false) {
                    throw new \Exception('No se pudo actualizar todos los registros correctamente.');
                }
            });
    
            return response()->json([
                'message' => 'Cliente actualizado correctamente.',
                'status' => 1
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 0,
                'line' => $e->getLine()
            ]);
        }
    }
    
    
    
    public function InstitucionesCambio(Request $request)
    {
        $request->validate([
            'busqueda' => 'nullable|string'
        ]);

        $busqueda = $request->busqueda;

        $query = DB::table('institucion as i')
            ->leftJoin('ciudad as c', 'i.ciudad_id', '=', 'c.idciudad')
            ->select('i.idInstitucion', 'i.nombreInstitucion', 'i.ruc', 'i.email', 
                    'i.telefonoInstitucion', 'i.direccionInstitucion', 'c.nombre as ciudad')
            ->when($busqueda, function ($query, $busqueda) {
                return $query->where('i.nombreInstitucion', 'LIKE', "%$busqueda%");
            })
            ->get();

        return response()->json($query);
    }

}

