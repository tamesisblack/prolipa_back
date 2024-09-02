<?php

namespace App\Http\Controllers;

use App\Models\Remision;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RemisionController extends Controller
{
    //
    public function GetRemision(){
        $query = DB::SELECT("SELECT *FROM 1_4_remision AS rem INNER JOIN 1_4_transporte AS trans on rem.trans_codigo=trans.trans_codigo 
        INNER JOIN 1_4_remision_motivo AS mot ON rem.remi_motivo=mot.mot_id  ORDER BY rem.remi_fecha_inicio DESC limit 50");
        return $query;
    }
    public function genCodigoE(Request $request){
        if ($request->id==1){
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_Prolipa as cod from  f_tipo_documento where tdo_letra='$request->letra'");
        }
        if ($request->id==3){
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_calmed as cod from  f_tipo_documento where tdo_letra='$request->letra'");
        }
        if(!empty($query1)){
            $cod=$query1[0]->cod;
            $codi=$cod+1;
            return $codi;
        }
    }
    public function GetRemision_xfiltro(Request $request){
        if ($request->busqueda == 'codigo') {
            $query = DB::SELECT("SELECT *FROM 1_4_remision AS rem INNER JOIN 1_4_transporte AS trans on rem.trans_codigo=trans.trans_codigo 
            INNER JOIN 1_4_remision_motivo AS mot ON rem.remi_motivo=mot.mot_id
            WHERE rem.remi_codigo LIKE '%$request->razonbusqueda%'
            ORDER BY rem.remi_fecha_inicio DESC
            ");
            return $query;
        }
        if ($request->busqueda == 'undefined') {
            $query = DB::SELECT("SELECT *FROM 1_4_remision AS rem INNER JOIN 1_4_transporte AS trans on rem.trans_codigo=trans.trans_codigo 
            INNER JOIN 1_4_remision_motivo AS mot ON rem.remi_motivo=mot.mot_id
            WHERE rem.remi_codigo LIKE '%$request->razonbusqueda%'
            ORDER BY rem.remi_fecha_inicio DESC
            ");
            return $query;
        }
        if ($request->busqueda == 'nombres') {
            $query = DB::SELECT("SELECT *FROM 1_4_remision AS rem INNER JOIN 1_4_transporte AS trans on rem.trans_codigo=trans.trans_codigo 
            INNER JOIN 1_4_remision_motivo AS mot ON rem.remi_motivo=mot.mot_id
            WHERE rem.remi_destinatario LIKE '%$request->razonbusqueda%'
            ORDER BY rem.remi_fecha_inicio DESC
            ");
            return $query;
        }
        if ($request->busqueda == 'factura') {
            $query = DB::SELECT("SELECT *FROM 1_4_remision AS rem INNER JOIN 1_4_transporte AS trans on rem.trans_codigo=trans.trans_codigo 
            INNER JOIN 1_4_remision_motivo AS mot ON rem.remi_motivo=mot.mot_id 
            WHERE rem.remi_num_factura LIKE '%$request->razonbusqueda%'
            ORDER BY rem.remi_fecha_inicio DESC
            ");
            return $query;
        }
    }
    public function PostRemision_Registrar_modificar(Request $request)
    {
        
        
        if($request->remi_codigo){
       
        $remision = Remision::findOrFail($request->remi_codigo);
        
            $remision->remi_motivo = $request->remi_motivo;
            $remision->remi_dir_partida = $request->remi_dir_partida; 
            $remision->remi_destinatario = $request->remi_destinatario;
            $remision->remi_ruc_destinatario = $request->remi_ruc_destinatario;
            $remision->remi_direccion = $request->remi_direccion;
            $remision->remi_nombre_transportista = $request->remi_nombre_transportista;
            $remision->remi_ci_transportista = $request->remi_ci_transportista;
            $remision->remi_detalle = $request->remi_detalle;
            $remision->remi_cantidad = $request->remi_cantidad;
            $remision->remi_unidad_medida = $request->remi_unidad_medida;
            $remision->trans_codigo = $request->trans_codigo;
            $remision->remi_guia_remision = $request->remi_guia_remision;
            $remision->remi_obs = $request->remi_obs;
            $remision->remi_responsable = $request->remi_responsable;
            $remision->remi_paquete = $request->remi_paquete;
            $remision->remi_funda = $request->remi_funda;
            $remision->remi_rollo = $request->remi_rollo;
            $remision->remi_flete = $request->remi_flete;
            $remision->remi_pagado = $request->remi_pagado;
            $remision->remi_idempresa = $request->remi_idempresa;
            $remision->updated_at = now();

       }else{
            $query = DB::SELECT("SELECT remi_codigo FROM 1_4_remision ORDER BY remi_fecha_inicio DESC, remi_codigo DESC LIMIT 1");
            $getSecuencia = 1;
             if(!empty($query)){
                $cod= $query[0]->remi_codigo;
                $codi = explode("-",$cod);
                $getSecuencia=$codi[1]+1;
                $pre=$codi[0];
                $co=$pre."-".$getSecuencia;

             }
             
        
           $remision = new Remision;
            
            $remision->remi_codigo = $co;
            $remision->remi_motivo = $request->remi_motivo;
            $remision->remi_dir_partida = $request->remi_dir_partida; 
            $remision->remi_destinatario = $request->remi_destinatario;
            $remision->remi_ruc_destinatario = $request->remi_ruc_destinatario;
            $remision->remi_direccion = $request->remi_direccion;
            $remision->remi_nombre_transportista = $request->remi_nombre_transportista;
            $remision->remi_ci_transportista = $request->remi_ci_transportista;
            $remision->remi_detalle = $request->remi_detalle;
            $remision->remi_cantidad = $request->remi_cantidad;
            $remision->remi_unidad_medida = $request->remi_unidad_medida;
            $remision->remi_num_factura = $request->remi_num_factura;
            $remision->remi_fecha_inicio = $request->remi_fecha_inicio;
            $remision->trans_codigo = $request->trans_codigo;
            $remision->remi_guia_remision = $request->remi_guia_remision;
            $remision->remi_obs = $request->remi_obs;
            $remision->remi_responsable = $request->remi_responsable;
            $remision->remi_paquete = $request->remi_paquete;
            $remision->remi_funda = $request->remi_funda;
            $remision->remi_rollo = $request->remi_rollo;
            $remision->remi_flete = $request->remi_flete;
            $remision->remi_pagado = $request->remi_pagado;
            $remision->remi_idempresa = $request->remi_idempresa;
            $remision->user_created = $request->user_created;
            $remision->created_at = now();
            $remision->updated_at = now();
           //$area->tipoareas_idtipoarea = $request->idtipoarea;
       }
       $remision->save();
       if($remision){
           return $remision;
       }else{
           return "No se pudo guardar/actualizar";
       }
    
    }
    
   

    public function Eliminar_Remision(Request $request){
        if ($request->remi_codigo) {
            $remision = Remision::find($request->remi_codigo);

            if (!$remision) {
                return "El remi_codigo no existe en la base de datos";
            }

           
            $remision->delete();

            return $remision;
        } else {
            return "No está ingresando ningún remi_codigo";
        }
        

    }

    // SOLO ES PARA REALIZAR EL REPORTE DE CALMED
    public function GetRemisionCALMED_FECHA_TRANSPORTE(Request $request){
        $query = DB::SELECT("SELECT r.*, t.trans_nombre, (select i.telefonoInstitucion from f_venta f 
        inner join institucion i on i.idInstitucion=f.institucion_id where f.ven_codigo=r.remi_num_factura and f.id_empresa=r.remi_idempresa) as telefono
         FROM remision_copy r
        LEFT JOIN 1_4_transporte t ON r.trans_codigo = t.trans_codigo
        WHERE DATE(REMI_FECHA_INICIO) = '$request->fecha_filtro' and remi_ci_transportista = '$request->cedula'
        ORDER BY remi_fecha_inicio ASC");
        return $query;
    }

    public function GetRemisionCALMED_FECHA(Request $request){
        if($request->op==0){
            $query = DB::SELECT("SELECT r.*, t.trans_nombre, (select concat(i.telefonoInstitucion,' ',u.telefono) from f_venta f 
        inner join institucion i on i.idInstitucion=f.institucion_id
        inner join usuario u on u.idusuario=f.ven_cliente
         where f.ven_codigo=r.remi_num_factura and f.id_empresa=r.remi_idempresa) as telefono,
            (select c.nombre from f_venta f
         inner join institucion i on i.idInstitucion=f.institucion_id 
         inner join ciudad c on i.ciudad_id=c.idciudad 
         where f.ven_codigo=r.remi_num_factura and f.id_empresa=r.remi_idempresa) as ciudad
            FROM remision_copy r
            LEFT JOIN 1_4_transporte t ON r.trans_codigo = t.trans_codigo
            WHERE DATE(REMI_FECHA_INICIO) = '$request->fecha_filtro'
            ORDER BY r.remi_fecha_inicio ASC");
        }else if($request->op==1){
            $query = DB::select("SELECT r.*, t.trans_nombre, (select concat(i.telefonoInstitucion,' ',u.telefono) from f_venta f 
        inner join institucion i on i.idInstitucion=f.institucion_id
        inner join usuario u on u.idusuario=f.ven_cliente
         where f.ven_codigo=r.remi_num_factura and f.id_empresa=r.remi_idempresa) as telefono,
         (select c.nombre from f_venta f
         inner join institucion i on i.idInstitucion=f.institucion_id 
         inner join ciudad c on i.ciudad_id=c.idciudad 
         where f.ven_codigo=r.remi_num_factura and f.id_empresa=r.remi_idempresa) as ciudad
            FROM remision_copy r
            LEFT JOIN 1_4_transporte t ON r.trans_codigo = t.trans_codigo
            WHERE DATE(r.REMI_FECHA_INICIO) = CURDATE()
            ORDER BY r.remi_fecha_inicio  ASC");
        }
        return $query;
    }
}
