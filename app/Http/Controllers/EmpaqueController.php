<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\_1_4_Empacado;
use App\Models\EmpaqueDetalle;
use App\Models\DetalleVentas;
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
        if($request->getEmpaqueBodega)          { return $this->getEmpaque();}
        if($request->getDetalleEmpaque)     { return $this->getDetalleEmpaque($request); }
        if($request->pendientes)    { return $this->pendientes(); }
        if($request->getchofer){ return $this->getchofer(); }
        if($request->getventa)     { return $this->getventa($request); }
    }

    //API:GET/empaqueBodega?getListadoEmpaqueFecha=1&fecha=2024-0
    public function getListadoEmpaqueFecha(Request $request)
    {
        $fecha = $request->fecha;
        $query = DB::SELECT("SELECT re.*, e.*, i.nombreInstitucion, fv.est_ven_codigo, em.descripcion_corta,
            (select sum(det_ven_cantidad_despacho) FROM f_detalle_venta dv WHERE dv.ven_codigo=fv.ven_codigo) as libros,
           i.ruc, (select count(det_empa_codigo) FROM rempaque_detallecopy dr WHERE dr.empa_codigo=e.empa_codigo) as cantidad,
            CONCAT(u.nombres,' ',u.apellidos) AS cliente  FROM remision_copy re
        inner join rempacado e on e.remi_codigo=re.remi_codigo
        inner join f_venta fv on fv.ven_codigo=re.remi_num_factura
        inner join institucion i on fv.institucion_id = i.idInstitucion
        LEFT join usuario u on fv.ven_cliente = u.idusuario
        inner join empresas em on em.id=re.remi_idempresa
        WHERE DATE(e.empa_fecha) = ?
        and e.idempresa=re.remi_idempresa and (e.empa_estado=2 or e.empa_estado=0) and (re.remi_estado=2 or re.remi_estado=0)
        group by re.remi_codigo, re.remi_idempresa, e.empa_codigo 
        ",[$fecha]);
        return $query;
    }
    public function pendientes()
    {
        $query = DB::SELECT("SELECT re.*, e.*, fpr.prof_observacion, fv.ven_observacion, i.nombreInstitucion, fv.est_ven_codigo, em.descripcion_corta,
            (select sum(det_ven_cantidad_despacho) FROM f_detalle_venta dv WHERE dv.ven_codigo=fv.ven_codigo) as libros,
           i.ruc, (select count(det_empa_codigo) FROM rempaque_detallecopy dr WHERE dr.empa_codigo=e.empa_codigo and dr.idempresa=e.idempresa) as cantidad,
            CONCAT(u.nombres,' ',u.apellidos) AS cliente  FROM remision_copy re
        inner join rempacado e on e.remi_codigo=re.remi_codigo
        inner join f_venta fv on fv.ven_codigo=re.remi_num_factura
        inner join institucion i on fv.institucion_id = i.idInstitucion
        LEFT join usuario u on fv.ven_cliente = u.idusuario
        inner join empresas em on em.id=re.remi_idempresa
        LEFT JOIN f_proforma fpr ON fpr.prof_id = fv.ven_idproforma
        where re.remi_estado=1 and e.empa_estado=1 and e.idempresa=re.remi_idempresa
        group by re.remi_codigo, re.remi_idempresa, e.empa_codigo 
        order by  e.empa_fecha");
        return $query;
    }

    public function getchofer()
    {
        $query = DB::SELECT("SELECT cedula, CONCAT(nombres,' ',apellidos) AS responsable  FROM usuario
        where (id_group=34 OR id_group=17 OR id_group=27) and estado_idEstado=1");
        return $query;
    }
    public function getventa(Request $request)
    {
        $query = DB::SELECT("SELECT sum(fv.det_ven_cantidad) AS cant,  sum(fv.det_ven_cantidad_despacho) AS desp FROM f_detalle_venta fv
        where fv.ven_codigo='$request->ven_codigo' and fv.id_empresa=$request->id ");
        return $query;
    }
    public function getDetalleEmpaque(Request $request)
    {
        //
        $query = DB::SELECT("SELECT de.det_empa_codigo, de.tip_empa_codigo as id, te.tip_empa_nombre as nombre FROM rempaque_detallecopy de
        INNER JOIN 1_4_tipo_empaque te ON te.tip_empa_codigo=de.tip_empa_codigo
         WHERE de.empa_codigo='$request->codigo'
         and de.idempresa=$request->id");
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
        if($request->EditEmpaque){ return $this->EditEmpaque($request); }
    }

    //API:POST/empaqueBodega?saveEmpaque=1
    public function saveEmpaque($request){
        try{
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $array=json_decode($request->detalle);
            DB::beginTransaction();
            //REGISTRO DE REMISION
                $remision = new Remision;
                $remision->remi_codigo = $request->remi_codigo;
                $remision->remi_idempresa = $request->remi_idempresa;
                $remision->remi_motivo = $request->remi_motivo;
                $remision->remi_dir_partida = $request->remi_dir_partida; 
                $remision->remi_destinatario = $request->remi_destinatario;
                $remision->remi_ruc_destinatario = $request->remi_ruc_destinatario;
                $remision->remi_direccion = $request->remi_direccion;
                $remision->remi_nombre_transportista = $request->remi_nombre_transportista;
                $remision->remi_ci_transportista = $request->remi_ci_transportista;
                $remision->remi_detalle = $request->remi_detalle;
                $remision->remi_num_factura = $request->empa_facturas;
                $remision->remi_fecha_inicio = now();
                $remision->trans_codigo = $request->trans_codigo;
                $remision->remi_obs = $request->remi_obs;
                $remision->remi_responsable = $request->remi_responsable;
                $remision->remi_carton = (int)$request->remi_carton;
                $remision->remi_paquete = (int)$request->remi_paquete;
                $remision->remi_funda = (int)$request->remi_funda;
                $remision->remi_rollo = (int)$request->remi_rollo;
                $remision->remi_flete = (int)$request->remi_flete;
                $remision->remi_pagado = (int)$request->remi_pagado;
                $remision->user_created = $request->user_created;
                $remision->created_at = now();
                $remision->updated_at = now();
                $remision->save();

                //ACTUALIZA EL TIPO DE DOCUMENTO REMISION Y EMPAQUE
                if($request->remi_idempresa==1){
                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from f_tipo_documento where tdo_letra='D'");
                    $query2 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from f_tipo_documento where tdo_letra='E'");
                }else if($request->remi_idempresa==3){
                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from f_tipo_documento where tdo_letra='D'");
                    $query2 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from f_tipo_documento where tdo_letra='E'");
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
                // VENTA ESTADO
                $venta = Ventas::where('ven_codigo', $request->empa_facturas)
                ->where('id_empresa', $request->remi_idempresa)
                ->firstOrFail();
                $venta->est_ven_codigo=$request->estado;
                $venta->save();
                
                //EMPACADO
                $emp = _1_4_Empacado::where('empa_codigo', $request->id_emp)
                ->where('idempresa', $request->remi_idempresa)
                ->first();


                if(!$emp){
                    $emp                = new _1_4_Empacado();
                    $emp->empa_codigo       = $request->id_emp;
                    $emp->idempresa         = $request->remi_idempresa;
                    $emp->empa_fecha        = now();
                    $emp->empa_libros       = $request->libros;
                    $emp->empa_facturas     = $request->empa_facturas;
                    $emp->empa_cartones     = $request->empa_cartones;
                    $emp->usu_codigo        = $request->usu_codigo;
                    $emp->remi_codigo       = $request->remi_codigo;
                    $emp->user_created      = $request->user_created;
                    $emp->save();

                }else{
                    return response()->json(['message' => 'Empaque no creado'], 404);
                }

                $emp->empa_codigo       = $request->id_emp;
                $emp->idempresa         = $request->remi_idempresa;
                $emp->empa_fecha        = now();
                $emp->empa_libros       = $request->libros;
                $emp->empa_facturas     = $request->empa_facturas;
                $emp->empa_cartones     = $request->empa_cartones;
                $emp->usu_codigo        = $request->usu_codigo;
                $emp->remi_codigo       = $request->remi_codigo;
                $emp->user_created      = $request->user_created;
                // $emp->tipo              = $request->tipo;
                $emp->save();
                //DETALLE DEL EMPACADO
                $cont=1;
                foreach($array as $key => $iten){
                    for ($i=0; $i < $iten->cantidad; $i++) { 
                        $detalle = new EmpaqueDetalle();
                        $detalle->det_empa_codigo = $request->id_emp.'-'.$cont;
                        $detalle->empa_codigo=$request->id_emp;
                        $detalle->idempresa=$request->remi_idempresa;
                        $detalle->tip_empa_codigo=$iten->id;
                        $detalle->dete_estado=1;
                        $detalle->created_at=now();
                        $detalle->updated_at=now();
                        $detalle->user_created=$request->user_created;
                        $detalle->save();
                        $cont++;
                    }
                }
            DB::commit();
            return response()->json(['message' => 'Empaque creado con éxito',"data" => $remision], 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'Error al crear el empaque'.$e], 500);
            DB::rollback();
        }
    }
    public function EditEmpaque($request){
        try{
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $array=json_decode($request->detalle);
            DB::beginTransaction();
            //REGISTRO DE REMISION
                $remision =Remision::where('remi_codigo', $request->remi_codigo)
                ->where('remi_idempresa', $request->remi_idempresa)
                ->firstOrFail();
                $remision->remi_direccion = $request->remi_direccion;
                $remision->remi_nombre_transportista = $request->remi_nombre_transportista;
                $remision->remi_ci_transportista = $request->remi_ci_transportista;
                $remision->trans_codigo = $request->trans_codigo;
                $remision->remi_obs = $request->remi_obs;
                $remision->remi_carton = $request->remi_carton;
                $remision->remi_paquete = $request->remi_paquete;
                $remision->remi_funda = $request->remi_funda;
                $remision->remi_rollo = $request->remi_rollo;
                $remision->remi_flete = $request->remi_flete;
                $remision->remi_pagado = $request->remi_pagado;
                $remision->updated_at = now();
                $remision->save();
                // VENTA ESTADO
                $venta = Ventas::where('ven_codigo', $request->empa_facturas)
                ->where('id_empresa', $request->remi_idempresa)
                ->firstOrFail();
                $venta->est_ven_codigo=$request->estado;
                $venta->save();
                //EMPACADO
                $emp                = _1_4_Empacado::where('empa_codigo',  $request->id_emp)
                ->where('remi_codigo', $request->remi_codigo)
                ->where('idempresa', $request->remi_idempresa)
                ->firstOrFail();
                $emp->empa_cartones     = $request->empa_cartones;
                $emp->empa_libros       = $request->libros;
                $emp->updated_at        = now();
                // $emp->tipo              = $request->tipo;
                $emp->save();
                EmpaqueDetalle::where('empa_codigo', $request->id_emp)
                ->where('idempresa', $request->remi_idempresa)
                ->delete();
                $cont=1;
                //DETALLE DEL EMPACADO;
                foreach($array as $key => $iten){
                    for ($i=0; $i < $iten->cantidad; $i++) { 
                        $detalle = new EmpaqueDetalle();
                        $detalle->det_empa_codigo = $request->id_emp.'-'.$cont;
                        $detalle->empa_codigo=$request->id_emp;
                        $detalle->idempresa=$request->remi_idempresa;
                        $detalle->tip_empa_codigo=$iten->id;
                        $detalle->created_at=now();
                        $detalle->updated_at=now();
                        $detalle->user_created=$request->user_created;
                        $detalle->save();
                        $cont++;
                    }
                }
            DB::commit();
            return response()->json(['message' => 'Empaque editado con éxito',"data" => $remision], 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'Error al editar el empaque'.$e], 500);
            DB::rollback();
        }
    }

    public function anulaEmpaque($request){
        try{
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $miarray = json_decode($request->libros);
            DB::beginTransaction();
                $remision = Remision::where('remi_codigo', $request->remi_codigo)
                ->where('remi_idempresa', $request->remi_idempresa)
                ->firstOrFail();
                $remision->updated_at = now();
                $remision->remi_estado= 0;
                $remision->save();
                $emp                = _1_4_Empacado::where('remi_codigo', $request->remi_codigo)->where('idempresa', $request->remi_idempresa)->firstOrFail();
                $emp->updated_at        = now();
                $emp->empa_estado       = 0;
                $emp->save();
                $venta =  Ventas::where('ven_codigo', $request->factura)
                ->where('id_empresa', $request->remi_idempresa)
                ->firstOrFail();
                $query=DB::SELECT("SELECT count(pro_codigo) as cont FROM f_detalle_venta dv 
                WHERE dv.ven_codigo='$request->factura'
                AND dv.id_empresa=$request->remi_idempresa");
                $total = $query[0]->cont;
                if($total==$request->cantidad){
                    $venta->est_ven_codigo=2;
                } else{
                    $venta->est_ven_codigo=11;
                }
                $venta->updated_at = now();
                $venta->save();
                foreach($miarray as $key => $item){
                    if($item){
                        $dventa = DetalleVentas::where('ven_codigo', $request->factura)
                        ->where('id_empresa', $request->remi_idempresa)
                        ->where('pro_codigo', $item)
                        ->firstOrFail();
                        if (!$dventa){
                            return "El det_ven_codigo no existe en la base de datos";
                        }
                        $dventa->det_ven_cantidad_despacho=0;
                        $dventa->save();
                    }
                }
            DB::commit();
            return response()->json(['message' => 'Empaque anulado con éxito',"data" => $venta], 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'Error al anular el empaque'.$e], 500);
            DB::rollback();
        }
    }
    public function despacharEmpaque($request){
        if($request->op==0){
            try{
                set_time_limit(6000000);
                ini_set('max_execution_time', 6000000);
    
                DB::beginTransaction();
                    $remision = Remision::where('remi_codigo', $request->remi_codigo)
                    ->where('remi_idempresa', $request->remi_idempresa)
                    ->where('remi_num_factura', $request->factura)
                    ->firstOrFail();
                    $remision->remi_guia_remision =  $request->guiaremision;
                    if($request->archivo)           { $remision->archivo = null; }
                    if($request->url)               { $remision->url     = null; }
                    
                    $remision->remi_fecha_final = now();
                    $remision->updated_at = now();
                    $remision->remi_estado= 2;
                    $remision->save();
                    $emp                = _1_4_Empacado::where('remi_codigo', $request->remi_codigo)
                    ->where('empa_codigo', $request->empa_codigo)
                    ->where('empa_facturas', $request->factura)->firstOrFail();
                    $emp->updated_at        = now();
                    $emp->empa_estado       = 2;
                    $emp->save();
                    $venta =  Ventas::where('ven_codigo', $request->factura)
                    ->where('id_empresa', $request->remi_idempresa)
                    ->firstOrFail();
                    if($request->estado==1){
                        $venta->est_ven_codigo=1;
                    }
                    $venta->updated_at = now();
                    $venta->save();
                DB::commit();
                return response()->json(['message' => 'Empaque finalizado con éxito',"data" => $remision], 200);
            }catch(\Exception $e){
                return response()->json(['message' => 'Error al anular el empaque'.$e], 500);
                DB::rollback();
            }
        } else if($request->op==1){
            try{
                set_time_limit(6000000);
                ini_set('max_execution_time', 6000000);
    
                DB::beginTransaction();
                    $remision = Remision::where('remi_codigo', $request->remi_codigo)
                    ->where('remi_idempresa', $request->remi_idempresa)
                    ->where('remi_num_factura', $request->factura)
                    ->firstOrFail();
                    $remision->remi_fecha_final = now();
                    $remision->updated_at = now();
                    $remision->remi_estado= 2;
                    $remision->save();
                    $emp                = _1_4_Empacado::where('remi_codigo', $request->remi_codigo)
                    ->where('empa_codigo', $request->empa_codigo)
                    ->where('empa_facturas', $request->factura)->firstOrFail();
                    $emp->updated_at        = now();
                    $emp->empa_estado       = 2;
                    $emp->save();
                    $venta =  Ventas::where('ven_codigo', $request->factura)
                    ->where('id_empresa', $request->remi_idempresa)
                    ->firstOrFail();
                    if($request->estado==1){
                        $venta->est_ven_codigo=$request->estado;
                    }
                    $venta->updated_at = now();
                    $venta->save();
                DB::commit();
                return response()->json(['message' => 'Empaque finalizado con éxito',"data" => $remision], 200);
            }catch(\Exception $e){
                return response()->json(['message' => 'Error al finalizar el empaque'.$e], 500);
                DB::rollback();
            }
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
