<?php
namespace App\Repositories\Facturacion;

use App\Models\_14Producto;
use App\Models\Proforma;
use App\Models\VentasHistoricoNotasMove;
use App\Repositories\BaseRepository;
use DB;
use Exception;

class  ProformaRepository extends BaseRepository
{
    public function __construct(Proforma $proforma)
    {
        parent::__construct($proforma);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $institucion
     */
    public function listadoProformasAgrupadas($institucion){
        $query = DB::SELECT("SELECT * FROM f_venta v
        WHERE v.institucion_id = ?
        AND v.est_ven_codigo <> '3'
        -- AND v.doc_intercambio IS NULL
        AND NOT (v.idtipodoc IN (3, 4) AND v.doc_intercambio IS NOT NULL)
        ",[$institucion]);
        return $query;
    }
    public function listadoInstitucionesXVenta($periodo, $empresa, $tipoInstitucion)
    {
        $query = DB::table('f_venta as v')
            ->distinct()
            ->join('institucion as i', 'v.institucion_id', '=', 'i.idInstitucion')
            ->where('v.est_ven_codigo', '<>', '3')
            ->where(function ($query) {
                $query->whereNotIn('v.idtipodoc', [3, 4])
                    ->orWhereNull('v.doc_intercambio');
            })
            ->where('v.periodo_id', $periodo)
            ->when(!is_null($empresa), function ($q) use ($empresa) {
                $q->where('v.id_empresa', $empresa);
            })
            ->where('i.punto_venta', $tipoInstitucion)
            ->select('v.institucion_id', 'i.nombreInstitucion')
            ->orderBy('i.nombreInstitucion', 'asc')
            ->get();

        return $query;
    }

    public function listadoDocumentosVenta($periodo, $empresa, $tipoInstitucion, $institucion, $tipoDocumento = [1])
    {
        // Usamos el Query Builder para mayor legibilidad y seguridad
        $query = DB::table('f_detalle_venta as v')
            ->join('f_venta as v2', function ($join) use ($empresa) {
                $join->on('v2.ven_codigo', '=', 'v.ven_codigo')
                    ->where('v.id_empresa', '=', $empresa);
            })
            ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'v2.institucion_id')
            ->leftJoin('libros_series as ls', 'ls.codigo_liquidacion', '=', 'v.pro_codigo')
            ->leftJoin('series as s', 's.id_serie', '=', 'ls.id_serie')
            ->leftJoin('f_proforma as pr', 'pr.prof_id', '=', 'v2.ven_idproforma')
            ->where('v2.institucion_id', '=', $institucion)
            ->where('v2.est_ven_codigo', '<>', '3')
            // ->whereNull('v2.doc_intercambio')
            ->where(function ($query) {
                // Validamos que no se cumpla la condición: idtipodoc es 3 o 4 y doc_intercambio no es nulo
                $query->whereNotIn('v2.idtipodoc', [3, 4])  // Excluye idtipodoc 3 o 4
                      ->orWhereNull('v2.doc_intercambio');  // O donde doc_intercambio sea nulo
            })
            ->where('i.punto_venta', '=', $tipoInstitucion)
            ->where('i.idInstitucion', '=', $institucion)
            ->where('v2.id_empresa', '=', $empresa)
            ->where('v2.periodo_id', '=', $periodo)
            ->whereIn('v2.idtipodoc', $tipoDocumento)
            ->select('v.*','s.nombre_serie','pr.idPuntoventa')  // Seleccionamos los campos necesarios
            ->orderBy('v.pro_codigo', 'asc')
            ->get();  // Ejecutamos la consulta y obtenemos los resultados

        return $query;
    }
    public function listadoDocumentosAgrupado($periodo, $empresa, $tipoInstitucion, $institucion)
    {
        $query = DB::table('f_detalle_venta_agrupado as v')
            ->select('v.*', 's.nombre_serie','ls.nombre as nombrelibro')
            ->leftJoin('f_venta_agrupado as v2', function ($join) {
                $join->on('v2.id_factura', '=', 'v.id_factura')
                    ->on('v.id_empresa', '=', 'v2.id_empresa');
            })
            ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'v2.institucion_id')
            ->leftJoin('libros_series as ls', 'ls.codigo_liquidacion', '=', 'v.pro_codigo')
            ->leftJoin('series as s', 's.id_serie', '=', 'ls.id_serie')
            ->where('v2.institucion_id', '=', $institucion)
            ->where('i.punto_venta', '=', $tipoInstitucion)
            ->where('i.idInstitucion', '=', $institucion)
            ->where('v2.periodo_id', '=', $periodo)
            ->where('v2.estadoPerseo', '=', 1)
            ->where('v2.est_ven_codigo', '=', 0)
            ->when(!is_null($empresa), function ($q) use ($empresa) {
                $q->where('v2.id_empresa', '=', $empresa);
            })
            ->orderBy('v.pro_codigo', 'asc')
            ->get();

        return $query;
    }

