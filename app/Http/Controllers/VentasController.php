<?php

namespace App\Http\Controllers;
use App\Models\Ventas;
use App\Models\DetalleVentas;
use App\Models\VentasF;
use App\Models\DetalleVentasF;
use App\Models\Proforma;
use App\Models\f_tipo_documento;
use App\Models\_14Producto ;
use App\Http\Controllers\Log;
use DB;
use App\Http\Controllers\Controller;
use App\Models\Pedidos;
use App\Models\User;
use App\Traits\Pedidos\TraitProforma;
use Illuminate\Http\Request;

class VentasController extends Controller
{
    use TraitProforma;
    //
    public function Get_tdocu(){
        $query = DB::SELECT("SELECT *FROM f_tipo_documento where (tdo_id=1 or tdo_id=3 or tdo_id=4) and tdo_estado=1");
            return $query;
    }
    public function Get_tipoVenta(){
        $query = DB::SELECT("SELECT *FROM 1_4_tipo_venta");
            return $query;
    }
    public function GetVentas(Request $request){
        if($request->tipo==0){
        $query1 = DB::SELECT("SELECT dv.pro_codigo,  SUM(dv.det_ven_cantidad) AS cantidad FROM f_venta AS v
        INNER JOIN f_detalle_venta AS dv ON v.ven_codigo=dv.ven_codigo
        INNER JOIN f_proforma AS p ON p.prof_id=v.ven_idproforma
        INNER JOIN pedidos AS pe ON pe.id_pedido=p.pedido_id
        WHERE pe.id_pedido=$request->pedido_id AND (v.est_ven_codigo = 2 OR v.est_ven_codigo = 1) AND p.pedido_id=$request->pedido_id
        GROUP BY dv.pro_codigo");
        return $query1;
        }else if($request->tipo== 1){
        $query1 = DB::SELECT("SELECT SUM(dv.det_ven_cantidad) AS cantidad, dv.pro_codigo FROM f_venta AS v
        INNER JOIN f_detalle_venta dv ON v.ven_codigo=dv.ven_codigo
		  INNER JOIN f_proforma fp ON fp.prof_id=v.ven_idproforma
        WHERE fp.idPuntoventa = '$request->pedido_id' AND (v.est_ven_codigo = 2 OR v.est_ven_codigo = 1)
        GROUP BY dv.pro_codigo");
        return $query1;
    }
    }
    // listar las facturas
    public function GetFacturasX(Request $request){
        $query = DB::SELECT("SELECT fv.*, ins.nombreInstitucion,ins.direccionInstitucion, ins.telefonoInstitucion, em.img_base64, usa.nombres, usa.apellidos, fpr.prof_observacion,
        COUNT(DISTINCT dfv.pro_codigo) AS item,
        (SELECT SUM(det_ven_cantidad)
         FROM f_detalle_venta
         WHERE ven_codigo = fv.ven_codigo) AS libros,usa.cedula, usa.email,usa.telefono
        FROM f_venta fv
        LEFT JOIN f_proforma fpr ON fpr.prof_id = fv.ven_idproforma
        LEFT JOIN empresas em ON fpr.emp_id = em.id
        LEFT JOIN pedidos pe ON fpr.idPuntoventa = pe.ca_codigo_agrupado
        LEFT JOIN institucion ins ON fpr.id_ins_depacho = ins.idInstitucion
        LEFT JOIN usuario usa ON fpr.ven_cliente = usa.idusuario
        LEFT JOIN f_detalle_venta dfv ON fv.ven_codigo = dfv.ven_codigo
        WHERE fpr.idPuntoventa = '$request->prof_id' and fv.periodo_id=$request->periodo and (fv.idtipodoc=1 or fv.idtipodoc=3 or fv.idtipodoc=4)
        GROUP BY fv.ven_codigo, em.img_base64, usa.nombres, usa.apellidos, fpr.prof_observacion order by ven_fecha desc
        ");
        return $query;
    }
    public function GetPreFacturasxAgrupa(Request $request){
        if(!$request->busqueda){
            return;
        }else{
            $query = DB::SELECT("SELECT
            fv.ven_codigo,
            fv.ven_fecha,
            fv.ven_idproforma,
            fv.proformas_codigo,
            fv.est_ven_codigo,
            fv.ven_valor,
            fv.id_empresa,
            fv.estadoPerseo,
            fv.*,
            ins.nombreInstitucion,
            ins.direccionInstitucion,
            ins.telefonoInstitucion,
            usa.nombres,
            usa.apellidos,
            usa.cedula,
            usa.email,
            usa.telefono,
            em.nombre,
            em.img_base64,
            COUNT(DISTINCT dfv.pro_codigo) AS item,
            SUM(dfv.det_ven_cantidad) AS libros,
            SUM(dfv.det_ven_dev) AS devuelto
        FROM
            f_venta fv
            INNER JOIN institucion ins ON fv.institucion_id = ins.idInstitucion
            INNER JOIN usuario usa ON fv.ven_cliente = usa.idusuario
            INNER JOIN f_detalle_venta dfv ON fv.ven_codigo = dfv.ven_codigo
            INNER JOIN empresas em ON em.id = fv.id_empresa
        WHERE
            (ins.nombreInstitucion LIKE '%$request->busqueda%' OR usa.cedula LIKE '%$request->busqueda%')
            AND fv.id_empresa=$request->empresa
            AND dfv.id_empresa = fv.id_empresa
            AND fv.proformas_codigo IS NOT NULL
            AND fv.idtipodoc = 1
            AND fv.id_factura  IS NULL
            AND fv.periodo_id=$request->periodo
        GROUP BY
            fv.ven_codigo,
            fv.ven_fecha,
            fv.ven_idproforma,
            fv.proformas_codigo,
            fv.est_ven_codigo,
            fv.ven_valor,
            ins.nombreInstitucion,
            ins.direccionInstitucion,
            ins.telefonoInstitucion,
            usa.nombres,
            usa.apellidos,
            usa.cedula,
            usa.email,
            usa.telefono
        ORDER BY
            fv.ven_fecha DESC");
    return $query;
        }
    }
    public function GetFacturasxAgrupa(){

            $query = DB::SELECT("SELECT
            fv.id_factura,
            fv.ven_cliente,
            fv.ven_fecha,
            fv.ven_valor,
            fv.id_empresa,
            fv.estadoPerseo,
            fv.*,
            ins.nombreInstitucion,
            ins.direccionInstitucion,
            ins.telefonoInstitucion,
            usa.nombres,
            usa.apellidos,
            usa.cedula,
            usa.email,
            usa.telefono,
            em.nombre,
            em.img_base64,
            COUNT(DISTINCT dfv.pro_codigo) AS item,
            SUM(dfv.det_ven_cantidad) AS libros
        FROM
            f_venta_agrupado fv
            INNER JOIN institucion ins ON fv.institucion_id = ins.idInstitucion
            INNER JOIN usuario usa ON fv.ven_cliente = usa.idusuario
            INNER JOIN f_detalle_venta_agrupado dfv ON fv.id_factura = dfv.id_factura
            INNER JOIN empresas em ON em.id = fv.id_empresa
        WHERE dfv.id_empresa = fv.id_empresa
            AND fv.idtipodoc = 11
        GROUP BY
            fv.id_factura,
            fv.ven_cliente,
            fv.ven_fecha,
            fv.ven_valor,
             fv.ven_valor,
            fv.id_empresa,
            fv.estadoPerseo,
            ins.nombreInstitucion,
            ins.direccionInstitucion,
            ins.telefonoInstitucion,
            usa.nombres,
            usa.apellidos,
            usa.cedula,
            usa.email,
            usa.telefono
        ORDER BY
            fv.ven_fecha DESC");
    return $query;
    }


    public function Get_DFactura(Request $request){
        $query = DB::SELECT("SELECT dv.det_ven_codigo, dv.pro_codigo, dv.det_ven_cantidad, dv.det_ven_valor_u,
        ls.nombre, s.nombre_serie FROM f_detalle_venta as dv
   inner join f_venta as fv on dv.ven_codigo=fv.ven_codigo
   INNER JOIN libros_series as ls ON dv.pro_codigo=ls.codigo_liquidacion
   INNER JOIN series as s ON ls.id_serie=s.id_serie
        WHERe dv.ven_codigo='$request->ven_codigo'");

             return $query;
    }
    // listar las Notas
    // public function GetNotas(Request $request){
    //     if($request->tipo==0){
    //     $query = DB::SELECT("SELECT fv.*, ins.*, usa.nombres, usa.apellidos, pe.observacion, fpr.prof_observacion, COUNT(dfv.pro_codigo) AS item, SUM(dfv.det_ven_cantidad) AS libros
    //     FROM f_venta fv
    //     INNER JOIN f_proforma fpr ON fpr.prof_id=fv.ven_idproforma
    //     INNER JOIN pedidos pe ON fpr.pedido_id=pe.id_pedido
    //     inner join pedidos_beneficiarios as pb on fpr.pedido_id=pb.id_pedido
    //     INNER JOIN usuario usa ON pb.id_usuario=usa.idusuario
    //     INNER JOIN f_detalle_venta dfv ON fv.ven_codigo=dfv.ven_codigo
	// 	LEFT JOIN institucion ins ON fv.institucion_id=ins.idInstitucion
    //     WHERE fpr.pedido_id=$request->prof_id AND (fv.ven_codigo LIKE 'BC%' OR fv.ven_codigo LIKE 'AI%' OR fv.ven_codigo LIKE 'N%') GROUP BY fv.ven_codigo order by ven_fecha desc");
    //         return $query;
    //     }else if($request->tipo== 1){
    //         $query = DB::SELECT("SELECT fv.*, ins.nombreInstitucion,ins.direccionInstitucion, ins.telefonoInstitucion, em.img_base64, usa.nombres, usa.apellidos, fpr.prof_observacion,
    //         COUNT(DISTINCT dfv.pro_codigo) AS item,
    //         (SELECT SUM(det_ven_cantidad)
    //         FROM f_detalle_venta
    //         WHERE ven_codigo = fv.ven_codigo) AS libros,usa.cedula, usa.email,usa.telefono
    //         FROM f_venta fv
    //         LEFT JOIN f_proforma fpr ON fpr.prof_id = fv.ven_idproforma
    //         LEFT JOIN empresas em ON fpr.emp_id = em.id
    //         LEFT JOIN pedidos pe ON fpr.idPuntoventa = pe.ca_codigo_agrupado
    //         LEFT JOIN institucion ins ON fpr.id_ins_depacho = ins.idInstitucion
    //         LEFT JOIN usuario usa ON fpr.ven_cliente = usa.idusuario
    //         LEFT JOIN f_detalle_venta dfv ON fv.ven_codigo = dfv.ven_codigo
    //         WHERE fpr.idPuntoventa = '$request->prof_id'
    //             AND (fv.ven_codigo LIKE 'BC%' OR fv.ven_codigo LIKE 'AI%' OR fv.ven_codigo LIKE 'N%')
    //         GROUP BY fv.ven_codigo, em.img_base64, usa.nombres, usa.apellidos, fpr.prof_observacion order by ven_fecha desc");
    //         return $query;
    //     }
    // }
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
        if($request->id==1){
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_Prolipa as cod from  f_tipo_documento where tdo_letra='$request->letra'");
        }else if ($request->id==3){
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_calmed as cod from  f_tipo_documento where tdo_letra='$request->letra'");
        }
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
                    $secuencia = "000".$getSecuencia;
                }else if($getSecuencia>9999 && $getSecuencia<100000){
                    $secuencia = "00".$getSecuencia;
                }else if($getSecuencia>99999 && $getSecuencia<1000000){
                    $secuencia = "0".$getSecuencia;
                }else if($getSecuencia>999999 && $getSecuencia<10000000){
                    $secuencia = $getSecuencia;
                }
                $array = array($pre,$secuencia);
             }
             return $array;
    }
    public function Verificarventa(Request $request){
        $query = DB::SELECT("SELECT fdv.ven_codigo, fdv.id_empresa, fdv.pro_codigo, SUM(fdv.det_ven_cantidad) as venta, SUM(fdp.det_prof_cantidad)AS proforma FROM f_venta fv
        INNER JOIN f_detalle_venta fdv ON fv.ven_codigo=fdv.ven_codigo
        INNER JOIN f_proforma fp ON fp.prof_id=fv.ven_idproforma
        INNER JOIN f_detalle_proforma fdp ON fdp.prof_id=fp.id
        WHERE fv.id_empresa=fdv.id_empresa AND fv.id_empresa=fp.emp_id AND fdv.pro_codigo=fdp.pro_codigo AND fv.ven_idproforma='$request->prof_id' AND fv.est_ven_codigo<>3
        GROUP BY fdv.ven_codigo, fdv.id_empresa, fdv.pro_codigo");
                return $query;
    }
    public function getNumeroDocumento($empresa,$letra){
        if($empresa==1){
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_Prolipa as cod from  f_tipo_documento where tdo_letra='$letra'");
        }else if ($empresa==3){
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_calmed as cod from  f_tipo_documento where tdo_letra='$letra'");
        }
        $pre="$letra";
        $getSecuencia = 1;
        if(!empty($query1)){
            $pre= $query1[0]->tdo_letra;
            $codi=$query1[0]->cod;
            $getSecuencia=(int)$codi+1;
            if($letra == 'F'){
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
                $secuencia = $secuencia;
                return $secuencia;
            }else{
                return $getSecuencia;
            }
        }
    }
    public function Postventa_Registra(Request $request){
        $letraDocumento     = $request->letra;
        $id_empresa         = $request->id_empresa;
        $codigo_contrato    = $request->codigo_contrato;
        $cod_usuario        = $request->cod_usuario;
        // $getNumeroDocumento = $this->getNumeroDocumento($id_empresa,$letraDocumento);
        // if($letraDocumento == 'F'){
        //     $ven_codigo         = $letraDocumento."-".$codigo_contrato."-".$cod_usuario."-".$getNumeroDocumento;
        // }else{
        //     $ven_codigo         = "N-".$codigo_contrato."-".$cod_usuario."-".$letraDocumento.$getNumeroDocumento;
        // }
        try {
            $idProforma = $request->idProforma;
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $miarray = json_decode($request->data_detalleventa);
            //setMenu 0 => por pedidos, 1 => por punto de venta
            $setMenu                        = $request->setMenu;
            //tipod 0 => despacho en institucion; 1 => despacho en punto de venta
            $tipodoc                        = $request->tipodoc;
            //si es setMenu 0 voy a validar que este colocado el ca_codigo_agrupado de la tabla pedidos
            if($setMenu==0){
                $query = DB::SELECT("SELECT ca_codigo_agrupado, id_institucion FROM pedidos WHERE id_pedido=$request->id_pedido");
                //si es tipodoc 1 voy a validar que este colocado el ca_codigo_agrupado de la tabla pedidos
                if($tipodoc == 1){
                    if(empty($query)){ return ["status" => "0", "No se encontro el pedido"]; }
                    //si el ca_codigo_agrupado es nulo o cero o vacio mandar un mensaje seleccione el despacho
                    if($query[0]->ca_codigo_agrupado==0 || $query[0]->ca_codigo_agrupado==null || $query[0]->ca_codigo_agrupado==""){
                        return ["status" => "0", "message" => "Seleccione el despacho"];
                    }
                }
                //si es tipodoc 0 por institucion voy a tomar el id_institucion y voy a colocar en el ca_codigo_agrupado en la tabla pedidos
                if($tipodoc == 0){

                }
            }
            DB::beginTransaction();
            //si el ven_codigo ya existe no creo
            $query = DB::SELECT("SELECT ven_codigo FROM f_venta WHERE ven_codigo='$request->ven_codigo' AND id_empresa = '$request->id_empresa'");
            if(!empty($query)){
                $this->finalizarDocumento($idProforma);
                return ["status" => "2", "message" => "El ven_codigo ya existe"];
            }else{
                $id_empresa                 = $request->id_empresa;
                $datoProforma               = Proforma::findOrFail($request->idProforma);
                $clientesidPerseo           = $datoProforma->clientesidPerseo;
                $venta                      = new Ventas;
                $venta->ven_codigo          = $request->ven_codigo;
                $venta->tip_ven_codigo      = $request->tip_ven_codigo;
                $venta->est_ven_codigo      = $request->est_ven_codigo;
                $venta->ven_tipo_inst       = $request->ven_tipo_inst;
                $venta->ven_valor           = $request->ven_valor;
                $venta->ven_com_porcentaje  = $request->ven_com_porcentaje;
                $venta->ven_iva_por         = $request->ven_iva_por;
                $venta->ven_iva             = $request->ven_iva;
                $venta->ven_desc_por        = $request->ven_desc_por;
                $venta->ven_descuento       = $request->ven_descuento;
                $venta->ven_fecha           = $request->ven_fecha;
                $venta->ven_idproforma      = $request->ven_idproforma;
                $venta->ven_transporte      = $request->ven_transporte;
                // $venta->institucion_id      = $request->institucion_id;
                $venta->periodo_id          = $request->periodo_id;
                $venta->user_created        = $request->user_created;
                $venta->id_empresa          = $request->id_empresa;
                $venta->institucion_id      = $request->institucion_id;
                // $venta->id_sucursal         = $request->id_sucursal;
                $venta->idtipodoc           = $request->l;
                $venta->ven_cliente         = $request->ven_cliente;
                $user                       = User::where('idusuario',$request->ven_cliente)->first();
                $getCedula                  = $user->cedula;
                $venta->ruc_cliente         = $getCedula;
                $venta->clientesidPerseo    = $clientesidPerseo;
                $venta->save();
                $oldvalue                   = Proforma::findOrFail($request->idProforma);
                $proform                    = Proforma::findOrFail($request->idProforma);
                if($request->prof_estado == 2){

                }else{
                    $proform->prof_estado       = $request->prof_estado;
                }

                $proform->user_editor       = $request->user_created;
                $proform->save();
                if($proform){
                    $newvalue               = Proforma::findOrFail($request->idProforma);
                    $this->tr_GuardarEnHistorico($request->ven_idproforma,$request->user_created,$oldvalue,$newvalue);
                }
                if($request->id_empresa==1){
                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from  f_tipo_documento where tdo_letra='$request->letra'");
                }else if ($request->id_empresa==3){
                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from  f_tipo_documento where tdo_letra='$request->letra'");
                }
                $id=$query1[0]->id;
                $codi=$query1[0]->cod;
                $co=(int)$codi+1;
                $tipo_doc = f_tipo_documento::findOrFail($id);
                if($request->id_empresa==1){
                    $tipo_doc->tdo_secuencial_Prolipa = $co;
                }else if ($request->id_empresa==3){
                    $tipo_doc->tdo_secuencial_calmed = $co;
                }
                $tipo_doc->save();
                foreach($miarray as $key => $item){
                    if($item->cantidad > 0){
                       // `pro_stockCalmed` INT(11) NULL DEFAULT NULL COMMENT 'factura CALMED',
                        //`pro_depositoCalmed` INT(11) NULL DEFAULT NULL COMMENT 'notas CALMED',
                        //l 1 => facturas; 3 y 4 notas
                        //id_empresa 1 => Prolipa; 2 = Iluminar; 3 = Grupo Calmed
                        //si es prolipa id_empresa=1
                        if($id_empresa==1){
                            if($request->l==1){
                                $query2 = DB::SELECT("SELECT pro_stock as stoc from 1_4_cal_producto where pro_codigo='$item->pro_codigo'");
                            }else{
                                $query2 = DB::SELECT("SELECT pro_deposito as stoc from 1_4_cal_producto where pro_codigo='$item->pro_codigo'");
                            }
                        }
                        //grupo calmed
                        if($id_empresa==3){
                            if($request->l==1){
                                $query2 = DB::SELECT("SELECT pro_stockCalmed as stoc from 1_4_cal_producto where pro_codigo='$item->pro_codigo'");
                            }else{
                                $query2 = DB::SELECT("SELECT pro_depositoCalmed as stoc from 1_4_cal_producto where pro_codigo='$item->pro_codigo'");
                            }
                        }
                        $codi=$query2[0]->stoc;
                        $this->descontarStock($codi,$item,$request->l,$id_empresa);
                        $venta1=new DetalleVentas;
                        $venta1->ven_codigo         = $request->ven_codigo;
                        $venta1->id_empresa         = $request->id_empresa;
                        $venta1->pro_codigo         = $item->pro_codigo;
                        $venta1->det_ven_cantidad   = $item->cantidad;
                        $venta1->det_ven_valor_u    = $item->det_prof_valor_u;
                        $venta1->idProforma         = $idProforma;
                        $venta1->save();
                    }
                }
            }
            $this->finalizarDocumento($idProforma);
            //finalizar documento
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return ["error" => "0", "message" => "No se pudo guardar", "exception" => $e->getMessage()];
        }
        return $venta1;
    }
    public function finalizarDocumento($idProforma){
        $proforma       = Proforma::where('id', $idProforma)->first();
        //obtengo los valores de la proforma
        $dataProforma   = DB::SELECT("SELECT * FROM f_detalle_proforma dp
        WHERE dp.prof_id  = '$idProforma';
        ");
        $datosFinalizar = [];
        foreach($dataProforma as $key => $item){
            $cantidadProforma = $item->det_prof_cantidad;
            //buscar la cantidad facturada
            $getFacturada = DB::SELECT("SELECT SUM(d.det_ven_cantidad) AS cantidadFacturada
            FROM f_detalle_venta d
            LEFT JOIN f_venta v ON d.ven_codigo = v.ven_codigo
            WHERE d.idProforma = '$idProforma'
            AND d.pro_codigo = '$item->pro_codigo'
            AND v.est_ven_codigo <> '3'
            ");
            //si la cantidadFacturada es igual a la cantidadFacturada guardo el producto con un estado 2 en un array
            if($getFacturada[0]->cantidadFacturada == $cantidadProforma){
                $datosFinalizar[] = [
                    'pro_codigo' => $item->pro_codigo,
                    'estado'     => 2
                ];
            }
            //de lo contrato con estado 1
            else{
                $datosFinalizar[] = [
                    'pro_codigo' => $item->pro_codigo,
                    'estado'     => 1
                ];
            }
        }
        //si dentro de mi array datosFinalizar no encuentro un estado 1 que significa que todos los productos fueron facturados
        //cambio el estado a 2 el prof_estado
        $ifEncontrarNoFinalizado =0;
        foreach($datosFinalizar as $key => $item){
            if($item['estado'] == 1){
                $ifEncontrarNoFinalizado = 1;
            }
        }
        if($ifEncontrarNoFinalizado == 0){
            $proforma->prof_estado = 2;
            $proforma->save();
            return "finalizado";
        }else{
            return "no finalizado";
        }
    }
    public function descontarStock($codi,$item,$tipo,$id_empresa){
        //tipo 1 => facturas; 3 y 4 notas
        //id_empresa 1 => Prolipa; 2 = Iluminar; 3 = Grupo Calmed
        $co         = (int) $codi-(int)$item->cantidad;
        $pro        = _14Producto::findOrFail($item->pro_codigo);
        //PROLIPA
        if($id_empresa ==1){
            if($tipo==1){
                $pro->pro_stock = $co;
            }else{
                $pro->pro_deposito= $co;
            }
        }
        //CALMED
        if($id_empresa ==3){
            if($tipo==1){
                $pro->pro_stockCalmed = $co;
            }else{
                $pro->pro_depositoCalmed= $co;
            }
        }
        $pro->save();
    }
    //Cambiar el estado de venta
    public function Desactivar_venta(Request $request)
    {
        if($request->tipo=='anular'){
            try{
                set_time_limit(6000000);
                ini_set('max_execution_time', 6000000);
                $miarray=json_decode($request->dat);
                DB::beginTransaction();
                $venta = Ventas::where('ven_codigo', $request->ven_codigo)
                ->where('id_empresa', $request->empresa)
                ->firstOrFail();
                    if (!$venta){
                        return "El ven_codigo no existe en la base de datos";
                    }
                    $venta->est_ven_codigo  = $request->est_ven_codigo;
                    $venta->save();
                    foreach($miarray as $key => $item){
                        //con pro_stock y  pro_reservar
                        if($request->empresa==1){
                            if($request->tipodoc==1){
                                $query1 = DB::SELECT("SELECT pro_stock as stoc, pro_reservar as res from 1_4_cal_producto where pro_codigo='$item->pro_codigo'");
                            }else{
                                $query1 = DB::SELECT("SELECT pro_deposito as stoc, pro_reservar as res from 1_4_cal_producto where pro_codigo='$item->pro_codigo'");
                            }
                        }
                        //grupo calmed
                        if($request->empresa==3){
                            if($request->tipodoc==1){
                                $query1 = DB::SELECT("SELECT pro_stockCalmed as stoc, pro_reservar as res from 1_4_cal_producto where pro_codigo='$item->pro_codigo'");
                            }else{
                                $query1 = DB::SELECT("SELECT pro_depositoCalmed as stoc, pro_reservar as res from 1_4_cal_producto where pro_codigo='$item->pro_codigo'");
                            }
                        }
                        //obtener stock y reservar o bodega general
                        $codi       = $query1[0]->stoc;
                        // $res        = $query1[0]->res;
                        //l 1 => facturas; 3 y 4 notas
                        //id_empresa 1 => Prolipa; 2 = Iluminar; 3 = Grupo Calmed
                        //regresar stock
                        $this->regresarStock($codi,$item,$request->tipodoc,$request->empresa);

                    }
                    $proform= Proforma::where('prof_id', $request->proforma)->where('emp_id', $request->empresa)->firstOrFail();
                    $proform->prof_estado = 3;
                    $proform->save();
                DB::commit();
                return response()->json(['message' => 'Anulación exitosa de la venta.'], 200);
            }catch(\Exception $e){
                return response()->json(["error"=>"0", "message" => "No se pudo anular","error"=>$e->getMessage()],500);
                DB::rollback();
            }

        }else{
            $venta= Ventas::findOrFail($request->ven_codigo);
            if (!$venta){
                return "El ven_codigo no existe en la base de datos";
            }
            $venta->est_ven_codigo = $request->est_ven_codigo;
            $venta->save();
            return $venta;
        }
    }
    public function regresarStock($codi,$item,$tipo,$id_empresa){
        //tipo 1 => facturas; 3 y 4 notas
        //id_empresa 1 => Prolipa; 2 = Iluminar; 3 = Grupo Calmed
        // $re         = (int)$res+$item->det_ven_cantidad;
        $co         = (int)$codi+(int)$item->det_ven_cantidad;
        $pro        = _14Producto::findOrFail($item->pro_codigo);
        //PROLIPA
        if($id_empresa ==1){
            // $pro->pro_reservar          = $re;
            if($tipo==1){
                $pro->pro_stock         = $co;
            }else{
                $pro->pro_deposito      = $co;
            }
            $pro->save();
        }
        //CALMED
        if($id_empresa ==3){
            // $pro->pro_reservar          = $re;
            if($tipo==1){
                $pro->pro_stockCalmed   = $co;
            }else{
                $pro->pro_depositoCalmed= $co;
            }
            $pro->save();
        }
    }
    //APIS PARA DESPACHO
    public function GetVentasPendientes() {
        $query1 = DB::table('f_venta as f')
            ->select(
                'f.*',
                'tv.tip_ven_nombre',
                'i.nombreInstitucion',
                'pe.periodoescolar',
                DB::raw("CONCAT(u.nombres,' ',u.apellidos) AS responsable"),
                DB::raw("CONCAT(us.nombres,' ',us.apellidos) AS cliente"),
                'pr.pedido_id',
                DB::raw("SUM(dv.det_ven_cantidad) AS cantidad_despacho")
            )
            ->join('1_4_tipo_venta as tv', 'f.tip_ven_codigo', '=', 'tv.tip_ven_codigo')
            ->join('institucion as i', 'f.institucion_id', '=', 'i.idInstitucion')
            ->join('periodoescolar as pe', 'f.periodo_id', '=', 'pe.idperiodoescolar')
            ->join('usuario as u', 'f.user_created', '=', 'u.idusuario')
            ->join('usuario as us', 'f.ven_cliente', '=', 'us.idusuario')
            ->join('f_proforma as pr', 'f.ven_idproforma', '=', 'pr.prof_id')
            ->join('f_detalle_venta as dv', 'f.ven_codigo', '=', 'dv.ven_codigo')
            ->where('f.est_ven_codigo', 2)
            ->groupBy('f.ven_codigo');
        $query2 = DB::table('f_venta as f')
            ->select(
                'f.*',
                'tv.tip_ven_nombre',
                'i.nombreInstitucion',
                'pe.periodoescolar',
                DB::raw("CONCAT(u.nombres,' ',u.apellidos) AS responsable"),
                DB::raw("CONCAT(us.nombres,' ',us.apellidos) AS cliente"),
                'pl.id_pedido AS pedido_id',
                'pl.total_unidades AS cantidad_despacho'
            )
            ->join('1_4_tipo_venta as tv', 'f.tip_ven_codigo', '=', 'tv.tip_ven_codigo')
            ->join('institucion as i', 'f.institucion_id', '=', 'i.idInstitucion')
            ->join('periodoescolar as pe', 'f.periodo_id', '=', 'pe.idperiodoescolar')
            ->join('usuario as u', 'i.asesor_id', '=', 'u.idusuario')
            ->join('usuario as us', 'f.ven_cliente', '=', 'us.idusuario')
            ->join('p_libros_obsequios as pl', 'pl.id', '=', 'f.ven_p_libros_obsequios')
            ->where('f.est_ven_codigo', 2);

        $result = $query1->union($query2)->orderBy('ven_fecha')->get();

        return $result;
    }
    public function GetVentasDespachadasxParametro(Request $request){
        $query1 = DB::table('f_venta as f')
        ->select(
            'f.*',
            'tv.tip_ven_nombre',
            'i.nombreInstitucion',
            'pe.periodoescolar',
            DB::raw("CONCAT(u.nombres,' ',u.apellidos) AS responsable"),
            'pr.pedido_id',
            DB::raw("SUM(dv.det_ven_cantidad) AS cantidad_despacho")
        )
        ->join('1_4_tipo_venta as tv', 'f.tip_ven_codigo', '=', 'tv.tip_ven_codigo')
        ->join('institucion as i', 'f.institucion_id', '=', 'i.idInstitucion')
        ->join('periodoescolar as pe', 'f.periodo_id', '=', 'pe.idperiodoescolar')
        ->join('usuario as u', 'i.asesor_id', '=', 'u.idusuario')
        ->join('f_proforma as pr', 'f.ven_idproforma', '=', 'pr.prof_id')
        ->join('f_detalle_venta as dv', 'f.ven_codigo', '=', 'dv.ven_codigo')
        ->where('f.ven_codigo', 'like', '%' . $request->parte_documento . '%')
        ->where('f.est_ven_codigo', 1)
        ->groupBy('f.ven_codigo');

        $query2 = DB::table('f_venta as f')
        ->select(
            'f.*',
            'tv.tip_ven_nombre',
            'i.nombreInstitucion',
            'pe.periodoescolar',
            DB::raw("CONCAT(u.nombres,' ',u.apellidos) AS responsable"),
            'pl.id_pedido AS pedido_id',
            'pl.total_unidades AS cantidad_despacho'
        )
        ->join('1_4_tipo_venta as tv', 'f.tip_ven_codigo', '=', 'tv.tip_ven_codigo')
        ->join('institucion as i', 'f.institucion_id', '=', 'i.idInstitucion')
        ->join('periodoescolar as pe', 'f.periodo_id', '=', 'pe.idperiodoescolar')
        ->join('usuario as u', 'i.asesor_id', '=', 'u.idusuario')
        ->join('p_libros_obsequios as pl', 'pl.documento', '=', 'f.ven_codigo')
        ->where('f.ven_codigo', 'like', '%' . $request->parte_documento . '%')
        ->where('f.est_ven_codigo', 1);

        $result = $query1->union($query2)->orderBy('ven_fecha')->get();

        return $result;

    }
    public function GetDetalleVentaxNumeroDocumentoParametro(Request $request){
        $query = DB::SELECT("SELECT dv.*, p.pro_nombre FROM f_detalle_venta dv
        INNER JOIN 1_4_cal_producto p ON dv.pro_codigo = p.pro_codigo
        WHERE dv.ven_codigo = '$request->numero_documento'");
        return $query;
    }
    public function get_facturasNotasxParametro(Request $request){
        $query = DB::SELECT("SELECT fv.* FROM f_venta fv
        WHERE fv.institucion_id='$request->institucion'
        AND fv.periodo_id='$request->periodo'
        AND fv.id_empresa='$request->empresa'
        AND fv.est_ven_codigo <> 3");
        return $query;
    }
    public function InstitucionesDesp(Request $request){
        if($request->op==0){
            $query = DB::SELECT("SELECT inst.idInstitucion, inst.*, ci.nombre AS ciudad, CONCAT(usa.nombres,' ', usa.apellidos) AS cliente,fv_filtered.id_empresa
            FROM institucion inst
            INNER JOIN (
                SELECT DISTINCT id_institucion, id_empresa
                FROM f_venta
                WHERE id_empresa = $request->id AND (est_ven_codigo = 10 OR est_ven_codigo = 2)
            ) fv_filtered ON fv_filtered.id_institucion = inst.idInstitucion
            INNER JOIN usuario usa ON usa.idusuario = (SELECT ven_cliente FROM f_venta WHERE id_institucion = inst.idInstitucion LIMIT 1)
            INNER JOIN ciudad ci ON ci.idciudad = inst.ciudad_id;");
            // SELECT inst.*, fv.id_empresa, ci.nombre AS ciudad, CONCAT(usa.nombres, usa.apellidos) AS cliente FROM institucion inst
            // INNER JOIN f_venta  fv ON fv.institucion_id=inst.idInstitucion
            // INNER JOIN usuario usa ON usa.idusuario=fv.ven_cliente
            // INNER JOIN ciudad ci ON ci.idciudad=inst.ciudad_id
            // WHERE fv.id_empresa=$request->id and (fv.est_ven_codigo=10 OR fv.est_ven_codigo=2)
            // GROUP BY inst.idInstitucion
            // SELECT inst.*, fv.id_empresa, ci.nombre AS ciudad, CONCAT(usa.nombres, usa.apellidos) AS cliente FROM institucion inst
            // INNER JOIN f_venta  fv ON fv.institucion_id=inst.idInstitucion
            // INNER JOIN usuario usa ON usa.idusuario=fv.ven_cliente
            // INNER JOIN ciudad ci ON ci.idciudad=inst.ciudad_id
            // WHERE fv.id_empresa=$request->id and (fv.est_ven_codigo=11)
            // GROUP BY inst.idInstitucion
        }
        if($request->op==1){
            $query = DB::SELECT("SELECT inst.idInstitucion, inst.*, ci.nombre AS ciudad, CONCAT(usa.nombres,' ', usa.apellidos) AS cliente
            FROM institucion inst
            INNER JOIN (
                SELECT DISTINCT id_institucion, id_empresa
                FROM f_venta
                WHERE id_empresa = $request->id AND (est_ven_codigo = 11)
            ) fv_filtered ON fv_filtered.id_institucion = inst.idInstitucion
            INNER JOIN usuario usa ON usa.idusuario = (SELECT ven_cliente FROM f_venta WHERE id_institucion = inst.idInstitucion LIMIT 1)
            INNER JOIN ciudad ci ON ci.idciudad = inst.ciudad_id;");
        }

            return $query;
    }
    public function GetVentasP(Request $request){
        if($request->op==0){
            $query = DB::SELECT("SELECT fv.*, COUNT(dv.pro_codigo) AS item, SUM(dv.det_ven_cantidad) AS libros, SUM(dv.det_ven_cantidad_despacho) AS despacho   FROM f_venta  fv
            INNER JOIN f_detalle_venta dv ON fv.ven_codigo=dv.ven_codigo
            WHERE fv.id_institucion=$request->ventaId AND fv.id_empresa=dv.id_empresa
            and (fv.est_ven_codigo=2 or fv.est_ven_codigo=10)
            GROUP BY fv.ven_codigo, fv.id_empresa");
        }
        if($request->op==1){
            $query = DB::SELECT("SELECT fv.*, COUNT(dv.pro_codigo) AS item, SUM(dv.det_ven_cantidad) AS libros, SUM(dv.det_ven_cantidad_despacho) AS despacho   FROM f_venta  fv
            INNER JOIN f_detalle_venta dv ON fv.ven_codigo=dv.ven_codigo
            WHERE fv.id_institucion=$request->ventaId AND fv.id_empresa=dv.id_empresa
            and fv.est_ven_codigo=11 and dv.det_ven_cantidad<>dv.det_ven_cantidad_despacho
            GROUP BY fv.ven_codigo, fv.id_empresa");
        }
                   return $query;
    }
    public function getDventas(Request $request){
        $query = DB::SELECT(" SELECT dv.*, dv.det_ven_cantidad as despacho, pro.*
            FROM f_detalle_venta dv
            INNER JOIN 1_4_cal_producto pro ON pro.pro_codigo = dv.pro_codigo
            WHERE dv.ven_codigo = '$request->id'");
        return $query;
    }
    //cambio ingereso de datos de libros a excluir
    public function despachar(Request $request){
        try{
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $miarray=json_decode($request->deta);
            DB::beginTransaction();
            $venta = Ventas::where('ven_codigo', $request->ven_codigo)
            ->where('id_empresa', $request->id_empresa)
            ->firstOrFail();
                if (!$venta){
                    return "El ven_codigo no existe en la base de datos";
                }
                $venta->est_ven_codigo = 10;
                $venta->save();
                foreach($miarray as $key => $item){
                    if($item->despacho!=0){
                        $venta = DetalleVentas::findOrFail($item->det_ven_codigo);
                        if (!$venta){
                            return "El det_ven_codigo no existe en la base de datos";
                        }
                        $venta->det_ven_cantidad_despacho=$item->despacho;
                        $venta->save();
                    }
                }
            DB::commit();
            return response()->json(['message' => 'Preparación de empaque exitosa'], 200);
        }catch(\Exception $e){
            return response()->json(["error"=>"0", "message" => "No se pudo preparar", 'error' => $e->getMessage()], 500);
            DB::rollback();
        }
    }
    //agrupados para pedidos
    public function Postventa_factura(Request $request){
        try {
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $miarray = json_decode($request->data_detalle);
            $mia = json_decode($request->data_ventas);
            DB::beginTransaction();
            //si el ven_codigo ya existe no creo
            $query = DB::SELECT("SELECT id_factura FROM f_venta_agrupado WHERE id_factura='$request->ven_codigo'");
            if(!empty($query)){
                return response()->json(["status" => "2", "message" => "El ven_codigo ya existe"],0);
            }else{
                $venta                      = new VentasF;
                $venta->id_factura          = $request->ven_codigo;
                $venta->id_empresa          = $request->id_empresa;
                $venta->ven_desc_por        = $request->ven_desc_por;
                $venta->ven_iva_por         = $request->ven_iva_por;
                $venta->ven_descuento       = $request->ven_descuento;
                $venta->ven_iva             = $request->ven_iva;
                $venta->ven_transporte      = $request->ven_transporte;
                $venta->ven_valor           = $request->ven_valor;
                $venta->ven_subtotal        = $request->ven_subtotal;
                $venta->ven_devolucion      = $request->ven_devolucion;
                $venta->ven_pagado          = $request->ven_pagado;
                $venta->institucion_id      = $request->id_ins_depacho;
                $venta->periodo_id          = $request->periodo_id;
                $venta->idtipodoc           = 11;
                $venta->ven_cliente         = $request->ven_cliente;
                $venta->clientesidPerseo    = $request->clientesidPerseo;
                $venta->ven_fecha           = $request->ven_fecha;
                $venta->user_created        = $request->user_created;
                $venta->updated_at          = now();
                $venta->save();
                if($request->id_empresa==1){
                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from  f_tipo_documento where tdo_id=11");
                }else if ($request->id_empresa==3){
                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from  f_tipo_documento where tdo_id=11");
                }
                $id=$query1[0]->id;
                $codi=$query1[0]->cod;
                $co=(int)$codi+1;
                $tipo_doc = f_tipo_documento::findOrFail($id);
                if($request->id_empresa==1){
                    $tipo_doc->tdo_secuencial_Prolipa = $co;
                }else if ($request->id_empresa==3){
                    $tipo_doc->tdo_secuencial_calmed = $co;
                }
                $tipo_doc->save();
                foreach($miarray as $key => $item){
                    if($item->cantidad > 0){
                        $venta1=new DetalleVentasF;
                        $venta1->id_factura         = $request->ven_codigo;
                        $venta1->id_empresa         = $request->id_empresa;
                        $venta1->pro_codigo         = $item->pro_codigo;
                        $venta1->det_ven_cantidad   = $item->cantidad;
                        $venta1->det_ven_valor_u    = $item->det_ven_valor_u;
                        $venta1->save();
                    }
                }
                foreach($mia as $key => $it){
                    if($it->ven_codigo){
                        $ventas = Ventas::where('ven_codigo', $it->ven_codigo)
                         ->where('id_empresa', $request->id_empresa)->firstOrFail();
                        $ventas->id_factura  = $request->ven_codigo;
                        $ventas->save();
                    }
                }
            }
            //finalizar documento
           DB::commit();
            return response()->json(["status" => "200",'message' => 'Documento creado con éxito'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["error" => "500", "message" => "No se pudo guardar", "exception" => $e->getMessage()],500);
        }
    }
}
