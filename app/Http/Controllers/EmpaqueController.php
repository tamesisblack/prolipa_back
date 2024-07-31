<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\_1_4_Empacado;
use App\Models\EmpaqueDetalle;
use App\Models\Remision;
use App\Models\f_tipo_documento;
use App\Models\Ventas;
use App\Repositories\Facturacion\EmpaqueRepository;
use Illuminate\Http\Request;
use DB;
class EmpaqueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //API:GET/empaqueBodega
    protected $empaqueRepository;
    public function __construct(EmpaqueRepository $empaqueRepository)
    {
        $this->empaqueRepository = $empaqueRepository;
    }
    public function index(Request $request)
    {
        //api/GET/empaqueBodega?getTipoEmpaques=1
        if($request->getTipoEmpaques)           { return $this->empaqueRepository->getTipoEmpaquest(); }
        if($request->getListadoEmpaqueFecha)    { return $this->getListadoEmpaqueFecha($request); }
        if($request->getDetalleEmpaque)    { return $this->getDetalleEmpaque($request); }
    }

    //API:GET/empaqueBodega?getListadoEmpaqueFecha=1&fecha=2024-0
    public function getListadoEmpaqueFecha(Request $request)
    {
        $fecha = $request->fecha;
        $query = DB::SELECT("SELECT * FROM remision_copy re
        inner join rempacado e on e.remi_codigo=re.remi_codigo
        WHERE DATE(e.empa_fecha) = ?
        ",[$fecha]);
        return $query;
    }
    public function getDetalleEmpaque(Request $request)
    {
        //
        $query = DB::SELECT("SELECT * FROM rempaque_detallecopy WHERE empa_codigo='$request->codigo'");
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
        if($request->saveEmpaque){ return $this->saveEmpaque($request); }
        if($request->anulaEmpaque){ return $this->anulaEmpaque($request); }
        if($request->despacharEmpaque){ return $this->despacharEmpaque($request); }

    }

    //API:POST/empaqueBodega?saveEmpaque=1
    public function saveEmpaque($request){
        try{
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $miarray=json_decode($request->estado);
            $array=json_decode($request->detalle);
            DB::beginTransaction();
                $remision = new Remision;
                $remision->remi_codigo = $request->remi_codigo;
                $remision->remi_motivo = $request->remi_motivo;
                $remision->remi_dir_partida = $request->remi_dir_partida; 
                $remision->remi_destinatario = $request->remi_destinatario;
                $remision->remi_ruc_destinatario = $request->remi_ruc_destinatario;
                $remision->remi_direccion = $request->remi_direccion;
                $remision->remi_nombre_transportista = $request->remi_nombre_transportista;
                $remision->remi_ci_transportista = $request->remi_ci_transportista;
                $remision->remi_detalle = $request->remi_detalle;
                $remision->remi_num_factura = $request->empa_facturas;;
                $remision->remi_fecha_inicio = now();
                $remision->trans_codigo = $request->trans_codigo;
                $remision->remi_obs = $request->remi_obs;
                $remision->remi_responsable = $request->remi_responsable;
                $remision->remi_carton = $request->remi_carton;
                $remision->remi_paquete = $request->remi_paquete;
                $remision->remi_funda = $request->remi_funda;
                $remision->remi_rollo = $request->remi_rollo;
                $remision->remi_flete = $request->remi_flete;
                $remision->remi_pagado = $request->remi_pagado;
                $remision->remi_idempresa = $request->remi_idempresa;
                $remision->user_created = $request->user_created;
                $remision->created_at = now();
                $remision->updated_at = now();
                $remision->save();
                if($request->remi_idempresa==1){
                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from f_tipo_documento where tdo_letra='D'");
                    $query2 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from f_tipo_documento where tdo_letra='E'");
                }else if($request->remi_idempresa==3){
                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from f_tipo_documento where tdo_letra='D'");
                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from f_tipo_documento where tdo_letra='E'");
                }
                if(!empty($query1)){
                    $id=$query1[0]->id;
                    $codi=$query1[0]->cod;
                    $co=(int)$codi+1;
                    $tipo_doc = f_tipo_documento::findOrFail($id);
                    if($request->remi_idempresa==1){
                        $tipo_doc->tdo_secuencial_Prolipa = $co;
                    }else if($request->remi_idempresa==3){
                        $tipo_doc->tdo_secuencial_calmed = $co;
                    }
                    $tipo_doc->save();
                }
                if(!empty($query2)){
                    $ide=$query2[0]->id;
                    $codig=$query2[0]->cod;
                    $cod=(int)$codig+1;
                    $tipo_doc = f_tipo_documento::findOrFail($ide);
                    if($request->remi_idempresa==1){
                        $tipo_doc->tdo_secuencial_Prolipa = $cod;
                    }else if($request->remi_idempresa==3){
                        $tipo_doc->tdo_secuencial_calmed = $cod;
                    }
                    $tipo_doc->save();
                }
                foreach($miarray as $key => $item){
                        $venta = Ventas::findOrFail($item->ven_codigo);
                        $venta->ven_remision = $request->remi_codigo;
                        if($item->estado==1){
                            $venta->est_ven_codigo=6;
                        }else if($item->estado==0){
                            $venta->est_ven_codigo=10;
                        }
                        $venta->ven_fech_remision=now();
                        $venta->save();
                }
                $emp                = new _1_4_Empacado();
                $emp->empa_codigo       = $request->id_emp;
                $emp->empa_fecha        = now();
                $emp->empa_facturas     = $request->empa_facturas;
                $emp->empa_cartones     = $request->empa_cartones;
                $emp->usu_codigo        = $request->usu_codigo;
                $emp->remi_codigo       = $request->remi_codigo;
                $emp->empa_estado       = $request->empa_estado;
                $emp->user_created      = $request->user_created;
                // $emp->tipo              = $request->tipo;
                $emp->save();
                foreach($array as $key => $iten){
                    $detalle = new EmpaqueDetalle();
                    $detalle->det_empa_codigo = $iten->cod;
                    $detalle->empa_codigo=$request->id_emp;
                    $detalle->tip_empa_codigo=$iten->id;
                    $detalle->dete_estado=1;
                    $detalle->created_at=now();
                    $detalle->updated_at=now();
                    $detalle->user_created=$request->user_created;
                    $detalle->save();
                }
            DB::commit();
            return response()->json(['message' => 'Empaque creado con éxito',"data" => $emp], 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'Error al crear el empaque'.$e], 500);
            DB::rollback();
        }
    }
    public function anulaEmpaque($request){
        try{
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $miarray=json_decode($request->estado);
            DB::beginTransaction();
                $remision = Remision::where('remi_codigo', $request->remi_codigo)
                ->where('remi_idempresa', $request->remi_idempresa)
                ->firstOrFail();
                $remision->updated_at = now();
                $remision->remi_estado= 0;
                $remision->save();
                echo $remision;
                $emp                = _1_4_Empacado::where('remi_codigo', $request->remi_codigo)->firstOrFail();
                $emp->updated_at        = now();
                $emp->empa_estado       = 0;
                $emp->save();
                echo $emp;
                foreach($miarray as $key => $item){
                        $venta =  Ventas::where('ven_codigo', $item)
                        ->where('id_empresa', $request->remi_idempresa)
                        ->firstOrFail();
                        $venta->ven_remision = '';
                        $venta->est_ven_codigo=2;
                        $venta->ven_fech_remision='';
                        $venta->updated_at = now();
                        $venta->save();
                        echo $venta;
                }
            // DB::commit();
            return response()->json(['message' => 'Empaque anulado con éxito',"data" => $emp], 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'Error al anular el empaque'.$e], 500);
            DB::rollback();
        }
    }
    public function despacharEmpaque($request){
        try{
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $miarray=json_decode($request->estado);
            DB::beginTransaction();
                $remision = Remision::where('remi_codigo', $request->remi_codigo)
                ->where('remi_idempresa', $request->remi_idempresa)
                ->firstOrFail();
                $remision->remi_guia_remision =  $request->remi_guia_remision;
                $remision->remi_fecha_final = $request->remi_fecha_final;
                $remision->updated_at = now();
                $remision->remi_estado= 2;
                $remision->save();
                echo $remision;
                $emp                = _1_4_Empacado::where('remi_codigo', $request->remi_codigo)->firstOrFail();
                $emp->updated_at        = now();
                $emp->empa_estado       = 2;
                $emp->save();
                echo $emp;
                foreach($miarray as $key => $item){
                        $venta =  Ventas::where('ven_codigo', $item)
                        ->where('id_empresa', $request->remi_idempresa)
                        ->firstOrFail();
                        $venta->est_ven_codigo=1;
                        $venta->updated_at = now();
                        $venta->save();
                        echo $venta;
                }
                
            // DB::commit();
            return response()->json(['message' => 'Empaque anulado con éxito',"data" => $emp], 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'Error al anular el empaque'.$e], 500);
            DB::rollback();
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
