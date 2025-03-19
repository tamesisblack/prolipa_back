<?php

namespace App\Http\Controllers;

use App\Models\Documentoliq;
use App\Models\Models\Pedidos\PedidosDocumentosLiq;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentoliqController extends Controller
{
    //
    public function GetDocumentoliq(){
        $query = DB::SELECT("SELECT * FROM 1_4_documento_liq as doc LEFT JOIN institucion as ins ON doc.institucion_id= ins.idInstitucion ORDER BY doc_codigo DESC limit 50");
        return $query;
    }
    //api:get/GetDocumentoliqXPedido?pedido=1405
    public function GetDocumentoliqXPedido(Request $request){
        $query = PedidosDocumentosLiq::where('id_pedido', $request->pedido)
        ->with('tipoPagos','formaPagos')  // Cargar la relación tipoPagos, formaPagos
        ->get();
    
        return $query;
    }
    public function GetPago(){
        $query = DB::SELECT("SELECT * FROM 1_4_tipo_pago");
        return $query;
    }
     public function GetDocumentoliq_xfiltro(Request $request){
        if ($request->busqueda == 'codigo') {
            $query = DB::SELECT("SELECT * FROM 1_4_documento_liq as doc LEFT JOIN institucion as ins ON doc.institucion_id= ins.idInstitucion 
            WHERE doc.doc_codigo LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
        if ($request->busqueda == 'undefined') {
            $query = DB::SELECT("SELECT * FROM 1_4_documento_liq as doc LEFT JOIN institucion as ins ON doc.institucion_id= ins.idInstitucion 
            WHERE doc.doc_codigo LIKE '%$request->razonbusqueda%'
            
            ");
            return $query;
        }
        if ($request->busqueda == 'institucion') {
            $query = DB::SELECT("SELECT * FROM 1_4_documento_liq as doc LEFT JOIN institucion as ins ON doc.institucion_id= ins.idInstitucion 
            WHERE ins.nombreInstitucion LIKE '%$request->razonbusqueda%'
            ORDER BY doc_codigo DESC
            ");
            return $query;
        }
        if ($request->busqueda == 'contrato') {
            $query = DB::SELECT("SELECT * FROM 1_4_documento_liq as doc LEFT JOIN institucion as ins ON doc.institucion_id= ins.idInstitucion 
            WHERE doc.ven_codigo LIKE '%$request->razonbusqueda%'
            ORDER BY doc_codigo DESC
            ");
            return $query;
        }
    }

    public function PostDocumentoliq_Registrar_modificar(Request $request)
    {
        if($request->doc_codigo){
        $documento = Documentoliq::findOrFail($request->doc_codigo);
        $documento->doc_valor = $request->doc_valor;
        $documento->doc_numero = $request->doc_numero;
        $documento->doc_nombre = $request->doc_nombre;
        $documento->doc_apellidos = $request->doc_apellidos;
        $documento->doc_ci = $request->doc_ci;
        $documento->doc_ruc = $request->doc_ruc;
        $documento->doc_cuenta = $request->doc_cuenta;
        $documento->doc_institucion = $request->doc_institucion;
        $documento->doc_tipo = $request->doc_tipo;
        $documento->doc_observacion = $request->doc_observacion;
        $documento->ven_codigo = $request->ven_codigo;
        $documento->doc_fecha = $request->doc_fecha;
        $documento->user_created = $request->user_created;
        $documento->distribuidor_temporada_id = $request->distribuidor_temporada_id;
        $documento->tip_pag_codigo = $request->tip_pag_codigo;
        $documento->tipo_aplicar = $request->tipo_aplicar;
        $documento->calculo = $request->calculo;
        $documento->unicoEvidencia = $request->unicoEvidencia;
        $documento->archivo = $request->archivo;
        $documento->url = $request->url;
        $documento->verificaciones_pagos_detalles_id = $request->verificaciones_pagos_detalles_id;
       }else{
           $documento = new Documentoliq;
        $documento->doc_valor = $request->doc_valor;
        $documento->doc_numero = $request->doc_numero;
        $documento->doc_nombre = $request->doc_nombre;
        $documento->doc_apellidos = $request->doc_apellidos;
        $documento->doc_ci = $request->doc_ci;
        $documento->doc_ruc = $request->doc_ruc;
        $documento->doc_cuenta = $request->doc_cuenta;
        $documento->doc_institucion = $request->doc_institucion;
        $documento->doc_tipo = $request->doc_tipo;
        $documento->doc_observacion = $request->doc_observacion;
        $documento->ven_codigo = $request->ven_codigo;
        $documento->doc_fecha = $request->doc_fecha;
        $documento->user_created = $request->user_created;
        $documento->distribuidor_temporada_id = $request->distribuidor_temporada_id;
        $documento->tip_pag_codigo = $request->tip_pag_codigo;
        $documento->tipo_aplicar = $request->tipo_aplicar;
        $documento->calculo = $request->calculo;
        $documento->unicoEvidencia = $request->unicoEvidencia;
        $documento->archivo = $request->archivo;
        $documento->url = $request->url;
        $documento->verificaciones_pagos_detalles_id = $request->verificaciones_pagos_detalles_id;
        $documento->estado = $request->estado;
           //$area->tipoareas_idtipoarea = $request->idtipoarea;
       }
       $documento->save();
       if($documento){
           return $documento;
       }else{
           return "No se pudo guardar/actualizar";
       }
    }

    
    public function Desactivar_Documentoliq(Request $request)
    {
        if ($request->doc_codigo) {
            $documentoliq= Documentoliq::find($request->doc_codigo);

            if (!$documentoliq){
                return "El doc_codigo no existe en la base de datos";
            }

            $documentoliq->estado = $request->estado;
            $documentoliq->save();

            return $documentoliq;       
         } else {
            return "No está ingresando ningún doc_codigo";
        }
    }

    public function Eliminar_Documentoliq(Request $request)
    {
        if ($request->doc_codigo) {
            $documentoliq = Documentoliq::find($request->doc_codigo);

            if (!$documentoliq) {
                return "El doc_codigo no existe en la base de datos";
            }

           
            $documentoliq->delete();

            return $documentoliq;
        } else {
            return "No está ingresando ningún doc_codigo";
        }
        

    }

}
