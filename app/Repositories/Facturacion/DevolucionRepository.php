<?php
namespace App\Repositories\Facturacion;

use App\Models\CodigosLibros;
use App\Models\CodigosLibrosDevolucionHeader;
use App\Models\CodigosLibrosDevolucionSon;
use App\Models\CodigosLibrosDevolucionSonFacturador;
use App\Repositories\BaseRepository;
use App\Traits\Codigos\TraitCodigosGeneral;
use Illuminate\Support\Facades\DB;
class  DevolucionRepository extends BaseRepository
{
    use TraitCodigosGeneral;
 
    public function __construct(CodigosLibrosDevolucionHeader $devolucionHeader)
    {
        parent::__construct($devolucionHeader);
    }
  
    public function getFacturaAvailable($request, $cantidadNecesaria, $idtipodoc=[1], $documentoEspecifico=null, $codigo=null) {
        $pro_codigo     = $request->pro_codigo;
        $id_institucion = $request->id_institucion;
        $id_periodo     = $request->id_periodo;
        $id_empresa     = $request->id_empresa;
        // Obtener todas las facturas disponibles que cumplen con los criterios
        $disponiblePrefactura = \DB::table('f_detalle_venta as dg')
            ->join('f_venta as dv', function ($join) use ($id_empresa) {
                $join->on('dg.ven_codigo', '=', 'dv.ven_codigo')
                    ->where('dg.id_empresa', '=', $id_empresa)
                    ->where('dv.id_empresa', '=', $id_empresa);
            })
            ->where('dg.pro_codigo', $pro_codigo)
            ->where('dv.id_empresa', $id_empresa)
            ->where('dg.id_empresa', $id_empresa)
            ->where('dv.institucion_id', $id_institucion)
            ->where('dv.periodo_id', $id_periodo)
            ->where('dv.est_ven_codigo', '<>', 3)
            ->when($documentoEspecifico, function ($query) use ($documentoEspecifico) {
                // Si se proporciona un documento específico, filtrar solo por ese documento
                $query->where('dv.ven_codigo', $documentoEspecifico);
            })
            ->when(is_null($documentoEspecifico), function ($query) use ($idtipodoc) {
                // Si no se proporciona un documento específico, filtrar por el tipo de documento
                $query->whereIn('dv.idtipodoc', $idtipodoc);
            })
            ->where(function ($query) {
                // Validamos que no se cumpla la condición: idtipodoc es 3 o 4 y doc_intercambio no es nulo
                $query->whereNotIn('dv.idtipodoc', [3, 4])  // Excluye idtipodoc 3 o 4
                      ->orWhereNull('dv.doc_intercambio');  // O donde doc_intercambio sea nulo
            })
            ->selectRaw('COALESCE(SUM(dg.det_ven_cantidad - dg.det_ven_dev), 0) as cantidad, dv.ven_codigo, dg.pro_codigo, dv.idtipodoc')
            ->groupBy('dv.ven_codigo') // Agrupar por ven_codigo para obtener los totales
            ->havingRaw('COALESCE(SUM(dg.det_ven_cantidad - dg.det_ven_dev), 0) >= ?', [$cantidadNecesaria])
            ->get(); // Obtener todas las facturas disponibles
        
        if ($disponiblePrefactura->isEmpty()) {
            return null; // Si no hay prefactura disponible que cumpla con la cantidad necesaria
        }
    
        // Iterar sobre las facturas disponibles para calcular la cantidad disponible
        foreach ($disponiblePrefactura as $factura) {
            // Obtener la cantidad reservada (creada o en reserva) para cada factura
            $cantidadReservadaCreada = \DB::table('codigoslibros_devolucion_son as cs')
                ->where('cs.id_empresa', $id_empresa)
                ->where('cs.id_cliente', $id_institucion)
                ->where('cs.id_periodo', $id_periodo)
                ->where('cs.pro_codigo', $pro_codigo)
                ->where('documento', $factura->ven_codigo)
                ->whereNull('cs.combo') 
                ->where('cs.tipo_codigo', '0') 
                ->where('cs.estado', 0) 
                ->when($codigo, function($query) use ($codigo) {
                    $query->where('cs.codigo', '<>', $codigo); // Excluir el código si se pasa como parámetro
                })
                ->count();
    
            // Calcular la cantidad disponible restando la cantidad reservada
            $cantidadDisponible = $factura->cantidad - $cantidadReservadaCreada;
    
            // Verificar si la cantidad disponible es suficiente
            if ($cantidadDisponible >= $cantidadNecesaria) {
                // Si hay suficiente cantidad, asignamos la cantidad disponible
                $factura->cantidadDisponible = $cantidadDisponible;
                return $factura; // Devolver solo esta factura disponible
            }
        }
    
        return null; // Si no se encuentra ninguna prefactura con suficiente cantidad
    }
    