    public function listadoContratosAgrupadoInstitucion($getDatosVenta)
    {
        if ($getDatosVenta->isEmpty()) {
            return [];
        }

        // Extraer todos los ca_codigo_agrupado en un solo array usando pluck()
        $idsPuntosVenta = $getDatosVenta->pluck('idPuntoventa');

        // Consultar todos los datos en una sola consulta para evitar N+1
        $contratos = DB::table('pedidos')
            ->whereIn('ca_codigo_agrupado', $idsPuntosVenta)
            ->where('estado', '1')
            ->select('id_pedido', 'ca_codigo_agrupado', 'contrato_generado')
            ->get();
        return $contratos->isNotEmpty() ? $contratos->unique('id_pedido')->values() : [];
    }


    public function prefacturaValidaForDevolver($preFactura,$empresa){
        $getPreproforma    = DB::SELECT("SELECT * FROM f_venta v
        WHERE v.ven_codigo = '$preFactura'
        AND v.id_empresa   ='$empresa'
        ");
        if(empty($getPreproforma))    { return []; }
        foreach($getPreproforma as $key => $item){
            $query = DB::SELECT("SELECT * FROM f_venta_agrupado v
            WHERE v.id_factura = ?
            AND v.id_empresa = ?
            ",[$item->id_factura,$item->id_empresa]);
            // $query = DB::SELECT("SELECT * FROM f_venta_agrupado v
            // WHERE v.id_factura = ?
            // AND v.estadoPerseo = '1'
            // AND v.id_empresa = ?
            // ",[$item->id_factura,$item->id_empresa]);
            if(count($query) > 0){
                $getPreproforma[$key]->ifPedidoPerseo = 1;
            }else{
                $getPreproforma[$key]->ifPedidoPerseo = 0;
            }
        }
        $resultado = collect($getPreproforma);
        //filtrar por ifPedidoPerseo igual a 1
        $resultado = $resultado->where('ifPedidoPerseo','1')->all();
        return $resultado;
    }
    public function getNumeroDocumento($empresa){
        if($empresa == 1){
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_Prolipa as cod from f_tipo_documento where tdo_nombre='PRE-FACTURA'");
        }else if ($empresa==3){
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_calmed as cod from f_tipo_documento where tdo_nombre='PRE-FACTURA'");
        }
        $getSecuencia = 1;
        if(!empty($query1)){
            $pre= $query1[0]->tdo_letra;
            $codi=$query1[0]->cod;
            $getSecuencia=(int)$codi+1;
            if($getSecuencia>0 && $getSecuencia<10){
                $secuencia = "000000".$getSecuencia;
            } else if($getSecuencia>9 && $getSecuencia<100){
                $secuencia = "00000".$getSecuencia;
            } else if($getSecuencia>99 && $getSecuencia<1000){
                $secuencia = "0000".$getSecuencia;
            }else if($getSecuencia>999 && $getSecuencia<10000){
                $secuencia = "000".$getSecuencia;
            }else if($getSecuencia>9999 && $getSecuencia<100000){
                $secuencia = "00".$getSecuencia;
            }else if($getSecuencia>99999 && $getSecuencia<1000000){
                $secuencia = "0".$getSecuencia;
            }else if($getSecuencia>999999 && $getSecuencia<10000000){
                $secuencia = $getSecuencia;
            }
        }

        return $secuencia;
    }
    public function saveHistoricoNotasMove($datos){
        $VentasHistoricoNotasMove                   = new VentasHistoricoNotasMove();
        $VentasHistoricoNotasMove->descripcion      = $datos->descripcion;
        $VentasHistoricoNotasMove->tipo             = $datos->tipo;
        $VentasHistoricoNotasMove->nueva_prefactura = $datos->nueva_prefactura;
        $VentasHistoricoNotasMove->cantidad         = $datos->cantidad;
        $VentasHistoricoNotasMove->id_periodo       = $datos->id_periodo;
        $VentasHistoricoNotasMove->id_empresa       = $datos->id_empresa;
        $VentasHistoricoNotasMove->observacion      = $datos->observacion;
        $VentasHistoricoNotasMove->user_created     = $datos->user_created;
        $VentasHistoricoNotasMove->save();
    }
    //aumentar stock en las notas y disminuir en las prefacturas
    public function sumaStock($datos, $noAfectarReserva = 0)
    {
        try {
            $codigo_liquidacion         = $datos->codigo_liquidacion;
            $proforma_empresa           = $datos->proforma_empresa;
            $valorNew                   = $datos->cantidad;
            $documentoPrefactura        = $datos->documentoPrefactura;

            // Obtener stock
            $getStock                   = _14Producto::obtenerProducto($codigo_liquidacion);
            if (!$getStock) {
                throw new Exception('Producto no encontrado');
            }
            $stockAnteriorReserva       = $getStock->pro_reservar;

            // Prolipa
            if ($proforma_empresa == 1) {
                if ($documentoPrefactura == 0) {
                    $stockEmpresa = $getStock->pro_stock;
                }
                if ($documentoPrefactura == 1) {
                    $stockEmpresa = $getStock->pro_deposito;
                }
            }

            // Calmed
            if ($proforma_empresa == 3) {
                if ($documentoPrefactura == 0) {
                    $stockEmpresa = $getStock->pro_stockCalmed;
                }
                if ($documentoPrefactura == 1) {
                    $stockEmpresa = $getStock->pro_depositoCalmed;
                }
            }

            $nuevoStockReserva          = $stockAnteriorReserva + $valorNew;
            $nuevoStockEmpresa          = $stockEmpresa + $valorNew;

            // Actualizar stock en la tabla de productos
            if ($noAfectarReserva == 1) {
                _14Producto::updateStockNoReserva($codigo_liquidacion, $proforma_empresa, $nuevoStockEmpresa, $documentoPrefactura);
            } else {
                _14Producto::updateStock($codigo_liquidacion, $proforma_empresa, $nuevoStockReserva, $nuevoStockEmpresa, $documentoPrefactura);
            }

        } catch (Exception $e) {
            // Manejar la excepción, logearla o lanzar una nueva
            throw new Exception('Error al procesar la suma de stock: ' . $e->getMessage());
        }
    }

    public function restaStock($datos, $noAfectarReserva = 0)
    {
        try {
            $codigo_liquidacion         = $datos->codigo_liquidacion;
            $proforma_empresa           = $datos->proforma_empresa;
            $valorNew                   = $datos->cantidad;
            $documentoPrefactura        = $datos->documentoPrefactura;

            // Obtener stock
            $getStock                   = _14Producto::obtenerProducto($codigo_liquidacion);
            if (!$getStock) {
                throw new Exception('Producto no encontrado');
            }
            $stockAnteriorReserva       = $getStock->pro_reservar;

            // Prolipa
            if ($proforma_empresa == 1) {
                if ($documentoPrefactura == 0) {
                    $stockEmpresa = $getStock->pro_stock;
                }
                if ($documentoPrefactura == 1) {
                    $stockEmpresa = $getStock->pro_deposito;
                }
            }

            // Calmed
            if ($proforma_empresa == 3) {
                if ($documentoPrefactura == 0) {
                    $stockEmpresa = $getStock->pro_stockCalmed;
                }
                if ($documentoPrefactura == 1) {
                    $stockEmpresa = $getStock->pro_depositoCalmed;
                }
            }

            // Comprobar si hay suficiente stock para restar
            if ($stockEmpresa < $valorNew) {
                throw new Exception('No hay suficiente stock para restar en "'.$codigo_liquidacion.  '". Stock disponible: ' . $stockEmpresa . ', cantidad a restar: ' . $valorNew);
            }

            $nuevoStockReserva          = $stockAnteriorReserva - $valorNew;
            $nuevoStockEmpresa          = $stockEmpresa - $valorNew;

            // Actualizar stock en la tabla de productos
            if ($noAfectarReserva == 1) {
                _14Producto::updateStockNoReserva($codigo_liquidacion, $proforma_empresa, $nuevoStockEmpresa, $documentoPrefactura);
            } else {
                _14Producto::updateStock($codigo_liquidacion, $proforma_empresa, $nuevoStockReserva, $nuevoStockEmpresa, $documentoPrefactura);
            }

        } catch (Exception $e) {
            // Aquí puedes manejar la excepción, logearla o incluso lanzar una nueva
            throw new Exception('Error al procesar la resta de stock: ' . $e->getMessage());
        }
    }

}
?>
