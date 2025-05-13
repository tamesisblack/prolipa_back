<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CodigosLibros;
use App\Models\CodigosLibrosDevolucionSon;
use App\Models\DetalleVentas;
use App\Models\f_tipo_documento;
use App\Models\Periodo;
use App\Models\Ventas;
use App\Repositories\Facturacion\ProformaRepository;
use App\Traits\Codigos\TraitCodigosGeneral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class PrefacturaController extends Controller
{
    use TraitCodigosGeneral;
    protected $proformaRepository;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(ProformaRepository $proformaRepository)
    {
        $this->proformaRepository   = $proformaRepository;
    }
    //api:get/prefactura_documentos
    public function index(Request $request)
    {
        if($request->getReportePrefacturaAgrupado) { return $this->getReportePrefacturaAgrupado($request); }
        if($request->notasMovidasAnteriores)       { return $this->getNotasMovidasAnteriores($request); }
        if($request->getPrefacturasMovidas)       { return $this->getPrefacturasMovidas($request); }
    }
    //api:get/prefactura_documentos?getReportePrefacturaAgrupado=1&periodo_id=25&tipoVenta=1
    public function getReportePrefacturaAgrupado(Request $request)
    {
        try {
            $periodo_id         = $request->get('periodo_id');
            $tipoVenta          = $request->get('tipoVenta');
            $empresa            = 1; // O el valor que necesites
            $tipoInstitucion    = $tipoVenta == 1 ? 0 : 1; // 0 para directa, 1 para punto de venta
            
            // Valida que se envíe el periodo_id
            if (!$periodo_id) {
                return $this->response()->setStatusCode(200)->json(['error' => 'Falta el periodo_id']);
            }
            
            // Obtén las instituciones
            $getInstituciones = $this->proformaRepository->listadoInstitucionesXVenta($periodo_id, $empresa, $tipoInstitucion);
            if (count($getInstituciones) == 0) {
                return $this->response()->setStatusCode(200)->json(['error' => 'No se encontraron instituciones']);
            }

            // Inicializamos los arrays para los productos agrupados por precio
            $productosAgrupadosPorPrecioReal = [];
            $productosAgrupadosPorPrecioPerseo = [];

            foreach ($getInstituciones as $key => $item) {
                // Obtener los datos de venta PREFACTURAS
                $getDatosVenta = $this->proformaRepository->listadoDocumentosVenta($periodo_id, $empresa, $tipoInstitucion, $item->institucion_id, [1]);
                
                // Obtener los contratos de la institucion
                $getContratos = $this->proformaRepository->listadoContratosAgrupadoInstitucion($getDatosVenta );

                // Obtener los datos de venta AGRUPADOS
                $getDatosVentaAgrupado = $this->proformaRepository->listadoDocumentosAgrupado($periodo_id, $empresa, $tipoInstitucion, $item->institucion_id, [1]);
                // Inicializamos los arrays para cada institución
                $resultadosUnicos = [];
                $agrupadosPorProCodigo = [];
                $agrupadosPorProCodigoAgrupado = [];
                $resultadoProductos = [];

                // Agrupamos datos de getDatosVenta
                foreach ($getDatosVenta as $documento) {
                    $venCodigo = $documento->ven_codigo;
                    $idEmpresa = $documento->id_empresa;
                    $proCodigo = $documento->pro_codigo;
                    $detVenCantidad = $documento->det_ven_cantidad;
                    $detVenDev = $documento->det_ven_dev;
                    $detVenValorU = $documento->det_ven_valor_u;
                    $nombreSerie = $documento->nombre_serie;

                    // Resultados únicos por ven_codigo e id_empresa
                    $resultadosUnicos["$venCodigo-$idEmpresa"] = [
                        'ven_codigo' => $venCodigo,
                        'id_empresa' => $idEmpresa
                    ];

                    // Agrupamos por pro_codigo (getDatosVenta)
                    if (isset($agrupadosPorProCodigo[$proCodigo])) {
                        $agrupadosPorProCodigo[$proCodigo]['det_ven_cantidad'] += $detVenCantidad;
                        $agrupadosPorProCodigo[$proCodigo]['cantidad_real'] += ($detVenCantidad - $detVenDev);
                        $agrupadosPorProCodigo[$proCodigo]['det_ven_dev'] += $detVenDev;
                    } else {
                        $agrupadosPorProCodigo[$proCodigo] = [
                            'proCodigo' => $proCodigo,
                            'det_ven_cantidad' => $detVenCantidad,
                            'cantidad_real' => $detVenCantidad - $detVenDev,
                            'det_ven_valor_u' => $detVenValorU,
                            'nombre_serie' => $nombreSerie,
                            'det_ven_dev' => $detVenDev
                        ];
                    }
                    
                    // Agrupamos por precio (det_ven_valor_u) y nombre_serie para cantidad real
                    $clave = $detVenValorU . '|' . $nombreSerie;

                    if (isset($productosAgrupadosPorPrecioReal[$clave])) {
                        $productosAgrupadosPorPrecioReal[$clave]['cantidad_real'] += ($detVenCantidad - $detVenDev);
                    } else {
                        $productosAgrupadosPorPrecioReal[$clave] = [
                            'det_ven_valor_u' => $detVenValorU,
                            'nombre_serie' => $nombreSerie,
                            'cantidad_real' => ($detVenCantidad - $detVenDev)
                        ];
                    }

                }

                // Agrupamos datos de getDatosVentaAgrupado
                foreach ($getDatosVentaAgrupado as $documento) {
                    $proCodigo = $documento->pro_codigo;
                    $detVenCantidad = $documento->det_ven_cantidad;
                    $detVenValorU = $documento->det_ven_valor_u;
                    $nombreSerie = $documento->nombre_serie;

                    // Agrupamos por pro_codigo (getDatosVentaAgrupado)
                    if (isset($agrupadosPorProCodigoAgrupado[$proCodigo])) {
                        $agrupadosPorProCodigoAgrupado[$proCodigo]['det_ven_cantidad'] += $detVenCantidad;
                    } else {
                        $agrupadosPorProCodigoAgrupado[$proCodigo] = [
                            'proCodigo' => $proCodigo,
                            'det_ven_cantidad' => $detVenCantidad,
                            'det_ven_valor_u' => $detVenValorU,
                            'nombre_serie' => $nombreSerie
                        ];
                    }

                   
                    // Agrupamos por precio (det_ven_valor_u) y nombre_serie para cantidad perseo
                    $clave = $detVenValorU . '|' . $nombreSerie;

                    if (isset($productosAgrupadosPorPrecioPerseo[$clave])) {
                        $productosAgrupadosPorPrecioPerseo[$clave]['cantidad_perseo'] += $detVenCantidad;
                    } else {
                        $productosAgrupadosPorPrecioPerseo[$clave] = [
                            'det_ven_valor_u' => $detVenValorU,
                            'nombre_serie' => $nombreSerie,
                            'cantidad_perseo' => $detVenCantidad
                        ];
                    }
                }

                // Asignamos los resultados únicos de ven_codigo y id_empresa
                $item->prefacturas = array_values($resultadosUnicos);

                // Asignamos los contratos de la institucion
                $item->contratos = $getContratos;

                // Asignamos los productos agrupados por pro_codigo (getDatosVenta)
                $item->productos_prefactura = array_values($agrupadosPorProCodigo);

                // Asignamos los productos agrupados por pro_codigo (getDatosVentaAgrupado)
                $item->productos_agrupados = array_values($agrupadosPorProCodigoAgrupado);

                // Combinamos los productos de ambos arrays
                $todosLosProductos = array_merge($agrupadosPorProCodigo, $agrupadosPorProCodigoAgrupado);
                // Combinamos los productos
                foreach ($todosLosProductos as $proCodigo => $producto) {
                    // Si el producto existe en ambos arrays, lo unimos
                    $resultadoProductos[$proCodigo] = [
                        'proCodigo' => $proCodigo,
                        // Si el producto está en $agrupadosPorProCodigo, asignamos su cantidad real, sino a 0
                        'cantidad_real' => isset($agrupadosPorProCodigo[$proCodigo]) ? $agrupadosPorProCodigo[$proCodigo]['cantidad_real'] : 0,
                        // Si el producto está en $agrupadosPorProCodigoAgrupado, asignamos su cantidad, sino a 0
                        'cantidad_perseo' => isset($agrupadosPorProCodigoAgrupado[$proCodigo]) ? $agrupadosPorProCodigoAgrupado[$proCodigo]['det_ven_cantidad'] : 0,
                        // Valor unitario del producto (del primer array que tenga el producto)
                        'det_ven_valor_u' => isset($agrupadosPorProCodigo[$proCodigo]) ? $agrupadosPorProCodigo[$proCodigo]['det_ven_valor_u'] : (isset($agrupadosPorProCodigoAgrupado[$proCodigo]) ? $agrupadosPorProCodigoAgrupado[$proCodigo]['det_ven_valor_u'] : 0),
                        // Serie
                        'nombre_serie' => isset($agrupadosPorProCodigo[$proCodigo]) ? $agrupadosPorProCodigo[$proCodigo]['nombre_serie'] : (isset($agrupadosPorProCodigoAgrupado[$proCodigo]) ? $agrupadosPorProCodigoAgrupado[$proCodigo]['nombre_serie'] : 0),
                        //det_ven_dev devolucion
                        'det_ven_dev' => isset($agrupadosPorProCodigo[$proCodigo]) ? $agrupadosPorProCodigo[$proCodigo]['det_ven_dev'] : 0,
                        //det_ven_cantidad
                        'det_ven_cantidad' => isset($agrupadosPorProCodigo[$proCodigo]) ? $agrupadosPorProCodigo[$proCodigo]['det_ven_cantidad'] : (isset($agrupadosPorProCodigoAgrupado[$proCodigo]) ? $agrupadosPorProCodigoAgrupado[$proCodigo]['det_ven_cantidad'] : 0)
                    ];
                }

                // Asignamos los productos combinados con cantidad_real de ambos arrays
                $item->resultadoProductos = array_values($resultadoProductos);
            }

            // Excluimos los productos con precio cero
            $productosAgrupadosPorPrecioReal = array_filter($productosAgrupadosPorPrecioReal, function($producto) {
                return $producto['det_ven_valor_u'] > 0;
            });

            $productosAgrupadosPorPrecioPerseo = array_filter($productosAgrupadosPorPrecioPerseo, function($producto) {
                return $producto['det_ven_valor_u'] > 0;
            });

            // Ordenamos los productos agrupados por precio real en orden ascendente
            usort($productosAgrupadosPorPrecioReal, function($a, $b) {
                return $a['det_ven_valor_u'] <=> $b['det_ven_valor_u'];  // Orden ascendente
            });

            // Ordenamos los productos agrupados por precio perseo en orden ascendente
            usort($productosAgrupadosPorPrecioPerseo, function($a, $b) {
                return $a['det_ven_valor_u'] <=> $b['det_ven_valor_u'];  // Orden ascendente
            });

            return [
                'getInstituciones' => $getInstituciones,
                'productos_agrupados_por_precio_real' => $productosAgrupadosPorPrecioReal,
                'productos_agrupados_por_precio_perseo' => $productosAgrupadosPorPrecioPerseo
            ];
        }
        catch (\Exception $e) {
            return ['status' => '0', 'message' => $e->getMessage()];
        }
    }

      // $notas = DB::table('f_venta_historico_notas_cambiadas')->get();

        // foreach ($notas as $nota) {
        //     // Mostrar la observacion para ver qué contiene
        //     echo "Observación: " . $nota->observacion . "\n"; // Verificar la cadena

        //     // Usamos preg_match para extraer la parte entre "Se movió de la nota" y "a la prefactura"
        //     if (preg_match('/se\s*movi[oó]\s*de\s*la\s*nota\s*(N-C-[^ ]+)/i', $nota->observacion, $matches)) {
        //         // Mostrar lo que captura la expresión regular
        //         echo "Coincidencia: " . $matches[1] . "\n";  // Esto debería imprimir el valor capturado

        //         // Si se encuentra una coincidencia, actualizamos el campo 'origen'
        //         DB::table('f_venta_historico_notas_cambiadas')
        //             ->where('id', $nota->id) // Asumiendo que tienes una columna 'id' en la tabla
        //             ->update(['origen' => $matches[1]]);
        //     } else {
        //         echo "No se encontró una coincidencia\n"; // Si no encuentra coincidencia
        //     }
        // }
    //api:get/prefactura_documentos?notasMovidasAnteriores=1&periodo_id=25
    public function getNotasMovidasAnteriores(Request $request)
    {
        // Obtener todos los registros con 'origen' no nulo
        $todas = DB::table('f_venta_historico_notas_cambiadas')
            ->whereNotNull('origen')
            ->where('id_periodo', $request->get('periodo_id'))
            ->get();

        // Obtener combinaciones únicas de 'origen', 'id_empresa' y 'nueva_prefactura'
        $notas = DB::table('f_venta_historico_notas_cambiadas as h')
            ->whereNotNull('h.origen')
            ->where('h.id_periodo', $request->get('periodo_id'))
            ->select('h.origen', 'h.id_empresa', 'h.nueva_prefactura')  // Seleccionamos los campos que necesitamos
            ->distinct()  // Nos aseguramos de que las combinaciones sean únicas
            ->get();
        foreach($notas as $item){
            $getDatosDocumentos = DB::SELECT("SELECT i.nombreInstitucion,
                CONCAT(u.nombres,' ', u.apellidos) AS cliente, u.cedula
                FROM f_venta v
                LEFT JOIN institucion i ON i.idInstitucion = v.institucion_id
                LEFT JOIN usuario u ON u.idusuario = v.ven_cliente
                WHERE v.ven_codigo = '$item->origen'
                AND v.id_empresa = '$item->id_empresa'
            ");
            if(count($getDatosDocumentos) > 0){
                $item->nombreInstitucion = $getDatosDocumentos[0]->nombreInstitucion;
                $item->cliente = $getDatosDocumentos[0]->cliente;
                $item->cedula = $getDatosDocumentos[0]->cedula;
            }else{
                $item->nombreInstitucion = null;
                $item->cliente = null;
                $item->cedula = null;
            }
        }
        // Retornar ambas consultas
        return [
            "todas" => $todas,
            "notas" => $notas
        ];
    }

    public function getPrefacturasMovidas(Request $request)
    {
        $periodoId = $request->get('periodo_id');

        // Obtener todos los registros de intercambio para los detalles
        $todas = DB::table('historico_intercambio_documentos')
            ->whereNotNull('ven_codigo_original')
            ->where('periodo_id', $periodoId)
            ->get();

        // Obtener agrupación por ven_codigo_intercambio
        $prefacturas = DB::table('historico_intercambio_documentos as h')
            ->whereNotNull('h.ven_codigo_intercambio')
            ->where('h.periodo_id', $periodoId)
            ->select('h.ven_codigo_intercambio', 'h.empresa_id', DB::raw('GROUP_CONCAT(h.ven_codigo_original) as ven_codigos_originales'))
            ->groupBy('h.ven_codigo_intercambio', 'h.empresa_id')
            ->get();

        foreach ($prefacturas as $item) {
            // Consultar información adicional
            $getDatosDocumentos = DB::SELECT("SELECT i.nombreInstitucion,
                CONCAT(u.nombres,' ', u.apellidos) AS cliente, u.cedula
                FROM f_venta v
                LEFT JOIN institucion i ON i.idInstitucion = v.institucion_id
                LEFT JOIN usuario u ON u.idusuario = v.ven_cliente
                WHERE v.ven_codigo = '$item->ven_codigo_intercambio'
                AND v.id_empresa = '$item->empresa_id'
            ");

            if (count($getDatosDocumentos) > 0) {
                $item->nombreInstitucion = $getDatosDocumentos[0]->nombreInstitucion;
                $item->cliente = $getDatosDocumentos[0]->cliente;
                $item->cedula = $getDatosDocumentos[0]->cedula;
            } else {
                $item->nombreInstitucion = null;
                $item->cliente = null;
                $item->cedula = null;
            }

            // Convertir la lista de ven_codigos_originales en un array
            $item->ven_codigos_originales = explode(',', $item->ven_codigos_originales);
        }

        return [
            "todas" => $todas,
            "prefacturas" => $prefacturas
        ];
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
    //api:post>>/notasMoverToPrefactura
    public function notasMoverToPrefactura(Request $request)
    {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $validator = Validator::make($request->all(), [
            'ven_codigo'   => 'required|string',
            'id_empresa'   => 'required|integer',
            'id_periodo'   => 'required|integer',
            'iniciales'    => 'required|string',
            'observacion'  => 'required|string|max:500', // Validación para máximo 500 caracteres
            'id_usuario'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => '0', 'message' => $validator->errors()->first()], 200);
        }
        $ven_codigoAnterior = $request->input('ven_codigo');
        $id_empresa         = $request->input('id_empresa');
        $id_periodo         = $request->input('id_periodo');
        $iniciales          = $request->input('iniciales');
        $observacion        = $request->input('observacion');
        $id_usuario         = $request->input('id_usuario');
        $letraDocumento     = "PF";
        $letraEmpresa       = $id_empresa == 1 ? "P" : "C";
        // Obtener el código de contrato del período
        $getPeriodo = Periodo::where('idperiodoescolar', $id_periodo)->first();
        if (!$getPeriodo) {
            return ["status" => "0", "mensaje" => "No existe el código para el período escolar"];
        }
        $codigo_contrato    = $getPeriodo->codigo_contrato;

        // Obtener nuevo número de documento
        $getNumeroDocumento = $this->proformaRepository->getNumeroDocumento($id_empresa);
        $nuevo_ven_codigo   = $letraDocumento . "-" . $letraEmpresa . "-" . $codigo_contrato . "-" . $iniciales . "-" . $getNumeroDocumento;

        try {
            // Iniciar la transacción
            DB::beginTransaction();
            // Buscar la venta existente
            $f_venta = DB::table('f_venta')
                ->where('id_empresa', $id_empresa)
                ->where('ven_codigo', $ven_codigoAnterior)
                // ->where('ven_valor','>',0)
                ->first();
            if (!$f_venta) {
                return ["status" => "0", "message" => "El registro de venta no existe."];
            }

            // Convertir el objeto en un array
            $f_ventaArray = (array)$f_venta;

            // Eliminar campos innecesarios
            unset($f_ventaArray['ven_fecha'], $f_ventaArray['updated_at']);
            $f_ventaArray['ven_codigo'] = $nuevo_ven_codigo; // Cambiar al nuevo código
            $f_ventaArray['idtipodoc']  = 1; // Cambiar a pre-factura
            $f_ventaArray['ven_fecha']  = now();

            // Insertar el nuevo registro
            $insertado = DB::table('f_venta')->insert($f_ventaArray);
            if (!$insertado) {
                return ["status" => "0", "message" => "No se pudo insertar el nuevo registro en f_venta."];
            }

            // Procesar los detalles de venta
            $f_detalle_venta = DB::table('f_detalle_venta')
                ->where('ven_codigo', $ven_codigoAnterior)
                ->where('id_empresa', $id_empresa)
                ->get();

            if ($f_detalle_venta->isEmpty()) {
                return ["status" => "0", "message" => "No se encontraron detalles para el ven_codigo: $ven_codigoAnterior."];
            }

            // Transformar y preparar los detalles
            $f_detalle_ventaArray = $f_detalle_venta->map(function ($detalle) use ($nuevo_ven_codigo) {
                $detalleArray = (array)$detalle;
                unset($detalleArray['det_ven_codigo'], $detalleArray['created_at'], $detalleArray['updated_at']);
                $detalleArray['ven_codigo'] = $nuevo_ven_codigo; // Cambiar al nuevo código
                return $detalleArray;
            })->toArray();

            // Insertar los nuevos detalles en la nueva pre factura
            $insertado_f_detalle_venta = DB::table('f_detalle_venta')->insert($f_detalle_ventaArray);
            if (!$insertado_f_detalle_venta) {
                return ["status" => "0", "message" => "No se pudieron insertar los detalles en f_detalle_venta."];
            }

            // Actualizar la nota de venta existente
            $filasActualizadasVenta = DB::table('f_venta')
                ->where('id_empresa', $id_empresa)
                ->where('ven_codigo', $ven_codigoAnterior)
                ->update([
                    'ven_valor'                 => 0,
                    'ven_subtotal'              => 0,
                    'ven_descuento'             => 0,
                    'doc_intercambio'           => $nuevo_ven_codigo,
                    'user_intercambio'          => $id_usuario,
                    'fecha_intercambio'         => now(),
                    'observacion_intercambio'   => $observacion,
                ]);

            if ($filasActualizadasVenta === 0) {
                return ["status" => "0", "message" => "No se actualizó f_venta con ven_codigo: $ven_codigoAnterior"];
            }

            // Actualizar los detalles existentes
            $filasActualizadasDetalle = DB::table('f_detalle_venta')
                ->where('ven_codigo', $ven_codigoAnterior)
                ->where('id_empresa', $id_empresa)
                ->update([
                    'det_ven_cantidad'          => 0,
                    'det_ven_cantidad_despacho' => 0,
                    'det_ven_dev'               => 0,
                ]);

            if ($filasActualizadasDetalle === 0) {
                return ["status" => "0", "message" => "No se actualizaron los detalles en f_detalle_venta con ven_codigo: $ven_codigoAnterior."];
            }
            // Mensaje histórico
            $mensajeHistorico = "Se movió la nota $ven_codigoAnterior a la prefactura $nuevo_ven_codigo";

            // Realizar la consulta en lotes (chunk) para evitar sobrecargar la memoria
            CodigosLibros::where('codigo_proforma', $ven_codigoAnterior)
                ->where('proforma_empresa', $id_empresa)
                ->chunk(1000, function ($registros) use ($id_usuario, $id_periodo, $mensajeHistorico) {
                    foreach ($registros as $itemCodigo) {
                        // Obtener el estado de la venta y la institución correspondiente
                        $venta_estado   = $itemCodigo->venta_estado;
                        $id_institucion = $venta_estado == 2 ? $itemCodigo->venta_lista_institucion : $itemCodigo->bc_institucion;

                        // Guardar en el historial
                        $this->GuardarEnHistorico(0, $id_institucion, $id_periodo, $itemCodigo->codigo, $id_usuario, $mensajeHistorico, null, null, null, null);
                    }
                });

            // Actualizar los códigos en la tabla CodigosLibros
            $existsCodigosLibros = CodigosLibros::where('codigo_proforma', $ven_codigoAnterior)
            ->where('proforma_empresa', $id_empresa)
            ->exists();

            if ($existsCodigosLibros) {
                $codigosLibros = CodigosLibros::where('codigo_proforma', $ven_codigoAnterior)
                    ->where('proforma_empresa', $id_empresa)
                    ->update([
                        'codigo_proforma' => $nuevo_ven_codigo,
                    ]);

                // Verificar si la actualización fue exitosa
                if ($codigosLibros === 0) {
                    return ["status" => "0", "message" => "No se actualizó el código en CodigosLibros con ven_codigo: $ven_codigoAnterior."];
                }
            }

            //Actualizar CodigosLibrosDevolucionSon donde documento este igual a ven_codigoAnterior y id_empresa igual a id_empresa
            $existsCodigosLibrosDevolucionSon = CodigosLibrosDevolucionSon::where('documento', $ven_codigoAnterior)
                ->where('id_empresa', $id_empresa)
                ->exists();

            if ($existsCodigosLibrosDevolucionSon) {
                $codigosLibrosDevolucionSon = CodigosLibrosDevolucionSon::where('documento', $ven_codigoAnterior)
                    ->where('id_empresa', $id_empresa)
                    ->update([
                        'documento' => $nuevo_ven_codigo,
                    ]);

                if ($codigosLibrosDevolucionSon === 0) {
                    return ["status" => "0", "message" => "No se actualizó el código en CodigosLibrosDevolucionSon con ven_codigo: $ven_codigoAnterior."];
                }
            }
            //ACTUALIZAR STOCK
            foreach($f_detalle_venta as $key => $item){
                //GUARDAR EN HISTORICO PARA NOTAS
                $datos = (Object)[
                    "descripcion"       => $item->pro_codigo,
                    "tipo"              => "1",
                    "nueva_prefactura"  => $nuevo_ven_codigo,
                    "cantidad"          => 1,
                    "id_periodo"        => $id_periodo,
                    "id_empresa"        => $id_empresa,
                    "observacion"       => $mensajeHistorico,
                    "user_created"      => $id_usuario
                ];
                $this->proformaRepository->saveHistoricoNotasMove($datos);
                //disminuir stock en las notas
                $datosStockNota = (Object)[
                    "codigo_liquidacion"  => $item->pro_codigo,
                    "cantidad"            => $item->det_ven_cantidad,
                    "proforma_empresa"    => $id_empresa,
                    "documentoPrefactura" => 1
                ];
                //aumentar stock en prefacturas
                $datosStockPrefactura = (Object)[
                    "codigo_liquidacion"  => $item->pro_codigo,
                    "cantidad"            => $item->det_ven_cantidad,
                    "proforma_empresa"    => $id_empresa,
                    "documentoPrefactura" => 0
                ];
                //NOTA EL disminuir NO SE CAMBIA PORQUE SOLO SE INTERCAMBIA LOS VALORES DE NOTAS SE DESCUENTA Y SE MUEVAN A LA PREFACTURA
                //metodo aumentar stock en notas
                $this->proformaRepository->restaStock($datosStockNota,1);
                //metodo aumentar stock en prefacturas
                $this->proformaRepository->sumaStock($datosStockPrefactura,1);
            }
            //SUMAR SECUENCIA
            // ACTUALIZAR SECUENCIAL
            if($id_empresa==1){
                $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from  f_tipo_documento where tdo_id=1");
            }else if ($id_empresa==3){
                $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from  f_tipo_documento where tdo_id=1");
            }
            $id=$query1[0]->id;
            $codi=$query1[0]->cod;
            $co=(int)$codi+1;
            $tipo_doc = f_tipo_documento::findOrFail($id);
            if($id_empresa==1){
                $tipo_doc->tdo_secuencial_Prolipa = $co;
            }else if ($id_empresa==3){
                $tipo_doc->tdo_secuencial_calmed = $co;
            }
            $tipo_doc->save();
            if(!$tipo_doc){
                return ["status" => "0", "message" => "No se pudo actualizar la secuencia de documentos."];
            }
            // Confirmar la transacción
            DB::commit();
        } catch (\Exception $e) {
            // Deshacer la transacción si ocurre un error
            DB::rollBack();
            return response()->json(['status' => '0', 'message' => $e->getMessage()], 200);
        }

        return response()->json(['status' => '1', 'message' => "La nota fue movida y guardada con la nueva Pre factura $nuevo_ven_codigo."]);
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
