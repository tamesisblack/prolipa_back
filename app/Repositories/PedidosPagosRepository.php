<?php
namespace App\Repositories;
use DB;
use App\Models\Models\Pagos\VerificacionPago;
use App\Models\Models\Pedidos\PedidosDocumentosLiq;
use App\Models\Pedidos;
use App\Models\Verificacion;
use App\Traits\Pedidos\TraitPedidosGeneral;

class  PedidosPagosRepository extends BaseRepository
{
    use TraitPedidosGeneral;
    public function __construct(VerificacionPago $modelo)
    {
        parent::__construct($modelo);
    }
    public function getPagosXID($verificacion_pago_id){
        $query = DB::SELECT("SELECT pd.* ,
        CONCAT(u.nombres,' ', u.apellidos) AS distribuidor_usuario,
        dt.saldo_actual, tp.tip_pag_nombre
        FROM verificaciones_pagos_detalles pd
        LEFT JOIN distribuidor_temporada dt ON pd.distribuidor_temporada_id = dt.id
        LEFT JOIN pedidos_formas_pago tp ON tp.forma_pago_id = pd.forma_pago_id
        LEFT JOIN usuario u ON pd.idusuario = u.idusuario
        WHERE pd.verificacion_pago_id = ?
        ",[$verificacion_pago_id]);
        return $query;
    }
    public function getPagosxContrato($contrato){
        $pagos = PedidosDocumentosLiq::with([
            'tipoPagos',
            'formaPagos',
            'pedidoPagosHijo'
        ])
        ->where('ven_codigo',$contrato)
        ->where('forma_pago_id','>','0')
        ->OrderBy('doc_codigo','DESC')
        ->get();
        return $pagos;
    }
    public function getPagosSinContrato($institucion,$periodo){
        $pagos = PedidosDocumentosLiq::with([
            'tipoPagos',
            'formaPagos',
            'pedidoPagosHijo'
        ])
        ->where('forma_pago_id','>','0')
        ->where('1_4_documento_liq.institucion_id','=',$institucion)
        ->where('1_4_documento_liq.periodo_id',   '=',$periodo)
        ->OrderBy('doc_codigo','DESC')
        ->get();
        return $pagos;
    }
    public function getPagosInstitucion($institucion,$periodo,$tipo=null,$parametro1=null,$parametro2=null,$parametro3=null,$parametro4=null,$ifPrint=null){
        $pagos = PedidosDocumentosLiq::query();
        //null => solo aprobados
        if($tipo == null)  { $pagos->porInstitucionYPeriodo($institucion,$periodo)->where('estado','1'); }
        //convenio exepto el que se esta utilizado
        if($tipo == 1)     { $pagos->porInstitucionYPeriodo($institucion,$periodo)->where('tipo_pago_id','4')->where('doc_codigo','<>',$parametro1);  }
        //convenios for status
        if($tipo == 2)     { $pagos->porInstitucionYPeriodo($institucion,$periodo)->where('tipo_pago_id','4')->where('estado',     '=',$parametro1);  }
        //convenios for status x id convenio
        if($tipo == 3)     { $pagos->porInstitucionYPeriodo($institucion,$periodo)->where('estado',     '=',$parametro1)->where('pedidos_convenios_id','=',$parametro2);  }
        //dinamico diferente 2 campo
        if($tipo == 4)     { $pagos->porInstitucionYPeriodo($institucion,$periodo)->where('doc_codigo', '<>',$parametro1)->where($parametro2,'=',$parametro3);  }
        //dinamico igual 2 campos
        if($tipo == 5)     { $pagos->where($parametro1, '=',$parametro2)->where($parametro3,'=',$parametro4);  }
        //los pagos de tipo y estado
        if($tipo == 6)     { $pagos->where('tipo_pago_id',$parametro1)->where('estado',$parametro2); }
        if($ifPrint == 1){ return $pagos;}
        $resultado = $pagos->get();
        return $resultado;
    }
    public function saveDocumentosLiq($request){
        $tipoPago = 0;
        if($request->id > 0){
            $documento                                  = PedidosDocumentosLiq::findOrFail($request->id);
            $tipoPago                                   = $documento->tipo_pago_id;
        }else{
            $documento                                  = new PedidosDocumentosLiq();
        }
        $documento->doc_fecha                           = $request->doc_fecha == null || $request->doc_fecha == "null" ? null : $request->doc_fecha;
        $documento->unicoEvidencia                      = $request->unicoEvidencia;
        $documento->doc_valor                           = $request->doc_valor;
        $documento->doc_numero                          = $request->doc_numero      == null || $request->doc_numero == 'null'? null : $request->doc_numero;
        $documento->doc_nombre                          = $request->doc_nombre      == null || $request->doc_nombre == 'null'? null : $request->doc_nombre;
        $documento->doc_apellidos                       = $request->doc_apellidos   == null || $request->doc_apellidos == 'null'? null : $request->doc_apellidos;
        $documento->doc_ruc                             = $request->doc_ruc         == null || $request->doc_ruc == 'null'? null : $request->doc_ruc;
        $documento->doc_cuenta                          = $request->doc_cuenta      == null || $request->doc_cuenta == 'null'? null : $request->doc_cuenta;
        $documento->doc_institucion                     = $request->doc_institucion == null || $request->doc_institucion == 'null'? null : $request->doc_institucion;
        $documento->doc_ci                              = $request->tipo_pago_id;
        $documento->doc_tipo                            = $request->doc_tipo        == null || $request->doc_tipo == 'null'? null : $request->doc_tipo;
        $documento->doc_observacion                     = $request->doc_observacion == null || $request->doc_observacion == 'null'? null : $request->doc_observacion;
        $documento->ven_codigo                          = $request->ven_codigo      == null || $request->ven_codigo == "null" ? null : $request->ven_codigo;
        $documento->user_created                        = $request->user_created;
        $documento->distribuidor_temporada_id           = $request->distribuidor_temporada_id == null || $request->distribuidor_temporada_id == "null" ? null : $request->distribuidor_temporada_id ;
        $documento->forma_pago_id                       = $request->forma_pago_id;
        $documento->tipo_pago_id                        = $request->tipo_pago_id;
        $documento->calculo                             = $request->calculo;
        if($request->institucion_id)                    { $documento->institucion_id = $request->institucion_id; }
        if($request->periodo_id)                        { $documento->periodo_id     = $request->periodo_id; }
        if($request->id_pedido)                         { $documento->id_pedido      = $request->id_pedido; }
        if(isset($request->estado))                     { $documento->estado         = $request->estado; }
        $documento->ifAntAprobado                        = $request->ifAntAprobado;
        if($request->tipo_pago_id == 7)                 { $documento->campo_dinamico = $request->campo_dinamico; }
        $documento->save();
        $nuevodocumento                                 = PedidosDocumentosLiq::findOrFail($documento->doc_codigo);
        if($request->tipo_pago_id == 6)                 {  $this->updateDeuda($request->id_pedido); }
        //deuda con metodo de pago anterior
        if ($request->tipo_pago_id == 1 && $request->doc_observacion !== null) {
            $doc_observacion = strtolower($request->doc_observacion);
            if (strpos($doc_observacion, 'deuda') !== false) {
                $this->updateDeudaMetodoAnterior($request->id_pedido);
            }
        }
        //deuda proxima
        if($request->tipo_pago_id == 3 || $tipoPago == 3) { $this->updateDeudaProxima($request->id_pedido); }
        return $nuevodocumento;
    }

    public function updateDeudaMetodoAnterior($id_pedido)
    {
        $deudas = PedidosDocumentosLiq::where('id_pedido', $id_pedido)->where('tipo_pago_id', 1)->where('doc_observacion','like','%deuda%')->where('estado','1')->get();
        $sumaDeudas = 0;
        foreach ($deudas as $deuda) { $sumaDeudas += $deuda->doc_valor; }
        $pedido                         = Pedidos::findOrFail($id_pedido);
        $pedido->deuda                  = $sumaDeudas;
        $pedido->save();

    }
    public function updateDeuda($id_pedido)
    {
        $deudas = PedidosDocumentosLiq::where('id_pedido', $id_pedido)->where('tipo_pago_id', 6)->where('estado','1')->get();
        // if (count($deudas) > 0) {
            $sumaDeudas = 0;
            foreach ($deudas as $deuda) { $sumaDeudas += $deuda->doc_valor; }
            $pedido                         = Pedidos::findOrFail($id_pedido);
            $pedido->deuda                  = $sumaDeudas;
            //para que no vuelva a crear automaticamente la deuda
            $pedido->anticipoDeudaIngresada = 1;
            $pedido->save();
        // }
    }
    public function updateDeudaProxima($id_pedido)
    {
        $deudas  = $this->obtenerDeudasProximas($id_pedido);
        // if (count($deudas) > 0) {
            $sumaDeudas = 0;
            foreach ($deudas as $deuda) { $sumaDeudas += abs($deuda->doc_valor); }
            $pedido                         = Pedidos::findOrFail($id_pedido);
            $pedido->totalDeudaProxima      = $sumaDeudas;
            $pedido->save();
        // }
    }
    public function updateVentaDirecta($ven_codigo)
    {
        if($ven_codigo == null || $ven_codigo == "") { return; }
         //actualizar verificaciones con los cobros de venta directa ingresados
         $query = Verificacion::Where('contrato',$ven_codigo)->Where('cobro_venta_directa','2')->Where('tipoPago','2')->Where('cobro_venta_directa_ingresada','0')
         ->update([
             'cobro_venta_directa_ingresada' => 1
         ]);
    }
    //api:get>>/pedigo_Pagos?getVentaRealXAsesor=1&idAsesor=1&idPeriodo=1
    public function getVentaRealXAsesor($idusuario,$periodo){
        $query = DB::SELECT("SELECT p.id_pedido, p.contrato_generado, p.TotalVentaReal,p.total_venta
        FROM pedidos p
        WHERE p.id_asesor = ?
        AND p.tipo ='0'
        AND p.estado = '1'
        AND p.id_periodo = ?
        AND p.contrato_generado IS NOT NULL
        ",[$idusuario,$periodo]);
        return $query;
    }
    //api/get>>pedigo_Pagos?updateVentaReal=1&idAsesor=1&idPeriodo=1
    public function updateVentaReal($request){
        $query      = $this->getVentaRealXAsesor($request->idAsesor,$request->idPeriodo);
        $contador   = 0;
        foreach($query as $item){
            $contrato           = $item->contrato_generado;
            $ventaReal          = 0;
            $verificaciones     = $this->getVerificaciones($contrato);
            foreach($verificaciones as $key2 => $item2){ $ventaReal = $ventaReal + $item2->venta_real; }
            $pedido = Pedidos::findOrFail($item->id_pedido);
            $pedido->TotalVentaReal = $ventaReal;
            $pedido->save();
            $contador++;
        }
        return "Se actualizaron ".$contador." contratos";
    }
    public function obtenerDeudasProximas($id_pedido){
        $deudas     = PedidosDocumentosLiq::where('id_pedido', $id_pedido)->where('tipo_pago_id', 3)->where('estado','1')->get();
        return $deudas;
        // $valor      = 0;
        // foreach ($deudas as $deuda) { $valor += abs($deuda->doc_valor); }
        // return $valor;
    }
}
?>
