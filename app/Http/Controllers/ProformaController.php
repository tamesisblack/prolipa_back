<?php

namespace App\Http\Controllers;
use App\Models\Proforma;
use App\Models\DetalleProforma;
use App\Models\f_tipo_documento;
use App\Models\_14Producto;
use App\Models\Institucion;
use App\Models\InstitucionSucursales;
use App\Models\Proformahistorico;
use DB;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\Pedidos\TraitPedidosGeneral;
use App\Traits\Pedidos\TraitProforma;
use Illuminate\Http\Request;
use Mockery\Undefined;
use PhpParser\Node\Stmt\ElseIf_;
use App\Models\NotificacionGeneral;
use App\Repositories\pedidos\NotificacionRepository;

class ProformaController extends Controller
{
    use TraitProforma;
    use TraitPedidosGeneral;
    public $NotificacionRepository;
    public function __construct(NotificacionRepository $NotificacionRepository)
    {
        $this->NotificacionRepository   = $NotificacionRepository;
    }
    //consultas
    public function Get_MinStock(Request $request){
        $query = DB::SELECT("SELECT minimo as min, maximo as max  from configuracion_general where id='$request->nombre'");
        if(!empty($query)){
            //reservar
            if($request->nombre=='1'){
                $codi=(int)$query[0]->min;
                return $codi;
                //descuento
            }else if($request->nombre=='6'){
                $codi=(int)$query[0]->min;
                $cod=(int)$query[0]->max;
                $array = array($codi,$cod);
                return $array;
            }else{
                $codi=(int)$query[0]->min;
                return $codi;
            }
        }
    }
    //datos para librerias
    public function Get_DatoSolicitud(Request $request){
        if($request->tipo==0){
            $query = DB::SELECT("SELECT  COUNT(fp.prof_id) as con
            FROM f_proforma as fp
            inner join  pedidos as p on p.ca_codigo_agrupado=fp.idPuntoventa
            INNER JOIN  f_venta as fv ON fv.ven_idproforma= fp.prof_id
            WHERe fp.idPuntoventa='$request->prof_id'
            -- AND fp.idPuntoventa=fv.institucion_id
            AND (fp.prof_tipo_proforma=1 OR fp.prof_tipo_proforma=2)
            AND fv.est_ven_codigo=1");
        }
        if($request->tipo==1){
            $query = DB::SELECT("SELECT  COUNT(fp.prof_id) as con FROM f_proforma as fp
            inner join  pedidos as p on p.id_pedido=fp.pedido_id
            INNER JOIN  f_venta as fv ON fv.ven_idproforma= fp.prof_id
            WHERe fp.pedido_id='$request->prof_id' AND p.id_institucion=fv.institucion_id AND (fp.prof_tipo_proforma=1 OR fp.prof_tipo_proforma=2) AND fv.est_ven_codigo=1");

        }
             return $query;
    }
    //libreria funcion
    public function Get_pedidoSolicitud(Request $request){
        $query = DB::SELECT("SELECT * FROM f_contratos_usuarios_agrupados fcua
        INNER JOIN f_contratos_agrupados fc ON fcua.ca_id=fc.ca_id
        WHERE fcua.idusuario=$request->usuario and fcua.fcu_estado=1 ");
        $datos = [];
        foreach($query as $key => $item){
            $query2 = DB::SELECT("SELECT COUNT(f.id) AS finalizado
                FROM f_proforma f
                WHERE f.idPuntoventa = '$item->ca_codigo_agrupado'
                AND f.prof_estado = 2");
                if(count($query2) > 0){
                    $finalizado = $query2[0]->finalizado;
                }
            $query3=DB::SELECT("SELECT COUNT(f.id) AS anulado
                FROM f_proforma f
                WHERE f.idPuntoventa = '$item->ca_codigo_agrupado'
                AND f.prof_estado =0");
                if(count($query3) > 0){
                    $anulado = $query3[0]->anulado;
                }
           $query4=DB::SELECT("SELECT COUNT(f.id) AS pendiente
                FROM f_proforma f
                WHERE f.idPuntoventa = '$item->ca_codigo_agrupado'
                AND f.prof_estado<>0 and f.prof_estado<>2");
                if(count($query4) > 0){
                    $pendiente = $query4[0]->pendiente;
                }
                $total=(int)$finalizado+(int)$anulado+(int)$pendiente;
                $datos[] = array(
                    'fcu_id' => $item->fcu_id,
                    'ca_id' => $item->ca_id,
                    'idusuario' => $item->idusuario,
                    'ca_descripcion'=>$item->ca_descripcion,
                    'ca_codigo_agrupado' => $item->ca_codigo_agrupado,
                    'ca_tipo_pedido' => $item->ca_tipo_pedido,
                    'ca_cantidad' => $item->ca_cantidad,
                    'id_periodo' => $item->id_periodo,
                    'finalizado' => (int)$finalizado,
                    'anulado'   => (int)$anulado,
                    'pendiente' => (int)$pendiente,
                    'total' => (int)$total,
                );
        }
        return $datos;
    }
    public function Get_proformasCliente(Request $request){
        $datos = DB::SELECT("SELECT 
                fp.id,
                fp.prof_id,
                fp.idPuntoventa,
                fp.prof_estado,
                SUM(dpr.det_prof_cantidad) AS cantidad,
                fcg.ca_codigo_agrupado,
                fcg.id_periodo,
                pe.descripcion
            FROM 
                f_detalle_proforma AS dpr
            INNER JOIN 
                f_proforma AS fp ON dpr.prof_id = fp.id
            INNER JOIN 
                libros_series AS ls ON dpr.pro_codigo = ls.codigo_liquidacion
            INNER JOIN 
                1_4_cal_producto AS p ON dpr.pro_codigo = p.pro_codigo
            INNER JOIN 
                series AS s ON ls.id_serie = s.id_serie
            INNER JOIN 
                libro AS l ON ls.idLibro = l.idlibro
            LEFT JOIN 
                f_contratos_agrupados AS fcg ON fcg.ca_codigo_agrupado = fp.idPuntoventa
            LEFT JOIN 
                periodoescolar AS pe ON pe.idperiodoescolar = fcg.id_periodo
            WHERE 
                fp.ven_cliente = '$request->usuario' OR fp.usuario_solicitud = '$request->usuario'
            GROUP BY 
                fp.id, fp.prof_id, fp.idPuntoventa,fp.prof_estado,fcg.ca_codigo_agrupado,fcg.id_periodo;
        ");
        return $datos;
    }

    public function Get_DatosFactura(Request $request){
        $datos = [];
        if($request->empresa == 1){
            $array1 = DB::SELECT("SELECT fp.prof_id, fp.emp_id, dpr.det_prof_id, dpr.pro_codigo, dpr.det_prof_cantidad, dpr.det_prof_cantidad AS cantidad,dpr.det_prof_valor_u,
                ls.nombre, s.nombre_serie, fp.pro_des_por, fp.prof_iva_por, p.pro_stock as facturas_prolipa, p.pro_stockCalmed as facturas_calmed,
                p.pro_deposito as bodega_prolipa, p.pro_depositoCalmed as bodega_calmed, p.pro_reservar, l.descripcionlibro, ls.id_serie
                FROM f_detalle_proforma as dpr
                INNER JOIN  f_proforma as fp on dpr.prof_id=fp.id
                INNER JOIN libros_series as ls ON dpr.pro_codigo=ls.codigo_liquidacion
                INNER JOIN 1_4_cal_producto as p on dpr.pro_codigo=p.pro_codigo
                INNER JOIN series as s ON ls.id_serie=s.id_serie
                INNER JOIN libro l ON ls.idLibro = l.idlibro
                WHERe dpr.prof_id='$request->prof_id'
            ");
        }else if($request->empresa == 3){
            $array1 = DB::SELECT("SELECT fp.prof_id, fp.emp_id, dpr.det_prof_id, dpr.pro_codigo, dpr.det_prof_cantidad, dpr.det_prof_cantidad AS cantidad,dpr.det_prof_valor_u,
                ls.nombre, s.nombre_serie, fp.pro_des_por, fp.prof_iva_por, p.pro_stock as facturas_prolipa, p.pro_stockCalmed as facturas_calmed,
                p.pro_deposito as bodega_prolipa, p.pro_depositoCalmed as bodega_calmed, p.pro_reservar, l.descripcionlibro, ls.id_serie
                FROM f_detalle_proforma as dpr
                INNER JOIN  f_proforma as fp on dpr.prof_id=fp.id
                INNER JOIN libros_series as ls ON dpr.pro_codigo=ls.codigo_liquidacion
                INNER JOIN 1_4_cal_producto as p on dpr.pro_codigo=p.pro_codigo
                INNER JOIN series as s ON ls.id_serie=s.id_serie
                INNER JOIN libro l ON ls.idLibro = l.idlibro
                WHERe dpr.prof_id='$request->prof_id'
            ");
        }else{
            $array1 = DB::SELECT("SELECT fp.prof_id, fp.emp_id, dpr.det_prof_id, dpr.pro_codigo, dpr.det_prof_cantidad, dpr.det_prof_cantidad AS cantidad,dpr.det_prof_valor_u,
            ls.nombre, s.nombre_serie, fp.pro_des_por, fp.prof_iva_por, p.pro_stock as facturas_prolipa, p.pro_stockCalmed as facturas_calmed,
            p.pro_deposito as bodega_prolipa, p.pro_depositoCalmed as bodega_calmed, p.pro_reservar, l.descripcionlibro, ls.id_serie
            FROM f_detalle_proforma as dpr
            INNER JOIN  f_proforma as fp on dpr.prof_id=fp.id
            INNER JOIN libros_series as ls ON dpr.pro_codigo=ls.codigo_liquidacion
            INNER JOIN 1_4_cal_producto as p on dpr.pro_codigo=p.pro_codigo
            INNER JOIN series as s ON ls.id_serie=s.id_serie
            INNER JOIN libro l ON ls.idLibro = l.idlibro
            WHERe dpr.prof_id='$request->prof_id'
        ");
        }
       
        foreach($array1 as $key => $item){
            $cantidad = 0;
            if($item->prof_id&&$item->emp_id){
                    $array2 = DB::SELECT(" SELECT dfv.pro_codigo, SUM(dfv.det_ven_cantidad) AS cant from f_detalle_venta AS dfv
                    inner join f_venta AS fv ON dfv.ven_codigo = fv.ven_codigo
                    where fv.ven_idproforma='$item->prof_id' AND fv.id_empresa=$item->emp_id AND (fv.est_ven_codigo = 2 OR fv.est_ven_codigo = 1) AND dfv.pro_codigo = '$item->pro_codigo' group BY dfv.pro_codigo
                    ");
                    if(count($array2) > 0){
                        $cantidad = $array2[0]->cant;
                    }
            }

            $datos[] = array(
                'det_prof_id' => $item->det_prof_id,
                'pro_codigo' => $item->pro_codigo,
                'det_prof_cantidad' => $item->det_prof_cantidad,
                'cantidad'=>$item->det_prof_cantidad,
                'det_prof_valor_u' => $item->det_prof_valor_u,
                'nombre' => $item->nombre,
                'nombre_serie' => $item->nombre_serie,
                'facturas_prolipa' => $item->facturas_prolipa,
                'facturas_calmed' => $item->facturas_calmed,
                'bodega_prolipa' => $item->bodega_prolipa,
                'bodega_calmed' => $item->bodega_calmed,
                'cant'   => (int)$cantidad,
                'pro_reservar' => $item->pro_reservar,
                'descripcion' => $item->descripcionlibro,
                'id_serie' => $item->id_serie,
            );

        }
        return $datos;

    }
    public function Get_PuntosV(){
        $query = DB::SELECT("SELECT  i.idInstitucion, i.nombreInstitucion,i.ruc,i.email,i.telefonoInstitucion,
        i.direccionInstitucion, i.idrepresentante, u.cedula,
        CONCAT(u.nombres,' ',u.apellidos) as representante
        FROM institucion i
        LEFT JOIN usuario u ON i.idrepresentante=u.idusuario
        WHERE i.punto_venta=1
        AND i.estado_idEstado=1
        ");
        return $query;
    }
    public function Get_InstituV(Request $request){
        $query = DB::SELECT("SELECT  idInstitucion, nombreInstitucion,ruc,email,telefonoInstitucion, direccionInstitucion, punto_venta FROM institucion WHERE idInstitucion=$request->id_insti AND estado_idEstado=1");
        return $query;
    }
    public function Get_sucursalV(Request $request){
        $query = DB::SELECT("SELECT isuc_id, isuc_nombre, isuc_ruc, isuc_direccion FROM institucion_sucursales
        WHERE isuc_idInstitucion=$request->id_insti and isuc_estado=1");
        return $query;
    }
    public function Get_sucur(Request $request){
        $query = DB::SELECT("SELECT * FROM institucion_sucursales
        WHERE isuc_id=$request->id_insti");
        return $query;
    }

    public function GetAseClieuser(Request $request){
        if($request->tipo==0){
            $query1 = DB::SELECT("SELECT us.* FROM institucion as ins
        inner join usuario as us on ins.asesor_id = us.idusuario
            where ins.idInstitucion='$request->id'");
         return $query1;
        }
        if($request->tipo==1){
            $query1 = DB::SELECT("SELECT usu.* FROM institucion as ins
        inner join usuario as usu on ins.idrepresentante= usu.idusuario
            where ins.idInstitucion='$request->id'");
         return $query1;
        }
        if($request->tipo==2){
            $query1 = DB::SELECT("SELECT us.*FROM pedidos AS pe
			INNER JOIN pedidos_beneficiarios AS pb ON pe.id_pedido=pb.id_pedido
        	INNER JOIN usuario as us on pb.id_usuario = us.idusuario
        WHERE pe.id_pedido='$request->id'");
         return $query1;
        }
        if($request->tipo==3){
            $query1 = DB::SELECT("SELECT *FROM usuario u WHERE u.cedula LIKE '%$request->id%'");
         return $query1;
        }
    }
    public function GetProuser(Request $request){
        $query1 = DB::SELECT("SELECT  us.nombres as nombre, us.apellidos as apellido FROM f_proforma as pro
        inner join usuario as us on pro.usu_codigo = us.idusuario
        where pro.prof_id='$request->pro_id'");
             if(!empty($query1)){
                $cod= $query1[0]->nombre;
                $codi= $query1[0]->apellido;
                $user=$cod." ".$codi;
             }

             return $user;
    }

    public function GetProformaGeneral(Request $request){
        if($request->estadoproforma == 0){
            $query = DB::SELECT("SELECT CONCAT(usa.nombres, ' ', usa.apellidos) AS usercreator, CONCAT(us.nombres, ' ', us.apellidos) AS usuarioeditor,
            fca.ca_descripcion, fca.id_periodo, fpr.*,  em.nombre, us.nombres as username, us.apellidos as lastname, COUNT(dpr.pro_codigo) AS item,
            SUM(dpr.det_prof_cantidad) AS libros,fca.ca_tipo_pedido,CONCAT(usc.nombres,' ',usc.apellidos) as cliente
            FROM f_proforma fpr
            LEFT JOIN empresas em ON fpr.emp_id =em.id
            INNER JOIN f_detalle_proforma dpr ON dpr.prof_id=fpr.id
            INNER JOIN f_contratos_agrupados fca ON fpr.idPuntoventa = fca.ca_codigo_agrupado
            LEFT JOIN usuario us ON fpr.user_editor = us.idusuario
            LEFT JOIN usuario usa ON fpr.usu_codigo = usa.idusuario
            left join usuario usc on fpr.ven_cliente = usc.idusuario
            WHERE fca.id_periodo = $request->periodorecibido
            AND fpr.prof_estado = 1
            GROUP BY fpr.id, fpr.prof_id, fpr.prof_observacion,em.nombre, em.img_base64, fca.id_periodo
            order by fpr.created_at DESC");
            return $query;
        }else if($request->estadoproforma == 1){
            $query = DB::SELECT("SELECT CONCAT(usa.nombres, ' ', usa.apellidos) AS usercreator, CONCAT(us.nombres, ' ', us.apellidos) AS usuarioeditor,
            fca.ca_descripcion, fca.id_periodo, fpr.*,  em.nombre, us.nombres as username, us.apellidos as lastname, COUNT(dpr.pro_codigo) AS item,
            SUM(dpr.det_prof_cantidad) AS libros,fca.ca_tipo_pedido,CONCAT(usc.nombres,' ',usc.apellidos) as cliente
            FROM f_proforma fpr
            LEFT JOIN empresas em ON fpr.emp_id =em.id
            INNER JOIN f_detalle_proforma dpr ON dpr.prof_id=fpr.id
            INNER JOIN f_contratos_agrupados fca ON fpr.idPuntoventa = fca.ca_codigo_agrupado
            LEFT JOIN usuario us ON fpr.user_editor = us.idusuario
            LEFT JOIN usuario usa ON fpr.usu_codigo = usa.idusuario
            left join usuario usc on fpr.ven_cliente = usc.idusuario
            WHERE fca.id_periodo = $request->periodorecibido AND fpr.prof_estado = 3
            GROUP BY fpr.id, fpr.prof_id, fpr.prof_observacion,em.nombre, em.img_base64, fca.id_periodo
            order by fpr.created_at DESC");
            return $query;
        }else if($request->estadoproforma == 2){
            $query = DB::SELECT("SELECT CONCAT(usa.nombres, ' ', usa.apellidos) AS usercreator, CONCAT(us.nombres, ' ', us.apellidos) AS usuarioeditor,
            fca.ca_descripcion, fca.id_periodo, fpr.*,  em.nombre, us.nombres as username, us.apellidos as lastname, COUNT(dpr.pro_codigo) AS item,
            SUM(dpr.det_prof_cantidad) AS libros,fca.ca_tipo_pedido,CONCAT(usc.nombres,' ',usc.apellidos) as cliente
            FROM f_proforma fpr
            LEFT JOIN empresas em ON fpr.emp_id =em.id
            INNER JOIN f_detalle_proforma dpr ON dpr.prof_id=fpr.id
            INNER JOIN f_contratos_agrupados fca ON fpr.idPuntoventa = fca.ca_codigo_agrupado
            LEFT JOIN usuario us ON fpr.user_editor = us.idusuario
            LEFT JOIN usuario usa ON fpr.usu_codigo = usa.idusuario
            left join usuario usc on fpr.ven_cliente = usc.idusuario
            WHERE fca.id_periodo = $request->periodorecibido AND fpr.prof_estado = 2
            GROUP BY fpr.id, fpr.prof_id, fpr.prof_observacion,em.nombre, em.img_base64, fca.id_periodo
            order by fpr.created_at DESC");
            return $query;
        }else if($request->estadoproforma == 3){
            $query = DB::SELECT("SELECT CONCAT(usa.nombres, ' ', usa.apellidos) AS usercreator, CONCAT(us.nombres, ' ', us.apellidos) AS usuarioeditor,
            fca.ca_descripcion, fca.id_periodo, fpr.*,  em.nombre, us.nombres as username, us.apellidos as lastname, COUNT(dpr.pro_codigo) AS item,
            SUM(dpr.det_prof_cantidad) AS libros,fca.ca_tipo_pedido,CONCAT(usc.nombres,' ',usc.apellidos) as cliente
            FROM f_proforma fpr
            LEFT JOIN empresas em ON fpr.emp_id =em.id
            INNER JOIN f_detalle_proforma dpr ON dpr.prof_id=fpr.id
            INNER JOIN f_contratos_agrupados fca ON fpr.idPuntoventa = fca.ca_codigo_agrupado
            LEFT JOIN usuario us ON fpr.user_editor = us.idusuario
            LEFT JOIN usuario usa ON fpr.usu_codigo = usa.idusuario
            left join usuario usc on fpr.ven_cliente = usc.idusuario
            WHERE fca.id_periodo = $request->periodorecibido AND fpr.prof_estado = 0
            GROUP BY fpr.id, fpr.prof_id, fpr.prof_observacion,em.nombre, em.img_base64, fca.id_periodo
            order by fpr.created_at DESC");
            return $query;
        }else if($request->estadoproforma == 4){
            $query = DB::SELECT("SELECT CONCAT(usa.nombres, ' ', usa.apellidos) AS usercreator, CONCAT(us.nombres, ' ', us.apellidos) AS usuarioeditor,
            fca.id_periodo, fca.ca_descripcion, fpr.*, us.nombres as username, us.apellidos as lastname, COUNT(dpr.pro_codigo) AS item,
            SUM(dpr.det_prof_cantidad) AS libros,pe.codigo_contrato
            FROM f_proforma fpr
            INNER JOIN f_detalle_proforma dpr ON dpr.prof_id=fpr.id
            INNER JOIN f_contratos_agrupados fca ON fpr.idPuntoventa = fca.ca_codigo_agrupado
            LEFT JOIN usuario us ON fpr.user_editor = us.idusuario
            LEFT JOIN usuario usa ON fpr.usu_codigo = usa.idusuario
            LEFT JOIN periodoescolar pe ON fca.id_periodo = pe.idperiodoescolar
            WHERE fca.id_periodo = $request->periodorecibido AND fpr.prof_id IS NULL
            AND fpr.prof_estado = 1
            GROUP BY fpr.id, fpr.prof_id, fpr.prof_observacion, fca.id_periodo
            order by fpr.created_at DESC");
            return $query;
        }
        else{
            return "Estado no controlado";
        }
    }

//INNER JOIN usuario usa ON fpr.usu_codigo=usa.idusuario
    public function GetProfarmas(Request $request){
        $tipo = $request->tipo;
            if($tipo ==0){
                $query = DB::SELECT("SELECT fpr.*, pe.observacion, em.nombre, em.img_base64, usa.nombres, usa.apellidos, us.nombres as username, us.apellidos as lastname, COUNT(dpr.pro_codigo) AS item, SUM(dpr.det_prof_cantidad) AS libros
                FROM f_proforma fpr
                INNER JOIN pedidos pe ON fpr.pedido_id = pe.id_pedido
                INNER JOIN pedidos_beneficiarios AS pb ON fpr.pedido_id = pb.id_pedido
                INNER JOIN usuario usa ON pb.id_usuario = usa.idusuario
                LEFT JOIN usuario us ON fpr.user_editor = us.idusuario
                INNER JOIN empresas em ON fpr.emp_id = em.id
                INNER JOIN f_detalle_proforma dpr ON dpr.prof_id = fpr.id
                WHERE fpr.pedido_id= $request->prof_id GROUP BY fpr.id, fpr.prof_id,fpr.prof_observacion, pe.observacion,em.nombre, em.img_base64, usa.nombres, usa.apellidos
                order by fpr.created_at desc");
                    return $query;
            }
            if($tipo ==1){
                $query = DB::SELECT("SELECT fpr.*,  em.nombre, em.img_base64,
                us.nombres as username, us.apellidos as lastname, COUNT(dpr.pro_codigo) AS item, SUM(dpr.det_prof_cantidad) AS libros,
                CONCAT(COALESCE(usa.nombres, ''), ' ', COALESCE(usa.apellidos, '')) AS cliente,
                i.nombreInstitucion,i.ruc as rucPuntoVenta
                FROM f_proforma fpr
                LEFT JOIN usuario us ON fpr.user_editor = us.idusuario
                LEFT JOIN empresas em ON fpr.emp_id =em.id
                INNER JOIN f_detalle_proforma dpr ON dpr.prof_id=fpr.id
                left join usuario usa on fpr.ven_cliente = usa.idusuario
                LEFT JOIN institucion i ON fpr.id_ins_depacho = i.idInstitucion
                WHERE fpr.idPuntoventa= '$request->prof_id'
                GROUP BY fpr.id, fpr.prof_id,fpr.prof_observacion,em.nombre, em.img_base64
                order by fpr.created_at desc");
                return $query;
            }
    }

    public function GetProformasSolicitud(Request $request) {
        $query = DB::select("
            SELECT 
                fpr.*,  
                em.nombre, 
                em.img_base64,
                us.nombres AS username, 
                us.apellidos AS lastname, 
                COUNT(dpr.pro_codigo) AS total_items, 
                SUM(dpr.det_prof_cantidad) AS total_libros,
                CONCAT(COALESCE(usa.nombres, ''), ' ', COALESCE(usa.apellidos, '')) AS cliente,
                i.nombreInstitucion,
                i.ruc AS rucPuntoVenta
            FROM f_proforma fpr
            LEFT JOIN usuario us ON fpr.user_editor = us.idusuario
            LEFT JOIN empresas em ON fpr.emp_id = em.id
            INNER JOIN f_detalle_proforma dpr ON dpr.prof_id = fpr.id
            LEFT JOIN usuario usa ON fpr.ven_cliente = usa.idusuario
            LEFT JOIN institucion i ON fpr.id_ins_depacho = i.idInstitucion
            WHERE fpr.idPuntoventa = ? 
            AND (fpr.ven_cliente = ? OR fpr.usuario_solicitud = ?)
            GROUP BY fpr.id, fpr.prof_id, fpr.prof_observacion, em.nombre, em.img_base64, 
                     us.nombres, us.apellidos, i.nombreInstitucion, i.ruc
            ORDER BY fpr.created_at DESC
        ", [$request->prof_id, $request->usuario, $request->usuario]);
    
        return $query;
    }
    

    //generar codigo para la proforma
    public function Get_Cod_Pro(Request $request){
        if($request->id==1){
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_Prolipa as cod from f_tipo_documento where tdo_nombre='PRE-PROFORMA'");
        }else if ($request->id==3){
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_calmed as cod from f_tipo_documento where tdo_nombre='PRE-PROFORMA'");
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
                $array = array($pre,$secuencia);
            }

            return $array;
    }
    public function getNumeroDocumento($empresa){
        $letra ="";
        if($empresa == 1){
            $letra = "P";
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_Prolipa as cod from f_tipo_documento where tdo_nombre='PRE-PROFORMA'");
        }else if ($empresa==3){
            $letra = "C";
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_calmed as cod from f_tipo_documento where tdo_nombre='PRE-PROFORMA'");
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

            return $letra."-".$secuencia;
    }
    //registro y edicion de datos de la proforma y detalles de proforma
    public function Proforma_Registrar_modificar(Request $request)
    {
        try {
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            
            $miarray = json_decode($request->data_detalle);
            DB::beginTransaction();
    
            $proforma = new Proforma;
    
            if ($request->id_group == 1 || $request->id_group == 22 || $request->id_group == 23) {
                $id_empresa = $request->emp_id;
                $iniciales = $request->iniciales;
                $getNumeroDocumento = $this->getNumeroDocumento($id_empresa);
                $ven_codigo = "PP-" . $iniciales . "-" . $getNumeroDocumento;
                $proforma->prof_id = $ven_codigo;
            }
    
            // Asignación de valores al modelo Proforma
            $proforma->usu_codigo = $request->usu_codigo;
            $proforma->pedido_id = $request->pedido_id;
            $proforma->emp_id = $request->emp_id;
            $proforma->idPuntoventa = $request->idPuntoventa;
            $proforma->prof_observacion = $request->prof_observacion;
            $proforma->prof_observacion_libreria = $request->prof_observacion_libreria;
            $proforma->prof_com = $request->prof_com;
            $proforma->prof_descuento = $request->prof_descuento;
            $proforma->pro_des_por = $request->pro_des_por;
            $proforma->prof_iva = $request->prof_iva;
            $proforma->prof_iva_por = $request->prof_iva_por;
            $proforma->prof_total = $request->prof_total;
            $proforma->prof_estado = $request->prof_estado;
            $proforma->prof_tipo_proforma = $request->prof_tipo_proforma;
            $proforma->created_at = $request->created_at;
    
            if ($request->emp_id == 1) {
                $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from f_tipo_documento where tdo_nombre='PRE-PROFORMA'");
            } else if ($request->emp_id == 3) {
                $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from f_tipo_documento where tdo_nombre='PRE-PROFORMA'");
            }
    
            if (!empty($query1)) {
                $id = $query1[0]->id;
                $codi = $query1[0]->cod;
                $co = (int)$codi + 1;
                $tipo_doc = f_tipo_documento::findOrFail($id);
                if ($request->emp_id == 1) {
                    $tipo_doc->tdo_secuencial_Prolipa = $co;
                } else if ($request->emp_id == 3) {
                    $tipo_doc->tdo_secuencial_calmed = $co;
                }
                $tipo_doc->save();
            }
    
            $proforma->idtipodoc = 5;
            $proforma->id_ins_depacho = $request->id_ins_depacho;
            $proforma->ven_cliente = $request->ven_cliente;
            $proforma->clientesidPerseo = $request->clientesidPerseo;
            
            // Si el cliente está definido, obtener la cédula
            if ($request->ven_cliente) {
                $user = User::where('idusuario', $request->ven_cliente)->first();
                $getCedula = $user->cedula;
                $proforma->ruc_cliente = $getCedula;
            }
    
            // Guardar la proforma
            $proforma->save();
            $ultimo_id = $proforma->id;
    
            $proformaGuardada = Proforma::find($proforma->id);

            if (!$proformaGuardada) {
                throw new \Exception("No se pudo guardar la proforma", 500);
            }else{
                
                if($proformaGuardada->prof_id == null){
                    $proformaGuardada->usuario_solicitud = $request->idusuario;
                    $guardado = $proformaGuardada->save();
                    if(!$guardado){
                         // Manejar el error, quizás retornando un mensaje o ejecutando otra acción
                         throw new \Exception('Error al guardar usuario de la solicitud', 500);
                    }
                    $temporada = DB::table('f_contratos_agrupados')->where('ca_codigo_agrupado', $proforma->idPuntoventa)->first();
                    if (!$temporada) {
                        // Manejar el error, quizás retornando un mensaje o ejecutando otra acción
                         throw new \Exception('No se encontró la temporada', 500);
                    }
                    $notificacion = new NotificacionGeneral();
                    $notificacion->id_padre = $proformaGuardada->id;
                    $notificacion->tipo = 4;
                    $notificacion->estado = 0;
                    $notificacion->descripcion = 'Se creo una Solicitud de proforma';
                    $notificacion->user_created = $request->idusuario;
                    $notificacion->id_periodo = $temporada->id_periodo;
                    $notificacion->nombre = 'Solicitud de proforma';
                    $success      = $notificacion->save();
                    if($success){
                        $channel = 'admin.notifications_verificaciones';
                        $event = 'NewNotification';
                        $data = [
                            'message' => 'Nueva notificación',
                        ];
                        $this->NotificacionRepository->notificacionVerificaciones($channel, $event, $data);
                    }
                }
            }
    
    
            // Guardar los detalles de la proforma
            foreach ($miarray as $key => $item) {
                if ((int)$item->cantidad != 0) {
                    $query1 = DB::SELECT("SELECT pro_reservar as stoc from 1_4_cal_producto where pro_codigo='$item->codigo_liquidacion'");
                    $codi = $query1[0]->stoc;
                    $co = (int)$codi - (int)$item->cantidad;
                    $pro = _14Producto::findOrFail($item->codigo_liquidacion);
                    $pro->pro_reservar = $co;
                    $pro->save();
    
                    // Crear y guardar el detalle de la proforma
                    $DetalleProforma = new DetalleProforma;
                    $DetalleProforma->prof_id = $ultimo_id;
                    $DetalleProforma->pro_codigo = $item->codigo_liquidacion;
                    $DetalleProforma->det_prof_cantidad = (int)$item->cantidad;
                    $DetalleProforma->det_prof_valor_u = $item->precio;
                    $DetalleProforma->save();
                }
            }
    
            // Confirmar la transacción
            DB::commit();
    
            return response()->json(['message' => 'Proforma y detalles guardados con éxito'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["error" => "0", "message" => "No se pudo guardar", 'error' => $e->getMessage()], 500);
        }
    }

    public function Proforma_Registrar_modificar_solicitud(Request $request)
    {
        try {
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            
            $miarray = json_decode($request->data_detalle);
            DB::beginTransaction();
    
            $proforma = new Proforma;
    
            if ($request->id_group == 1 || $request->id_group == 22 || $request->id_group == 23) {
                if($request->prof_tipo_proforma != 0){
                    $id_empresa = $request->emp_id;
                    $iniciales = $request->iniciales;
                    $getNumeroDocumento = $this->getNumeroDocumento($id_empresa);
                    $ven_codigo = "PP-" . $iniciales . "-" . $getNumeroDocumento;
                    $proforma->prof_id = $ven_codigo;
                }
                
            }
    
            // Asignación de valores al modelo Proforma
            $proforma->usu_codigo = $request->usu_codigo;
            $proforma->pedido_id = $request->pedido_id;
            $proforma->emp_id = $request->emp_id;
            $proforma->idPuntoventa = $request->idPuntoventa;
            $proforma->prof_observacion = $request->prof_observacion;
            $proforma->prof_observacion_libreria = $request->prof_observacion_libreria;
            $proforma->prof_com = $request->prof_com;
            $proforma->prof_descuento = $request->prof_descuento;
            $proforma->pro_des_por = $request->pro_des_por;
            $proforma->prof_iva = $request->prof_iva;
            $proforma->prof_iva_por = $request->prof_iva_por;
            $proforma->prof_total = $request->prof_total;
            $proforma->prof_estado = $request->prof_estado;
            $proforma->prof_tipo_proforma = $request->prof_tipo_proforma;
            $proforma->created_at = $request->created_at;
    
            if ($request->emp_id == 1) {
                $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from f_tipo_documento where tdo_nombre='PRE-PROFORMA'");
            } else if ($request->emp_id == 3) {
                $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from f_tipo_documento where tdo_nombre='PRE-PROFORMA'");
            }
    
            if (!empty($query1)) {
                $id = $query1[0]->id;
                $codi = $query1[0]->cod;
                $co = (int)$codi + 1;
                $tipo_doc = f_tipo_documento::findOrFail($id);
                if ($request->emp_id == 1) {
                    $tipo_doc->tdo_secuencial_Prolipa = $co;
                } else if ($request->emp_id == 3) {
                    $tipo_doc->tdo_secuencial_calmed = $co;
                }
                $tipo_doc->save();
            }
    
            $proforma->idtipodoc = 5;
            $proforma->id_ins_depacho = $request->id_ins_depacho;
            $proforma->ven_cliente = $request->ven_cliente;
            $proforma->clientesidPerseo = $request->clientesidPerseo;
            
            // Si el cliente está definido, obtener la cédula
            if ($request->ven_cliente) {
                $user = User::where('idusuario', $request->ven_cliente)->first();
                $getCedula = $user->cedula;
                $proforma->ruc_cliente = $getCedula;
            }
    
            // Guardar la proforma
            $proforma->save();
            $ultimo_id = $proforma->id;
    
            $proformaGuardada = Proforma::find($proforma->id);

            if (!$proformaGuardada) {
                throw new \Exception("No se pudo guardar la proforma", 500);
            }else{
                
                if($proformaGuardada->prof_id == null){
                    $proformaGuardada->usuario_solicitud = $request->idusuario;
                    $guardado = $proformaGuardada->save();
                    if(!$guardado){
                         // Manejar el error, quizás retornando un mensaje o ejecutando otra acción
                         throw new \Exception('Error al guardar usuario de la solicitud', 500);
                    }
                    $temporada = DB::table('f_contratos_agrupados')->where('ca_codigo_agrupado', $proforma->idPuntoventa)->first();
                    if (!$temporada) {
                        // Manejar el error, quizás retornando un mensaje o ejecutando otra acción
                         throw new \Exception('No se encontró la temporada', 500);
                    }
                    $notificacion = new NotificacionGeneral();
                    $notificacion->id_padre = $proformaGuardada->id;
                    $notificacion->tipo = 4;
                    $notificacion->estado = 0;
                    $notificacion->descripcion = 'Se creo una Solicitud de proforma';
                    $notificacion->user_created = $request->idusuario;
                    $notificacion->id_periodo = $temporada->id_periodo;
                    $notificacion->nombre = 'Solicitud de proforma';
                    $success      = $notificacion->save();
                    if($success){
                        $channel = 'admin.notifications_verificaciones';
                        $event = 'NewNotification';
                        $data = [
                            'message' => 'Nueva notificación',
                        ];
                        $this->NotificacionRepository->notificacionVerificaciones($channel, $event, $data);
                    }
                }
            }
    
    
            // Guardar los detalles de la proforma
            foreach ($miarray as $key => $item) {
                if ((int)$item->cantidad != 0) {
                    $query1 = DB::SELECT("SELECT pro_reservar as stoc from 1_4_cal_producto where pro_codigo='$item->codigo_liquidacion'");
                    $codi = $query1[0]->stoc;
                    $co = (int)$codi - (int)$item->cantidad;
                    $pro = _14Producto::findOrFail($item->codigo_liquidacion);
                    $pro->pro_reservar = $co;
                    $pro->save();
    
                    // Crear y guardar el detalle de la proforma
                    $DetalleProforma = new DetalleProforma;
                    $DetalleProforma->prof_id = $ultimo_id;
                    $DetalleProforma->pro_codigo = $item->codigo_liquidacion;
                    $DetalleProforma->det_prof_cantidad = (int)$item->cantidad;
                    $DetalleProforma->det_prof_valor_u = $item->precio;
                    $DetalleProforma->save();
                }
            }
    
            // Confirmar la transacción
            DB::commit();
    
            return response()->json(['message' => 'Proforma y detalles guardados con éxito'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["error" => "0", "message" => "No se pudo guardar", 'error' => $e->getMessage()], 500);
        }
    }
    
    public function PostProforma_Editar_solicitud(Request $request)
       {
        try{
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $miarray=json_decode($request->data_detalle);
            DB::beginTransaction();
                $oldvalue = Proforma::findOrFail($request->id);
                $proforma = Proforma::findOrFail($request->id);
                $emp_idEdit = $proforma->emp_id;
                $profIdStatus = $oldvalue->prof_id;
                // $proforma->prof_id = $request->prof_id;
                $proforma->emp_id = $request->emp_id;
                if($request->id_group == 1 || $request->id_group == 22|| $request->id_group == 23){
                    if($request->prof_tipo_proforma != 0){
                        //CODIGO PROF_IF
                        if($oldvalue->prof_id == null || $oldvalue->prof_id ==""){
                            $id_empresa         = $request->emp_id;
                            $iniciales        = $request->iniciales;
                            $getNumeroDocumento = $this->getNumeroDocumento($id_empresa);
                            $ven_codigo         = "PP-".$iniciales."-".$getNumeroDocumento;
                            $proforma->prof_id  = $ven_codigo;
                        }
                    }
                }
                //CODIGO PROF_IF
                if($request->prof_observacion){
                    $proforma->prof_observacion = $request->prof_observacion;
                }
                $proforma->prof_observacion_libreria = $request->prof_observacion_libreria == null || $request->prof_observacion_libreria == 'null' ? '' : $request->prof_observacion_libreria;
                $proforma->prof_descuento = $request->prof_descuento;
                $proforma->pro_des_por = $request->pro_des_por;
                $proforma->prof_iva = $request->prof_iva;
                $proforma->prof_iva_por = $request->prof_iva_por;
                $proforma->prof_total = $request->prof_total;
                $proforma->user_editor = $request->id_usua;
                if($request->estado){
                    $proforma->prof_estado = $request->estado;
                }
                $proforma->id_ins_depacho = $request->id_ins_depacho;
                $proforma->ven_cliente = $request->ven_cliente;
                if($request->ven_cliente){
                $user                   = User::where('idusuario',$request->ven_cliente)->first();
                $getCedula              = $user->cedula;
                $proforma->ruc_cliente  = $getCedula;
                }
                $proforma->clientesidPerseo = $request->clientesidPerseo;
                $proforma->save();
                if($proforma){
                    //======SECUENCIA======
                    //si la libreria crea la solicitud y el facturador al editar ricien elige la empresa para asignar el codigo de proforma
                    if($emp_idEdit == null || $emp_idEdit == 0 || $profIdStatus == null || $profIdStatus == ""){
                        if($request->emp_id==1){
                            $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from f_tipo_documento where tdo_nombre='PRE-PROFORMA'");
                        }else if($request->emp_id==3){
                            $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from f_tipo_documento where tdo_nombre='PRE-PROFORMA'");
                        }
                        if(!empty($query1)){
                            $id=$query1[0]->id;
                            $codi=$query1[0]->cod;
                            $co=(int)$codi+1;
                            $tipo_doc = f_tipo_documento::findOrFail($id);
                            if($request->emp_id==1){
                                $tipo_doc->tdo_secuencial_Prolipa = $co;
                            }else if($request->emp_id==3){
                                $tipo_doc->tdo_secuencial_calmed = $co;
                            }
                            $tipo_doc->save();
                        }
                    }
                    //======SECUENCIA======
                    $newvalue = Proforma::findOrFail($request->id);
                    $this->tr_GuardarEnHistorico($request->prof_id,$request->id_usua,$oldvalue,$newvalue);
                }
                foreach($miarray as $key => $item){
                    if($item->det_prof_id!=null ||$item->det_prof_id!="" ){
                        $query1 = DB::SELECT("SELECT pro_reservar as stoc from 1_4_cal_producto where pro_codigo='$item->pro_codigo'");
                        $codi=$query1[0]->stoc;
                        $co=(int)$codi+(int)$item->nuevo;
                        $pro= _14Producto::findOrFail($item->pro_codigo);
                        $pro->pro_reservar = $co;
                        $pro->save();
                        $proforma2 = DetalleProforma::findOrFail($item->det_prof_id);
                        $proforma2->det_prof_cantidad = $item->cantidad;
                        $proforma2->save();
                    }  else{
                        if((int)$item->cantidad != 0){
                            $query1 = DB::SELECT("SELECT pro_reservar as stoc from 1_4_cal_producto where pro_codigo='$item->pro_codigo'");
                            $codi=$query1[0]->stoc;
                            $co=(int)$codi-(int)$item->cantidad;
                            $pro= _14Producto::findOrFail($item->pro_codigo);
                            $pro->pro_reservar = $co;
                            $pro->save();
                            $proforma1 = new DetalleProforma;
                            $proforma1->prof_id = $request->id;
                            $proforma1->pro_codigo = $item->pro_codigo;
                            $proforma1->det_prof_cantidad = (int)$item->cantidad;
                            $proforma1->det_prof_valor_u = $item->det_prof_valor_u;
                            $proforma1->save();
                        }
                    }

                }
            DB::commit();
            return response()->json(['message' => 'Proforma actualizado con éxito'], 200);
        }catch(\Exception $e){
            DB::rollback();
            return response()->json(["error"=>"0", "message" => "No se pudo actualizar", 'error' => $e->getMessage()], 500);
        }
    }
    
    public function PostProforma_Editar(Request $request)
       {
        try{
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $miarray=json_decode($request->data_detalle);
            DB::beginTransaction();
                $oldvalue = Proforma::findOrFail($request->id);
                $proforma = Proforma::findOrFail($request->id);
                $emp_idEdit = $proforma->emp_id;
                $profIdStatus = $oldvalue->prof_id;
                // $proforma->prof_id = $request->prof_id;
                $proforma->emp_id = $request->emp_id;
                if($request->id_group == 1 || $request->id_group == 22|| $request->id_group == 23){
                //CODIGO PROF_IF
                    if($oldvalue->prof_id == null || $oldvalue->prof_id ==""){
                        $id_empresa         = $request->emp_id;
                        $iniciales        = $request->iniciales;
                        $getNumeroDocumento = $this->getNumeroDocumento($id_empresa);
                        $ven_codigo         = "PP-".$iniciales."-".$getNumeroDocumento;
                        $proforma->prof_id  = $ven_codigo;
                    }
                }
                //CODIGO PROF_IF
                if($request->prof_observacion){
                    $proforma->prof_observacion = $request->prof_observacion;
                }
                $proforma->prof_observacion_libreria = $request->prof_observacion_libreria == null || $request->prof_observacion_libreria == 'null' ? '' : $request->prof_observacion_libreria;
                $proforma->prof_descuento = $request->prof_descuento;
                $proforma->pro_des_por = $request->pro_des_por;
                $proforma->prof_iva = $request->prof_iva;
                $proforma->prof_iva_por = $request->prof_iva_por;
                $proforma->prof_total = $request->prof_total;
                $proforma->user_editor = $request->id_usua;
                if($request->estado){
                    $proforma->prof_estado = $request->estado;
                }
                $proforma->id_ins_depacho = $request->id_ins_depacho;
                $proforma->ven_cliente = $request->ven_cliente;
                if($request->ven_cliente){
                $user                   = User::where('idusuario',$request->ven_cliente)->first();
                $getCedula              = $user->cedula;
                $proforma->ruc_cliente  = $getCedula;
                }
                $proforma->clientesidPerseo = $request->clientesidPerseo;
                $proforma->save();
                if($proforma){
                    //======SECUENCIA======
                    //si la libreria crea la solicitud y el facturador al editar ricien elige la empresa para asignar el codigo de proforma
                    if($emp_idEdit == null || $emp_idEdit == 0 || $profIdStatus == null || $profIdStatus == ""){
                        if($request->emp_id==1){
                            $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from f_tipo_documento where tdo_nombre='PRE-PROFORMA'");
                        }else if($request->emp_id==3){
                            $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from f_tipo_documento where tdo_nombre='PRE-PROFORMA'");
                        }
                        if(!empty($query1)){
                            $id=$query1[0]->id;
                            $codi=$query1[0]->cod;
                            $co=(int)$codi+1;
                            $tipo_doc = f_tipo_documento::findOrFail($id);
                            if($request->emp_id==1){
                                $tipo_doc->tdo_secuencial_Prolipa = $co;
                            }else if($request->emp_id==3){
                                $tipo_doc->tdo_secuencial_calmed = $co;
                            }
                            $tipo_doc->save();
                        }
                    }
                    //======SECUENCIA======
                    $newvalue = Proforma::findOrFail($request->id);
                    $this->tr_GuardarEnHistorico($request->prof_id,$request->id_usua,$oldvalue,$newvalue);
                }
                foreach($miarray as $key => $item){
                    if($item->det_prof_id!=null ||$item->det_prof_id!="" ){
                        $query1 = DB::SELECT("SELECT pro_reservar as stoc from 1_4_cal_producto where pro_codigo='$item->pro_codigo'");
                        $codi=$query1[0]->stoc;
                        $co=(int)$codi+(int)$item->nuevo;
                        $pro= _14Producto::findOrFail($item->pro_codigo);
                        $pro->pro_reservar = $co;
                        $pro->save();
                        $proforma2 = DetalleProforma::findOrFail($item->det_prof_id);
                        $proforma2->det_prof_cantidad = $item->cantidad;
                        $proforma2->save();
                    }  else{
                        if((int)$item->cantidad != 0){
                            $query1 = DB::SELECT("SELECT pro_reservar as stoc from 1_4_cal_producto where pro_codigo='$item->pro_codigo'");
                            $codi=$query1[0]->stoc;
                            $co=(int)$codi-(int)$item->cantidad;
                            $pro= _14Producto::findOrFail($item->pro_codigo);
                            $pro->pro_reservar = $co;
                            $pro->save();
                            $proforma1 = new DetalleProforma;
                            $proforma1->prof_id = $request->id;
                            $proforma1->pro_codigo = $item->pro_codigo;
                            $proforma1->det_prof_cantidad = (int)$item->cantidad;
                            $proforma1->det_prof_valor_u = $item->det_prof_valor_u;
                            $proforma1->save();
                        }
                    }

                }
            DB::commit();
            return response()->json(['message' => 'Proforma actualizado con éxito'], 200);
        }catch(\Exception $e){
            DB::rollback();
            return response()->json(["error"=>"0", "message" => "No se pudo actualizar", 'error' => $e->getMessage()], 500);
        }
    }
    public function PostProformaIntitu(Request $request)
       {
        if($request->idInstitucion!='' || $request->idInstitucion!=null ){

            $inst = Institucion::findOrFail($request->idInstitucion);
            $inst->ruc = $request->ruc;
            $inst->telefonoInstitucion = $request->telefonoInstitucion;
            $inst->direccionInstitucion= $request->direccionInstitucion;
            $inst->email = $request->email;
            $inst->save();
            if($inst){
                return $inst;
            }else{
                 return "No se pudo actualizar";
            }
        } else {
            return ["error"=>"0", "message" => "No se pudo actualizar"];
        }
    }
    public function PostProformaSucursal(Request $request)
       {
        if($request->isuc_id!='' || $request->isuc_id!=null ){
             $sucur = InstitucionSucursales::findOrFail($request->isuc_id);
            $sucur->isuc_ruc = $request->isuc_ruc;
            $sucur->isuc_telefono = $request->isuc_telefono;
            $sucur->isuc_direccion= $request->isuc_direccion;
            $sucur->isuc_correo = $request->isuc_correo;
            $sucur->save();
            if($sucur){
                return $sucur;
            }else{
                return "No se pudo actualizar";
            }
        }else {
            return ["error"=>"0", "message" => "No se pudo actualizar"];
        }
    }
    //Cambiar el estado de proforma
    // public function Desactivar_Proforma(Request $request)
    // {
    //     if (!$request->id) {
    //         return response()->json(["error" => "No está ingresando ningún prof_id"], 400);
    //     }
    
    //     DB::beginTransaction();
    
    //     try {
    //         // Obtener la proforma
    //         $proform = Proforma::findOrFail($request->id);
            
    //         // Actualizar la proforma
    //         $oldvalue = clone $proform; // Crear copia del valor antiguo
    //         $proform->prof_estado = $request->prof_estado;
    //         $proform->user_editor = $request->id_usua;
    //         $proform->save();
    
    //         // Verificar si el tipo es 'anular'
    //         if ($request->tipo === 'anular') {
    //             // Obtener las ventas asociadas
    //             $ventas = DB::table('f_venta')->where('ven_idproforma', $proform->prof_id)->get();
    
    //             if ($ventas->isEmpty()) {
    //                 throw new \Exception('No se encontraron ventas asociadas');
    //             }
    
    //             foreach ($ventas as $venta) {
    //                 // Verificar estado de la venta
    //                 if ($venta->est_ven_codigo != 2 && $venta->est_ven_codigo != 3) {
    //                     return response()->json([
    //                         "status" => "0", 
    //                         "message" => "El documento " . $venta->ven_codigo . " ya no se encuentra pendiente de despacho. No se puede anular."
    //                     ]);
    //                 }
    
    //                 // Actualizar stock en los productos relacionados
    //                 $detalles = DB::table('f_detalle_venta')->where('ven_codigo', $venta->ven_codigo)->get();
    //                 if ($detalles->isEmpty()) {
    //                     return response()->json(["error" => "0", "message" => "No se encontró detalles del documento: " . $venta->ven_codigo]);
    //                 }
    
    //                 foreach ($detalles as $detalle) {
    //                     $producto = DB::table('1_4_cal_producto')->where('pro_codigo', $detalle->pro_codigo)->first();
    
    //                     if (!$producto) {
    //                         return response()->json(["status" => "0", "message" => "Producto no encontrado"]);
    //                     }
    
    //                     // Ajustar stock según la empresa
    //                     if ($venta->id_empresa == 1) {
    //                         DB::table('1_4_cal_producto')
    //                             ->where('pro_codigo', $detalle->pro_codigo)
    //                             ->update([
    //                                 'pro_deposito' => $producto->pro_deposito + (int)$detalle->det_ven_cantidad,
    //                                 'pro_reservar' => $producto->pro_reservar + (int)$detalle->det_ven_cantidad
    //                             ]);
    //                     } elseif ($venta->id_empresa == 3) {
    //                         DB::table('1_4_cal_producto')
    //                             ->where('pro_codigo', $detalle->pro_codigo)
    //                             ->update([
    //                                 'pro_depositoCalmed' => $producto->pro_depositoCalmed + (int)$detalle->det_ven_cantidad,
    //                                 'pro_reservar' => $producto->pro_reservar + (int)$detalle->det_ven_cantidad
    //                             ]);
    //                     }
    //                 }
    //             }
    //         } else {
    //             // Si no es 'anular', entonces es una actualización normal
    //             try {
    //                 $oldvalue = Proforma::findOrFail($request->id);
    //                 $proform = Proforma::find($request->id);
    
    //                 if (!$proform) {
    //                     return response()->json(['error' => 'El prof_id no existe en la base de datos'], 400);
    //                 }
    
    //                 // Actualizar los campos de la proforma
    //                 $proform->prof_estado = $request->prof_estado;
    //                 $proform->user_editor = $request->id_usua;
    //                 $proform->save();
    
    //                 // Registrar el cambio en el histórico
    //                 $newvalue = Proforma::findOrFail($request->id);
    //                 $this->tr_GuardarEnHistorico($request->id, $request->id_usua, $oldvalue, $newvalue);
    
    //                 DB::commit();
    //                 return response()->json(['message' => 'Proforma actualizada con éxito'], 200);
    //             } catch (\Exception $e) {
    //                 DB::rollback();
    //                 return response()->json(["error" => "0", "message" => "No se pudo actualizar", 'error' => $e->getMessage()], 500);
    //             }
    //         }
    
    //         // Si la operación fue de 'anular', retornar el mensaje respectivo
    //         DB::commit();
    //         return response()->json(['message' => 'Proforma ' . ($request->tipo === 'anular' ? 'anulada' : 'actualizada') . ' con éxito'], 200);
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return response()->json(["error" => "0", "message" => "No se pudo procesar la solicitud", 'error' => $e->getMessage()], 500);
    //     }
    // }
    public function Desactivar_Proforma(Request $request)
    {
        DB::beginTransaction();  // Iniciamos la transacción para asegurar la consistencia de los datos
        $proforma_id = $request->id;
        $miarray=json_decode($request->dat);
        try {
            // Obtener la proforma
            $proform = DB::table('f_proforma')->where('id', $proforma_id)->first();
            
            if (!$proform) {
                throw new \Exception('Proforma no encontrada');
            }

            // Verificar que la proforma no esté en estado de "anulada"
            if ($proform->prof_estado == 0) {
                return response()->json(["status" => "0", "message" => "La proforma ya está anulada"]);
            }

            // // Verificar que el estado de la proforma es el adecuado para desactivarla
            // if ($proform->prof_estado != 'pendiente') {
            //     return response()->json(["status" => "0", "message" => "La proforma no se encuentra en estado pendiente"]);
            // }

            // Obtener las ventas asociadas a la proforma
            $ventas = DB::table('f_venta')->where('ven_idproforma', $proform->prof_id)->get();
            
            // if ($ventas->isEmpty()) {
            //     throw new \Exception('No se encontraron ventas asociadas a la proforma');
            // }
            if($ventas){
                foreach ($ventas as $venta) {
                    // Verificar si la venta está en estado pendiente de despacho (estado 2)
                    if ($venta->est_ven_codigo != 2 && $venta->est_ven_codigo != 3) {
                        return response()->json([
                            "status" => "0", 
                            "message" => "La venta " . $venta->ven_codigo . " ya fue despachada y no puede ser anulada."
                        ]);
                    }else{
                        if($venta->est_ven_codigo == 2){
                            // Anular la venta cambiando su estado
                            DB::table('f_venta')
                            ->where('ven_codigo', $venta->ven_codigo)
                            ->where('id_empresa', $venta->id_empresa)->update([
                                'est_ven_codigo' => 3,  // El estado "anulado" se debe definir en tu base de datos
                                'user_anulado' => $request->id_usua,  // El usuario que está realizando la anulación
                                'observacionAnulacion' => 'Se anulo desde la proforma'  // El usuario que está realizando la anulación
                            ]);

                             // Obtener los detalles de la venta
                            $detalles = DB::table('f_detalle_venta')->where('ven_codigo', $venta->ven_codigo)->where('id_empresa', $venta->id_empresa)->get();
                            if ($detalles->isEmpty()) {
                                return response()->json(["error" => "0", "message" => "No se encontraron detalles para la venta: " . $venta->ven_codigo]);
                            }
                
                            // Recorrer los detalles de la venta y restaurar el stock
                            foreach ($detalles as $detalle) {
                                // Obtener el producto asociado al detalle
                                $producto = DB::table('1_4_cal_producto')->where('pro_codigo', $detalle->pro_codigo)->first();
                                if (!$producto) {
                                    return response()->json(["status" => "0", "message" => "Producto no encontrado en la venta"]);
                                }
                
                                // Ajustar el stock según la empresa
                                if ($venta->id_empresa == 1) {
                                    if($venta->idtipodoc == 1){
                                        DB::table('1_4_cal_producto')
                                            ->where('pro_codigo', $detalle->pro_codigo)
                                            ->update([
                                                'pro_stock' => $producto->pro_stock + (int)$detalle->det_ven_cantidad,
                                            ]);
                                    }else{
                                        DB::table('1_4_cal_producto')
                                            ->where('pro_codigo', $detalle->pro_codigo)
                                            ->update([
                                                'pro_deposito' => $producto->pro_deposito + (int)$detalle->det_ven_cantidad,
                                            ]);
                                    }
                                } elseif ($venta->id_empresa == 3) {
                                    if($venta->idtipodoc == 1){
                                        DB::table('1_4_cal_producto')
                                            ->where('pro_codigo', $detalle->pro_codigo)
                                            ->update([
                                                'pro_stockCalmed' => $producto->pro_stockCalmed + (int)$detalle->det_ven_cantidad,
                                            ]);
                                    }else{
                                        DB::table('1_4_cal_producto')
                                            ->where('pro_codigo', $detalle->pro_codigo)
                                            ->update([
                                                'pro_depositoCalmed' => $producto->pro_depositoCalmed + (int)$detalle->det_ven_cantidad,
                                            ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Anular la proforma
            DB::table('f_proforma')->where('id', $proforma_id)->update([
                'prof_estado' => 0,
                'user_editor' => $request->id_usua  // El usuario que está realizando la anulación
            ]);

            foreach($miarray as $key => $item){
                $query1 = DB::SELECT("SELECT pro_reservar as stoc from 1_4_cal_producto where pro_codigo='$item->pro_codigo'");
                $codi=$query1[0]->stoc;
                $co=(int)$codi+(int)$item->det_prof_cantidad;
                $pro= _14Producto::findOrFail($item->pro_codigo);
                $pro->pro_reservar = $co;
                $pro->save();
            }
            
            DB::commit();  // Confirmamos la transacción

            return response()->json([
                "status" => "1", 
                "message" => "La proforma y las ventas asociadas han sido anuladas con éxito."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();  // Si ocurre un error, deshacemos todos los cambios

            return response()->json([
                "status" => "0", 
                "message" => "Error: " . $e->getMessage(),
                "line" => $e->getLine()
            ]);
        }
    }
    public function AceptarSolicitudProforma(Request $request)
    {
            if ($request->id) {
                try{
                    DB::beginTransaction();
                        $oldvalue= Proforma::findOrFail($request->id);
                        $proform= Proforma::find($request->id);

                        if (!$proform){
                            return "El prof_id no existe en la base de datos";
                        }
                        $proform->prof_estado = $request->prof_estado;
                        $proform->user_editor = $request->id_usua;
                        $proform->save();
                        if($proform){
                            $newvalue = Proforma::findOrFail($request->id);
                            $this->tr_GuardarEnHistorico($request->id,$request->id_usua,$oldvalue,$newvalue);
                        }
                    DB::commit();
                    return response()->json(['message' => 'Proforma actualizado con éxito'], 200);
                }catch(\Exception $e){
                    DB::rollback();
                    return response()->json(["error"=>"0", "message" => "No se pudo actualizar", 'error' => $e->getMessage()], 500);
                }
             } else {
                return "No está ingresando ningún prof_id";
            }
    }

    public function AprobarProforma(Request $request)
    {
        if (!$request->id) {
            return response()->json(["error" => "No está ingresando ningún prof_id"], 400);
        }
    
        DB::beginTransaction();
    
        try {
            // Obtener la proforma
            $proform = Proforma::findOrFail($request->id);
            
            // Actualizar la proforma
            $oldvalue = clone $proform; // Crear copia del valor antiguo
            $proform->prof_estado = $request->prof_estado;
            $proform->user_editor = $request->id_usua;
            $proform->save();
            try {
                $oldvalue = Proforma::findOrFail($request->id);
                $proform = Proforma::find($request->id);

                if (!$proform) {
                    return response()->json(['error' => 'El prof_id no existe en la base de datos'], 400);
                }

                // Actualizar los campos de la proforma
                $proform->prof_estado = $request->prof_estado;
                $proform->user_editor = $request->id_usua;
                $proform->save();

                // Registrar el cambio en el histórico
                $newvalue = Proforma::findOrFail($request->id);
                $this->tr_GuardarEnHistorico($request->id, $request->id_usua, $oldvalue, $newvalue);

                DB::commit();
                return response()->json(['message' => 'Proforma actualizada con éxito'], 200);
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(["error" => "0", "message" => "No se pudo actualizar", 'error' => $e->getMessage()], 500);
            }
    
            // Si la operación fue de 'anular', retornar el mensaje respectivo
            DB::commit();
            return response()->json(['message' => 'Proforma actualizada con éxito'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["error" => "0", "message" => "No se pudo procesar la solicitud", 'error' => $e->getMessage()], 500);
        }
    }
    public function DesactivarProforma(Request $request)
    {
        if ($request->id) {
            try{
                DB::beginTransaction();
                    $oldvalue= Proforma::findOrFail($request->id);
                    $proform= Proforma::find($request->id);

                    if (!$proform){
                        return "El prof_id no existe en la base de datos";
                    }
                    $proform->prof_estado = $request->prof_estado;
                    $proform->user_editor = $request->id_usua;
                    $proform->save();
                    if($proform){
                        $newvalue = Proforma::findOrFail($request->id);
                        $this->tr_GuardarEnHistorico($request->id,$request->id_usua,$oldvalue,$newvalue);
                    }
                DB::commit();
                return response()->json(['message' => 'Proforma actualizado con éxito'], 200);
            }catch(\Exception $e){
                DB::rollback();
                return response()->json(["error"=>"0", "message" => "No se pudo actualizar", 'error' => $e->getMessage()], 500);
            }
        }
        else {
            return "No está ingresando ningún id";
        }
    }
//eliminacion de datos del detalle de la proforma
    public function Eliminar_DetaProforma(Request $request)
    {
        if ($request->det_prof_id) {
            try{
                DB::beginTransaction();
                    $query1 = DB::SELECT("SELECT pro_reservar as stoc from 1_4_cal_producto where pro_codigo='$request->pro_codigo'");
                    $codi=$query1[0]->stoc;
                    $co=(int)$codi+(int)$request->det_prof_cantidad;
                    $pro= _14Producto::findOrFail($request->pro_codigo);
                    $pro->pro_reservar = $co;
                    $pro->save();
                    $proforma = DetalleProforma::find($request->det_prof_id);

                    if (!$proforma) {
                        return "El det_prof_id no existe en la base de datos";
                    }
                    $proforma->delete();
                    // $ultimoId  = DetalleProforma::max('det_prof_id') + 1;
                    // DB::statement('ALTER TABLE f_detalle_proforma AUTO_INCREMENT = ' . $ultimoId);

                DB::commit();
                return response()->json(['message' => 'Detalle de la proforma eliminado con éxito'], 200);
            }catch(\Exception $e){
                DB::rollback();
                return response()->json(["error"=>"0", "message" => "No se pudo Eliminar", 'error' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(["error"=>"0", "message" => "No está ingresando ningún det_prof_id", 'error' => $e->getMessage()], 500);
        }
    }
    //eliminar proforma y detalles
    public function Eliminar_Proforma(Request $request)
    {
        if ($request->id) {
            try {
                set_time_limit(6000000);
                ini_set('max_execution_time', 6000000);
                $miarray = json_decode($request->dat);
                DB::beginTransaction();
                $profor = DetalleProforma::where('prof_id', $request->id)->get();

                if ($profor->isEmpty()) {
                    return ["error" => "0", "message" =>"El prof_id no existe en la base de datos"];
                } else {
                    DetalleProforma::where('prof_id', $request->id)->delete();
                }

                $profor = DetalleProforma::where('prof_id', $request->id)->get();
                if ($profor->isEmpty()) {
                    $profort = Proforma::find($request->id);
                    if (!$profort) {
                        return ["error" => "0", "message" =>"El prof_id no existe en la base de datos"];
                    } else {
                        $profort->delete();
                    }
                }

                foreach ($miarray as $key => $item) {
                    $query1 = DB::SELECT("SELECT pro_reservar as stoc FROM 1_4_cal_producto WHERE pro_codigo='$item->pro_codigo'");
                    $codi = $query1[0]->stoc;
                    $co = (int)$codi + (int)$item->det_prof_cantidad;
                    $pro = _14Producto::findOrFail($item->pro_codigo);
                    $pro->pro_reservar = $co;
                    $pro->save();
                }
                DB::commit();
                return response()->json(['message' => 'Proforma eliminada con éxito'], 200);
            } catch (\Exception $e) {
                DB::rollback(); // Mover esto antes del return
                return ["error" => "0", "message" => "No se pudo actualizar", "error" => $e];
            }
        }else{
            return ["error" => "0", "message" =>"No hay el id de la proforma"];
        }
    }
    public function InfoAgrupadoPedido($ca_codigo_agrupado,$id_periodo){
        $query = $this->tr_pedidosXDespacho($ca_codigo_agrupado,$id_periodo);
        return $query;
    }
    public function InfoClienteAgrupado($ca_codigo_agrupado, $id_periodo){
        $query = DB::SELECT("SELECT DISTINCT usu.*, CONCAT(usu.nombres,' ', usu.apellidos) nombres
        FROM f_proforma f
        INNER JOIN f_venta fv ON fv.ven_idproforma = f.prof_id
        LEFT JOIN usuario usu ON fv.ven_cliente  = usu.idusuario
        WHERE f.idPuntoventa = '$ca_codigo_agrupado'
        AND fv.periodo_id = '$id_periodo'");

        return $query;
    }
    
    public function CambiarEmpresaPedido(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',  // Validar que el pedido exista
            'empresa' => 'required',  // Validar que la empresa exista
        ]);
    
        $id_proforma = $request->id;
        $id_empresa = $request->empresa;
    
        // Iniciar la transacción
        DB::beginTransaction();
    
        try {
            // Buscar el pedido
            $pedido = DB::table('f_proforma')->where('id', $id_proforma)->first();
    
            if (!$pedido) {
                // Si el pedido no existe, lanzar una excepción para que se haga rollback
                return response()->json([
                    'status' => 404,
                    'message' => 'Pedido no encontrado',
                ]);
            }
    
            // Buscar la empresa
            $empresa = DB::table('empresas')->where('id', $id_empresa)->first();
    
            if (!$empresa) {
                // Si la empresa no existe, lanzar una excepción para que se haga rollback
                return response()->json([
                    'status' => 404,
                    'message' => 'Empresa no encontrada',
                ]);
            }
    
            // Cambiar la empresa del pedido
            $updatedRows = DB::table('f_proforma')
                ->where('id', $id_proforma)
                ->update(['emp_id' => $empresa->id]);
    
            // Verificar si la actualización se realizó con éxito
            if ($updatedRows === 0) {
                // Si no se actualizó ningún registro, lanzar una excepción
                throw new \Exception('No se pudo actualizar el pedido. Hubo un error en la actualización.');
            }

            $pedidoActualizado = DB::SELECT("SELECT fpr.*,  em.nombre, em.img_base64,
            us.nombres as username, us.apellidos as lastname, COUNT(dpr.pro_codigo) AS item, SUM(dpr.det_prof_cantidad) AS libros,
            CONCAT(COALESCE(usa.nombres, ''), ' ', COALESCE(usa.apellidos, '')) AS cliente,
            i.nombreInstitucion,i.ruc as rucPuntoVenta
            FROM f_proforma fpr
            LEFT JOIN usuario us ON fpr.user_editor = us.idusuario
            LEFT JOIN empresas em ON fpr.emp_id =em.id
            INNER JOIN f_detalle_proforma dpr ON dpr.prof_id=fpr.id
            left join usuario usa on fpr.ven_cliente = usa.idusuario
            LEFT JOIN institucion i ON fpr.id_ins_depacho = i.idInstitucion
            WHERE fpr.id= '$id_proforma'
            GROUP BY fpr.id, fpr.prof_id,fpr.prof_observacion,em.nombre, em.img_base64
            order by fpr.created_at desc");

            // Si todo es correcto, hacer commit de la transacción
            DB::commit();
    
            return response()->json([
                'status' => 200,
                'message' => 'Pedido cambiado con éxito',
                'data' => $pedidoActualizado
            ]);
    
        } catch (\Exception $e) {
            // Si ocurre algún error, hacer rollback
            DB::rollback();
    
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    
    
}


