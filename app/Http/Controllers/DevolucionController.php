<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CodigosLibros;
use App\Models\CodigosLibrosDevolucionHeader;
use App\Models\CodigosLibrosDevolucionSon;
use App\Repositories\Facturacion\ProformaRepository;
use App\Repositories\pedidos\PedidosRepository;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;
use DB;
class DevolucionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $proformaRepository;
    protected $pedidosRepository;
    public function __construct(ProformaRepository $proformaRepository, PedidosRepository $pedidosRepository)
    {
        $this->proformaRepository = $proformaRepository;
        $this->pedidosRepository    = $pedidosRepository;
    }
    //API:GET/devoluciones
    public function index(Request $request)
    {
        if($request->listadoProformasAgrupadas)          { return $this->listadoProformasAgrupadas($request); }
        if($request->filtroDocumentosDevueltos)          { return $this->filtroDocumentosDevueltos($request); }
        if($request->getCodigosxDocumentoDevolucion)     { return $this->getCodigosxDocumentoDevolucion($request); }
       if($request->todoDevolucionCliente)  { return $this->todoDevolucionCliente($request); }
       if($request->devolucionDetalle)  { return $this->devolucionDetalle($request); }
       if($request->CargarDevolucion)  { return $this->CargarDevolucion($request); }
       if($request->CargarDocumentos)  { return $this->CargarDocumentos($request); }
       if($request->CargarDocumentosDetalles)  { return $this->CargarDocumentosDetalles($request); }
       if($request->CargarDetallesDocumentos)  { return $this->CargarDetallesDocumentos($request); }
       if($request->documentoExiste)  { return $this->verificarDocumento($request); }
       
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
        }
        $resultado = collect($getProformas);
        //filtrar por ifPedidoPerseo igual a 0
        $resultado = $resultado->where('ifPedidoPerseo','0')->values();
        return $resultado;
    }
    //api:get/devoluciones?filtroDocumentosDevueltos=1&fechaInicio=2024-10-01&fechaFin=2024-10-06
    public function filtroDocumentosDevueltos(Request $request)
    {
        $fechaInicio    = $request->input('fechaInicio');
        $fechaFin       = $request->input('fechaFin');
        $id_cliente     = $request->input('id_cliente');
        $revisados      = $request->input('revisados');
        $finalizados    = $request->input('finalizados');

        if ($fechaFin) {
            $fechaFin = date('Y-m-d', strtotime($fechaFin)) . ' 23:59:59';
        }

        $getDocumentos = CodigosLibrosDevolucionHeader::with([
            'institucion:idInstitucion,nombreInstitucion',
            'usuario:idusuario,nombres,apellidos',
            'usuarioRevision:idusuario,nombres,apellidos',
            'usuarioFinalizacion:idusuario,nombres,apellidos',
            'devolucionSon' => function ($query) {
                $query->where('prueba_diagnostico', '0');
            }
        ])
        ->when($fechaInicio && $fechaFin, function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        })
        ->when($id_cliente, function ($query) use ($id_cliente) {
            $query->where('id_cliente', $id_cliente);
        })
        ->when($revisados, function ($query) use ($revisados) {
            $query->where('estado', 1);
        })
        ->when($finalizados, function ($query) use ($finalizados) {
            $query->where('estado', 2);
        })
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($documento) {
            return [
                'id'                    => $documento->id,
                'id_cliente'            => $documento->id_cliente,
                'codigo_devolucion'     => $documento->codigo_devolucion,
                'observacion'           => $documento->observacion,
                'codigo_nota_credito'   => $documento->codigo_nota_credito,
                'created_at'            => $documento->created_at_formatted,
                'estado'                => $documento->estado,
                'fecha_revisado'        => $documento->fecha_revisado,
                'fecha_finalizacion'    => $documento->fecha_finalizacion,
                'institucion'           => $documento->institucion,
                'usuario'               => [
                    'nombres'           => $documento->usuario->nombres ?? null,
                    'apellidos'         => $documento->usuario->apellidos ?? null
                ],
                'usuario_revision'      => [
                    'nombres'           => $documento->usuarioRevision->nombres ?? null,
                    'apellidos'         => $documento->usuarioRevision->apellidos ?? null
                ],
                'usuario_finalizacion'  => [
                    'nombres'           => $documento->usuarioFinalizacion->nombres ?? null,
                    'apellidos'         => $documento->usuarioFinalizacion->apellidos ?? null
                ],
                'cantidadHijos'         => count($documento->devolucionSon),
                // 'devolucionSon'         => $documento->devolucionSon,
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
        $getCodigos = CodigosLibrosDevolucionSon::query()
            ->leftJoin('codigoslibros', 'codigoslibros.codigo', '=', 'codigoslibros_devolucion_son.codigo')
            ->leftJoin('libro', 'libro.idlibro', '=', 'codigoslibros.libro_idlibro')
            ->leftJoin('libros_series','libros_series.idLibro', '=', 'libro.idlibro')
            ->where('codigoslibros_devolucion_id', $id_documento)
            ->where('codigoslibros_devolucion_son.prueba_diagnostico','0')
            ->when($revisados, function ($query) use ($revisados) {
                $query->where('codigoslibros_devolucion_son.estado', 1);
            })
            ->when($finalizados, function ($query) use ($finalizados) {
                $query->where('codigoslibros_devolucion_son.estado', 2);
            })
            ->select('codigoslibros_devolucion_son.*', 'codigoslibros.estado_liquidacion','codigoslibros.liquidado_regalado','libro.nombrelibro','libros_series.codigo_liquidacion')
            ->get();
        $resultado = [];
        if ($agrupar == 1) {
            // Agrupar por nombre libro y contar cuántas veces se repite
            $resultado = collect($getCodigos)->groupBy('nombrelibro')->map(function ($item) {
                return [
                    'nombrelibro'        => $item[0]->nombrelibro,
                    'codigo'             => $item[0]->codigo_liquidacion,
                    'cantidad'           => count($item),
                ];
            })->values(); // Esto convertirá el resultado a un array normal
        } else {
            $resultado = $getCodigos;
        }
        
        return $resultado;
    }
    //api:get/devoluciones?devolucionCliente=yes
    public function devolucionCliente(Request $request)
    {       
        $query = DB::SELECT("SELECT DISTINCT i.nombreInstitucion AS cliente, cl.id_cliente FROM codigoslibros_devolucion_header cl
            INNER JOIN institucion i ON i.idInstitucion = cl.id_cliente
            WHERE i.nombreInstitucion LIKE '%$request->busqueda%'
        ");
        return $query;
    }
    //api:get/devoluciones?todoDevolucionCliente=yes
    public function todoDevolucionCliente(Request $request)
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
            fv.ven_fecha, fv.user_created, fv.ven_valor, ins.nombreInstitucion, ins.direccionInstitucion, ins.telefonoInstitucion, ins.asesor_id, 
            CONCAT(u.nombres, ' ', u.apellidos) AS asesor,
            usa.nombres, usa.apellidos, fpr.prof_observacion, fpr.idPuntoventa,
            COUNT(DISTINCT dfv.pro_codigo) AS item, CONCAT(us.nombres, ' ', us.apellidos) AS responsable,
            (SELECT SUM(det_ven_cantidad) FROM f_detalle_venta WHERE ven_codigo = fv.ven_codigo AND id_empresa = fv.id_empresa) AS libros,
            fv.ruc_cliente AS cedula, usa.email, usa.telefono,fv.idtipodoc, em.id AS empresa_id, fv.ven_tipo_inst, fv.ven_idproforma, fv.ven_observacion,
            fv.ven_subtotal, fv.ven_desc_por, fv.ven_descuento, fv.ven_iva, fv.ven_transporte
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
            WHERE fv.ven_codigo = '$request->documentos' AND fv.est_ven_codigo <> 3
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
        $query = DB::SELECT("SELECT dv.det_ven_codigo, dv.pro_codigo, dv.det_ven_dev, dv.det_ven_cantidad, dv.det_ven_valor_u,
            l.descripcionlibro, ls.nombre, s.nombre_serie, ls.id_serie FROM f_detalle_venta AS dv
            INNER JOIN f_venta AS fv ON dv.ven_codigo=fv.ven_codigo
            INNER JOIN libros_series AS ls ON dv.pro_codigo=ls.codigo_liquidacion
            INNER JOIN series AS s ON ls.id_serie=s.id_serie
            INNER JOIN libro l ON ls.idLibro = l.idlibro
            WHERE dv.ven_codigo='$request->codigo' AND dv.id_empresa=fv.id_empresa
            AND fv.id_empresa= $request->empresa ORDER BY dv.pro_codigo");
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
        if($request->devolverDocumentoBodega)   { return $this->devolverDocumentoBodega($request); }
        if($request->updateDocumentoDevolucion) { return $this->updateDocumentoDevolucion($request); }
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
            // Transacción
            DB::beginTransaction();
            
            $id_documento   = $request->input('id_documento');
            $revision       = $request->input('revision');
            $finalizacion   = $request->input('finalizacion');
            $id_usuario     = $request->input('id_usuario');
    
            if ($revision) {
                $updateData['estado'] = 1;
                $updateData['user_created_revisado'] = $id_usuario;
                $updateData['fecha_revisado'] = now();
            }
    
            if ($finalizacion) {
                $updateData['estado'] = 2;
                $updateData['user_created_finalizado'] = $id_usuario;
                $updateData['fecha_finalizacion'] = now();
            }
    
            // Actualizar el encabezado
            CodigosLibrosDevolucionHeader::where('id', $id_documento)->update($updateData);
    
            // Actualizar la tabla codigoslibros_devolucion_son
            CodigosLibrosDevolucionSon::where('codigoslibros_devolucion_id', $id_documento)
                ->where('estado', '<>', '0')
                ->update(['estado' => $revision ? 1 : 2]);
    
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
}
