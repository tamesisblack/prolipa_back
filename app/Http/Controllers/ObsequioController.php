<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Institucion;
use App\Models\Obsequio;
use App\Models\ObsequioDetalle;
use App\Models\Pedidos;
use App\Traits\Pedidos\TraitPedidosGeneral;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Http;
use stdClass;
class ObsequioController extends Controller
{
    use TraitPedidosGeneral;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //listado de obsequios:(asesor)
        if($request->listadoObsequioAsesor){
            return $this->listadoObsequioAsesor($request->periodo_id);
        }
        //institucion pedido
        if($request->institucionPedido){
            return $this->institucionPedido($request->institucion_id,$request->periodo_id);
        }
        //listado obsequios gerencia
        if($request->listadoObsequioGerencia){
            return $this->listadoObsequioGerencia();
        }
         //listado obsequios factuador
         if($request->listadoObsequiofacturador){
            return $this->listadoObsequioFacturador();
        }
    }

    public function listadoObsequioAsesor($periodo_id){
        $query = DB::SELECT("SELECT o.*,
        CONCAT(u.nombres,' ',u.apellidos) as asesor, u.cedula,
        CONCAT(uf.nombres,' ',uf.apellidos) as facturador,
        i.nombreInstitucion, c.nombre AS ciudad,pe.periodoescolar as periodo
        FROM obsequios o
        LEFT JOIN usuario u ON o.asesor_id = u.idusuario
        LEFT JOIN usuario uf ON o.id_facturador = uf.idusuario
        LEFT JOIN institucion i ON o.institucion_id = i.idInstitucion
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        LEFT JOIN periodoescolar pe ON o.periodo_id = pe.idperiodoescolar
        WHERE  o.periodo_id = '$periodo_id'
        ORDER BY o.id DESC");
        return $query;
    }
    public function institucionPedido($institucion,$periodo){
        //obtener contrato de la institucion y periodo
        $query = DB::SELECT("SELECT t.*, i.maximo_porcentaje_autorizado,
        p.porcentaje_obsequio,i.verificar_obsequios
        FROM temporadas t
        LEFT JOIN institucion i ON t.idInstitucion = i.idInstitucion
        LEFT JOIN periodoescolar p ON p.idperiodoescolar = t.id_periodo
        WHERE t.idInstitucion   = ?
        AND t.id_periodo        = ?
        and t.estado = '1'
        ",[$institucion,$periodo]);
        if(count($query)  == 0){
            return ["status" => "0", "message" => "No existe un contrato asociado a está institución en este período"];
        }
        if(count($query) > 1){
            return ["status" => "0", "message" => "Existe mas de 1 un contrato con la misma institución"];
        }
        //variables
        $contrato                       = $query[0]->contrato;
        $maximo_porcentaje_autorizado   = $query[0]->maximo_porcentaje_autorizado;
        $porcentaje_obsequio            = $query[0]->porcentaje_obsequio;
        $verificar_obsequios            = $query[0]->verificar_obsequios;
        //si tiene marcado verificar en 1 verifica que tiene verificaicones el contrato
        if($verificar_obsequios == 1){
            //validar que si tiene verificaciones en prolipa
            $validate = DB::SELECT("SELECT * FROM verificaciones v
            WHERE v.contrato = '$contrato'");
            if(empty($validate)){
                return ["status" => "0", "message" => "El contrato $contrato no tiene verificaciones"];
            }
        }
        try {
            //get total gastado
            $query2 = DB::SELECT("SELECT
                SUM(o.valor_total) AS total_gastado
                FROM obsequios o
                WHERE o.institucion_id  = '$institucion'
                AND o.periodo_id        = '$periodo'
                AND (o.estado = '4' OR o.estado = '5' OR o.estado = '6')
            ");
            $total_gastado = 0;
            if(count($query2) > 0){
                $total_gastado = $query2[0]->total_gastado;
            }
            //VARIABLES
            $ven_valor      = 0;
            $ven_anticipo   = 0;
            $ven_descuento  = 0;
            $dataFinally    = [];
            //si es costa 23 y sierra 23 traigo de prolipa
            if($periodo > 21){
                 //TRAER DETALLE CONTRATO
                $getPedido          = Pedidos::Where('contrato_generado',$contrato)->get();
                if(count($getPedido) == 0){
                    return ["status" => "0","message" => "No se encontro el contrato en pedidos"];
                }
                $pedido             = $this->getPedido(0,$getPedido[0]->id_pedido);
                $ven_descuento      = $pedido[0]->descuento;
                $ven_anticipo       = $pedido[0]->anticipo_aprobado;
                $verificaciones     = $this->getVerificaciones($contrato);
                foreach($verificaciones as $key => $item){
                    $ven_valor      = $ven_valor + $item->venta_real;
                }
            }
            //si no traigo de la tabla facturacion
            else{
                $getDetailsContrato = DB::SELECT("SELECT *
                FROM 1_4_venta
                WHERE ven_codigo = ?
                ",[$contrato]);
                $ven_valor      = $getDetailsContrato[0]->ven_valor;
                $ven_anticipo   = $getDetailsContrato[0]->ven_anticipo;
                $ven_descuento  = $getDetailsContrato[0]->ven_descuento;
            }
            //setear array
            $obj = new stdClass();
            $obj->veN_CODIGO                    = $contrato;
            $obj->veN_VALOR                     = $ven_valor;
            $obj->veN_ANTICIPO                  = $ven_anticipo;
            $obj->veN_DESCUENTO                 = $ven_descuento;
            $obj->total_gastado                 = $total_gastado == null ? 0 : $total_gastado;
            $obj->maximo_porcentaje_autorizado  = $maximo_porcentaje_autorizado;
            $obj->porcentaje_obsequio           = $porcentaje_obsequio;
            array_push($dataFinally,$obj);
            return $dataFinally;
        } catch (\Exception  $ex) {
        return ["status" => "0","message" => "Hubo problemas con la conexión al servidor".$ex];
        }
        return $query;
    }
    public function listadoObsequioGerencia(){
        $query = DB::SELECT("SELECT o.*,
        CONCAT(u.nombres,' ',u.apellidos) as asesor, u.cedula,
        CONCAT(uf.nombres,' ',uf.apellidos) as facturador,
        i.nombreInstitucion, c.nombre AS ciudad,pe.periodoescolar as periodo,
        pe.obsequios_gerencia,pe.maximo_porcentaje_autorizado
        FROM obsequios o
        LEFT JOIN usuario u ON o.asesor_id = u.idusuario
        LEFT JOIN usuario uf ON o.id_facturador = uf.idusuario
        LEFT JOIN institucion i ON o.institucion_id = i.idInstitucion
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        LEFT JOIN periodoescolar pe ON o.periodo_id = pe.idperiodoescolar
        WHERE (o.estado = '4' OR o.estado = '5' OR o.estado = '6')
        -- WHERE  pe.estado = '1'
        ORDER BY o.id DESC
        ");
        return $query;
    }
    public function listadoObsequioFacturador(){
        $query = DB::SELECT("SELECT o.*,
        CONCAT(u.nombres,' ',u.apellidos) as asesor, u.cedula,
        CONCAT(uf.nombres,' ',uf.apellidos) as facturador,
        i.nombreInstitucion, c.nombre AS ciudad,pe.periodoescolar as periodo,
        pe.pedido_facturacion,pe.maximo_porcentaje_autorizado
        FROM obsequios o
        LEFT JOIN usuario u ON o.asesor_id = u.idusuario
        LEFT JOIN usuario uf ON o.id_facturador = uf.idusuario
        LEFT JOIN institucion i ON o.institucion_id = i.idInstitucion
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        LEFT JOIN periodoescolar pe ON o.periodo_id = pe.idperiodoescolar
        ORDER BY o.id DESC");
        return $query;
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        //obsequio
        if($request->id > 0){
            $ob = Obsequio::findOrFail($request->id);
            //validar se puede editar siempre que este abierto
            if( $ob->estado > 1 ){
                return ["status" => "0", "message" => "El pedido de obsequios ya no se puede editar"];
            }
        }else{
            //OBTENER EL id del asesor
            $institucion            = Institucion::findOrFail($request->institucion_id);
            $asesor_id              = $institucion->asesor_id;
            $ob = new Obsequio();
            $ob->asesor_id          = $asesor_id;
        }
        $ob->institucion_id         = $request->institucion_id;
        $ob->periodo_id             = $request->periodo_id;
        $ob->maximo_porcentaje_autorizado   = $request->maximo_porcentaje_autorizado;
        $ob->save();
        //detalle obsequio
        //variables
        $obsequios = json_decode($request->data_obsequios);
        foreach($obsequios as $key => $item){
            if($item->id > 0){
                $obd = ObsequioDetalle::findOrFail($item->id);
            }else{
                $obd = new ObsequioDetalle();
                $obd->obsequios_id   = $ob->id;
            }
            $obd->cantidad       = $item->cantidad;
            $obd->descripcion    = $item->descripcion;
            $obd->especificacion = $item->especificacion;
            $obd->save();
        }
        if($ob){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "0", "message" => "No se puedo guardar"];
        }
    }
    //api:post/aprobarPedidoObsequio
    public function changeEstadoObsequio(Request $request){
        $fechaActual          = date("Y-m-d H:i:s");
        $ob = Obsequio::findOrFail($request->id);
        $ob->estado = $request->estado;
        //=====FACTURADOR APRUEBA======
        if($request->estado == 3){
            $ob->id_facturador              = $request->id_facturador;
            if( $request->observacion_facturador == null ||  $request->observacion_facturador == "null"){
                $ob->observacion_facturador     = null;
            }else{
                $ob->observacion_facturador     = $request->observacion_facturador;
            }
            $ob->fecha_facturador_aprueba       = $fechaActual;
            $ob->valor_real_actual              = $request->valor_real_actual;
            $ob->comision_escuela               = $request->comision_escuela;
            $ob->porcentaje_obsequio            = $request->porcentaje_obsequio;
            $ob->valor_obsequios                = $request->valor_obsequios;
            $ob->valor_disponible               = $request->valor_disponible;
        }
         //=====FACTURADOR NO APRUEBA======
         if($request->estado == 2){
            $ob->id_facturador              = $request->id_facturador;
            if( $request->observacion_facturador == null ||  $request->observacion_facturador == "null"){
                $ob->observacion_facturador     = null;
            }else{
                $ob->observacion_facturador     = $request->observacion_facturador;
            }
            // $ob->fecha_facturador_aprueba   = $fechaActual;
        }
        //======FIN FACTURADOR APRUEBA======
        //======GERENCIA AUTORIZA===========
        if($request->estado == 5){
            $ob->aprobado_by_gerencia            = 1;
            $ob->fecha_gerencia_aprueba          = $fechaActual;
        }
        //======FIN GERENCIA AUTORIZA=======
        $ob->save();
        if($ob){
            return ["status" => "1", "message" => "Se realizo correctamente"];
        }else{
            return ["status" => "0", "message" => "No se puedo guardar"];
        }
    }
    //api:post/metodosPostObsequios/anularPedido
    public function metodosPostObsequios(Request $request){
        if($request->anularPedido){
            return $this->anularPedido($request);
        }
    }
    public function anularPedido(Request $request){
        try{
            $ob = Obsequio::findOrFail($request->id);
            //anular si es estado 3
            if($ob->estado != 3){
                return ["status" => "0", "message" => "El pedido de obsequios no se pudo anular solo se puede anular los pedidos en estado aprobado por facturación"];
            }
            $ob->estado = 2;
            $ob->comentario_anulado = $request->comentario_anulado;
            $ob->id_facturador      = $request->user_created;
            $ob->save();
            if($ob){
                return ["status" => "1", "message" => "Se anulo correctamente"];
            }else{
                return ["status" => "0", "message" => "No se puedo anular"];
            }
        }catch(\Exception $ex){
            return ["status" => "0", "message" => "Hubo problemas con anular", "error" => "error: ".$ex];
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $query = DB::SELECT("SELECT o.*,
        CONCAT(u.nombres,' ',u.apellidos) as asesor, u.cedula,
        i.nombreInstitucion, c.nombre AS ciudad,pe.periodoescolar as periodo
        FROM obsequios o
        LEFT  JOIN usuario u ON o.asesor_id = u.idusuario
        LEFT JOIN institucion i ON o.institucion_id = i.idInstitucion
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        LEFT JOIN periodoescolar pe ON o.periodo_id = pe.idperiodoescolar
        WHERE o.id = '$id'
        ORDER BY o.id DESC
        ");
        //detalle
        $query2 = DB::SELECT("SELECT * FROM obsequios_detalle d WHERE d.obsequios_id = '$id'");
        return ["pedido" => $query,"detalle" => $query2];
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
    //api post\obsequio_eliminar
    public function obsequio_eliminar(Request $request){
        //validar se puede editar siempre que este abierto
        $ob = Obsequio::findOrFail($request->id);
        if( ($ob->estado > 1)){
            return ["status" => "0", "message" => "El pedido de obsequios ya no se puede eliminar ya se encuentra aprobado"];
        }
        DB::DELETE("DELETE FROM obsequios WHERE  id = $request->id");
        DB::DELETE("DELETE FROM obsequios_detalle WHERE obsequios_id = $request->id");
    }
    public function deleteDetalleObsequio(Request $request){
        DB::DELETE("DELETE FROM obsequios_detalle WHERE id = $request->id");
    }
    //api para obtener los contadores de los pedidos
    public function getContadorPedidos(Request $request){
        $datos = [];
        $root  = 0;
        $validaPermiso = DB::SELECT("SELECT * FROM permisos_super
        WHERE usuario_id =  '$request->idusuario'
        ");
        if(count($validaPermiso) >0) {
           $root = 1;
        }
        if($request->grupo == 22 || $request->grupo == 23 || $root == 1){
            $obsequios              = DB::SELECT("SELECT * FROM obsequios o WHERE o.estado = '1'");
            $obsequios_aprobados    = DB::SELECT("SELECT * FROM obsequios o WHERE o.estado = '3'");
            $obsequios_autorizado   = DB::SELECT("SELECT * FROM obsequios o WHERE o.estado = '5'");
            $guias = DB::SELECT("SELECT * FROM pedidos p WHERE p.tipo = '1' AND p.estado = '1' AND p.estado_entrega = '1'");
            $datos = [
                "cont_obsequios"            => count($obsequios),
                "cont_obsequios_aprobados"  => count($obsequios_aprobados),
                "obsequios_autorizado"      => count($obsequios_autorizado),
                "cont_guias"                => count($guias),
                "root"                      => $root,
            ];
            return $datos;
        }
        if($request->grupo == 1){
            $obsequios_aprobados    = DB::SELECT("SELECT * FROM obsequios o WHERE o.estado = '3'");
            $obsequios_autorizado   = DB::SELECT("SELECT * FROM obsequios o WHERE o.estado = '3'");
            $guias = DB::SELECT("SELECT * FROM pedidos p WHERE p.tipo = '1' AND p.estado = '1' AND p.estado_entrega = '1'");
            $datos = [
                "cont_obsequios_aprobados"  => count($obsequios_aprobados),
                "obsequios_autorizado"      => count($obsequios_autorizado),
                "root"                      => $root,
            ];
        }
        return $datos;
    }
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
