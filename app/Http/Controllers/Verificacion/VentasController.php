<?php

namespace App\Http\Controllers;
use DB;
use App\Models\Ventas;
use App\Models\DetalleVentas;
use App\Models\Proforma;
use App\Models\f_tipo_documento;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VentasController extends Controller
{
    //
    public function Get_tdocu(){
        $query = DB::SELECT("SELECT *FROM f_tipo_documento where tdo_nombre<>'PROFORMA'");
            return $query;
    }
    public function Get_tipoVenta(){
        $query = DB::SELECT("SELECT *FROM 1_4_tipo_venta");
            return $query;
    }
    // listar las facturas
    public function GetFacturas(Request $request){
        $query = DB::SELECT("SELECT fv.*, ins.*, em.img_base64, usa.nombres, usa.apellidos, pe.observacion, fpr.prof_observacion, COUNT(dfv.pro_codigo) AS item, SUM(dfv.det_ven_cantidad) AS libros 
        FROM f_venta fv
        INNER JOIN f_proforma fpr ON fpr.prof_id=fv.ven_idproforma
        inner join empresas em on fpr.emp_id= em.id
        INNER JOIN pedidos pe ON fpr.pedido_id=pe.id_pedido
        inner join pedidos_beneficiarios as pb on fpr.pedido_id=pb.id_pedido
        INNER JOIN usuario usa ON pb.id_usuario=usa.idusuario
        INNER JOIN f_detalle_venta dfv ON fv.ven_codigo=dfv.ven_codigo 
		LEFT JOIN institucion ins ON fv.institucion_id=ins.idInstitucion
        WHERE fpr.pedido_id=$request->prof_id AND fv.ven_codigo LIKE 'F%' GROUP BY fv.ven_codigo");
            return $query;
    }
    
    public function Get_DFactura(Request $request){
        $query = DB::SELECT("SELECT dv.det_ven_codigo, dv.pro_codigo, dv.det_ven_cantidad, dv.det_ven_valor_u,  
        ls.nombre, s.nombre_serie, dv.det_ven_descontar, dv.det_ven_iva FROM f_detalle_venta as dv
   inner join f_venta as fv on dv.ven_codigo=fv.ven_codigo
   INNER JOIN libros_series as ls ON dv.pro_codigo=ls.codigo_liquidacion
   INNER JOIN series as s ON ls.id_serie=s.id_serie		 
        WHERe dv.ven_codigo='$request->ven_codigo'");
        
             return $query;
    }
    // listar las Notas
    public function GetNotas(Request $request){
        $query = DB::SELECT("SELECT fv.*, ins.*, usa.nombres, usa.apellidos, pe.observacion, fpr.prof_observacion, COUNT(dfv.pro_codigo) AS item, SUM(dfv.det_ven_cantidad) AS libros 
        FROM f_venta fv
        INNER JOIN f_proforma fpr ON fpr.prof_id=fv.ven_idproforma
        INNER JOIN pedidos pe ON fpr.pedido_id=pe.id_pedido
        inner join pedidos_beneficiarios as pb on fpr.pedido_id=pb.id_pedido
        INNER JOIN usuario usa ON pb.id_usuario=usa.idusuario
        INNER JOIN f_detalle_venta dfv ON fv.ven_codigo=dfv.ven_codigo 
		LEFT JOIN institucion ins ON fv.institucion_id=ins.idInstitucion
        WHERE fpr.pedido_id=$request->prof_id AND (fv.ven_codigo LIKE 'BC%' OR fv.ven_codigo LIKE 'AI%') GROUP BY fv.ven_codigo");
            return $query;
    }
    public function GetVuser(Request $request){
        $query1 = DB::SELECT("SELECT  us.nombres as nombre, us.apellidos as apellido FROM f_venta as ven 
        inner join usuario as us on ven.user_created = us.idusuario 
        where ven.ven_codigo='$request->ven_codigo'");
         $pre=$request->ven_codigo;
        $getSecuencia = 1;
             if(!empty($query1)){
                $cod= $query1[0]->nombre;
                $codi= $query1[0]->apellido;
                $user=$cod." ".$codi;
             }
             
             return $user;
    }
    public function Get_CodVenta(Request $request){
        $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial as cod from f_tipo_documento where tdo_letra='$request->letra'"); 
        
        $pre="$request->letra";
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
                    $secuencia = "0000".$getSecuencia;
                }else if($getSecuencia>9999 && $getSecuencia<100000){
                    $secuencia = "000".$getSecuencia;
                }else if($getSecuencia>99999 && $getSecuencia<1000000){
                    $secuencia = "00".$getSecuencia;
                }else if($getSecuencia>999999 && $getSecuencia<10000000){
                    $secuencia = "0".$getSecuencia;
                }else if($getSecuencia>9999999 && $getSecuencia<100000000){
                    $secuencia = $getSecuencia;
                }
                $array = array($pre,$secuencia);
             }else{
                if($getSecuencia>0 && $getSecuencia<10){
                    $secuencia = "000000".$getSecuencia;
                } else if($getSecuencia>9 && $getSecuencia<100){
                    $secuencia = "00000".$getSecuencia;
                } else if($getSecuencia>99 && $getSecuencia<1000){
                    $secuencia = "0000".$getSecuencia;
                }else if($getSecuencia>999 && $getSecuencia<10000){
                    $secuencia = "0000".$getSecuencia;
                }else if($getSecuencia>9999 && $getSecuencia<100000){
                    $secuencia = "000".$getSecuencia;
                }else if($getSecuencia>99999 && $getSecuencia<1000000){
                    $secuencia = "00".$getSecuencia;
                }else if($getSecuencia>999999 && $getSecuencia<10000000){
                    $secuencia = "0".$getSecuencia;
                }else if($getSecuencia>9999999 && $getSecuencia<100000000){
                    $secuencia = $getSecuencia;
                }
                $array = array($pre,$secuencia);
             }
             
             return $array;
    }

    public function Postventa_Registra(Request $request){
        try{
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $miarray=json_decode($request->data_detalleventa);
            DB::beginTransaction();
            $venta = new Ventas;
            $venta->ven_codigo = $request->ven_codigo;
            $venta->tip_ven_codigo = $request->tip_ven_codigo;
            $venta->est_ven_codigo = $request->est_ven_codigo;
            $venta->ven_observacion = $request->ven_observacion;
            $venta->ven_valor = $request->ven_valor;
            if($request->ven_com_porcentaje>0){
            $venta->ven_com_porcentaje = $request->ven_com_porcentaje;
            $venta->ven_desc_por=0;
            } else{
                $venta->ven_com_porcentaje = 0;
                $venta->ven_desc_por=$request->ven_desc_por;
            }
            $venta->ven_iva = $request->ven_iva;
            $venta->ven_descuento = $request->ven_descuento;
            $venta->ven_fecha = $request->ven_fecha;
            $venta->ven_idproforma = $request->ven_idproforma;
            $venta->ven_transporte = $request->ven_transporte;
            $venta->institucion_id = $request->institucion_id;
            $venta->periodo_id = $request->periodo_id;
            $venta->ven_estado = $request->ven_estado;
            $venta->user_created = $request->user_created;
            $venta->id_empresa = $request->id_empresa;
            $venta->save();
            $proform= Proforma::findOrFail($request->ven_idproforma);
            $proform->prof_estado = $request->prof_estado;
            $proform->save();
            $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial as cod from f_tipo_documento where tdo_letra='$request->letra'"); 
            $id=$query1[0]->id;
            $codi=$query1[0]->cod;
            $co=(int)$codi+1;
            $tipo_doc = f_tipo_documento::findOrFail($id);
            $tipo_doc->tdo_secuencial = $co;
            $tipo_doc->save();
              
            if ($venta->save()) {
                foreach($miarray as $key => $item){
                    if($item->det_prof_cantidad != 0){
                        $venta1=new DetalleVentas;
                        $venta1->ven_codigo = $request->ven_codigo;
                        $venta1->pro_codigo = $item->pro_codigo;
                        $venta1->det_ven_cantidad = $item->det_prof_cantidad;
                        $venta1->det_ven_valor_u = $item->det_prof_valor_u;
                        $venta1->det_ven_iva = $item->iva;
                        $venta1->det_ven_descontar = $item->descuento;
                        $venta1->save();
                    }
                }
            }
            DB::commit();
        }catch(\Exception $e){
            return ["error"=>"0", "message" => "No se pudo guardar","error"=>$e];
            DB::rollback();
            Log::error('Error al guardar datos: ' . $e->getMessage());
        }
        if($venta){
        return ["status"=>"1", "message" => "Se guardo correctamente"];
        }else{
        return ["error"=>"0", "message" => "No se pudo guardar"];
        }
        if($venta1){
            return ["status"=>"1", "message" => "Se guardo correctamente"];
            }else{
            return ["error"=>"0", "message" => "No se pudo guardar"];
            }
    }
    //Cambiar el estado de venta
    public function Desactivar_venta(Request $request)
    {
            $venta= Ventas::findOrFail($request->ven_codigo);

            if (!$venta){
                return "El ven_codigo no existe en la base de datos";
            }

            $venta->est_ven_codigo = $request->est_ven_codigo;
            $venta->save();

            return $venta;       
        
    }
}
