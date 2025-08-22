<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PedidoNotificacion;
use Illuminate\Http\Request;
use App\Models\PedidosSolicitudesGerencia;
use App\Models\Pedidos;
use App\Repositories\pedidos\ConvenioRepository;
use Illuminate\Support\Facades\Cache;
use DB;
class PedidosGerenciaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $convenioRepository;
    //constructor
    public function __construct(ConvenioRepository $convenioRepository){
        $this->convenioRepository = $convenioRepository;
    }
    //api:get/pedidos_gerencia
    public function index(Request $request)
    {
        if($request->solicitudesPendientesAll)  { return $this->solicitudesPendientesAll($request); }
        if($request->listarSolicitudesPedido)   { return $this->listarSolicitudesPedido($request); }
    }
    //api:get/pedidos_gerencia?solicitudesPendientesAll=1&tipo=0&estado=0
    public function solicitudesPendientesAll($request){
        $query = PedidosSolicitudesGerencia::Where('pedidos_solicitudes_gerencia.estado','<>',2)
        ->select('pedidos_solicitudes_gerencia.*','pedidos.contrato_generado','institucion.nombreInstitucion','pedidos.id_pedido',
        DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as asesor'))
        ->leftJoin('pedidos','pedidos.id_pedido','=','pedidos_solicitudes_gerencia.id_pedido')
        ->leftjoin('usuario','usuario.idusuario','=','pedidos.id_asesor')
        ->leftjoin('institucion','institucion.idInstitucion','=','pedidos.id_institucion')
        ->where('pedidos_solicitudes_gerencia.tipo',$request->tipo)
        ->where('pedidos_solicitudes_gerencia.estado','=',$request->estado)
        ->orderBy('pedidos_solicitudes_gerencia.id','desc')
        ->get();
        return $query;
    }
    //api:get/pedidos_gerencia?listarSolicitudesPedido=1&id_pedido=1&tipo=0
    public function listarSolicitudesPedido($request){
        $pedido               = Pedidos::find($request->id_pedido);
        $id_pedido            = $request->id_pedido;
        $tipo                 = $request->tipo;
        $ca_codigo_agrupado   = $pedido->ca_codigo_agrupado;
        $query = DB::SELECT("SELECT pedidos_solicitudes_gerencia.*,
        pedidos.contrato_generado,institucion.nombreInstitucion,pedidos.id_pedido,
        CONCAT(usuario.nombres,' ',usuario.apellidos) as asesor,
        CONCAT(edit.nombres,' ',edit.apellidos) as asesor_edit
        FROM pedidos_solicitudes_gerencia
        LEFT JOIN pedidos ON pedidos.id_pedido = pedidos_solicitudes_gerencia.id_pedido
        LEFT JOIN usuario ON usuario.idusuario = pedidos.id_asesor
        LEFT JOIN usuario edit ON edit.idusuario = pedidos_solicitudes_gerencia.user_finaliza
        LEFT JOIN institucion ON institucion.idInstitucion = pedidos.id_institucion
        WHERE pedidos_solicitudes_gerencia.id_pedido = $id_pedido
        AND pedidos_solicitudes_gerencia.tipo = $tipo
        ORDER BY pedidos_solicitudes_gerencia.id DESC
        ");
        $agrupado = DB::SELECT("SELECT * from f_proforma p
            WHERE p.idPuntoventa = '$ca_codigo_agrupado'
            AND p.prof_estado <> '0'
        ");
        return [
            "arregloComision" => $query,
            "agrupado"        => $agrupado
        ];
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
    //api:post/pedidos_gerencia
    public function store(Request $request)
    {
        Cache::flush();
        if($request->saveSolicitud)         { return $this->saveSolicitud($request); }
        if($request->aprobarSolicitud)      { return $this->aprobarSolicitud($request); }
    }
    //api:post/pedidos_gerencia/saveSolicitud
    public function saveSolicitud($request){
        //validar que solo se puede crear solicitud cuando este aprobado o anulado o rechazada
        if($request->id == 0){
            $query = PedidosSolicitudesGerencia::Where('id_pedido',$request->id_pedido)
            ->where('tipo',$request->tipo)
            ->where('estado','=',0)
            ->get();
            if(count($query) > 0){ return ["status" => "0", "message" => "Ya existe una solicitud pendiente"]; }
            $solicitud                          = new PedidosSolicitudesGerencia();
        }
        else{
            $solicitud                      = PedidosSolicitudesGerencia::findOrFail($request->id);
        }
        $getPedido                          = Pedidos::findOrFail($request->id_pedido);
        $periodo_id                         = $getPedido->id_periodo;
        $solicitud->id_pedido               = $request->id_pedido;
        $solicitud->tipo                    = $request->tipo;
        $solicitud->cantidad_solicitada     = $request->cantidad_solicitada;
        $solicitud->estado                  = $request->estado;
        $solicitud->periodo_id              = $periodo_id;
        $solicitud->user_created            = $request->user_created;
        $solicitud->observacion             = $request->observacion;
        $solicitud->save();
        //actualizar en el pedido si el estado es 0 de pendiente
        if($request->estado == 0){
            $pedido = Pedidos::Where('id_pedido',$request->id_pedido)
            ->update([
                "id_solicitud_gerencia_comision" => $solicitud->id,
                "solicitud_gerencia_estado"      => 1
            ]);
        }
        if($solicitud){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "0", "message" => "Error al guardar"];
        }
    }
    //api:post/pedidos_gerencia/aprobarSolicitud
    public function aprobarSolicitud($request){
        try{
            $aprobadoDespues                    = $request->aprobadoDespues;
            //transaccion
            DB::beginTransaction();
            $solicitud                          = PedidosSolicitudesGerencia::findOrFail($request->id);
            $id_pedido                          = $solicitud->id_pedido;
            $solicitud->cantidad_finalizada     = $request->cantidad_finalizada;
            $solicitud->estado                  = $request->estado;
            $solicitud->observacion_finaliza    = $request->observacion_finaliza;
            $solicitud->fecha_finaliza          = date("Y-m-d H:i:s");
            if($aprobadoDespues == 1){
                // si aprueba despues el root no cambio quien aprueba mantengo
            }else{
                $solicitud->id_grupo_finaliza       = $request->id_grupo_finaliza;
                $solicitud->user_finaliza           = $request->user_finaliza;
            }
            $solicitud->save();
            //convenio autorizar cierre
            if($solicitud->tipo == 1){
                $this->convenioRepository->autorizarConvenio($solicitud->id_pedido,$solicitud->id,$solicitud->cantidad_finalizada);
            }
            //comision cuando se aprueba
            if($solicitud->tipo == 0){
                //actualizar en el pedido si el estado es 1 de aprobado
                if($request->estado == 1){
                    $pedido = Pedidos::Where('id_pedido',$id_pedido)
                    ->update([
                        "id_solicitud_gerencia_comision" => $solicitud->id,
                        "descuento"                      => $solicitud->cantidad_finalizada,
                        "solicitud_gerencia_estado"      => 0
                    ]);
                }
                //cuando se rechaza
                if($request->estado == 3){
                    $pedido = Pedidos::Where('id_pedido',$id_pedido)
                    ->update([
                        "id_solicitud_gerencia_comision" => $solicitud->id,
                        "descuento"                      => $solicitud->cantidad_finalizada,
                        "solicitud_gerencia_estado"      => 2
                    ]);
                }
            }
            DB::commit();
            if($solicitud){
                return ["status" => "1", "message" => "Se aprobo correctamente"];
            }else{
                return ["status" => "0", "message" => "Error al aprobar"];
            }
        }catch(\Exception $ex){
            DB::rollback();
            return ["status" => "0", "message" => "Error al aprobar", "error" => "error: ".$ex];
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
    //API:POST/deleteMetodos
    public function deleteMetodos(Request $request){
        if($request->eliminarSolicitud){ return $this->eliminarSolicitud($request); }
    }
    //api:post/deleteMetodos/eliminarSolicitud
    public function eliminarSolicitud($request){
        $solicitud      = PedidosSolicitudesGerencia::findOrFail($request->id);
        $id_pedido      = $solicitud->id_pedido;
        //validar que la solicitud no este aprobado si esta aprobado no puede eliminar
        $estado         = $solicitud->estado;
        if($estado == 1){ return ["status" => "0", "message" => "No se puede eliminar porque ya la solicitud esta aprobada"]; }
        if($estado == 3){ return ["status" => "0", "message" => "No se puede eliminar porque ya esta rechazada"]; }
        $solicitud->delete();
        $pedido = Pedidos::Where('id_pedido',$id_pedido)
        ->update([
            "solicitud_gerencia_estado"      => 0
        ]);
        return ["status" => "1", "message" => "Se elimino correctamente"];
    }
}
