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
use App\Repositories\Facturacion\ProformaRepository;
use DB;
use App\Http\Controllers\Controller;
use App\Models\CodigosLibros;
use App\Models\CodigosLibrosDevolucionSon;
use App\Models\Pedidos;
use App\Models\Periodo;
use App\Models\User;
use App\Traits\Pedidos\TraitProforma;
use App\Repositories\pedidos\PedidosRepository;
use App\Traits\Codigos\TraitCodigosGeneral;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VentasController extends Controller
{
    use TraitProforma;
    use TraitCodigosGeneral;
    protected $pedidosRepository;
    protected $proformaRepository;
    public function __construct(PedidosRepository $pedidosRepository, ProformaRepository $proformaRepository)
    {
        $this->pedidosRepository    = $pedidosRepository;
        $this->proformaRepository   = $proformaRepository;
    }
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
            $query1 = DB::SELECT("SELECT SUM(dv.det_ven_cantidad) AS cantidad, dv.pro_codigo,v.ven_desc_por, ls.nombre as nombrelibro, dv.det_ven_valor_u
            FROM f_venta v
            INNER JOIN f_detalle_venta dv ON v.ven_codigo=dv.ven_codigo AND v.id_empresa=dv.id_empresa
            INNER JOIN libros_series as ls ON dv.pro_codigo=ls.codigo_liquidacion
            INNER JOIN f_proforma fp ON fp.prof_id=v.ven_idproforma
            WHERE fp.idPuntoventa = '$request->pedido_id'
            AND v.est_ven_codigo <> 3
            GROUP BY dv.pro_codigo");
            return $query1;
        }else if($request->tipo== 2){
            $query1 = DB::SELECT("SELECT SUM(v.ven_descuento) AS descuento, sum(v.ven_transporte) as transporte FROM f_venta v
                INNER JOIN f_proforma fp ON fp.prof_id=v.ven_idproforma and fp.emp_id=v.id_empresa
                WHERE fp.idPuntoventa = '$request->pedido_id' ");
            return $query1;
        }
    }
    // listar las facturas
    public function GetFacturasX(Request $request){
        $query = DB::SELECT("SELECT ins.ruc as rucPuntoVenta, em.nombre as empresa,
          CONCAT(usa.nombres, ' ',usa.apellidos) as cliente, fv.*, ins.nombreInstitucion,ins.direccionInstitucion, ins.telefonoInstitucion, ins.asesor_id, concat(u.nombres,' ',u.apellidos) as asesor,
        usa.nombres, usa.apellidos, fpr.prof_observacion, fpr.idPuntoventa,
        COUNT(DISTINCT dfv.pro_codigo) AS item, CONCAT(us.nombres,' ',us.apellidos) AS responsable,
        CONCAT(interc.nombres,' ',interc.apellidos) AS user_intercambio, CONCAT(ua.nombres,' ',ua.apellidos) AS usuario_anulado,
        (SELECT SUM(det_ven_cantidad)
         FROM f_detalle_venta
         WHERE ven_codigo = fv.ven_codigo and id_empresa=fv.id_empresa) AS libros,usa.cedula, usa.email,usa.telefono
        FROM f_venta fv
        LEFT JOIN f_proforma fpr ON fpr.prof_id = fv.ven_idproforma
        LEFT JOIN empresas em ON fpr.emp_id = em.id
        LEFT JOIN pedidos pe ON fpr.idPuntoventa = pe.ca_codigo_agrupado
        LEFT JOIN institucion ins ON fpr.id_ins_depacho = ins.idInstitucion
        LEFT JOIN usuario usa ON fpr.ven_cliente = usa.idusuario
        INNER JOIN usuario us ON fv.user_created = us.idusuario
        LEFT JOIN usuario u ON ins.asesor_id = u.idusuario
        LEFT JOIN f_detalle_venta dfv ON fv.ven_codigo = dfv.ven_codigo and fv.id_empresa=dfv.id_empresa
        LEFT JOIN usuario as interc ON fv.user_intercambio = interc.idusuario
        LEFT JOIN usuario ua ON fv.user_anulado = ua.idusuario
        WHERE fpr.idPuntoventa = '$request->prof_id' and fv.periodo_id=$request->periodo and (fv.idtipodoc=1 or fv.idtipodoc=3 or fv.idtipodoc=4)
        GROUP BY fv.ven_codigo,  usa.nombres, usa.apellidos, fpr.prof_observacion order by ven_fecha desc
        ");
        return $query;
    }
    //verificacion documento de venta con proformas
    public function VerificacionDVenta(Request $request){
        $query = DB::SELECT("SELECT SUM(dfv.det_ven_cantidad) AS venta, SUM(dp.det_prof_cantidad)as proforma
        from f_detalle_proforma dp
        left join f_detalle_venta dfv on dfv.idProforma=dp.prof_id
        where dp.prof_id='$request->id'");
    }

    public function VerificacionDVentaProforma(Request $request) {
        if ($request->id) {
            $proforma = DB::table('f_proforma')
                ->where('id', $request->id)
                ->where('prof_estado', 3)
                ->first();
            if ($proforma) {

                $ventas = DB::table('f_venta')
                    ->where('ven_idproforma', $proforma->prof_id)
                    ->where('est_ven_codigo', '!=', 3)
                    ->get();


                $result = [
                    'ventas' => $ventas,
                    'count' => $ventas->count(),
                ];

                return response()->json($result);
            } else {
                return response()->json(['message' => 'Proforma not found or not approved', 'status'=>'0']);
            }
        }
        return response()->json(['message' => 'No ID provided', 'status'=>'0']);
    }

    //api:get/GetFacturasTodas
    public function GetFacturasTodas(Request $request){
        $query = DB::SELECT("SELECT ins.ruc as rucPuntoVenta, em.nombre as empresa,
        CONCAT(usa.nombres, ' ',usa.apellidos) as cliente, fv.*, ins.nombreInstitucion,ins.direccionInstitucion, ins.telefonoInstitucion, ins.asesor_id, concat(u.nombres,' ',u.apellidos) as asesor,
        usa.nombres, usa.apellidos, fpr.prof_observacion, fpr.idPuntoventa,
        COUNT(DISTINCT dfv.pro_codigo) AS item, CONCAT(us.nombres,' ',us.apellidos) AS responsable,
        (SELECT SUM(det_ven_cantidad)
        FROM f_detalle_venta
        WHERE ven_codigo = fv.ven_codigo and id_empresa=fv.id_empresa ) AS libros,usa.cedula, usa.email,usa.telefono
        FROM f_venta fv
        LEFT JOIN f_proforma fpr ON fpr.prof_id = fv.ven_idproforma
        LEFT JOIN empresas em ON fpr.emp_id = em.id
        LEFT JOIN pedidos pe ON fpr.idPuntoventa = pe.ca_codigo_agrupado
        LEFT JOIN institucion ins ON fpr.id_ins_depacho = ins.idInstitucion
        LEFT JOIN usuario usa ON fpr.ven_cliente = usa.idusuario
        INNER JOIN usuario us ON fv.user_created = us.idusuario
        LEFT JOIN usuario u ON ins.asesor_id = u.idusuario
        LEFT JOIN f_detalle_venta dfv ON fv.ven_codigo = dfv.ven_codigo and fv.id_empresa=dfv.id_empresa
        WHERE fv.periodo_id=$request->periodo and fv.idtipodoc=1
        GROUP BY fv.ven_codigo,  usa.nombres, usa.apellidos, fpr.prof_observacion order by ven_fecha desc
      ");
      if($request->estadoPerseo){
        if(count($query) == 0) { return $query; }
        $resultado = collect($query)->where('estadoPerseo',0)->where('est_ven_codigo','<>','3')->values();
        return $resultado;
      }
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
            (ins.nombreInstitucion LIKE '%$request->busqueda%' OR usa.cedula='$request->busqueda')
            AND fv.id_empresa=$request->empresa
            AND dfv.id_empresa = fv.id_empresa
            AND fv.proformas_codigo IS NOT NULL
            AND fv.idtipodoc = 1
            AND fv.id_factura  IS NULL
            AND fv.periodo_id=$request->periodo
            AND fv.est_ven_codigo!=3
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
            em.descripcion_corta as nombre,
            CONCAT(us.nombres,' ',us.apellidos) AS responsable,
            COUNT(DISTINCT dfv.pro_codigo) AS item,
            SUM(dfv.det_ven_cantidad) AS libros
        FROM
            f_venta_agrupado fv
            INNER JOIN institucion ins ON fv.institucion_id = ins.idInstitucion
            INNER JOIN usuario usa ON fv.ven_cliente = usa.idusuario
            INNER JOIN f_detalle_venta_agrupado dfv ON fv.id_factura = dfv.id_factura
            INNER JOIN empresas em ON em.id = fv.id_empresa
            INNER JOIN usuario us ON us.idusuario=fv.user_created
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
            foreach($query as $key => $item){
                if($item->id_factura){
                    $quer1=DB::SELECT("select ven_codigo from f_venta where id_factura= '$item->id_factura' AND id_empresa=$item->id_empresa");
                    $query[$key]->prefacturas =$quer1;
                }
              }
    return $query;
    }


    public function Get_DFactura(Request $request){
        $query = DB::SELECT("SELECT dv.det_ven_codigo, dv.pro_codigo, dv.det_ven_dev, dv.det_ven_cantidad, dv.det_ven_valor_u,
        l.descripcionlibro, ls.nombre, s.nombre_serie, ls.id_serie FROM f_detalle_venta as dv
   inner join f_venta as fv on dv.ven_codigo=fv.ven_codigo
   INNER JOIN libros_series as ls ON dv.pro_codigo=ls.codigo_liquidacion
   INNER JOIN series as s ON ls.id_serie=s.id_serie
    INNER JOIN libro l ON ls.idLibro = l.idlibro
        WHERE dv.ven_codigo='$request->ven_codigo' and dv.id_empresa=fv.id_empresa
        and fv.id_empresa=$request->idemp  order by dv.pro_codigo");

             return $query;
    }
    //lista de datos la factura
    public function Get_DatoFactura(Request $request){
        $query = DB::SELECT("SELECT dv.det_ven_codigo, dv.pro_codigo,  dv.det_ven_cantidad, dv.det_ven_valor_u,
        l.descripcionlibro, ls.nombre, ls.id_serie FROM f_detalle_venta_agrupado as dv
   inner join f_venta_agrupado as fv on dv.id_factura=fv.id_factura
   INNER JOIN libros_series as ls ON dv.pro_codigo=ls.codigo_liquidacion
    INNER JOIN libro l ON ls.idLibro = l.idlibro
        WHERE dv.id_factura='$request->ven_codigo' and dv.id_empresa=fv.id_empresa
        and fv.id_empresa=$request->idemp  order by dv.pro_codigo");

             return $query;
    }
    //funcion para traer la imagen de la empresa
    public function GetVuser(Request $request){
        $query1 = DB::SELECT("SELECT  img_base64 as img FROM empresas
        where id=$request->id");
        $img=$query1[0]->img;
             return $img;
    }
    //api:get/empresaActual
    public function empresaActual(Request $request){
        $query = DB::SELECT("SELECT e.id, e.descripcion_corta FROM empresas e
            WHERE e.imagen_utilizada = '1'
        ");
        return $query;
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
    public function imprimirDVenta(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validar si existe la venta
            $venta = Ventas::where('ven_codigo', $request->ven_codigo)
                ->where('id_empresa', $request->id_empresa)
                ->first();

            if (!$venta) {
                return response()->json([
                    "status" => "0",
                    "message" => "El ven_codigo no existe en la base de datos"
                ], 200);
            }

            // Actualizar la tabla f_venta
            DB::table('f_venta')
                ->where('ven_codigo', $request->ven_codigo)
                ->where('id_empresa', $request->id_empresa)
                ->update([
                    'impresion' => '1',
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'message' => 'Impresión exitosa'
            ], 200);
        } catch (\Exception $e) {
            DB::rollback(); // Asegurar que se haga el rollback

            return response()->json([
                "status" => "0",
                "message" => "No se pudo imprimir",
                'error' => $e->getMessage()
            ], 200);
        }
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
    public function updateSubtotal(Request $request)
    {
        try {
            DB::beginTransaction();

            // Recupera todos los IDs de proforma con ventas no nulas
            $validated = DB::select("SELECT DISTINCT ven_idproforma FROM f_venta WHERE ven_idproforma IS NOT NULL");

            // Extrae los IDs de proforma de los resultados
            $proformaIds = array_map(function($item) {
                return $item->ven_idproforma;
            }, $validated);

            // Recupera todas las ventas con los ID de proforma proporcionados
            $ventas = Ventas::whereIn('ven_idproforma', $proformaIds)->get();

            foreach ($ventas as $venta) {
                // Calcula el nuevo subtotal
                $venta->ven_subtotal = $venta->ven_valor + $venta->ven_descuento;
                // Guarda el cambio
                $venta->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Subtotal actualizado correctamente',
                'data' => $ventas
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'message' => 'No se pudo actualizar el subtotal'
            ], 500);
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
                $venta->ven_subtotal        = $request->ven_subtotal;
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
            INNER JOIN f_venta v ON d.ven_codigo = v.ven_codigo AND v.id_empresa = d.id_empresa
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
                $id_usuario = $request->id_usuario;
                DB::beginTransaction();
                $venta = Ventas::where('ven_codigo', $request->ven_codigo)
                ->where('id_empresa', $request->empresa)
                ->firstOrFail();
                    if (!$venta){
                        return "El ven_codigo no existe en la base de datos";
                    }
                    DB::table('f_venta')
                    ->where('ven_codigo', $request->ven_codigo)
                    ->where('id_empresa', $request->empresa)
                    ->update([
                        'est_ven_codigo'       => $request->est_ven_codigo,
                        'user_anulado'         => $id_usuario,
                        'observacionAnulacion' => $request->observacionAnulacion,
                        'fecha_anulacion'      => now(),
                    ]);
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
            $venta= Ventas::where('ven_codigo',$request->ven_codigo)->where('id_empresa', $request->id_empresa)->first();
            if (!$venta){
                return "El ven_codigo no existe en la base de datos";
            }
            DB::table('f_venta')
            ->where('ven_codigo', $request->ven_codigo)
            ->where('id_empresa', $request->id_empresa)
            ->update([
                'est_ven_codigo'       => $request->est_ven_codigo,
            ]);
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
    public function GetVentasPendientes(Request $request) {
        if($request->op==0){
            $query=DB::SELECT("SELECT f.*,
       tv.tip_ven_nombre,
       i.nombreInstitucion,
       us.cedula,
       us.email,
       us.telefono,
       i.telefonoInstitucion,
       i.direccionInstitucion,
       i.asesor_id,
       CONCAT(usa.nombres,' ',usa.apellidos) AS asesor,
       em.nombre,
       user.cedula AS cedula1,
       pe.periodoescolar,
       CONCAT(u.nombres,' ',u.apellidos) AS responsable,
       CONCAT(us.nombres,' ',us.apellidos) AS cliente,
       CONCAT(user.nombres,' ',user.apellidos) AS cliente1,
       pr.pedido_id,
       pr.idPuntoventa,
       pr.prof_observacion,
       pl.id_pedido AS pedido_id,
       SUM(dv.det_ven_cantidad) AS cantidad_despacho,
       pl.porcentaje_descuento
FROM f_venta f
INNER JOIN 1_4_tipo_venta tv ON f.tip_ven_codigo = tv.tip_ven_codigo
INNER JOIN institucion i ON f.institucion_id = i.idInstitucion
INNER JOIN empresas em ON f.id_empresa = em.id
INNER JOIN periodoescolar pe ON f.periodo_id = pe.idperiodoescolar
INNER JOIN usuario u ON f.user_created = u.idusuario
LEFT JOIN usuario us ON f.ven_cliente = us.idusuario
LEFT JOIN pedidos_beneficiarios pb ON f.ven_cliente = pb.id_beneficiario_pedido
LEFT JOIN usuario user ON pb.id_usuario = user.idusuario
INNER JOIN f_detalle_venta dv ON f.ven_codigo = dv.ven_codigo AND f.id_empresa = dv.id_empresa
LEFT JOIN usuario usa ON i.asesor_id = usa.idusuario
LEFT JOIN f_proforma pr ON pr.prof_id = f.ven_idproforma
LEFT JOIN p_libros_obsequios pl ON pl.id = f.ven_p_libros_obsequios
WHERE f.est_ven_codigo = 2
GROUP BY f.ven_codigo, f.id_empresa, f.tip_ven_codigo, i.nombreInstitucion, us.cedula, us.email,
         us.telefono, i.telefonoInstitucion, i.direccionInstitucion, i.asesor_id,
         CONCAT(usa.nombres,' ',usa.apellidos), em.nombre, user.cedula,
         pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos),
         CONCAT(us.nombres,' ',us.apellidos), CONCAT(user.nombres,' ',user.apellidos),
         pr.pedido_id, pr.idPuntoventa, pr.prof_observacion, pl.id_pedido
ORDER BY f.ven_fecha;");
           foreach($query as $key => $item){
            if($item->idPuntoventa){
                $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.ca_codigo_agrupado= '$item->idPuntoventa'");
                $query[$key]->contratos =$quer1;
            }else{
                if($item->pedido_id){
                    $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.id_pedido= '$item->pedido_id'");
                    $query[$key]->contratos =$quer1;
                }
            }

          }
        }else if($request->op==1){
            $query=DB::SELECT("SELECT f.*, tv.tip_ven_nombre,i.nombreInstitucion,
           us.cedula,
           us.email,
           us.telefono,
           i.telefonoInstitucion,
           i.direccionInstitucion,
           i.asesor_id,
           concat(usa.nombres,' ',usa.apellidos) as asesor,
           em.nombre,
           user.cedula as cedula1,
           pe.periodoescolar,
           CONCAT(u.nombres,' ',u.apellidos) AS responsable,
           CONCAT(us.nombres,' ',us.apellidos) AS cliente,
           CONCAT(user.nombres,' ',user.apellidos) AS cliente1,
           pr.pedido_id,
           pr.idPuntoventa,
           pr.prof_observacion,
           pl.id_pedido AS pedido_id,
          SUM(dv.det_ven_cantidad) AS cantidad_despacho,
          pl.porcentaje_descuento
          FROM f_venta f
          INNER JOIN 1_4_tipo_venta tv ON f.tip_ven_codigo = tv.tip_ven_codigo
          INNER JOIN institucion i ON f.institucion_id = i.idInstitucion
          INNER JOIN empresas em ON f.id_empresa = em.id
          INNER JOIN periodoescolar pe ON f.periodo_id = pe.idperiodoescolar
          INNER JOIN usuario u ON f.user_created = u.idusuario
          LEFT JOIN usuario us ON f.ven_cliente = us.idusuario
          LEFT JOIN pedidos_beneficiarios pb ON f.ven_cliente =pb.id_beneficiario_pedido
          LEFT JOIN usuario user ON pb.id_usuario = user.idusuario
          INNER JOIN f_detalle_venta dv ON f.ven_codigo = dv.ven_codigo AND f.id_empresa = dv.id_empresa
          LEFT JOIN usuario usa ON i.asesor_id = usa.idusuario
          LEFT JOIN  f_proforma  pr on pr.prof_id=f.ven_idproforma
          LEFT JOIN p_libros_obsequios pl ON pl.id=f.ven_p_libros_obsequios
          WHERE f.est_ven_codigo = 11
          GROUP BY f.ven_codigo, f.id_empresa, f.tip_ven_codigo, i.nombreInstitucion, us.cedula, us.email,
         us.telefono, i.telefonoInstitucion, i.direccionInstitucion, i.asesor_id,
         CONCAT(usa.nombres,' ',usa.apellidos), em.nombre, user.cedula,
         pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos),
         CONCAT(us.nombres,' ',us.apellidos), CONCAT(user.nombres,' ',user.apellidos),
         pr.pedido_id, pr.idPuntoventa, pr.prof_observacion, pl.id_pedido
           order by f.ven_fecha");
           foreach($query as $key => $item){
            if($item->idPuntoventa){
                $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.ca_codigo_agrupado= '$item->idPuntoventa'");
                $query[$key]->contratos =$quer1;
            }else{
                if($item->pedido_id){
                    $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.id_pedido= '$item->pedido_id'");
                    $query[$key]->contratos =$quer1;
                }
            }

          }
        } else if($request->op==2)
        {
            $query=DB::SELECT("SELECT f.*, tv.tip_ven_nombre,i.nombreInstitucion,
           us.cedula,
           us.email,
           us.telefono,
           i.telefonoInstitucion,
           i.direccionInstitucion,
           i.asesor_id,
           concat(usa.nombres,' ',usa.apellidos) as asesor,
           em.nombre,
           user.cedula as cedula1,
           pe.periodoescolar,
           CONCAT(u.nombres,' ',u.apellidos) AS responsable,
           CONCAT(us.nombres,' ',us.apellidos) AS cliente,
           CONCAT(user.nombres,' ',user.apellidos) AS cliente1,
           pr.pedido_id,
           pr.idPuntoventa,
           pr.prof_observacion,
           pl.id_pedido AS pedido_id,
          SUM(dv.det_ven_cantidad) AS cantidad_despacho,
          pl.porcentaje_descuento
          FROM f_venta f
          INNER JOIN 1_4_tipo_venta tv ON f.tip_ven_codigo = tv.tip_ven_codigo
          INNER JOIN institucion i ON f.institucion_id = i.idInstitucion
          INNER JOIN empresas em ON f.id_empresa = em.id
          INNER JOIN periodoescolar pe ON f.periodo_id = pe.idperiodoescolar
          INNER JOIN usuario u ON f.user_created = u.idusuario
          LEFT JOIN usuario us ON f.ven_cliente = us.idusuario
          LEFT JOIN pedidos_beneficiarios pb ON f.ven_cliente =pb.id_beneficiario_pedido
          LEFT JOIN usuario user ON pb.id_usuario = user.idusuario
          INNER JOIN f_detalle_venta dv ON f.ven_codigo = dv.ven_codigo AND f.id_empresa = dv.id_empresa
          LEFT JOIN usuario usa ON i.asesor_id = usa.idusuario
          LEFT JOIN  f_proforma  pr on pr.prof_id=f.ven_idproforma
          LEFT JOIN p_libros_obsequios pl ON pl.id=f.ven_p_libros_obsequios
          WHERE f.est_ven_codigo = 12
          GROUP BY f.ven_codigo, f.id_empresa, f.tip_ven_codigo, i.nombreInstitucion, us.cedula, us.email,
         us.telefono, i.telefonoInstitucion, i.direccionInstitucion, i.asesor_id,
         CONCAT(usa.nombres,' ',usa.apellidos), em.nombre, user.cedula,
         pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos),
         CONCAT(us.nombres,' ',us.apellidos), CONCAT(user.nombres,' ',user.apellidos),
         pr.pedido_id, pr.idPuntoventa, pr.prof_observacion, pl.id_pedido
          order by f.ven_fecha");
           foreach($query as $key => $item){
            if($item->idPuntoventa){
                $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.ca_codigo_agrupado= '$item->idPuntoventa'");
                $query[$key]->contratos =$quer1;
            }else{
                if($item->pedido_id){
                    $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.id_pedido= '$item->pedido_id'");
                    $query[$key]->contratos =$quer1;
                }
            }

          }
        }
        else if($request->op==3)
        {
            $query=DB::SELECT("SELECT f.*, tv.tip_ven_nombre,i.nombreInstitucion,
           us.cedula,
           us.email,
           us.telefono,
           i.telefonoInstitucion,
           i.direccionInstitucion,
           i.asesor_id,
           concat(usa.nombres,' ',usa.apellidos) as asesor,
           em.nombre,
           user.cedula as cedula1,
           pe.periodoescolar,
           CONCAT(u.nombres,' ',u.apellidos) AS responsable,
           CONCAT(us.nombres,' ',us.apellidos) AS cliente,
           CONCAT(user.nombres,' ',user.apellidos) AS cliente1,
           pr.pedido_id,
           pr.idPuntoventa,
           pr.prof_observacion,
           pl.id_pedido AS pedido_id,
          SUM(dv.det_ven_cantidad) AS cantidad_despacho,
          pl.porcentaje_descuento
          FROM f_venta f
          INNER JOIN 1_4_tipo_venta tv ON f.tip_ven_codigo = tv.tip_ven_codigo
          INNER JOIN institucion i ON f.institucion_id = i.idInstitucion
          INNER JOIN empresas em ON f.id_empresa = em.id
          INNER JOIN periodoescolar pe ON f.periodo_id = pe.idperiodoescolar
          INNER JOIN usuario u ON f.user_created = u.idusuario
          LEFT JOIN usuario us ON f.ven_cliente = us.idusuario
          LEFT JOIN pedidos_beneficiarios pb ON f.ven_cliente =pb.id_beneficiario_pedido
          LEFT JOIN usuario user ON pb.id_usuario = user.idusuario
          INNER JOIN f_detalle_venta dv ON f.ven_codigo = dv.ven_codigo AND f.id_empresa = dv.id_empresa
          LEFT JOIN usuario usa ON i.asesor_id = usa.idusuario
          LEFT JOIN  f_proforma  pr on pr.prof_id=f.ven_idproforma
          LEFT JOIN p_libros_obsequios pl ON pl.id=f.ven_p_libros_obsequios
          WHERE f.est_ven_codigo = 10
          GROUP BY f.ven_codigo, f.id_empresa, f.tip_ven_codigo, i.nombreInstitucion, us.cedula, us.email,
         us.telefono, i.telefonoInstitucion, i.direccionInstitucion, i.asesor_id,
         CONCAT(usa.nombres,' ',usa.apellidos), em.nombre, user.cedula,
         pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos),
         CONCAT(us.nombres,' ',us.apellidos), CONCAT(user.nombres,' ',user.apellidos),
         pr.pedido_id, pr.idPuntoventa, pr.prof_observacion, pl.id_pedido
           order by f.ven_fecha");
           foreach($query as $key => $item){
            if($item->idPuntoventa){
                $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.ca_codigo_agrupado= '$item->idPuntoventa'");
                $query[$key]->contratos =$quer1;
            }else{
                if($item->pedido_id){
                    $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.id_pedido= '$item->pedido_id'");
                    $query[$key]->contratos =$quer1;
                }
            }

          }
        }else if($request->op==4)
        {
            $query=DB::SELECT("SELECT f.*, tv.tip_ven_nombre,i.nombreInstitucion,
           us.cedula,
           us.email,
           us.telefono,
           i.telefonoInstitucion,
           i.direccionInstitucion,
           i.asesor_id,
           concat(usa.nombres,' ',usa.apellidos) as asesor,
           em.nombre,
           user.cedula as cedula1,
           pe.periodoescolar,
           CONCAT(u.nombres,' ',u.apellidos) AS responsable,
           CONCAT(us.nombres,' ',us.apellidos) AS cliente,
           CONCAT(user.nombres,' ',user.apellidos) AS cliente1,
           pr.pedido_id,
           pr.idPuntoventa,
           pr.prof_observacion,
           pl.id_pedido AS pedido_id,
          SUM(dv.det_ven_cantidad) AS cantidad_despacho,
          pl.porcentaje_descuento
          FROM f_venta f
          INNER JOIN 1_4_tipo_venta tv ON f.tip_ven_codigo = tv.tip_ven_codigo
          INNER JOIN institucion i ON f.institucion_id = i.idInstitucion
          INNER JOIN empresas em ON f.id_empresa = em.id
          INNER JOIN periodoescolar pe ON f.periodo_id = pe.idperiodoescolar
          INNER JOIN usuario u ON f.user_created = u.idusuario
          LEFT JOIN usuario us ON f.ven_cliente = us.idusuario
          LEFT JOIN pedidos_beneficiarios pb ON f.ven_cliente =pb.id_beneficiario_pedido
          LEFT JOIN usuario user ON pb.id_usuario = user.idusuario
          INNER JOIN f_detalle_venta dv ON f.ven_codigo = dv.ven_codigo AND f.id_empresa = dv.id_empresa
          LEFT JOIN usuario usa ON i.asesor_id = usa.idusuario
          LEFT JOIN  f_proforma  pr on pr.prof_id=f.ven_idproforma
          LEFT JOIN p_libros_obsequios pl ON pl.id=f.ven_p_libros_obsequios
          WHERE f.est_ven_codigo = 3
          GROUP BY f.ven_codigo, f.id_empresa, f.tip_ven_codigo, i.nombreInstitucion, us.cedula, us.email,
         us.telefono, i.telefonoInstitucion, i.direccionInstitucion, i.asesor_id,
         CONCAT(usa.nombres,' ',usa.apellidos), em.nombre, user.cedula,
         pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos),
         CONCAT(us.nombres,' ',us.apellidos), CONCAT(user.nombres,' ',user.apellidos),
         pr.pedido_id, pr.idPuntoventa, pr.prof_observacion, pl.id_pedido
           order by f.ven_fecha");
           foreach($query as $key => $item){
            if($item->idPuntoventa){
                $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.ca_codigo_agrupado= '$item->idPuntoventa'");
                $query[$key]->contratos =$quer1;
            }else{
                if($item->pedido_id){
                    $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.id_pedido= '$item->pedido_id'");
                    $query[$key]->contratos =$quer1;
                }
            }

          }
        }else if($request->op==5)
        {
            $query=DB::SELECT("SELECT f.*, tv.tip_ven_nombre,i.nombreInstitucion,
            us.cedula,
            us.email,
            us.telefono,
            i.telefonoInstitucion,
            i.direccionInstitucion,
            i.asesor_id,
            concat(usa.nombres,' ',usa.apellidos) as asesor,
            em.nombre,
            user.cedula as cedula1,
            pe.periodoescolar,
            CONCAT(u.nombres,' ',u.apellidos) AS responsable,
            CONCAT(us.nombres,' ',us.apellidos) AS cliente,
            CONCAT(user.nombres,' ',user.apellidos) AS cliente1,
            pr.pedido_id,
            pr.idPuntoventa,
            pr.prof_observacion,
            pl.id_pedido AS pedido_id,
            SUM(dv.det_ven_cantidad) AS cantidad_despacho,
            pl.porcentaje_descuento
            FROM f_venta f
            INNER JOIN 1_4_tipo_venta tv ON f.tip_ven_codigo = tv.tip_ven_codigo
            INNER JOIN institucion i ON f.institucion_id = i.idInstitucion
            INNER JOIN empresas em ON f.id_empresa = em.id
            INNER JOIN periodoescolar pe ON f.periodo_id = pe.idperiodoescolar
            INNER JOIN usuario u ON f.user_created = u.idusuario
            LEFT JOIN usuario us ON f.ven_cliente = us.idusuario
            LEFT JOIN pedidos_beneficiarios pb ON f.ven_cliente =pb.id_beneficiario_pedido
            LEFT JOIN usuario user ON pb.id_usuario = user.idusuario
            INNER JOIN f_detalle_venta dv ON f.ven_codigo = dv.ven_codigo AND f.id_empresa = dv.id_empresa
            LEFT JOIN usuario usa ON i.asesor_id = usa.idusuario
            LEFT JOIN  f_proforma  pr on pr.prof_id=f.ven_idproforma
            LEFT JOIN p_libros_obsequios pl ON pl.id=f.ven_p_libros_obsequios
            WHERE f.est_ven_codigo = 11 OR f.est_ven_codigo = 12 OR f.est_ven_codigo = 10 OR f.est_ven_codigo = 2
            GROUP BY f.ven_codigo, f.id_empresa, f.tip_ven_codigo, i.nombreInstitucion, us.cedula, us.email,
            us.telefono, i.telefonoInstitucion, i.direccionInstitucion, i.asesor_id,
            CONCAT(usa.nombres,' ',usa.apellidos), em.nombre, user.cedula,
            pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos),
            CONCAT(us.nombres,' ',us.apellidos), CONCAT(user.nombres,' ',user.apellidos),
            pr.pedido_id, pr.idPuntoventa, pr.prof_observacion, pl.id_pedido
            order by f.ven_fecha");
            foreach($query as $key => $item){
                if($item->idPuntoventa){
                    $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.ca_codigo_agrupado= '$item->idPuntoventa'");
                    $query[$key]->contratos =$quer1;
                }else{
                    if($item->pedido_id){
                        $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.id_pedido= '$item->pedido_id'");
                        $query[$key]->contratos =$quer1;
                    }
                }
            }
        }
        // $query1 = DB::table('f_venta as f')
        //     ->select(
        //         'f.*',
        //         'tv.tip_ven_nombre',
        //         'i.nombreInstitucion',
        //         'i.ruc',
        //         'i.email',
        //         'i.telefonoInstitucion',
        //         'i.direccionInstitucion',
        //         'em.nombre',
        //         'em.img_base64',
        //         'pe.periodoescolar',
        //         DB::raw("CONCAT(u.nombres,' ',u.apellidos) AS responsable"),
        //         DB::raw("CONCAT(us.nombres,' ',us.apellidos) AS cliente"),
        //         'pr.pedido_id',
        //         DB::raw("SUM(dv.det_ven_cantidad) AS cantidad_despacho")
        //     )
        //     ->join('1_4_tipo_venta as tv', 'f.tip_ven_codigo', '=', 'tv.tip_ven_codigo')
        //     ->join('institucion as i', 'f.institucion_id', '=', 'i.idInstitucion')
        //     ->join('empresas as em', 'f.id_empresa', '=', 'em.id')
        //     ->join('periodoescolar as pe', 'f.periodo_id', '=', 'pe.idperiodoescolar')
        //     ->join('usuario as u', 'f.user_created', '=', 'u.idusuario')
        //     ->join('usuario as us', 'f.ven_cliente', '=', 'us.idusuario')
        //     ->join('f_proforma as pr', 'f.ven_idproforma', '=', 'pr.prof_id')
        //     ->join('f_detalle_venta as dv', 'f.ven_codigo', '=', 'dv.ven_codigo')
        //     ->where('f.est_ven_codigo', 2)
        //     ->orwhere('f.est_ven_codigo', 12)
        //     ->groupBy('f.ven_codigo');
        // $query2 = DB::table('f_venta as f')
        //     ->select(
        //         'f.*',
        //         'tv.tip_ven_nombre',
        //         'i.nombreInstitucion',
        //         'i.ruc',
        //         'i.email',
        //         'i.telefonoInstitucion',
        //         'i.direccionInstitucion',
        //         'em.nombre',
        //         'em.img_base64',
        //         'pe.periodoescolar',
        //         DB::raw("CONCAT(u.nombres,' ',u.apellidos) AS responsable"),
        //         DB::raw("CONCAT(us.nombres,' ',us.apellidos) AS cliente"),
        //         'pl.id_pedido AS pedido_id',
        //         'pl.total_unidades AS cantidad_despacho'
        //     )
        //     ->join('1_4_tipo_venta as tv', 'f.tip_ven_codigo', '=', 'tv.tip_ven_codigo')
        //     ->join('institucion as i', 'f.institucion_id', '=', 'i.idInstitucion')
        //     ->join('empresas as em', 'f.id_empresa', '=', 'em.id')
        //     ->join('periodoescolar as pe', 'f.periodo_id', '=', 'pe.idperiodoescolar')
        //     ->join('usuario as u', 'f.user_created', '=', 'u.idusuario')
        //     ->join('usuario as us', 'f.ven_cliente', '=', 'us.idusuario')
        //     ->join('p_libros_obsequios as pl', 'pl.id', '=', 'f.ven_p_libros_obsequios')
        //     ->where('f.est_ven_codigo', 12)
        //     ->orwhere('f.est_ven_codigo', 2);

        // $result = $query1->union($query2)->orderBy('ven_fecha')->get();

        // return $result;
        return $query;
    }
    public function GetVentasDespachadasxParametro(Request $request){
        if($request->opcion==0){
            $query=DB::SELECT("SELECT f.*, tv.tip_ven_nombre,i.nombreInstitucion,
            --    i.ruc,
            --    i.email,
               us.cedula,
               us.email,
               i.telefonoInstitucion,
               i.direccionInstitucion,
               em.nombre,
               user.cedula as cedula1,
               pe.periodoescolar,
               CONCAT(u.nombres,' ',u.apellidos) AS responsable,
               CONCAT(us.nombres,' ',us.apellidos) AS cliente,
               CONCAT(user.nombres,' ',user.apellidos) AS cliente1,
               CONCAT(interc.nombres,' ',interc.apellidos) AS user_intercambio,
               pr.pedido_id,
               pr.idPuntoventa,
               pl.id_pedido AS pedido_id,
              SUM(dv.det_ven_cantidad) AS cantidad_despacho,
              CONCAT(ua.nombres,' ',ua.apellidos) AS usuario_anulado,
              FROM f_venta f
              INNER JOIN 1_4_tipo_venta tv ON f.tip_ven_codigo = tv.tip_ven_codigo
              INNER JOIN institucion i ON f.institucion_id = i.idInstitucion
              INNER JOIN empresas em ON f.id_empresa = em.id
              INNER JOIN periodoescolar pe ON f.periodo_id = pe.idperiodoescolar
              INNER JOIN usuario u ON f.user_created = u.idusuario
              INNER JOIN usuario us ON f.ven_cliente = us.idusuario
              LEFT JOIN pedidos_beneficiarios pb ON f.ven_cliente =pb.id_beneficiario_pedido
              LEFT JOIN usuario user ON pb.id_usuario = user.idusuario
              INNER JOIN f_detalle_venta dv ON f.ven_codigo = dv.ven_codigo AND f.id_empresa = dv.id_empresa
              LEFT JOIN  f_proforma  pr on pr.prof_id=f.ven_idproforma
              LEFT JOIN p_libros_obsequios pl ON pl.id=f.ven_p_libros_obsequios
              LEFT JOIN usuario as interc ON f.user_intercambio = interc.idusuario
              LEFT JOIN usuario ua ON f.user_anulado = ua.idusuario
              WHERE f.est_ven_codigo = 1
              AND (f.ven_codigo like'%$request->parte_documento%' or f.ven_cliente like'%$request->parte_documento%'
              or i.nombreInstitucion like'%$request->parte_documento%')
              GROUP BY f.ven_codigo, f.id_empresa, f.tip_ven_codigo, i.nombreInstitucion, us.cedula, us.email,
             us.telefono, i.telefonoInstitucion, i.direccionInstitucion, i.asesor_id,
             CONCAT(u.nombres,' ',u.apellidos), em.nombre, user.cedula,
             pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos),
             CONCAT(us.nombres,' ',us.apellidos), CONCAT(user.nombres,' ',user.apellidos),
             pr.pedido_id, pr.idPuntoventa, pr.prof_observacion, pl.id_pedido
              order by f.ven_fecha");
               foreach($query as $key => $item){
                if($item->idPuntoventa){
                    $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.ca_codigo_agrupado= '$item->idPuntoventa'");
                    $query[$key]->contratos =$quer1;
                }else{
                    if($item->pedido_id){
                        $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.id_pedido= '$item->pedido_id'");
                        $query[$key]->contratos =$quer1;
                    }
                }
            }
        }else if($request->opcion==1){
            $query=DB::SELECT("SELECT f.*, tv.tip_ven_nombre,i.nombreInstitucion,
               us.cedula,
               us.email,
               i.telefonoInstitucion,
               i.direccionInstitucion,
               em.nombre,
               user.cedula as cedula1,
               pe.periodoescolar,
               CONCAT(u.nombres,' ',u.apellidos) AS responsable,
               CONCAT(us.nombres,' ',us.apellidos) AS cliente,
               CONCAT(user.nombres,' ',user.apellidos) AS cliente1,
               CONCAT(interc.nombres,' ',interc.apellidos) AS user_intercambio,
               pr.pedido_id,
               pr.idPuntoventa,
               pl.id_pedido AS pedido_id,
              SUM(dv.det_ven_cantidad) AS cantidad_despacho,
              CONCAT(au.nombres,' ',au.apellidos) AS usuario_anulado
              FROM f_venta f
              INNER JOIN 1_4_tipo_venta tv ON f.tip_ven_codigo = tv.tip_ven_codigo
              INNER JOIN institucion i ON f.institucion_id = i.idInstitucion
              INNER JOIN empresas em ON f.id_empresa = em.id
              INNER JOIN periodoescolar pe ON f.periodo_id = pe.idperiodoescolar
              INNER JOIN usuario u ON f.user_created = u.idusuario
              INNER JOIN usuario us ON f.ven_cliente = us.idusuario
              LEFT JOIN pedidos_beneficiarios pb ON f.ven_cliente =pb.id_beneficiario_pedido
              LEFT JOIN usuario user ON pb.id_usuario = user.idusuario
              INNER JOIN f_detalle_venta dv ON f.ven_codigo = dv.ven_codigo AND f.id_empresa = dv.id_empresa
              LEFT JOIN  f_proforma  pr on pr.prof_id=f.ven_idproforma
              LEFT JOIN p_libros_obsequios pl ON pl.id=f.ven_p_libros_obsequios
              LEFT JOIN usuario as interc ON f.user_intercambio = interc.idusuario
              LEFT JOIN usuario au ON au.idusuario = f.user_anulado
              WHERE (f.ven_codigo like'%$request->parte_documento%' or f.ruc_cliente like'%$request->parte_documento%' OR i.nombreInstitucion like'%$request->parte_documento%')
              and f.periodo_id=$request->periodo
              GROUP BY f.ven_codigo, f.id_empresa, f.tip_ven_codigo, i.nombreInstitucion, us.cedula, us.email,
             us.telefono, i.telefonoInstitucion, i.direccionInstitucion, i.asesor_id,
             CONCAT(u.nombres,' ',u.apellidos), em.nombre, user.cedula,
             pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos),
             CONCAT(us.nombres,' ',us.apellidos), CONCAT(user.nombres,' ',user.apellidos),
             pr.pedido_id, pr.idPuntoventa, pr.prof_observacion, pl.id_pedido
              order by f.ven_fecha");
               foreach($query as $key => $item){
                if($item->idPuntoventa){
                    $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.ca_codigo_agrupado= '$item->idPuntoventa'");
                    $query[$key]->contratos =$quer1;
                }else{
                    if($item->pedido_id){
                        $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.id_pedido= '$item->pedido_id'");
                        $query[$key]->contratos =$quer1;
                    }
                }
            }
        }
        else if($request->opcion==2){
            $query=DB::SELECT("SELECT f.*, tv.tip_ven_nombre,i.nombreInstitucion,
               us.cedula,
               us.email,
               i.telefonoInstitucion,
               i.direccionInstitucion,
               em.nombre,
               user.cedula as cedula1,
               pe.periodoescolar,
               CONCAT(u.nombres,' ',u.apellidos) AS responsable,
               CONCAT(us.nombres,' ',us.apellidos) AS cliente,
               CONCAT(user.nombres,' ',user.apellidos) AS cliente1,
               CONCAT(interc.nombres,' ',interc.apellidos) AS user_intercambio,
               pr.pedido_id,
               pr.idPuntoventa,
               pl.id_pedido AS pedido_id,
              SUM(dv.det_ven_cantidad) AS cantidad_despacho,
              CONCAT(au.nombres,' ',au.apellidos) AS usuario_anulado
              FROM f_venta f
              INNER JOIN 1_4_tipo_venta tv ON f.tip_ven_codigo = tv.tip_ven_codigo
              INNER JOIN institucion i ON f.institucion_id = i.idInstitucion
              INNER JOIN empresas em ON f.id_empresa = em.id
              INNER JOIN periodoescolar pe ON f.periodo_id = pe.idperiodoescolar
              INNER JOIN usuario u ON f.user_created = u.idusuario
              INNER JOIN usuario us ON f.ven_cliente = us.idusuario
              LEFT JOIN pedidos_beneficiarios pb ON f.ven_cliente =pb.id_beneficiario_pedido
              LEFT JOIN usuario user ON pb.id_usuario = user.idusuario
              INNER JOIN f_detalle_venta dv ON f.ven_codigo = dv.ven_codigo AND f.id_empresa = dv.id_empresa
              LEFT JOIN  f_proforma  pr on pr.prof_id=f.ven_idproforma
              LEFT JOIN p_libros_obsequios pl ON pl.id=f.ven_p_libros_obsequios
              LEFT JOIN usuario as interc ON f.user_intercambio = interc.idusuario
              LEFT JOIN usuario au ON au.idusuario = f.user_anulado
              WHERE (f.ven_codigo like'%$request->parte_documento%' or f.ruc_cliente like'%$request->parte_documento%')
              GROUP BY f.ven_codigo, f.id_empresa, f.tip_ven_codigo, i.nombreInstitucion, us.cedula, us.email,
             us.telefono, i.telefonoInstitucion, i.direccionInstitucion, i.asesor_id,
             CONCAT(u.nombres,' ',u.apellidos), em.nombre, user.cedula,
             pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos),
             CONCAT(us.nombres,' ',us.apellidos), CONCAT(user.nombres,' ',user.apellidos),
             pr.pedido_id, pr.idPuntoventa, pr.prof_observacion, pl.id_pedido
              order by f.ven_fecha");
               foreach($query as $key => $item){
                if($item->idPuntoventa){
                    $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.ca_codigo_agrupado= '$item->idPuntoventa'");
                    $query[$key]->contratos =$quer1;
                }else{
                    if($item->pedido_id){
                        $quer1=DB::SELECT("select pe.contrato_generado from pedidos pe where pe.id_pedido= '$item->pedido_id'");
                        $query[$key]->contratos =$quer1;
                    }
                }
            }
        }
        return $query;

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
            WHERE dv.ven_codigo = '$request->id'
            and dv.id_empresa = $request->idemp
            order by dv.pro_codigo");
        return $query;
    }
    //cambio ingereso de datos de libros a excluir o despachar
    public function despachar(Request $request) {
        try {
            set_time_limit(600);
            ini_set('max_execution_time', 600);

            $miarray = json_decode($request->deta);

            // Verifica si el JSON fue decodificado correctamente
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'JSON inválido'], 400);
            }

            DB::beginTransaction();

            // Busca la venta en la tabla f_ventas
            $venta = DB::table('f_venta')
                       ->where('ven_codigo', $request->ven_codigo)
                       ->where('id_empresa', $request->id_empresa)
                       ->first();

            if (!$venta) {
                return response()->json(['error' => 'El ven_codigo no existe en la base de datos'], 404);
            }

            // Actualiza el estado de la venta
            DB::table('f_venta')
              ->where('ven_codigo', $request->ven_codigo)
              ->where('id_empresa', $request->id_empresa)
              ->update([
                  'est_ven_codigo' => $request->estado,
                  'updated_at' => now(),
              ]);

            // Actualiza los detalles de la venta
            foreach ($miarray as $item) {
                if ($item->despacho != 0) {
                    $detalle = DB::table('f_detalle_venta')
                                 ->where('det_ven_codigo', $item->det_ven_codigo)
                                 ->where('id_empresa', $request->id_empresa)
                                 ->first();

                    if (!$detalle) {
                        return response()->json(['error' => 'El det_ven_codigo no existe en la base de datos'], 404);
                    }

                    // Actualiza la cantidad de despacho
                    DB::table('f_detalle_venta')
                      ->where('det_ven_codigo', $item->det_ven_codigo)
                      ->where('id_empresa', $request->id_empresa)
                      ->update(['det_ven_cantidad_despacho' => $item->despacho]);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Preparación de empaque exitosa'], 200);

        } catch (\Exception $e) {
            DB::rollback(); // Asegúrate de hacer rollback aquí
            return response()->json([
                "error" => "0",
                "message" => "No se pudo preparar",
                'error' => $e->getMessage()
            ], 500);
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
                $venta->ven_fecha           = now();
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

    public function PostFacturarReal(Request $request)
{
    try {
        $miarray = json_decode($request->data_detalle);
        DB::beginTransaction();

        // Verificar si el ven_codigo ya existe
        $query = DB::SELECT("SELECT id_factura FROM f_venta_agrupado WHERE id_factura = ?", [$request->ven_codigo]);

        if (!empty($query)) {
            return response()->json(["status" => "2", "message" => "El ven_codigo ya existe"], 0);
        }

        // Crear la venta
        $venta = new VentasF;
        $venta->id_factura = $request->ven_codigo;
        $venta->id_empresa = $request->id_empresa;
        $venta->ven_desc_por = $request->ven_desc_por;
        $venta->ven_iva_por = $request->ven_iva_por;
        $venta->ven_descuento = $request->ven_descuento;
        $venta->ven_iva = $request->ven_iva;
        $venta->ven_transporte = $request->ven_transporte;
        $venta->ven_valor = $request->ven_valor;
        $venta->ven_subtotal = $request->ven_subtotal;
        $venta->ven_devolucion = 0;
        $venta->ven_pagado = 0;
        $venta->institucion_id = $request->id_ins_depacho;
        $venta->periodo_id = $request->periodo_id;
        $venta->idtipodoc = 11;
        $venta->ven_cliente = $request->ven_cliente;
        $venta->clientesidPerseo = $request->clientesidPerseo;
        $venta->ven_fecha = $request->ven_fecha;
        $venta->user_created = $request->user_created;
        $venta->updated_at = now();
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

        // Guardar los detalles de la venta
        foreach ($miarray as $item) {
            if ($item->cantidad_real_facturar > 0) {
                $venta1 = new DetalleVentasF;
                $venta1->id_factura = $request->ven_codigo;  // Asegúrate de que esta relación existe
                $venta1->id_empresa = $request->id_empresa;
                $venta1->pro_codigo = $item->pro_codigo;
                $venta1->det_ven_cantidad = $item->cantidad_real_facturar;
                $venta1->det_ven_valor_u = $item->det_ven_valor_u;
                $venta1->save();
            }
        }

        // Finalizar la transacción
        DB::commit();
        return response()->json(["status" => "200", 'message' => 'Documento creado con éxito'], 200);
    } catch (\Exception $e) {
        DB::rollback();
        return response()->json(["error" => "500", "message" => "No se pudo guardar", "exception" => $e->getMessage(), "line" => $e->getLine()], 500);
    }
}


    public function prefacturasCliente(Request $request){
        $query = DB::SELECT("SELECT DISTINCT i.nombreInstitucion, i.idInstitucion FROM institucion i
        INNER JOIN f_venta fv ON i.idInstitucion = fv.institucion_id
        WHERE i.nombreInstitucion  LIKE '%$request->busqueda%'");

        // $query = DB::SELECT("SELECT fv.*, CONCAT(usu.nombres, '', usu.apellidos) AS clienteUsuario, i.nombreInstitucion AS clienteInstitucion FROM f_venta fv
        // INNER JOIN usuario usu ON usu.cedula  = fv.ruc_cliente
        // INNER JOIN institucion i ON i.idInstitucion = fv.institucion_id
        // WHERE i.nombreInstitucion  LIKE '%$request->busqueda%'");
        return $query;
    }
    public function getAllPrefacturas(Request $request) {
        $ventas = DB::table('f_venta as fv')
            ->join('empresas as e', 'fv.id_empresa', '=', 'e.id')
            ->join('periodoescolar as p', 'fv.periodo_id', '=', 'p.idperiodoescolar')
            ->select(
                'fv.*',
                'e.descripcion_corta',
                'p.periodoescolar'
            )
            ->where('institucion_id', $request->busqueda)
            ->where('periodo_id', $request->periodo)
            ->where('fv.idtipodoc', 1)
            ->where('fv.est_ven_codigo', '<>', 3)
            // ->whereNull('fv.id_factura')
            ->get();

        // Recopila los ruc_cliente para hacer la segunda consulta
        $rucClientes = $ventas->pluck('ruc_cliente')->unique();

        // Obtiene los usuarios y las instituciones en una sola consulta
        $usuarios = DB::table('usuario')
            ->whereIn('cedula', $rucClientes)
            ->get()
            ->keyBy('cedula');

        $instituciones = DB::table('institucion')
            ->whereIn('idInstitucion', $ventas->pluck('institucion_id')->unique())
            ->get()
            ->keyBy('idInstitucion');

        // Combina los resultados en un solo array
        foreach ($ventas as $key => $venta) {
            $usuario = $usuarios->get($venta->ruc_cliente);
            $institucion = $instituciones->get($venta->institucion_id);

            // Aquí puedes agregar los datos de cliente e institución directamente a la venta
            $ventas[$key]->clienteUsuario = $usuario ? $usuario->nombres . ' ' . $usuario->apellidos : null;
            $ventas[$key]->clienteInstitucion = $institucion ? $institucion->nombreInstitucion : null;
        }

        // Devuelve las ventas con los datos combinados
        return response()->json($ventas);
    }
    public function getCantidadFacturada(Request $request)
    {
        // Validar los parámetros de entrada
        $request->validate([
            'institucion' => 'required|integer',
            'empresa' => 'required|integer',
            'periodo' => 'required|integer',
        ]);

        // Ejecutar la consulta
        $resultados = DB::table('f_venta_agrupado as fva')
            ->join('f_detalle_venta_agrupado as fdva', 'fva.id_factura', '=', 'fdva.id_factura')
            ->select('fdva.pro_codigo', DB::raw('SUM(fdva.det_ven_cantidad) as det_ven_cantidad'))
            ->where('fva.institucion_id', $request->institucion)
            ->where('fva.id_empresa', $request->empresa)
            ->where('fva.periodo_id', $request->periodo)
            ->where('fva.est_ven_codigo',0)
            ->groupBy('fdva.pro_codigo')
            ->get();

        // Devolver los resultados
        return response()->json($resultados);
    }
    public function Get_PREFactura(Request $request){
        $query = DB::SELECT("SELECT dv.det_ven_codigo, dv.pro_codigo, dv.det_ven_dev, dv.det_ven_cantidad , dv.det_ven_valor_u,
        l.descripcionlibro, ls.nombre, s.nombre_serie, ls.id_serie, a.area_idarea, ls.year, fv.periodo_id, l.idlibro FROM f_detalle_venta as dv
        INNER JOIN f_venta as fv ON dv.ven_codigo=fv.ven_codigo
        INNER JOIN libros_series as ls ON dv.pro_codigo=ls.codigo_liquidacion
        INNER JOIN series as s ON ls.id_serie=s.id_serie
        INNER JOIN libro l ON ls.idLibro = l.idlibro
        LEFT JOIN asignatura a ON a.idasignatura = l.asignatura_idasignatura
        WHERE dv.ven_codigo='$request->ven_codigo' AND dv.id_empresa=fv.id_empresa
        AND fv.id_empresa=$request->idemp  ORDER BY dv.pro_codigo");

        foreach ($query as $key => $item) {

            //Precio por cada item
            $precio = $this->pedidosRepository->getPrecioXLibro($item->id_serie, $item->idlibro, $item->area_idarea, $item->periodo_id, $item->year);

            //Añadir el precio
            $query[$key]->precio = $precio ?? 0;
        }

        return $query;
    }

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

    public function updateDocument(Request $request)
    {
        $request->validate([
            'documentoVenta' => 'required|string',
            'id_institucion' => 'required|integer',
            'documentoProforma' => 'required|string',
        ], [
            'documentoVenta.required' => 'El documento de venta es obligatorio.',
            'id_institucion.required' => 'El ID de la institución es obligatorio.',
            'documentoProforma.required' => 'El documento de proforma es obligatorio.',
        ]);

        try {
            DB::beginTransaction();

            // Actualizar f_venta
            $venta = Ventas::where('ven_codigo', $request->documentoVenta)->where('id_empresa', $request->id_empresa)->first();
            if(!$venta){
                DB::rollBack();
                return "El ven_codigo no existe en la base de datos";
            }
            if ($venta->institucion_id !== $request->id_institucion) {
                DB::table('f_venta')
                ->where('ven_codigo', $request->documentoVenta)
                ->where('id_empresa', $request->id_empresa)
                ->update([
                    'institucion_id'       => $request->id_institucion,
                ]);
            }

            // Actualizar f_proforma
            $proforma = Proforma::where('prof_id', $venta->ven_idproforma)->firstOrFail();

            if ($proforma->id_ins_depacho !== $request->id_institucion) {
                $proforma->id_ins_depacho = $request->id_institucion;
                $proforma->save();
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Información actualizada correctamente.'], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Documento no encontrado.'], 404);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    //api:>>/GetNotas?notas=1&activas=1&periodo=25&empresa=1
    public function GetNotas(Request $request){
        $notas      = $request->input('notas');
        $prefactura = $request->input('prefactura');
        $activas    = $request->input('activas');
        $periodo    = $request->input('periodo');
        $empresa    = $request->input('empresa');
        $query = Ventas::query()
        ->where('periodo_id', $periodo)
        ->where('id_empresa', $empresa)
        ->when($notas, function ($query) {
            $query->where(function ($query) {
                $query->where('idtipodoc', 4)
                    ->orWhere('idtipodoc', 3);
            });
        })
        ->when($prefactura, function ($query) {
            $query->where(function ($query) {
                $query->where('idtipodoc', 1);
            });
        })
        ->when($activas, function ($query) {
            $query->where(function ($query) {
                $query->where('est_ven_codigo', '<>', 3);
            });
        })
        ->get();
        return $query;
    }

    //api:get/metodosGetVentas
    public function metodosGetVentas(Request $request){
        if($request->getMovimientosNotas)    { return $this->getMovimientosNotas($request); }
        if($request->getProductoDetalleVenta)   { return $this->getProductoDetalleVenta($request); }
    }
    //api:get/metodosGetVentas?getMovimientosNotas=1
    public function getMovimientosNotas($request){
        $f_venta_historico_notas_cambiadas = DB::table('f_venta_historico_notas_cambiadas as h')
            ->where('id_periodo', $request->get('periodo_id'))
            ->leftJoin('usuario as u', 'h.user_created', '=', 'u.idusuario')
            //left join con la empresa
            ->leftJoin('empresas as e', 'h.id_empresa', '=', 'e.id')
            //left join con el periodo
            ->leftJoin('periodoescolar as p', 'h.id_periodo', '=', 'p.idperiodoescolar')
            ->select(
                'h.*',
                'e.descripcion_corta',
                'p.periodoescolar',
                DB::raw("COALESCE(CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, '')), 'sin nombre') as editor")
            )
            ->get();
        return $f_venta_historico_notas_cambiadas;
    }

    //api:get/metodosGetVentas?getComboDetalleVenta=1&id_empresa=1&ven_codigo=PF-S24-FR-0000192
    public function getProductoDetalleVenta(Request $request) {
        // Recibir los parámetros
        $id_empresa = $request->input('id_empresa');
        $ven_codigo = $request->input('ven_codigo');

        // Validación básica
        if (!$id_empresa || !$ven_codigo) {
            return response()->json(['status' => '0', 'message' => 'Faltan parámetros'], 200);
        }

        // Realizar la consulta
        $query = DetalleVentas::where('ven_codigo', $ven_codigo)
            ->where('id_empresa', $id_empresa)
            ->get(); // Usar 'get()' si esperas varios resultados

        // Retornar la respuesta
        return response()->json($query); // Para devolver una respuesta JSON
    }

    public function anularPedido(Request $request)
    {
        $ven_codigo = $request->input('ven_codigo');
        $id_empresa = $request->input('id_empresa');

        DB::beginTransaction();

        try {
            $venta = DB::table('f_venta_agrupado')
                ->where('id_factura', $ven_codigo)
                ->where('id_empresa', $id_empresa)
                ->first();

            if (!$venta) {
                throw new \Exception('Pedido no encontrado o no se pudo anular.');
            }

            if ($venta->est_ven_codigo == 1) {
                return response()->json(['success' => false, 'message' => 'Este pedido ya está anulado.'], 400);
            }

            $updated = DB::table('f_venta_agrupado')
                ->where('id_factura', $ven_codigo)
                ->where('id_empresa', $id_empresa)
                ->update(['est_ven_codigo' => 1]);

            if (!$updated) {
                throw new \Exception('No se pudo actualizar el estado del pedido.');
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Pedido anulado correctamente.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }
    //api:post/metodosPostVentas
    public function metodosPostVentas(Request $request){
        if($request->importPrefacturas)         { return $this->importPrefacturas($request); }
        if($request->importPrefacturasTablaSon) { return $this->importPrefacturasTablaSon($request); }
    }
    //api:post/metodosPostVentas?importPrefacturas=1
    public function importPrefacturas($request)
    {
        try {
            // Validación del request
            $validatedData              = $this->validate($request, [
                'data_codigos'          => ['required', 'json'],
                'id_usuario'            => ['required', 'integer'],
                'ifPermitirReemplazar'  => ['required', 'boolean'],
            ]);

            DB::beginTransaction();

            $data                   = json_decode($validatedData['data_codigos']);
            $id_usuario             = $validatedData['id_usuario'];
            $ifPermitirReemplazar   = (bool) $validatedData['ifPermitirReemplazar'];
            $cambiados              = 0;
            $codigosNoCambiados     = [];

            foreach ($data as $item) {
                $codigo = CodigosLibros::where('codigo', $item->codigo)->first();

                if (!$codigo) {
                    $codigosNoCambiados[] = [
                        "codigo" => $item->codigo,
                        "mensaje" => "Código no existe",
                    ];
                    continue;
                }

                $validate = $ifPermitirReemplazar || is_null($codigo->codigo_proforma) || $codigo->codigo_proforma == $item->documento;

                if ($validate) {
                    $comentario = "Se agregó el documento {$item->documento}";
                    $this->actualizarCodigo($codigo, $item, $id_usuario, $comentario);
                    $cambiados++;
                } else {
                    $codigosNoCambiados[] = [
                        "codigo" => $item->codigo,
                        "mensaje" => "Ya existe una pre factura {$codigo->codigo_proforma} en la pre factura {$item->documento}",
                    ];
                }
            }

            DB::commit();

            return response()->json([
                "error" => false,
                "cambiados" => $cambiados,
                "codigosNoCambiados" => $codigosNoCambiados,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "error" => true,
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    private function actualizarCodigo($codigo, $item, $id_usuario, $comentario)
    {
        $venta_estado       = $codigo->venta_estado;
        if($venta_estado == 2){
            $bc_institucion = $codigo->venta_lista_institucion;
        }else{
            $bc_institucion = $codigo->bc_institucion;
        }
        $bc_periodo     = $codigo->bc_periodo;

        // Si existe codigo_union, buscar y registrar en histórico
        if ($codigo->codigo_union) {
            //actualizar codigo de union
            CodigosLibros::where('codigo', $codigo->codigo_union)->update([
                'codigo_proforma'  => $item->documento,
                'proforma_empresa' => $item->id_empresa,
            ]);
            $codigoUnion = CodigosLibros::where('codigo', $codigo->codigo_union)->first();
            if ($codigoUnion) {
                $this->GuardarEnHistorico(
                    0,
                    $bc_institucion,
                    $bc_periodo,
                    $codigo->codigo_union,
                    $id_usuario,
                    $comentario,
                    $codigoUnion,
                    json_encode($codigoUnion->getAttributes()), // Guardar todos los atributos del modelo
                    null,
                    null
                );
            }
        }

        // Actualizar el modelo actual
        CodigosLibros::where('codigo', $item->codigo)->update([
            'codigo_proforma'  => $item->documento,
            'proforma_empresa' => $item->id_empresa,
        ]);

        // Registrar en histórico
        $this->GuardarEnHistorico(
            0,
            $bc_institucion,
            $bc_periodo,
            $item->codigo,
            $id_usuario,
            $comentario,
            $codigo,
            json_encode($codigo->getAttributes()), // Guardar todos los atributos del modelo
            null,
            null
        );
    }

    //api:post/metodosPostVentas?importPrefacturasTablaSon=1
    public function importPrefacturasTablaSon(Request $request)
    {
        try {
            // Validación del request
            $validatedData = $this->validate($request, [
                'data_codigos'   => 'required|json',
                'id_usuario'     => 'required|integer',
            ]);
            DB::beginTransaction();

            $data               = json_decode($validatedData['data_codigos']);
            $id_usuario         = $validatedData['id_usuario'];
            $cambiados          = 0;
            $codigosNoCambiados = [];

            foreach ($data as $item) {
                $codigo = CodigosLibrosDevolucionSon::where('codigo', $item->codigo)->where('estado','0')->first();
                if (!$codigo) {
                    $codigosNoCambiados[] = [
                        "codigo"  => $item->codigo,
                        "mensaje" => "Código no existe o no se encuentra en estado creado",
                    ];
                    continue;
                }
                $estado            = $codigo->estado;
                //codigo no esta creado
                if($estado != '0'){
                    $codigosNoCambiados[] = [
                        "codigo"  => $item->codigo,
                        "mensaje" => "El código ya está no creado",
                    ];
                    continue;
                }
                //si el documento anterior es nulo o es al documento actual guardo el codigo
                if (is_null($codigo->documento) || $codigo->documento == $item->documento) {
                    $comentario ="Se agrego la documento $item->documento";
                    $this->actualizarCodigoTablaSon($codigo, $item, $id_usuario, $comentario);
                    $cambiados++;
                } else {
                    $codigosNoCambiados[] = [
                        "codigo"  => $item->codigo,
                        "mensaje" => "Ya existe una pre factura {$codigo->documento} en la pre factura {$item->documento}",
                    ];
                }
            }

            DB::commit();

            return [
                "cambiados"          => $cambiados,
                "codigosNoCambiados" => $codigosNoCambiados,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["error" => true, "message" => $e->getMessage()], 500);
        }
    }

    private function actualizarCodigoTablaSon($codigo, $item, $id_usuario, $comentario)
    {
        $bc_institucion = $codigo->id_cliente;
        $bc_periodo     = $codigo->id_periodo;

        // Actualizar el modelo actual
        CodigosLibrosDevolucionSon::where('codigo', $item->codigo)->update([
            'documento'       => $item->documento,
            'id_empresa'      => $item->id_empresa,
        ]);

        // Registrar en histórico
        $this->GuardarEnHistoricoTablaSon(
            0,
            $bc_institucion,
            $bc_periodo,
            $item->codigo,
            $id_usuario,
            $comentario,
            $codigo,
            json_encode($codigo->getAttributes()), // Guardar todos los atributos del modelo
            null,
            null
        );
    }

    public function cambioPrefacturasANota(Request $request)
    {
        // Recibimos los datos desde el frontend
        $id_ins_depacho = $request->id_ins_depacho;
        $empresa = $request->id_empresa;
        $data_detalle = $request->data_detalle;
        $tipoVenta = $request->tipoVenta; // Tipo de venta: 1 o 2
        $nuevoVenCodigo = $request->ven_codigo; // Nuevo código de venta
        $data_documentos = $request->documentos;
        $codigos_detalles = $request->codigosVentas;

        // Si los detalles vienen como cadena JSON, decodificarlo
        if (is_string($data_detalle)) {
            $data_detalle = json_decode($data_detalle, true);
        }

        // Si los detalles vienen como cadena JSON, decodificarlo
        if (is_string($data_documentos)) {
            $data_documentos = json_decode($data_documentos, true);
        }

        if (is_string($codigos_detalles)) {
            $codigos_detalles = json_decode($codigos_detalles, true);
        }

        // Validación básica de los datos
        if (!$id_ins_depacho || empty($data_detalle)) {
            return response()->json(['message' => 'items inválidos.']);
        }

        $resultados = [];

        // Inicializamos dos arrays para los códigos
        $codigos_estado_1 = [];
        $codigos_estado_0 = [];

        // Recorremos el array y separamos los elementos
        foreach ($codigos_detalles as $codigo) {
            if ($codigo['estado_liquidacion'] == '1') {
                $codigos_estado_1[] = $codigo; // Código con estado_liquidacion igual a 1
            } else {
                $codigos_estado_0[] = $codigo; // Código con estado_liquidacion diferente de 1
            }
        }

        // Iniciar la transacción
        DB::beginTransaction();

        try {
            // Crear un nuevo documento de venta con los valores que envía el frontend
            $nuevoDocumento = [
                'ven_codigo' => $nuevoVenCodigo, // Nuevo código de venta
                'id_empresa' => $empresa,
                'ven_desc_por' => $request->ven_desc_por, // Descuento porcentaje recibido
                'ven_iva_por' => $request->ven_iva_por, // IVA porcentaje recibido
                'ven_descuento' => $request->ven_descuento, // Descuento recibido
                'ven_iva' => $request->ven_iva, // IVA recibido
                'ven_transporte' => $request->ven_transporte, // Transporte recibido
                'ven_valor' => $request->ven_valor, // Valor total recibido
                'ven_subtotal' => $request->ven_subtotal, // Subtotal recibido
                'institucion_id' => $id_ins_depacho,
                'periodo_id' => $request->periodo_id, // Periodo recibido
                'ven_cliente' => $request->ven_cliente, // Cliente recibido
                'clientesidPerseo' => $request->clientesidPerseo, // Cliente de Perseo recibido
                'ven_fecha' => now(), // Fecha actual
                'user_created' => $request->user_created, // Usuario que crea
                'tip_ven_codigo' => $tipoVenta, // Tipo de venta (1 o 2)
                'ven_tipo_inst' => $tipoVenta == 1 ? 'V' : 'L', // Tipo de venta según el valor recibido
                'est_ven_codigo' => 13, // Tipo de venta según el valor recibido
                'idtipodoc'=> $tipoVenta == 1 ? 4 : 3,
                'ruc_cliente'=> $request->ruc_cliente,
            ];

            // Insertar el nuevo documento en f_venta
            DB::table('f_venta')->insert($nuevoDocumento);

            // Iteramos sobre los detalles de la venta
            foreach ($data_detalle as $item) {
                $codigo = $item['pro_codigo'];
                $cantidadSolicitada = $item['cantidad_real_facturar'];
                $cantidadRestante = $cantidadSolicitada;
                $cantidadConvertir = 0;
                $devolucionesPorDocumento = [];

                // Obtener los documentos asociados a la institución y empresa
                $documentos = DB::table('f_venta as fv')
                    ->where('fv.institucion_id', $id_ins_depacho)
                    ->where('fv.id_empresa', $empresa)
                    ->where('fv.est_ven_codigo', '<>', 3) // Excluir documentos anulados
                    ->where('fv.idtipodoc', 1) // solo afectar a pre-facturas
                    ->select('fv.ven_codigo', 'fv.id_empresa')
                    ->get();

                foreach ($documentos as $documento) {
                    // Obtener los detalles de los documentos
                    $detallesDocumentos = DB::table('f_detalle_venta as fdv')
                        ->join('f_venta as fv', function ($join) {
                            $join->on('fv.ven_codigo', '=', 'fdv.ven_codigo')
                                ->on('fv.id_empresa', '=', 'fdv.id_empresa');
                        })
                        ->where('fv.est_ven_codigo', '<>', 3)
                        ->where('fdv.ven_codigo', $documento->ven_codigo)
                        ->where('fdv.id_empresa', $empresa)
                        ->select('fdv.det_ven_codigo', 'fdv.pro_codigo', 'fdv.det_ven_cantidad', 'fdv.det_ven_dev', 'fdv.det_ven_valor_u')
                        ->orderBy('fdv.det_ven_cantidad', 'desc')
                        ->get();

                    foreach ($detallesDocumentos as $detalle) {
                        if ($detalle->pro_codigo == $codigo) {
                            $cantidadDisponible = $detalle->det_ven_cantidad - $detalle->det_ven_dev;
                            $idDetalle = $detalle->det_ven_codigo;

                            if ($cantidadDisponible > 0) {
                                $cantidadDevolucion = min($cantidadRestante, $cantidadDisponible);
                                $cantidadRestante -= $cantidadDevolucion;
                                $cantidadConvertir += $cantidadDevolucion;

                                $devolucionesPorDocumento[] = [
                                    'ven_codigo' => $documento->ven_codigo,
                                    'empresa' => $documento->id_empresa,
                                    'DetalleId' => $idDetalle,
                                    'cantidadConvertir' => $cantidadDevolucion,
                                    'cantidadDisponible' => $cantidadDisponible,
                                ];

                                // Si ya se cubrió la cantidad solicitada, salimos del ciclo
                                if ($cantidadRestante <= 0) {
                                    break 2;
                                }
                            }
                        }
                    }

                }

                $resultados[] = [
                    'codigo' => $codigo,
                    'cantidadSolicitada' => $cantidadSolicitada,
                    'cantidadConvertir' => $cantidadConvertir,
                    'cantidadPendiente' => $cantidadRestante,
                    'detallesConvertir' => $devolucionesPorDocumento,
                ];



                // Actualizar los detalles de cada documento convertido a nota
                foreach ($devolucionesPorDocumento as $devolucion) {
                    // Crear un nuevo detalle de venta para el nuevo documento
                    DB::table('f_detalle_venta')->insert([
                        'ven_codigo' => $nuevoVenCodigo,
                        'id_empresa' => $devolucion['empresa'],
                        'pro_codigo' => $codigo,
                        'det_ven_cantidad' => $devolucion['cantidadConvertir'], // Cantidad convertida
                        'det_ven_valor_u' => $detalle->det_ven_valor_u, // Valor unitario
                        'det_cant_intercambio' => $devolucion['cantidadConvertir'],
                        'doc_intercambio' => $devolucion['ven_codigo'],
                    ]);

                    // Actualizar la cantidad convertida en los detalles del documento original
                    DB::table('f_detalle_venta')
                        ->where('det_ven_codigo', $devolucion['DetalleId'])
                        ->update([
                            'det_cant_intercambio' => DB::raw('det_ven_cantidad'),
                            'det_ven_cantidad' => DB::raw('det_ven_cantidad - ' . $devolucion['cantidadConvertir']),
                            'doc_intercambio' => $nuevoVenCodigo, // Asignar el nuevo ven_codigo
                        ]);

                    // Calcular el nuevo subtotal de la venta
                    $nuevoSubtotal = DB::table('f_detalle_venta')
                        ->where('ven_codigo', $devolucion['ven_codigo'])
                        ->where('id_empresa', $devolucion['empresa'])
                        ->sum(DB::raw('det_ven_cantidad * det_ven_valor_u'));

                    // Obtener el porcentaje de descuento
                    $descuentoPorcentaje = DB::table('f_venta')
                        ->where('ven_codigo', $devolucion['ven_codigo'])
                        ->where('id_empresa', $devolucion['empresa'])
                        ->value('ven_desc_por'); // Porcentaje de descuento

                    // Calcular el valor del descuento
                    $valorDescuento = ($descuentoPorcentaje / 100) * $nuevoSubtotal;

                    // Calcular el nuevo valor total
                    $nuevoTotal = $nuevoSubtotal - $valorDescuento;

                    // Actualizar f_venta con el nuevo subtotal, descuento y total
                    DB::table('f_venta')
                        ->where('ven_codigo', $devolucion['ven_codigo'])
                        ->where('id_empresa', $devolucion['empresa'])
                        ->update([
                            'ven_subtotal' => $nuevoSubtotal,
                            'ven_descuento' => $valorDescuento,
                            'ven_valor' => $nuevoTotal,
                            'user_intercambio' => $request->user_created,
                            'fecha_intercambio' => now(),
                            'doc_intercambio' => $nuevoVenCodigo,
                        ]);

                    // Obtener el código del producto y la empresa
                    $empresaProducto = $request->id_empresa;
                    $cantProductoCambio = $devolucion['cantidadConvertir'];

                    // Verifica qué empresa es y actualiza los campos correspondientes
                    if ($empresaProducto == 1) {
                        // Para la empresa 1, actualizamos 'pro_stock' y 'pro_deposito'
                        DB::table('1_4_cal_producto')
                            ->where('pro_codigo', $codigo)
                            ->update([
                                'pro_stock' => DB::raw('pro_stock - ' . $devolucion['cantidadConvertir']),
                                'pro_deposito' => DB::raw('pro_deposito + ' . $devolucion['cantidadConvertir']),
                            ]);
                    } elseif ($empresaProducto == 3) {
                        // Para la empresa 3, actualizamos 'pro_stockCalmed' y 'pro_depositoCalmed'
                        DB::table('1_4_cal_producto')
                            ->where('pro_codigo', $codigo)
                            ->update([
                                'pro_stockCalmed' => DB::raw('pro_stockCalmed - ' . $devolucion['cantidadConvertir']),
                                'pro_depositoCalmed' => DB::raw('pro_depositoCalmed + ' . $devolucion['cantidadConvertir']),
                            ]);
                    }

                }


            }

           // Preparar las consultas de actualización en lugar de hacerlo dentro del ciclo
            // $updates = [];
            // foreach ($documentos as $documento) {
            //     $updates[] = [
            //         'codigo_proforma' => $documento->ven_codigo,
            //         'proforma_empresa' => $documento->id_empresa,
            //         'nuevo_codigo_proforma' => $nuevoVenCodigo
            //     ];
            // }

            // // Realizar una sola consulta de actualización en 'codigoslibros' utilizando el modelo Eloquent
            // if (count($updates) > 0) {
            //     $codigoProformas = array_column($updates, 'codigo_proforma');
            //     $proformaEmpresas = array_column($updates, 'proforma_empresa');

            //     // Obtener todos los códigos que necesitas actualizar
            //     $codigos = CodigosLibros::whereIn('codigo_proforma', $codigoProformas)
            //         ->where('estado_liquidacion', '<>', 3)
            //         ->whereIn('proforma_empresa', $proformaEmpresas)
            //         ->pluck('codigo')
            //         ->toArray();

            //     // Si hay códigos que coinciden, actualizar en la tabla 'codigoslibros_devolucion_son' de forma masiva
            //     if (!empty($codigos)) {
            //         foreach ($codigos as $itemsCod) {
            //             $codigos_son = DB::table('codigoslibros_devolucion_son')
            //             ->where('codigo',$itemsCod)
            //             ->where('estado',0)
            //             ->update(['documento' => $nuevoVenCodigo]);
            //         }
            //     }

            //     // Realizar la actualización masiva en 'codigoslibros'
            //     CodigosLibros::whereIn('codigo_proforma', $codigoProformas)
            //         ->where('estado_liquidacion', '<>', 3)
            //         ->whereIn('proforma_empresa', $proformaEmpresas)
            //         ->update(['codigo_proforma' => $nuevoVenCodigo]);
            // }

            // foreach ($codigos_estado_1 as $codigo_detalle) {
            //     $codigos_son = DB::table('codigoslibros_devolucion_son')
            //         ->where('codigo', $codigo_detalle['codigo'])
            //         ->where('estado', 0)
            //         ->update(['documento' => $nuevoVenCodigo]);
            // }
            // foreach ($codigos_detalles as $codigo_detalle) {
            //     $codigos_son = CodigosLibros::whereIn('codigo_proforma', $codigo_detalle['codigo_proforma'])
            //     ->where('estado_liquidacion', '<>', 3)
            //     ->whereIn('proforma_empresa', $empresa)
            //     ->update(['codigo_proforma' => $nuevoVenCodigo]);
            // }

            // Arrays para almacenar los códigos que se actualizaron y no se actualizaron
            $actualizados = [];
            $no_actualizados = [];

            // Verificamos si hay detalles de códigos
            if (!empty($codigos_detalles)) {
                // Recorremos cada código detalle
                foreach ($codigos_detalles as $codigo_detalle) {
                    if (isset($codigo_detalle['codigo']) && isset($codigo_detalle['codigo_proforma'])) {
                        // Intentamos hacer la actualización individualmente
                        $updated = DB::table('codigoslibros')
                            ->where('codigo', $codigo_detalle['codigo'])
                            ->where('codigo_proforma', $codigo_detalle['codigo_proforma'])
                            ->where('estado_liquidacion', '<>', 3)
                            ->where('proforma_empresa', $empresa)
                            ->update(['codigo_proforma' => $nuevoVenCodigo]);

                        // Verificamos si la actualización fue exitosa
                        if ($updated) {
                            // Si se actualizó, agregamos al array de actualizados
                            $actualizados[] = $codigo_detalle['codigo'];

                            // Llamamos al método para guardar en el histórico (si lo necesitas)
                            $mensajeHistorico = 'Se movió de la prefactura ' . $codigo_detalle['codigo_proforma'] . ' a la nota ' . $nuevoVenCodigo;
                            $this->GuardarEnHistorico( 0, $id_ins_depacho, $request->periodo_id, $codigo_detalle['codigo'], $request->user_created, $mensajeHistorico, null, null, null, null);
                        } else {
                            // Si no se actualizó, agregamos al array de no actualizados
                            $no_actualizados[] = $codigo_detalle['codigo'];
                        }
                    }
                }

                // Retornamos los resultados
                $Datos =[
                    'actualizados' => $actualizados,
                    'no_actualizados' => $no_actualizados
                ];
            }

            // ACTUALIZAR SECUENCIAL
            if($empresa==1){
                if($tipoVenta==1){

                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from  f_tipo_documento where tdo_id=4");
                }else if ($tipoVenta==2){
                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from  f_tipo_documento where tdo_id=3");
                }
            }else if ($empresa==3){
                if($tipoVenta==1){

                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from  f_tipo_documento where tdo_id=4");
                }else if ($tipoVenta==2){
                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from  f_tipo_documento where tdo_id=3");
                }
            }

            $id=$query1[0]->id;
            $codi=$query1[0]->cod;
            $co=(int)$codi+1;
            $tipo_doc = f_tipo_documento::findOrFail($id);
            if($empresa==1){
                $tipo_doc->tdo_secuencial_Prolipa = $co;
            }else if ($empresa==3){
                $tipo_doc->tdo_secuencial_calmed = $co;
            }
            $tipo_doc->save();

            // Si todo ha ido bien, hacemos commit
            DB::commit();

            return response()->json(['message' => 'Prefacturas convertidas a notas correctamente.','status'=>'0', 'codigos' =>$Datos]);

        } catch (\Exception $e) {
            // Si ocurre un error, hacemos rollback
            DB::rollBack();
            return response()->json(['message' => 'Hubo un error al procesar las prefacturas: ' . $e->getMessage(),'status'=>'1','line'=>$e->getLine()]);
        }
    }



    public function cambioDesfasANotaCredito(Request $request)
    {
        // Recibimos los datos desde el frontend
        $id_ins_depacho = $request->id_ins_depacho;
        $empresa = $request->id_empresa;
        $data_detalle = $request->data_detalle;
        $tipoVenta = $request->tipoVenta; // Tipo de venta: 1 o 2
        $nuevoVenCodigo = $request->ven_codigo; // Nuevo código de venta

        // Si los detalles vienen como cadena JSON, decodificarlo
        if (is_string($data_detalle)) {
            $data_detalle = json_decode($data_detalle, true);
        }

        // Validación básica de los datos
        if (!$id_ins_depacho || empty($data_detalle)) {
            return response()->json(['message' => 'items inválidos.']);
        }

        // Iniciar la transacción
        DB::beginTransaction();

        try {
            // Crear un nuevo documento de venta con los valores que envía el frontend
            $nuevoDocumento = [
                'ven_codigo' => $nuevoVenCodigo, // Nuevo código de venta
                'id_empresa' => $empresa,
                'ven_desc_por' => $request->ven_desc_por, // Descuento porcentaje recibido
                'ven_iva_por' => $request->ven_iva_por, // IVA porcentaje recibido
                'ven_descuento' => $request->ven_descuento, // Descuento recibido
                'ven_iva' => $request->ven_iva, // IVA recibido
                'ven_transporte' => $request->ven_transporte, // Transporte recibido
                'ven_valor' => $request->ven_valor, // Valor total recibido
                'ven_subtotal' => $request->ven_subtotal, // Subtotal recibido
                'institucion_id' => $id_ins_depacho,
                'periodo_id' => $request->periodo_id, // Periodo recibido
                'ven_cliente' => $request->ven_cliente, // Cliente recibido
                'clientesidPerseo' => $request->clientesidPerseo, // Cliente de Perseo recibido
                'ven_fecha' => now(), // Fecha actual
                'user_created' => $request->user_created, // Usuario que crea
                'tip_ven_codigo' => $tipoVenta, // Tipo de venta (1 o 2)
                'ven_tipo_inst' => $tipoVenta == 1 ? 'V' : 'L', // Tipo de venta según el valor recibido
                'est_ven_codigo' => 14, // Tipo de venta según el valor recibido
                'idtipodoc' => 16,
                'ruc_cliente' => $request->ruc_cliente, // RUC del cliente
                'fecha_notaCredito' => $request->ven_fecha,
            ];

            // Insertar el nuevo documento en f_venta
            DB::table('f_venta')->insert($nuevoDocumento);

            // Iteramos sobre los detalles de la venta
            foreach ($data_detalle as $item) {
                $codigo = $item['pro_codigo'];
                $cantidad = $item['cantidad_real_facturar'];
                $precio = $item['precio'];

                DB::table('f_detalle_venta')->insert([
                    'ven_codigo' => $nuevoVenCodigo,
                    'id_empresa' => $empresa,
                    'pro_codigo' => $codigo,
                    'det_ven_cantidad' => $cantidad,
                    'det_ven_valor_u' => $precio,
                ]);
            }

            // ACTUALIZAR SECUENCIAL
            if ($empresa == 1) {
                $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from  f_tipo_documento where tdo_id=16");
            } else if ($empresa == 3) {
                $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from  f_tipo_documento where tdo_id=16");
            }

            $id = $query1[0]->id;
            $codi = $query1[0]->cod;
            $co = (int)$codi + 1;
            $tipo_doc = f_tipo_documento::findOrFail($id);
            if ($empresa == 1) {
                $tipo_doc->tdo_secuencial_Prolipa = $co;
            } else if ($empresa == 3) {
                $tipo_doc->tdo_secuencial_calmed = $co;
            }
            $tipo_doc->save();

            // Si todo ha ido bien, hacemos commit
            DB::commit();

            return response()->json(['message' => 'Prefacturas convertidas a notas correctamente.', 'status' => '0']);

        } catch (\Exception $e) {
            // Si ocurre un error, hacemos rollback
            DB::rollBack();
            return response()->json(['message' => 'Hubo un error al procesar las prefacturas: ' . $e->getMessage(), 'status' => '1', 'line' => $e->getLine()]);
        }
    }

    public function getCantidadNotaCredito(Request $request)
    {
        // Validar los parámetros de entrada
        $request->validate([
            'institucion' => 'required|integer',
            'empresa' => 'required|integer',
            'periodo' => 'required|integer',
        ]);

        // Ejecutar la consulta
        $resultados = DB::table('f_venta as fva')
            ->join('f_detalle_venta as fdva', function ($join) {
                $join->on('fdva.ven_codigo', '=', 'fva.ven_codigo')
                    ->on('fdva.id_empresa', '=', 'fva.id_empresa');
            })
            ->select('fdva.pro_codigo', DB::raw('SUM(fdva.det_ven_cantidad) as det_ven_cantidad'))
            ->where('fva.institucion_id', $request->institucion)
            ->where('fva.id_empresa', $request->empresa)
            ->where('fva.periodo_id', $request->periodo)
            ->where('fva.est_ven_codigo', '<>', 3)
            ->where('fva.idtipodoc',16)
            ->groupBy('fdva.pro_codigo')
            ->get();

        // Devolver los resultados
        return response()->json($resultados);
    }

    public function getNotasIntercambio(Request $request)
    {
        $institucion = $request->input('institucion');
        $periodo = $request->input('periodo');

        // Ejecutar la primera consulta y obtener las notas
        $notas = DB::select("SELECT
                CASE
                    WHEN COUNT(DISTINCT f.doc_intercambio) = 1 THEN
                        MAX(f.doc_intercambio)
                    ELSE
                        GROUP_CONCAT(DISTINCT
                            CASE WHEN f.doc_intercambio <> '' THEN f.doc_intercambio END
                            ORDER BY f.doc_intercambio SEPARATOR ', ')
                END AS intercambio_doc,
                COUNT(f.doc_intercambio) AS total_intercambio
            FROM f_detalle_venta f
            LEFT JOIN f_venta fv ON fv.ven_codigo = f.ven_codigo AND fv.id_empresa = f.id_empresa
            WHERE fv.institucion_id = ?
            AND fv.periodo_id = ?
            AND fv.idtipodoc = 1
            AND f.doc_intercambio IS NOT NULL
            GROUP BY fv.institucion_id, fv.periodo_id, fv.idtipodoc", [$institucion, $periodo]);

        if (count($notas) === 0) {
            $notas[] = (object)[
                'intercambio_doc' => [],
                'total_intercambio' => 0
            ];
        }

        // Transformar el campo intercambio_doc en un arreglo de documentos
        foreach ($notas as $key => $item) {
            // Si intercambio_doc no está vacío
            if (!empty($item->intercambio_doc)) {
                // Convertir la cadena de documentos en un arreglo
                $item->intercambio_doc = explode(',', $item->intercambio_doc);
            }
        }

        // Devolver los datos con las notas y detalles integrados
        return response()->json([
            'notas' => $notas,
        ]);
    }

    public function getNotasCredito(Request $request)
    {
        $institucion = $request->input('institucion');
        $periodo = $request->input('periodo');

        // Ejecutar la primera consulta y obtener las notas
        $notas = DB::select("SELECT DISTINCT fv.*
            FROM f_detalle_venta f
            LEFT JOIN f_venta fv ON fv.ven_codigo = f.ven_codigo AND fv.id_empresa = f.id_empresa
            WHERE fv.institucion_id = ?
            AND fv.periodo_id = ?
            AND fv.idtipodoc = 16", [$institucion, $periodo]);

        // Devolver los datos con las notas y detalles integrados
        return $notas;
    }
    public function getNotasCLiente(Request $request)
    {
        $institucion = $request->input('institucion');
        $periodo = $request->input('periodo');

        // Ejecutar la primera consulta y obtener las notas
        $notas = DB::select("SELECT fv.*, i.nombreInstitucion
            FROM f_venta fv
            LEFT JOIN institucion i ON i.idInstitucion = fv.institucion_id
            WHERE fv.institucion_id = $institucion
            AND fv.periodo_id = $periodo
            AND (fv.est_ven_codigo = 13
            OR fv.idtipodoc = 16)");

        // Devolver los datos con las notas y detalles integrados
        return $notas;
    }

    public function Get_PREFacturaCodigos(Request $request)
    {
        // Asegurarnos de que ven_codigo y idemp sean arreglos (en caso de que vengan como cadenas)
        $venCodigo = is_array($request->ven_codigo) ? $request->ven_codigo : explode(',', $request->ven_codigo);
        $idemp = is_array($request->idemp) ? $request->idemp : explode(',', $request->idemp);

        // Consulta con whereIn
        $query = CodigosLibros::whereIn('codigo_proforma', $venCodigo)
                            ->where('estado_liquidacion', '<>', 3)
                            ->whereIn('proforma_empresa', $idemp)
                            ->select('codigo', 'codigo_union','codigo_proforma', 'proforma_empresa', 'estado_liquidacion')
                            ->get();

        return $query;
    }

    // METODOS JEYSON INICIO
    public function GetFacturasxAgrupa_SoloEnviados(Request $request){
        $identificacion = $request->input('identificacion');
        $id_empresa = $request->input('id_empresa');

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
            em.descripcion_corta as nombre,
            CONCAT(us.nombres,' ',us.apellidos) AS responsable,
            COUNT(DISTINCT dfv.pro_codigo) AS item,
            SUM(dfv.det_ven_cantidad) AS libros
        FROM
            f_venta_agrupado fv
            INNER JOIN institucion ins ON fv.institucion_id = ins.idInstitucion
            INNER JOIN usuario usa ON fv.ven_cliente = usa.idusuario
            INNER JOIN f_detalle_venta_agrupado dfv ON fv.id_factura = dfv.id_factura
            INNER JOIN empresas em ON em.id = fv.id_empresa
            INNER JOIN usuario us ON us.idusuario=fv.user_created
        WHERE dfv.id_empresa = fv.id_empresa
            AND fv.idtipodoc = 11
            AND fv.estadoPerseo = 1
            AND usa.cedula = $identificacion
            AND fv.id_empresa = $id_empresa
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
    // METODOS JEYSON FIN

}
