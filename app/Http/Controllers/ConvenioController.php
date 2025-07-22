<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Models\Pedidos\PedidosDocumentosLiq;
use App\Models\PedidoConvenio;
use App\Models\PedidoConvenioDetalle;
use App\Models\PedidoConvenioHistorico;
use App\Models\Pedidos;
use App\Models\Temporada;
use App\Models\User;
use App\Repositories\pedidos\ConvenioRepository;
use App\Repositories\PedidosPagosRepository;
use App\Traits\Pedidos\TraitPedidosGeneral;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
class ConvenioController extends Controller
{
    use TraitPedidosGeneral;
    private $pagoRepository;
    private $convenioRepository;
    public function __construct(PedidosPagosRepository $pagoRepository,ConvenioRepository $convenioRepository)
    {
        $this->pagoRepository     = $pagoRepository;
        $this->convenioRepository = $convenioRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //convenio
    public function index(Request $request)
    {
        //traer convenio de institucion
        if($request->getConvenioInstitucion){
            return $this->convenioRepository->getConvenioInstitucion($request->institucion_id);
        }
        if($request->AllConvenios){
            return $this->AllConvenios($request->institucion_id);
        }
        //informacion Convenio
        if($request->getInformacionConvenio){
            return $this->getInformacionConvenio($request->institucion_id,$request->periodo_id);
        }
        //informacion de convenio x id
        if($request->getInformacionConvenioXId){
            return $this->getInformacionConvenioXId($request->idConvenio);
        }
        //convenios x id
        if($request->getConveniosXId){
            return $this->getConveniosXId($request->id);
        }
        //traer todos los contratos
        if($request->allContratoXInstitucion){
            return $this->allContratoXInstitucion($request->institucion_id);
        }
        //update ids convenios to hijos
        if($request->setIdConvenioToHijos){
            return $this->setIdConvenioToHijos($request->institucion_id,$request->periodo_id,$request->idConvenio);
        }
    }

    public function AllConvenios($institucion){
        $query = DB::SELECT("SELECT c.*, i.nombreInstitucion, p.periodoescolar as periodo, sg.cantidad_finalizada,
        i.id_convenio_autorizacion,
        CONCAT(uc.nombres,' ',uc.apellidos) as usuario_creador,
        CONCAT(uf.nombres,' ',uf.apellidos) as usuario_cierre
        FROM pedidos_convenios c
        LEFT JOIN institucion i ON i.idInstitucion = c.institucion_id
        LEFT JOIN periodoescolar p ON p.idperiodoescolar = c.periodo_id
        LEFT JOIN pedidos_solicitudes_gerencia sg ON sg.id = c.id_solicitud_gerencia_convenio
        LEFT JOIN usuario uc ON c.usuario_creador = uc.idusuario
        LEFT JOIN usuario uf ON c.usuario_cierre = uf.idusuario
        WHERE c.institucion_id = ?
        AND (c.estado = '0' OR c.estado = '1' OR c.estado = '2')
        ORDER BY c.id DESC
        ",[$institucion]);
        return $query;
    }
    public function getInformacionConvenio($institucion,$periodo_id){
        $query = $this->obtenerConvenioInstitucionPeriodo($institucion,$periodo_id);
        if(empty($query)){
            return $query;
        }
        $idConvenio     = $query[0]->id;
        //traer los hijos del convenio global
        $query2 = $this->getConveniosXId($idConvenio);
        $datos = [];
        $contador =0;
        foreach($query2 as $key => $item){
            try {
                //===PROCESO======
                // $JsonDocumentos = $this->obtenerDocumentosLiq($item->contrato);
                $datos[$contador] = [
                    "id"                            => $item->id,
                    "pedido_convenio_institucion"   => $item->pedido_convenio_institucion,
                    "id_pedido"                     => $item->id_pedido,
                    "contrato"                      => $item->contrato,
                    "totalAnticipos"                => $item->valor,
                    "estado"                        => $item->estado,
                    "created_at"                    => $item->created_at,
                    // "datos"                         => $JsonDocumentos
                ];
                $contador++;
            } catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor".$ex];
            }
        }
        return ["convenio" => $query, "hijos_convenio" => $datos];
    }
    public function getInformacionConvenioXId($idConvenio){
        $query = PedidoConvenio::where('id','=',$idConvenio)->get();
        if(count($query) == 0){
            return $query;
        }
        $idConvenio     = $query[0]->id;
        //traer los hijos del convenio global
        $query2 = $this->getConveniosXId($idConvenio);
        $datos = [];
        $contador =0;
        foreach($query2 as $key => $item){
            try {
                //===PROCESO======
                // $JsonDocumentos = $this->obtenerDocumentosLiq($item->contrato);
                $datos[$contador] = [
                    "id"                            => $item->doc_codigo,
                    "pedido_convenio_institucion"   => $item->pedidos_convenios_id,
                    "id_pedido"                     => $item->id_pedido,
                    "contrato"                      => $item->ven_codigo,
                    "totalAnticipos"                => $item->doc_valor,
                    "estado"                        => $item->estado,
                    "created_at"                    => $item->created_at,
                    // "datos"                         => $JsonDocumentos
                ];
                $contador++;
            } catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor".$ex];
            }
        }
        return ["convenio" => $query, "hijos_convenio" => $datos];
    }
    //api:get/convenio?allContratoXInstitucion=yes&institucion_id=6
    public function allContratoXInstitucion($institucion){
        $query = DB::table('temporadas as t')
        ->select(DB::raw('t.*, p.id_pedido'))
        ->leftjoin('pedidos as p','t.contrato','p.contrato_generado')
        ->where('t.idInstitucion',$institucion)
        ->get();
        return $query;
    }
    //api:get/convenio?updateIdsConveniosToHijos=yes&idConvenio=4
    public function updateIdsConveniosToHijos($idConvenio){

    }
    public function getConveniosXId($idConvenio){
        $query = PedidosDocumentosLiq::Where('pedidos_convenios_id','=',$idConvenio)->where('estado','=',1)->get();
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
    //API:POST/convenio
    public function store(Request $request)
    {
        if($request->saveGlobal){
            return $this->saveGlobal($request);
        }
        //validar que el convenio este activo y no finalizado
        $convenio         = PedidoConvenio::findOrFail($request->idConvenio);
        $estadoConvenio   = $convenio->estado;
        if($estadoConvenio == 0) {
            return ["status" => "0", "message" => "Ya no se puede modificar el convenio ya finalizo"];
        }
        if($request->updateCamposDatos){
            return $this->updateCamposDatos($request);
        }
        if($request->updateCamposConvenio){
            return $this->updateCamposConvenio($request);
        }
        if($request->updateValuesSuggested){
            return $this->updateValuesSuggested($request);
        }
        if($request->saveContratoConvenio){
            return $this->saveContratoConvenio($request);
        }
        if($request->cerrarConvenio)            { return $this->cerrarConvenio($request); }
        if($request->aprobarConvenioCerrado)    { return $this->aprobarConvenioCerrado($request); }
    }
    public function saveGlobal($request){
        try{
            //transaccion
            DB::beginTransaction();
            Cache::flush();
            //variables
            $institucion_id     = $request->institucion_id;
            $id_pedido          = $request->id_pedido;
            $user_created       = $request->user_created;
            $anticipo_global    = $request->anticipo_global;
            $old_values         = [];
            //===PROCESS===
            //busco si hay convenio abierto
            $query = $this->convenioRepository->getConvenioInstitucion($request->institucion_id);
            if(!empty($query)){
                return ["status" => "0", "message" => "Ya existe un convenio que esta abierto"];
            }else{
                $global = new PedidoConvenio;
                $global->usuario_creador = $request->user_created;
            }
            $global->anticipo_global = $request->anticipo_global;
            $global->convenio_anios  = $request->convenio_anios;
            $global->institucion_id  = $request->institucion_id;
            $global->periodo_id      = $request->periodo_id;
            $global->id_pedido       = $request->id_pedido;
            $global->user_created    = $request->user_created;
            $global->observacion     = $request->observacion;
            $global->save();
            $this->saveHistorico($institucion_id,0,$user_created,$anticipo_global,null,0,$old_values,"Actualizar anticipo global");
            //validar que si ya tiene contrato y no ha sido registrado en la tabla de hijo crearlos
            $pedido                  = Pedidos::findOrFail($id_pedido);
            $pedido->convenio_anios  = $request->convenio_anios;
            $pedido->pedidos_convenios_id = $global->id;
            $pedido->save();
            //al pago que se realizo el voy a colocar el id de convenio
            $this->setIdConvenioToHijos($global->institucion_id,$global->periodo_id,$global->id);
            //almanacenar en movimientos
            $this->convenioRepository->saveMovimientosConvenio($global->id,$request->id_pedido);
             //fin transaccion
             DB::commit();
            if($global){
                return ["status" => "1","message" => "Se guardo correctamente"];
            }else{
                return ["status" => "0","message" => "No se puedo guardar"];
            }
        }
        catch(\Exception $ex){
            DB::rollBack();
            return ["status" => "0", "message" => "Ocurrio un error al intentar guardar el convenio", "error" => "error: ".$ex];
        }
    }
    public function setIdConvenioToHijos($institucion_id,$periodo_id,$idConvenio){
        //ACTIVOS
        $query = $this->pagoRepository->getPagosInstitucion($institucion_id,$periodo_id,2,1);
        foreach($query as $key => $item) { $datos = ['pedidos_convenios_id' => $idConvenio,]; PedidosDocumentosLiq::actualizarDocumentoLiq($item->doc_codigo, $datos); }
        //PENDIENTES
        $query2 = $this->pagoRepository->getPagosInstitucion($institucion_id,$periodo_id,2,0);
        foreach($query2 as $key => $item) { $datos = ['pedidos_convenios_id' => $idConvenio,]; PedidosDocumentosLiq::actualizarDocumentoLiq($item->doc_codigo, $datos); }
        return ["status" => "1", "message" => "Se guardo correctamente"];
    }
    public function updateCamposDatos($request){
        Cache::flush();
        $datos = [];
        $campo1 = $request->campo1;
        $campo2 = $request->campo2;
        $campo3 = $request->campo3;
        $campo4 = $request->campo4;
        $valor1 = $request->valor1;
        $valor2 = $request->valor2;
        $valor3 = $request->valor3;
        $valor4 = $request->valor4;
        $user_created   = $request->user_created;
        $tipoAccion     = $request->tipoAccion;
        $convenio_gerencia = $request->convenio_gerencia;
        $infoUsuario  = User::findOrFail($user_created);
        $id_group     = $infoUsuario->id_group;
        if($request->unCampo)   { $datos = [ $campo1 => $valor1]; }
        if($request->dosCampos) { $datos = [ $campo1 => $valor1, $campo2 => $valor2 ]; }
        if($request->tresCampos) { $datos = [ $campo1 => $valor1, $campo2 => $valor2, $campo3 => $valor3 ]; }
        if($request->cuatroCampos) { $datos = [ $campo1 => $valor1, $campo2 => $valor2, $campo3 => $valor3 ,$campo4 => $valor4 ]; }
        $old_values         = PedidoConvenio::findOrFail($request->id);
        if ($id_group == 22 || $id_group == 23 || $id_group == 1 && $tipoAccion == 1) {
            $datos['convenio_aprobado'] = 4; // ← Aquí estaba el problema
            $datos['usuario_aprueba'] = $user_created;
            $datos['fecha_aprobacion'] = date('Y-m-d H:i:s');
        }
        // gerencia aprueba convenio
        if($convenio_gerencia == 1){
            $datos['usuario_aprueba'] = $user_created;
            $datos['fecha_aprobacion'] = date('Y-m-d H:i:s');
        }
        DB::table('pedidos_convenios')
        ->where('id',$request->id)
        ->update($datos);
        //======actualizar los pedidos el convenio_anios por pedidos_convenios_id=====
        if($campo1 == "convenio_anios"){
            Pedidos::where('pedidos_convenios_id',$request->id)->update(['convenio_anios' => $valor1]);
        }
        //si es anticipo global actualizar los movimientos
        if($campo1 == 'anticipo_global'){
            //actualizar movimientos
            $this->convenioRepository->saveMovimientosConvenio($request->id);
        }
        //history
        //variables
        $institucion_id = $old_values->institucion_id;
        $periodo_id     = $old_values->periodo_id;
        $id_pedido      = $old_values->id_pedido;
        //update id convenio en hijo
        $this->setIdConvenioToHijos($institucion_id,$periodo_id,$old_values->id);
        //save historico
        $this->saveHistorico($institucion_id,$id_pedido,$user_created,$valor1,null,0,$old_values,"Se actualizo el campo $campo1");
    }
    public function updateValuesSuggested($request){
        $user_created   = $request->user_created;
        $contratos      = json_decode($request->data_contratos);
        $padreConvenio   = PedidoConvenio::findOrFail($request->idConvenio);
        $estadoConvenio  = $padreConvenio->estado;
        if($estadoConvenio == 0) { return ["status" => "0", "message" => "Ya no se puede modificar el convenio ya finalizo"]; }
        if($estadoConvenio == 2) { return ["status" => "0", "message" => "Ya no se puede modificar el convenio ya cerro"]; }
        foreach($contratos as $key => $item){
            //variables
            $institucion_id     = $item->institucion_id;
            $id_pedido          = $item->id_pedido;
            $contrato           = $item->ven_codigo;
            $old_values         = PedidosDocumentosLiq::findOrFail($item->doc_codigo);
            DB::table('1_4_documento_liq')
            ->where('doc_codigo',$item->doc_codigo)
            ->update(["doc_valor" => $item->valueSuggested]);
            //actualizar movimientos
            $this->convenioRepository->saveMovimientosConvenio($request->idConvenio);
            //update a pedido
            $this->convenioRepository->updatePedido($contrato,$padreConvenio->convenio_anios,$request->idConvenio);
            //history
            $this->saveHistorico($institucion_id,$id_pedido,$user_created,$item->valueSuggested,$contrato,1,$old_values,"Se actualizo el campo valor");
        }
        return "Se guardo correctamente";
    }
    public function saveContratoConvenio($request){
        $institucion_id  = $request->institucion_id;
        $id_pedido       = $request->id_pedido;
        $user_created    = $request->user_created;
        $valor           = $request->valor;
        $contrato        = $request->contrato;
        $old_values      = [];
        $padreConvenio   = PedidoConvenio::findOrFail($request->idConvenio);
        $estadoConvenio  = $padreConvenio->estado;
        if($estadoConvenio == 0) { return ["status" => "0", "message" => "Ya no se puede modificar el convenio ya finalizo"]; }
        if($estadoConvenio == 2) { return ["status" => "0", "message" => "Ya no se puede modificar el convenio ya cerro"]; }
        if($request->id > 0){
            $hijoConvenio = PedidosDocumentosLiq::findOrFail($request->id);
            $old_values   = $hijoConvenio;
        }else{

            //validar que el contrato ya no este creado
            $getConvenioHijo = PedidosDocumentosLiq::Where('ven_codigo',$contrato)->where('tipo_pago_id','4')->where('estado','1')->get();
            if(count($getConvenioHijo) > 0){
                return ["status" => "0","message" => "Ya existe el contrato $contrato creado en el convenio"];
            }
            $hijoConvenio = new PedidosDocumentosLiq();
            $hijoConvenio->doc_fecha                = date("Y-m-d H:i:s");
        }
        $hijoConvenio->pedidos_convenios_id         = $request->idConvenio;
        $hijoConvenio->id_pedido                    = $request->id_pedido;
        $hijoConvenio->ven_codigo                   = $request->contrato;
        $hijoConvenio->institucion_id               = $request->institucion_id;
        $hijoConvenio->periodo_id                   = $request->periodo_id;
        $hijoConvenio->doc_valor                    = $request->valor;
        $hijoConvenio->tipo_pago_id                 = 4;
        $hijoConvenio->estado                       = 1;
        $hijoConvenio->save();
        //update a pedido
        $this->convenioRepository->updatePedido($contrato,$padreConvenio->convenio_anios,$request->idConvenio);
        //actualizar movimientos
        $this->convenioRepository->saveMovimientosConvenio($request->idConvenio);
        //history
        $this->saveHistorico($institucion_id,$id_pedido,$user_created,$valor,$contrato,1,$old_values,"Se actualizo el campo valor o contrato");
        if($hijoConvenio){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }
    }
    //api:post/convenio?cerrarConvenio=1
    public function cerrarConvenio($request){
        try{
            $idConvenio     = $request->idConvenio;
            $old_values     = PedidoConvenio::findOrFail($idConvenio);
            //si ya existe un convenio cerrado en el perido no se puede cerrar
            $query = DB::SELECT("SELECT * FROM pedidos_convenios c  WHERE c.institucion_id = ? and c.periodo_id = ? and c.estado = '2'",[$old_values->institucion_id,$old_values->periodo_id]);
            if(count($query) > 0){
                return ["status" => "0", "message" => "Ya existe un convenio cerrado en el período"];
            }
            /*
                Validar que solo el ultimo pedido de convenio puede cerrar el convenio
            */
            $ultimoConvenio = Pedidos::ultimoConvenio($idConvenio);
            $id_pedido      = $request->id_pedido;
            if(count($ultimoConvenio) == 0){
                return ["status" => "0", "message" => "Solo el ultimo pedido puede cerrar el convenio"];
            }
            $idPedidoUltimo = $ultimoConvenio[0]->id_pedido;
            if($id_pedido != $idPedidoUltimo){
                return ["status" => "0", "message" => "Solo el ultimo pedido puede cerrar el convenio"];
            }
            //transaccion
            DB::beginTransaction();
            $contrato       = $request->contrato == null || $request->contrato == "null" ? null : $request->contrato;
            $user_created   = $request->user_created;
            $valor          = 0;

            $institucion_id = $old_values->institucion_id;
            $proceso        = PedidoConvenio::where('id',$idConvenio)
            ->update(["estado" => 2 ,
            "usuario_cierre" => $user_created,
            ]);
            //history
            $this->saveHistorico($institucion_id,$id_pedido,$user_created,$valor,$contrato,0,$old_values,"Se cerro el convenio");
            //almanacenar en movimientos
            $this->convenioRepository->saveMovimientosConvenio($idConvenio);
            //fin transaccion
            DB::commit();
            return ["status" => "1", "message" => "Se cerro correctamente"];
        }catch(\Exception $ex){
            DB::rollBack();
            return ["status" => "0", "message" => "Ocurrio un error al intentar cerrar el convenio".$ex];
        }
    }
    //api:post/convenio?aprobarConvenioCerrado=1
    public function aprobarConvenioCerrado($request){
       return $this->convenioRepository->aprobarConvenioCerrado($request->idConvenio,$request->user_created);
    }
    public function saveHistorico($institucion_id,$id_pedido,$user_created,$cantidad,$contrato,$tipo,$old_values,$observacion){
        $historico = new PedidoConvenioHistorico();
        $historico->institucion_id  = $institucion_id;
        $historico->id_pedido       = $id_pedido;
        $historico->user_created    = $user_created;
        $historico->cantidad        = $cantidad;
        $historico->contrato        = $contrato;
        $historico->tipo            = $tipo;
        if(isset($old_values->created_at)){
            $historico->old_values      = $old_values;
        }else{
            $historico->old_values      = count($old_values) == 0 ? "" : $old_values;
        }
        $historico->observacion     = $observacion;
        $historico->save();
    }
    //API:POST/eliminarConvenio
    public function eliminarConvenio(Request $request){
        //validar que el convenio este activo y no finalizado
        $convenio         = PedidoConvenio::findOrFail($request->idConvenio);
        $estadoConvenio   = $convenio->estado;
        if($estadoConvenio == 0) {
            return ["status" => "0", "message" => "Ya no se puede modificar el convenio ya finalizo"];
        }
        if($request->eliminarHijoConvenio){
            return $this->eliminarHijoConvenio($request->id);
        }
    }
    public function eliminarHijoConvenio($id){
        $convenio = PedidosDocumentosLiq::findOrFail($id);
        $contrato = $convenio->ven_codigo;
        //limipiar en pedido
        $this->convenioRepository->updatePedido($contrato,null,0);
        $convenio->delete();
        return "Se elimino correctamente";
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
}