    public function actualizarCodigo($codigo, $codigo_proforma, $nuevo_codigo, $id_institucion, $id_periodo, $id_usuario) {
        $codigoUpdate = CodigosLibros::find($codigo);
        if ($codigoUpdate) {
            $codigo_union = $codigoUpdate->codigo_union;
            if ($codigo_union && $codigo_union != "null") {
                CodigosLibros::where('codigo', $codigo_union)
                    ->update(['codigo_proforma' => $nuevo_codigo]);
            }
    
            // Actualizar la pre factura en el código
            CodigosLibros::where('codigo', $codigo)
                ->update(['codigo_proforma' => $nuevo_codigo]);
    
            // Guardar en histórico
            $mensajeHistorico = 'Se intercambió del documento ' . $codigo_proforma . ' al documento ' . $nuevo_codigo;
            $this->GuardarEnHistorico(0,$id_institucion,$id_periodo,$codigo,$id_usuario,$mensajeHistorico,null,null,null,null);
        }
    }
       
    
    
    public function detallePrefactura($ven_codigo,$id_empresa,$id_institucion,$combos = 0)
    {
        $disponiblePrefactura = \DB::table('f_detalle_venta as dg')
        ->join('f_venta as dv', function ($join) use ($id_empresa) {
            $join->on('dg.ven_codigo', '=', 'dv.ven_codigo')
                ->where('dg.id_empresa', '=', $id_empresa)
                ->where('dv.id_empresa', '=', $id_empresa);
            })
            ->leftJoin('libros_series as ls', 'ls.codigo_liquidacion', '=', 'dg.pro_codigo')
            ->leftJoin('1_4_cal_producto as pro', 'pro.pro_codigo', '=', 'dg.pro_codigo')
            ->where('dg.ven_codigo', $ven_codigo)
            ->where('dv.id_empresa', $id_empresa)
            ->where('dv.institucion_id', $id_institucion)
            ->where('dv.idtipodoc', '1')
            ->where('dv.est_ven_codigo', '<>', 3)
            ->where('dv.estadoPerseo', '0')
            ->when($combos == 1, function($query) use ($combos) {
                $query->where('pro.ifcombo', '=', 1);
            })
            ->selectRaw('dg.*, ls.nombre as nombrelibro, pro.ifcombo, pro.codigos_combos, CONCAT(ls.nombre," ", pro.pro_codigo) as nombrePro,
            ls.idLibro
            ')
            ->get();

        return $disponiblePrefactura;
    }
    public function getCodigosCombosDocumentoDevolucion($id_documento){
        $query = DB::SELECT("SELECT s.* , ls.nombre AS nombrelibro, pro.codigos_combos, e.descripcion_corta AS empresa
        FROM codigoslibros_devolucion_son s
        LEFT JOIN libros_series ls ON ls.codigo_liquidacion = s.pro_codigo
        LEFT JOIN  1_4_cal_producto pro  ON pro.pro_codigo = ls.codigo_liquidacion
        LEFT JOIN empresas e ON e.id  = s.id_empresa
        WHERE s.tipo_codigo = '1'
        AND s.codigoslibros_devolucion_id = '$id_documento'
        ORDER BY s.id desc
        ");
        return $query;
    }
    public function devolucionCliente($idCliente,$idPeriodo)
    {
        $query = DB::SELECT("SELECT h.*, l.nombrelibro
            FROM codigoslibros_devolucion_son h
            LEFT JOIN libro l ON h.id_libro = l.idlibro
            LEFT JOIN codigoslibros_devolucion_header p ON p.id = h.codigoslibros_devolucion_id
            where h.estado <> '0'
            AND p.id_cliente = ?
            AND p.periodo_id = ?
        ",[$idCliente,$idPeriodo]);
        return $query;
    }
    public function save_son_devolucion_facturador($datos){
        //validar que no existe el pro_codigo la id_empresay el codigoslibros_devolucion_header_facturador_id
        $validar = CodigosLibrosDevolucionSonFacturador::where('pro_codigo',$datos->pro_codigo)
        ->where('codigoslibros_devolucion_header_facturador_id',$datos->documentoPadre)
        ->where('descuento',$datos->descuento)
        ->where('cantidad',$datos->cantidad)
        ->where('id_empresa',$datos->id_empresa)->first();
        if($validar){
            return $validar;
        }
        $devolucionH = new CodigosLibrosDevolucionSonFacturador();
        $devolucionH->codigoslibros_devolucion_header_facturador_id = $datos->documentoPadre;
        $devolucionH->codigoslibros_devolucion_header_id            = $datos->codigoslibros_devolucion_header_id;
        $devolucionH->id_empresa                                    = $datos->id_empresa;
        $devolucionH->pro_codigo                                    = $datos->pro_codigo;
        $devolucionH->cantidad                                      = $datos->cantidad;
        $devolucionH->precio                                        = $datos->precio;
        $devolucionH->descuento                                     = $datos->descuento;
        $devolucionH->observacion_codigo                            = $datos->observacion_codigo;
        $devolucionH->save();
        return $devolucionH;
    }
    public function prefacturaLibreXCodigo($id_empresa, $id_institucion, $id_periodo, $pro_codigo,$cantidadNecesaria=0)
    {
        $query = DB::table('f_venta as v')
            ->leftJoin('f_detalle_venta as dv', function ($join) {
                $join->on('dv.ven_codigo', '=', 'v.ven_codigo')
                    ->on('dv.id_empresa', '=', 'v.id_empresa');
            })
            ->select(
                'dv.*',
                DB::raw('(COALESCE(dv.det_ven_cantidad, 0) - COALESCE(dv.det_ven_dev, 0)) AS disponible')
            )
            ->where('v.institucion_id', $id_institucion)
            ->where('v.id_empresa', $id_empresa)
            ->where('v.periodo_id', $id_periodo)
            ->where('v.est_ven_codigo', '<>', '3')
            // ->whereNull('v.doc_intercambio')
            ->where('v.idtipodoc', '1')
            ->where('dv.pro_codigo', $pro_codigo)
            ->having('disponible', '>', $cantidadNecesaria)
            ->get();

        return $query;
    }
    public function validateComboCreado($combo,$id_empresa,$id_devolucion)
    {
        $query = CodigosLibrosDevolucionSon::where('pro_codigo', $combo)->where('id_empresa', $id_empresa)->where('codigoslibros_devolucion_id', $id_devolucion)->get();
        return $query;
    }
    //actualizar valores de un documento f_venta
    public function updateValoresDocumentoF_venta($ven_codigo, $id_empresa)
    {
        try {
            // Iniciar transacción
            DB::beginTransaction();

            // Calcular el nuevo subtotal de la venta
            $nuevoSubtotal = DB::table('f_detalle_venta')
                ->where('ven_codigo', $ven_codigo)
                ->where('id_empresa', $id_empresa)
                ->sum(DB::raw('det_ven_cantidad * det_ven_valor_u'));

            // Obtener el porcentaje de descuento
            $descuentoPorcentaje = DB::table('f_venta')
                ->where('ven_codigo', $ven_codigo)
                ->where('id_empresa', $id_empresa)
                ->value('ven_desc_por'); // Porcentaje de descuento

            // Calcular el valor del descuento
            $valorDescuento = ($descuentoPorcentaje / 100) * $nuevoSubtotal;

            // Calcular el nuevo valor total
            $nuevoTotal = $nuevoSubtotal - $valorDescuento;

            // Actualizar f_venta con el nuevo subtotal, descuento y total
            DB::table('f_venta')
                ->where('ven_codigo', $ven_codigo)
                ->where('id_empresa', $id_empresa)
                ->update([
                    'ven_subtotal'  => $nuevoSubtotal,
                    'ven_descuento' => $valorDescuento,
                    'ven_valor'     => $nuevoTotal,
                ]);

            // Confirmar transacción
            DB::commit();
        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            DB::rollBack();

            // Lanzar una excepción personalizada
            throw new \Exception("No se pudo actualizar los valores del documento f_venta.");
        }
    }

    public function updateValoresDocumentoF_proforma($ven_codigo, $id_empresa)
    {
        try {
            // Iniciar transacción
            DB::beginTransaction();
            // return "holoa¿¿a";
            // Calcular el nuevo subtotal de la venta
            $nuevoSubtotal = DB::table('f_venta as v')
            ->join('f_proforma as p', 'v.ven_idproforma', '=', 'p.prof_id')
            ->join('f_detalle_proforma as dp', 'p.id', '=', 'dp.prof_id')
            ->where('p.emp_id', $id_empresa)
            ->where('v.ven_codigo', $ven_codigo)
            ->sum(DB::raw('dp.det_prof_cantidad * dp.det_prof_valor_u'));

            // Obtener el porcentaje de descuento
            $descuentoPorcentaje = DB::table('f_venta as v')
                ->join('f_proforma as p', 'v.ven_idproforma', '=', 'p.prof_id')
                ->where('v.ven_codigo', $ven_codigo)
                ->where('p.emp_id', $id_empresa)
                ->value('p.pro_des_por'); // Porcentaje de descuento

            // Calcular el valor del descuento
            $nuevo_prof_descuento = ($descuentoPorcentaje / 100) * $nuevoSubtotal;

            // Calcular el nuevo valor total
            $nuevo_prof_total = $nuevoSubtotal - $nuevo_prof_descuento;

            // Actualizar f_venta con el nuevo subtotal, descuento y total
            DB::table('f_venta as v')
            ->join('f_proforma as p', 'v.ven_idproforma', '=', 'p.prof_id')
            ->where('v.ven_codigo', $ven_codigo)
            ->where('p.emp_id', $id_empresa)
            ->update([
                'p.prof_total'    => $nuevo_prof_total,
                'p.prof_descuento' => $nuevo_prof_descuento,
            ]);

            // Confirmar transacción
            DB::commit();
        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            DB::rollBack();

            // Lanzar una excepción personalizada
            throw new \Exception("No se pudo actualizar los valores del documento f_vent2a.");
        }
    }

}
