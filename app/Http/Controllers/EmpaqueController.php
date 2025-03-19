<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\_1_4_Empacado;
use App\Models\EmpaqueDetalle;
use App\Models\DetalleVentas;
use App\Models\EmpacadoRemision;
use App\Models\EmpacadoRempacado;
use App\Models\EmpacadoRempacadoDetalle;
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
        if($request->getEmpaqueBodega)          { return $this->getEmpaque();}
        if($request->getDetalleEmpaque)         { return $this->getDetalleEmpaque($request); }
        if($request->pendientes)                { return $this->pendientes($request); }
        if($request->getchofer)                 { return $this->getchofer(); }
        if($request->getventa)                  { return $this->getventa($request); }
    }
    //api/GET/empaqueBodega?pendientes=1&individual=PF-C-C25-FR-0000606
    public function pendientes(Request $request)
    {
        $todos      = $request->todos;
        $fecha      = $request->fecha;
        $individual = $request->individual;
        // Construimos la consulta base
        $queryPadre = DB::table('empacado_remision as r')
            ->leftJoin('empresas as e', 'e.id', '=', 'r.remi_idempresa')
            ->leftJoin('empacado_rempacado as rem', 'rem.remision_id', '=', 'r.id')
            ->select(
                'r.*', 
                'e.descripcion_corta', 
                'rem.empa_codigo', 
                'rem.empa_fecha', 
                'rem.empa_facturas',
                'rem.empa_estado',
                'rem.empa_libros',
                'rem.empa_cartones',
                'rem.idempresa',
                'rem.id as idRempacado'
            );
        if($todos){
            $queryPadre->where('r.remi_estado', '1')
            ->where('rem.empa_estado', '1');
        }
        
        // Condicional para aplicar la fecha si está presente
        if ($fecha) {
            $queryPadre->whereDate('rem.empa_fecha', '=', $fecha)
            ->where('r.remi_estado', '1')
            ->where('rem.empa_estado', '1');
        }
        if ($individual) {
            $queryPadre->where('r.remi_num_factura', $individual)
            ->where('r.remi_estado', '<>','0')
            ->where('rem.empa_estado','<>', '0');
        }
     
        // Ejecutar la consulta
        $queryPadre = $queryPadre->get();

        if(count($queryPadre) == 0){
            return $queryPadre;
        }

        foreach ($queryPadre as $key => $value) {
            // Detalle de empaque
            $detalleEmpaque = DB::table('empacado_rempacado_detalle')
                ->where('empacado_rempacado_id', $value->idRempacado)
                ->where('idempresa', $value->remi_idempresa)
                ->sum('cantidad');

            $value->cantidad = $detalleEmpaque;

            // Detalle de unidades libros de pre-factura
            $detalleLibros = DB::table('f_detalle_venta')
                ->where('ven_codigo', $value->empa_facturas)
                ->where('id_empresa', $value->remi_idempresa)
                ->select(DB::raw('sum(det_ven_cantidad_despacho) as libros'))
                ->get();
            if(count($detalleLibros) > 0){
                $value->libros = $detalleLibros[0]->libros;
            }else{
                $value->libros = 0;
            }

            // Encabezado de la pre-factura
            $prefactura = DB::table('f_venta')
                ->leftJoin('institucion', 'institucion.idInstitucion', '=', 'f_venta.institucion_id')
                ->leftJoin('usuario', 'usuario.idusuario', '=', 'f_venta.ven_cliente')
                ->where('ven_codigo', $value->empa_facturas)
                ->where('id_empresa', $value->remi_idempresa)
                ->select(
                    'institucion.ruc', 
                    'institucion.email', 
                    'institucion.nombreInstitucion',
                    'f_venta.est_ven_codigo',
                    DB::raw("COALESCE(CONCAT_WS(' ', usuario.nombres, usuario.apellidos), 'Sin Cliente') AS cliente")
                )
                ->first();

            // Asignamos los valores a $value
            if ($prefactura) {
                $value->nombreInstitucion = $prefactura->nombreInstitucion;
                $value->cliente = $prefactura->cliente;
                $value->est_ven_codigo = $prefactura->est_ven_codigo;
            } else {
                $value->nombreInstitucion = null;
                $value->cliente = 'Sin Cliente';
            }
        }

        return $queryPadre;
    }

    public function getchofer()
    {
        $query = DB::SELECT("SELECT cedula, CONCAT(nombres,' ',apellidos) AS responsable  FROM usuario
        where (id_group=34 OR id_group=17 OR id_group=27) and estado_idEstado=1");
        return $query;
    }
    public function getventa(Request $request)
    {
        $query = DB::SELECT("SELECT sum(fv.det_ven_cantidad) AS cant,  sum(fv.det_ven_cantidad_despacho) AS desp FROM
        f_detalle_venta fv
        where fv.ven_codigo='$request->ven_codigo' and fv.id_empresa=$request->id ");
        return $query;
    }
    public function getDetalleEmpaque(Request $request)
    {
        $query = DB::SELECT("SELECT de.id as det_empa_codigo, de.tip_empa_codigo as id, 
        te.tip_empa_nombre as nombre, de.cantidad
        FROM empacado_rempacado_detalle de
        INNER JOIN 1_4_tipo_empaque te ON te.tip_empa_codigo=de.tip_empa_codigo
        WHERE de.empacado_rempacado_id='$request->empacado_rempacado_id'
        and de.idempresa=$request->id_empresa");
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
        if($request->saveEmpaque)       { return $this->saveEmpaque($request); }
        if($request->anulaEmpaque)      { return $this->anulaEmpaque($request); }
        if($request->despacharEmpaque)  { return $this->despacharEmpaque($request); }
        if($request->EditEmpaque)       { return $this->EditEmpaque($request); }
    }

    //API:POST/empaqueBodega?saveEmpaque=1
    // public function saveEmpaque($request){
    //     try{
    //         set_time_limit(6000000);
    //         ini_set('max_execution_time', 6000000);
    //         $array=json_decode($request->detalle);
    //         DB::beginTransaction();
    //         //REGISTRO DE REMISION
    //             $remision = new Remision;
    //             $remision->remi_codigo                  = $request->remi_codigo;
    //             $remision->remi_idempresa               = $request->remi_idempresa;
    //             $remision->remi_motivo = $request->remi_motivo;
    //             $remision->remi_dir_partida = $request->remi_dir_partida; 
    //             $remision->remi_destinatario = $request->remi_destinatario;
    //             $remision->remi_ruc_destinatario = $request->remi_ruc_destinatario;
    //             $remision->remi_direccion = $request->remi_direccion;
    //             $remision->remi_nombre_transportista = $request->remi_nombre_transportista;
    //             $remision->remi_ci_transportista = $request->remi_ci_transportista;
    //             $remision->remi_detalle = $request->remi_detalle;
    //             $remision->remi_num_factura = $request->empa_facturas;
    //             $remision->remi_fecha_inicio = now();
    //             $remision->trans_codigo = $request->trans_codigo;
    //             $remision->remi_obs = $request->remi_obs;
    //             $remision->remi_responsable = $request->remi_responsable;
    //             $remision->remi_carton = (int)$request->remi_carton;
    //             $remision->remi_paquete = (int)$request->remi_paquete;
    //             $remision->remi_funda = (int)$request->remi_funda;
    //             $remision->remi_rollo = (int)$request->remi_rollo;
    //             $remision->remi_flete = (int)$request->remi_flete;
    //             $remision->remi_pagado = (int)$request->remi_pagado;
    //             $remision->user_created = $request->user_created;
    //             $remision->created_at = now();
    //             $remision->updated_at = now();
    //             $remision->save();

    //             //ACTUALIZA EL TIPO DE DOCUMENTO REMISION Y EMPAQUE
    //             if($request->remi_idempresa==1){
    //                 $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from f_tipo_documento where tdo_letra='D'");
    //                 $query2 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from f_tipo_documento where tdo_letra='E'");
    //             }else if($request->remi_idempresa==3){
    //                 $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from f_tipo_documento where tdo_letra='D'");
    //                 $query2 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from f_tipo_documento where tdo_letra='E'");
    //             }
    //             if(!empty($query1)){
    //                 $id=$query1[0]->id;
    //                 $codi=$query1[0]->cod;
    //                 $co=(int)$codi+1;
    //                 $tipo_doc = f_tipo_documento::findOrFail($id);
    //                 if($request->remi_idempresa==1){
    //                     $tipo_doc->tdo_secuencial_Prolipa = $co;
    //                 }else if($request->remi_idempresa==3){
    //                     $tipo_doc->tdo_secuencial_calmed = $co;
    //                 }
    //                 $tipo_doc->save();
    //             }
    //             if(!empty($query2)){
    //                 $ide=$query2[0]->id;
    //                 $codig=$query2[0]->cod;
    //                 $cod=(int)$codig+1;
    //                 $tipo_doc = f_tipo_documento::findOrFail($ide);
    //                 if($request->remi_idempresa==1){
    //                     $tipo_doc->tdo_secuencial_Prolipa = $cod;
    //                 }else if($request->remi_idempresa==3){
    //                     $tipo_doc->tdo_secuencial_calmed = $cod;
    //                 }
    //                 $tipo_doc->save();
    //             }
    //             // VENTA ESTADO
    //             $venta = Ventas::where('ven_codigo', $request->empa_facturas)
    //             ->where('id_empresa', $request->remi_idempresa)
    //             ->firstOrFail();
    //             $venta->est_ven_codigo=$request->estado;
    //             $venta->save();
                
    //             //EMPACADO
    //             $emp = _1_4_Empacado::where('empa_codigo', $request->id_emp)
    //             ->where('idempresa', $request->remi_idempresa)
    //             ->first();


    //             if(!$emp){
    //                 $emp                = new _1_4_Empacado();
    //                 $emp->empa_codigo       = $request->id_emp;
    //                 $emp->idempresa         = $request->remi_idempresa;
    //                 $emp->empa_fecha        = now();
    //                 $emp->empa_libros       = $request->libros;
    //                 $emp->empa_facturas     = $request->empa_facturas;
    //                 $emp->empa_cartones     = $request->empa_cartones;
    //                 $emp->usu_codigo        = $request->usu_codigo;
    //                 $emp->remi_codigo       = $request->remi_codigo;
    //                 $emp->user_created      = $request->user_created;
    //                 $emp->save();

    //             }else{
    //                 return response()->json(['message' => 'Empaque no creado'], 404);
    //             }

    //             $emp->empa_codigo       = $request->id_emp;
    //             $emp->idempresa         = $request->remi_idempresa;
    //             $emp->empa_fecha        = now();
    //             $emp->empa_libros       = $request->libros;
    //             $emp->empa_facturas     = $request->empa_facturas;
    //             $emp->empa_cartones     = $request->empa_cartones;
    //             $emp->usu_codigo        = $request->usu_codigo;
    //             $emp->remi_codigo       = $request->remi_codigo;
    //             $emp->user_created      = $request->user_created;
    //             // $emp->tipo              = $request->tipo;
    //             $emp->save();
    //             //DETALLE DEL EMPACADO
    //             $cont=1;
    //             foreach($array as $key => $iten){
    //                 for ($i=0; $i < $iten->cantidad; $i++) { 
    //                     $detalle = new EmpaqueDetalle();
    //                     $detalle->det_empa_codigo = $request->id_emp.'-'.$cont;
    //                     $detalle->empa_codigo=$request->id_emp;
    //                     $detalle->idempresa=$request->remi_idempresa;
    //                     $detalle->tip_empa_codigo=$iten->id;
    //                     $detalle->dete_estado=1;
    //                     $detalle->created_at=now();
    //                     $detalle->updated_at=now();
    //                     $detalle->user_created=$request->user_created;
    //                     $detalle->save();
    //                     $cont++;
    //                 }
    //             }
    //         //actualizar el estado de despacho
    //         $this->despachar($request);
    //         DB::commit();
    //         return response()->json(['message' => 'Empaque creado con éxito',"data" => $remision], 200);
    //     }catch(\Exception $e){
    //         return response()->json(['message' => 'Error al crear el empaque'.$e], 500);
    //         DB::rollback();
    //     }
    // }
    public function saveEmpaque($request)
    {
        try {
            set_time_limit(120); // Máximo 2 minutos
            ini_set('max_execution_time', 120);
    
            $arrayEmpaquesSugeridos = json_decode($request->arrayEmpaquesSugeridos);
            DB::beginTransaction();
    
            $remi_codigo = "";
            $emp_codigo = "";
    
            // IDs de los documentos
            $idDocumentoEmpaque = 8; // EMPACADO (E)
            $idDocumentoRemision = 9; // REMISIÓN (D)
    
            // Generar códigos con el orden correcto
            $emp_codigo = $this->empaqueRepository->actualizarSecuencia($idDocumentoEmpaque, $request->id_empresa, 'E'); // EMPACADO
            $remi_codigo = $this->empaqueRepository->actualizarSecuencia($idDocumentoRemision, $request->id_empresa, 'D'); // REMISIÓN
    
            // Validación para evitar errores si la secuencia no se genera
            if (!$remi_codigo || !$emp_codigo) {
                return response()->json(['error' => 'No se pudo generar la secuencia del documento'], 200);
            }
    
            // REGISTRO DE REMISIÓN
            $remision = new EmpacadoRemision([
                'remi_codigo'                   => $remi_codigo,
                'remi_idempresa'                => $request->id_empresa,
                'remi_motivo'                   => $request->remi_motivo,
                'remi_dir_partida'              => $request->remi_dir_partida,
                'remi_destinatario'             => $request->remi_destinatario,
                'remi_ruc_destinatario'         => $request->remi_ruc_destinatario,
                'remi_direccion'                => $request->remi_direccion,
                'remi_nombre_transportista'     => $request->remi_nombre_transportista,
                'remi_ci_transportista'         => $request->remi_ci_transportista,
                'remi_detalle'                  => $request->remi_detalle,
                'remi_num_factura'              => $request->ven_codigo,
                'remi_fecha_inicio'             => now(),
                'trans_codigo'                  => $request->trans_codigo,
                'remi_obs'                      => $request->remi_obs,
                'remi_responsable'              => $request->remi_responsable,
                'remi_carton'                   => (int) $request->remi_carton,
                'remi_paquete'                  => (int) $request->remi_paquete,
                'remi_funda'                    => (int) $request->remi_funda,
                'remi_rollo'                    => (int) $request->remi_rollo,
                'remi_flete'                    => (int) $request->remi_flete,
                'remi_pagado'                   => (int) $request->remi_pagado,
                'user_created'                  => $request->user_created,
            ]);
            $remision->save();
            
            if (!$remision->save()) {
                return response()->json(["status" => "0", "message" => "Error al guardar la remisión"], 200);
            }

            // VENTA ESTADO
            $venta = Ventas::where('ven_codigo', $request->ven_codigo)
                ->where('id_empresa', $request->id_empresa)
                ->firstOrFail();
            $venta->est_ven_codigo = $request->estado;
            $venta->save();
    
            // EMPACADO
            $emp = EmpacadoRempacado::firstOrNew([
                'remision_id'   => $remision->id,
                'idempresa'     => $request->id_empresa,
            ]);
            $emp->empa_codigo   = $emp_codigo;
            $emp->empa_fecha    = now();
            $emp->empa_libros   = $request->libros;
            $emp->empa_facturas = $request->ven_codigo;
            $emp->empa_cartones = $request->empa_cartones;
            $emp->usu_codigo    = $request->usu_codigo;
            $emp->user_created  = $request->user_created;
            $emp->save();

            if (!$emp->save()) {
                return response()->json(["status" => "0", "message" => "Error al guardar el empacado"], 200);
            }
    
            // DETALLE DEL EMPACADO
            foreach ($arrayEmpaquesSugeridos as $item) {
                $detalle = new EmpacadoRempacadoDetalle();
                $detalle->empacado_rempacado_id     = $emp->id;
                $detalle->idempresa                 = $request->id_empresa;
                $detalle->tip_empa_codigo           = $item->id;
                $detalle->dete_estado               = 1;
                $detalle->cantidad                  = $item->cantidad;  // Aquí es donde se guarda la cantidad correctamente
                $detalle->user_created              = $request->user_created;
            
                // Intentamos guardar el detalle
                if (!$detalle->save()) {
                    // Si no se guarda, regresamos un error
                    return response()->json(['status' => '0', 'message' => 'Error al guardar el detalle de empaque.'], 200);
                }
            }

            // Actualizar el estado de despacho
            $this->despachar($request);
    
            DB::commit();
            return response()->json(['status' => '1', 'message' => 'Empaque creado con éxito', "data" => $remision], 200);
        } catch (\Exception $e) {
            DB::rollback(); // 🔥 Ahora se ejecuta correctamente
            return response()->json(['status' => '0', 'message' => 'Error al crear el empaque: ' . $e->getMessage()], 200);
        }
    }
    
     //cambio ingereso de datos de libros a excluir o despachar
     public function despachar($request) {
        try {
            set_time_limit(600);
            ini_set('max_execution_time', 600);

            $miarray = json_decode($request->arrayLibros);

            // Verifica si el JSON fue decodificado correctamente
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON inválido');
            }

            DB::beginTransaction();

            // Busca la venta en la tabla f_ventas
            $venta = DB::table('f_venta')
                       ->where('ven_codigo', $request->ven_codigo)
                       ->where('id_empresa', $request->id_empresa)
                       ->first();

            if (!$venta) {
                throw new \Exception('El ven_codigo no existe en la base de datos');
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
                        throw new \Exception('El det_ven_codigo no existe en la base de datos');
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
            throw new \Exception('No se pudo preparar'.$e->getMessage());
        }
    }
    public function EditEmpaque($request){
        try {
            set_time_limit(120); // Máximo 2 minutos
            ini_set('max_execution_time', 120);
            $arrayEmpaquesSugeridos = json_decode($request->arrayEmpaquesSugeridos);
            DB::beginTransaction();
            
            // REGISTRO DE REMISION
            try {
                $remision = EmpacadoRemision::where('id', $request->id)
                    ->where('remi_idempresa', $request->id_empresa)
                    ->firstOrFail();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return response()->json(['status' => '0', 'message' => 'No existe empacado con ese codigo'], 200);
            }
    
            // Asignar manualmente los valores y luego guardar
            $remision->remi_direccion               = $request->remi_direccion;
            $remision->remi_nombre_transportista    = $request->remi_nombre_transportista;
            $remision->remi_ci_transportista        = $request->remi_ci_transportista;
            $remision->trans_codigo                 = $request->trans_codigo;
            $remision->remi_obs                     = $request->remi_obs;
            $remision->remi_carton                  = $request->remi_carton;
            $remision->remi_paquete                 = $request->remi_paquete;
            $remision->remi_funda                   = $request->remi_funda;
            $remision->remi_rollo                   = $request->remi_rollo;
            $remision->remi_flete                   = $request->remi_flete;
            $remision->remi_pagado                  = $request->remi_pagado;
            $remision->user_edit                    = $request->user_created;
            
            // Guardar el modelo remision
            if (!$remision->save()) {
                return response()->json(['status' => '0', 'message' => 'Error al guardar la remisión'], 200);
            }
    
            // VENTA ESTADO
            $venta = Ventas::where('ven_codigo', $request->ven_codigo)
                ->where('id_empresa', $request->id_empresa)
                ->firstOrFail();
            
            $venta->est_ven_codigo = $request->estado;
            $venta->save();
    
            // EMPACADO
            $emp = EmpacadoRempacado::where('remision_id', $remision->id)
                ->where('idempresa', $request->id_empresa)
                ->firstOrFail();
    
            $emp->empa_cartones = $request->empa_cartones;
            $emp->empa_libros   = $request->libros;
            $emp->save();
    
            // EMPACADO DETALLE
            EmpacadoRempacadoDetalle::where('empacado_rempacado_id', $remision->id)
                ->where('idempresa', $request->id_empresa)
                ->delete();
    
            // DETALLE DEL EMPACADO
            foreach ($arrayEmpaquesSugeridos as $item) {
                $detalle                        = new EmpacadoRempacadoDetalle();
                $detalle->empacado_rempacado_id = $emp->id;
                $detalle->idempresa             = $request->id_empresa;
                $detalle->tip_empa_codigo       = $item->id;
                $detalle->dete_estado           = 1;
                $detalle->cantidad              = $item->cantidad;  // Aquí es donde se guarda la cantidad correctamente
                $detalle->user_created          = $request->user_created;
    
                // Intentamos guardar el detalle
                if (!$detalle->save()) {
                    // Si no se guarda, regresamos un error
                    return response()->json(['status' => '0', 'message' => 'Error al guardar el detalle de empaque.'], 200);
                }
            }
    
            // Actualizar el estado de despacho
            $this->despachar($request);
    
            DB::commit();
    
            return response()->json(['message' => 'Empaque editado con éxito', "data" => $remision], 200);
    
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Error al editar el empaque: ' . $e->getMessage()], 200);
        }
    }
    

    public function anulaEmpaque($request){
        try{
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $miarray = json_decode($request->libros);
            DB::beginTransaction();

                //REMISION
                $remision = EmpacadoRemision::where('id', $request->id)
                ->where('remi_idempresa', $request->remi_idempresa)
                ->firstOrFail();
                $remision->remi_estado= 0;
                $remision->save();
                if(!$remision){
                    return ["status" => "0", "message" => "No se encontro la remision"];
                }
                
                //EMPACADO
                $emp                = EmpacadoRempacado::where('remision_id', $request->id)->where('idempresa', $request->remi_idempresa)->firstOrFail();
                $emp->empa_estado   = 0;
                $emp->save();
                if(!$emp){
                    return ["status" => "0", "message" => "No se encontro el empacado remision"];
                }

                //VENTAS
                $venta =  Ventas::where('ven_codigo', $request->factura)
                ->where('id_empresa', $request->remi_idempresa)
                ->firstOrFail();
                if(!$venta){
                    return ["status" => "0", "message" => "No se encontro el documento de venta"];
                }
                //DETALLE VENTAS
                // $query=DB::SELECT("SELECT 
                // SUM(det_ven_cantidad) as cantidadTotal, SUM(det_ven_cantidad_despacho) as totalEmpacado
                // FROM f_detalle_venta dv 
                // WHERE dv.ven_codigo='$request->factura'
                // AND dv.id_empresa=$request->remi_idempresa
                // ");
                // //si es igual dejar como pendiente
                // $cantidadTotal = $query[0]->cantidadTotal;
                // $totalEmpacado = $query[0]->totalEmpacado;
                // if($cantidadTotal==$totalEmpacado){
                //     $venta->est_ven_codigo=2;
                // }
                // //si no es igual dejar como excluido 
                // else{
                //     $venta->est_ven_codigo=11;
                // }
                $venta->est_ven_codigo=2;
                $venta->updated_at    = now();
                $venta->save();
                foreach($miarray as $key => $item){
                    if($item){
                        $dventa = DetalleVentas::where('ven_codigo', $request->factura)
                        ->where('id_empresa', $request->remi_idempresa)
                        ->where('pro_codigo', $item)
                        ->firstOrFail();
                        if (!$dventa){
                            return ["status" => "0", "message" => "El documento de ventano existe en la base de datos"];
                        }
                        $dventa->det_ven_cantidad_despacho=0;
                        $dventa->save();
                    }
                }
            DB::commit();
            return ["status" => "1", "message" => "Empaque anulado con éxito"];
        }catch(\Exception $e){
            return ["status" => "0", "message" => "Error al anular el empaque".$e->getMessage()];
            DB::rollback();
        }
    }
    public function despacharEmpaque($request){
        if($request->op==0){
            try {
                set_time_limit(600);  // Mejor limitar el tiempo de ejecución a un valor razonable.
                ini_set('max_execution_time', 600);  // Igualmente, mejor mantenerlo en un límite razonable.
            
                DB::beginTransaction();
            
                $remision = EmpacadoRemision::where('id', $request->id)
                    ->where('remi_idempresa', $request->remi_idempresa)
                    ->where('remi_num_factura', $request->factura)
                    ->firstOrFail();
            
                // Actualización solo si hay cambios
                $remision->remi_guia_remision = $request->guiaremision;
                if ($request->archivo) {
                    $remision->archivo = null;
                }
                if ($request->url) {
                    $remision->url = null;
                }
                $remision->remi_fecha_final = now();
                $remision->remi_estado      = 2;
            
                // Solo guardar si hay cambios
                if ($remision->isDirty()) {
                    $remision->save();
                }
            
                $emp = EmpacadoRempacado::where('remision_id', $request->id)
                    ->where('empa_facturas', $request->factura)
                    ->firstOrFail();
            
                $emp->empa_estado = 2;
            
                // Solo guardar si hay cambios
                if ($emp->isDirty()) {
                    $emp->save();
                }
            
                DB::commit();
            
                return response()->json(['message' => 'Empaque finalizado con éxito', "data" => $remision], 200);
            
            } catch (\Exception $e) {
                DB::rollback();  // Asegúrate de hacer rollback en caso de error
                return response()->json(['message' => 'Error al anular el empaque.'], 200);
            }
            
        } else if($request->op==1){
            try {
                set_time_limit(600);  // Mejor limitar el tiempo de ejecución a un valor razonable.
                ini_set('max_execution_time', 600);  // Igualmente, mejor mantenerlo en un límite razonable.
            
                DB::beginTransaction();
            
                // Buscar la remisión
                $remision = EmpacadoRemision::where('id', $request->id)
                    ->where('remi_idempresa', $request->remi_idempresa)
                    ->where('remi_num_factura', $request->factura)
                    ->first();
            
                // Validar que la remisión exista
                if (!$remision) {
                    return ["status" => "0", "message" => "No se encontro la remision"];
                }
            
                // Actualizar la remisión
                $remision->remi_fecha_final = now();
                $remision->remi_estado      = 2;
                $remision->save();
            
                // Buscar el empacado
                $emp = EmpacadoRempacado::where('remision_id', $request->id)
                    ->where('empa_facturas', $request->factura)
                    ->first();
            
                // Validar que el empacado exista
                if (!$emp) {
                    return ["status" => "0", "message" => "No se encontro el reempacado remision"];
                }
            
                // Actualizar el empacado
                $emp->empa_estado = 2;
                $emp->save();
                if(!$emp){
                    return ["status" => "0", "message" => "No se encontro el reempacado remision"];
                }
                // Confirmar la transacción
                DB::commit();
            
                // Responder con éxito
                return response()->json(['message' => 'Empaque finalizado con éxito', "data" => $remision], 200);
            
            } catch (\Exception $e) {
                // Deshacer la transacción en caso de error
                DB::rollback();
                return response()->json(['message' => 'Error al finalizar el empaque: ' . $e->getMessage()], 200);
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
