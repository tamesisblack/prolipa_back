<?php

namespace App\Http\Controllers;

use DB;
use App\Models\CodigoLibros;
use Illuminate\Http\Request;
use App\Models\CodigosLibros;
use App\Models\CodigosPaquete;
use App\Models\HistoricoPaquetes;
use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Traits\Codigos\TraitCodigosGeneral;
use App\Repositories\Codigos\CodigosRepository;
use App\Repositories\Codigos\PaquetesRepository;
class PaqueteController extends Controller
{
    use TraitCodigosGeneral;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Respons
     */
    private $paqueteRepository;
    private $codigosRepository;
    public function __construct(PaquetesRepository $paqueteRepository ,CodigosRepository $codigosRepository) {
        $this->paqueteRepository = $paqueteRepository;
        $this->codigosRepository = $codigosRepository;
    }
    //api:get/paquetes/paquetes
    public function index(Request $request)
    {
        if($request->traerConfiguracionPaquete){
            return $this->traerConfiguracionPaquete();
        }
    }
    public function traerConfiguracionPaquete(){
        $query = DB::SELECT("SELECT * FROM codigos_configuracion");
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
    public function getPaquete($paquete){
        $query = DB::SELECT("SELECT * FROM codigos_paquetes p
        WHERE p.codigo = '$paquete'
        AND p.estado   = '1'
        ");
        return $query;
    }
    public function getExistsPaquete($paquete){
        $query = DB::SELECT("SELECT * FROM codigos_paquetes p
        WHERE p.codigo = '$paquete'
        ");
        return $query;
    }
    //paquetes/guadarPaquete
    public function guardarPaquete(Request $request){
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);
        $miArrayDeObjetos               = json_decode($request->data_codigos);
        //variables
        $usuario_editor                 = $request->id_usuario;
        $institucion_id                 = 0;
        $periodo_id                     = $request->periodo_id;
        $arregloResumen                 = [];
        $contadorResumen                = 0;
        $codigoConProblemas             = collect();
        $arregloProblemaPaquetes        = [];
        $contadorErrPaquetes            = 0;
        //====PROCESO===================================
        foreach($miArrayDeObjetos as $key => $item){
            $problemasconCodigo         = [];
            $contadorProblemasCodigos   = 0;
            $ExistsPaquete              = [];
            $contadorA                  = 0;
            $contadorD                  = 0;
            $noExisteA                  = 0;
            $noExisteD                  = 0;
            //VALIDAR QUE EL CODIGO DE PAQUETE EXISTE
            $ExistsPaquete = $this->getPaquete(strtoupper($item->codigoPaquete));
            if(!empty($ExistsPaquete)){
                foreach($item->codigosHijos as $key2 => $tr){
                    $codigoActivacion       = strtoupper($tr->codigoActivacion);
                    $codigoDiagnostico      = strtoupper($tr->codigoDiagnostico);
                    $errorA                 = 1;
                    $errorD                 = 1;
                    $mensajeError           = "";
                    //validacion
                    $validarA               = $this->getCodigos($codigoActivacion,0);
                    $validarD               = $this->getCodigos($codigoDiagnostico,0);
                    $comentario             = "Se agrego al paquete ".strtoupper($item->codigoPaquete);
                    //======si ambos codigos existen========
                    if(count($validarA) > 0 && count($validarD) > 0){
                        //====Activacion=====
                        //validar que el codigo de paquete sea nulo
                        $ifcodigo_paqueteA           = $validarA[0]->codigo_paquete;
                        //codigo de union
                        $codigo_unionA               = strtoupper($validarA[0]->codigo_union);
                        //liquidado regalado
                        //======Diagnostico=====
                        //validar que el codigo de paquete sea nulo
                        $ifcodigo_paqueteD           = $validarD[0]->codigo_paquete;
                        //codigo de union
                        $codigo_unionD               = strtoupper($validarD[0]->codigo_union);
                        //===VALIDACION====
                        //error 0 => no hay error; 1 hay error
                        if($ifcodigo_paqueteA == null && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA  == null || $codigo_unionA == "" || $codigo_unionA == "0")) )    $errorA = 0;
                        if($ifcodigo_paqueteD == null && (($codigo_unionD == $codigoActivacion)  || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) )     $errorD = 0;
                        //===MENSAJE VALIDACION====
                        if($errorA == 1 && $errorD == 0) { $mensajeError = "Problema con el código de activación";  $codigoConProblemas->push($validarA); }
                        if($errorA == 0 && $errorD == 1) { $mensajeError = "Problema con el código de diagnóstico"; $codigoConProblemas->push($validarD); }
                        if($errorA == 1 && $errorD == 1) { $mensajeError = "Ambos códigos tienen problemas";        $codigoConProblemas->push($validarA); $codigoConProblemas->push($validarD);}
                        //SI AMBOS CODIGOS PASAN LA VALIDACION GUARDO
                        if($errorA == 0 && $errorD == 0){
                            $old_valuesA    = CodigosLibros::Where('codigo',$codigoActivacion)->get();
                            $ingresoA       = $this->updatecodigosPaquete(strtoupper($item->codigoPaquete),$codigoActivacion,$codigoDiagnostico);
                            $old_valuesD    = CodigosLibros::findOrFail($codigoDiagnostico);
                            $ingresoD       = $this->updatecodigosPaquete(strtoupper($item->codigoPaquete),$codigoDiagnostico,$codigoActivacion);
                            //si se guarda codigo de activacion
                            if($ingresoA){ $contadorA++; $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoActivacion,$usuario_editor,$comentario,$old_valuesA,null); }
                            //si se guarda codigo de diagnostico
                            if($ingresoD){ $contadorD++; $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoDiagnostico,$usuario_editor,$comentario,$old_valuesD,null); }
                            //colocar el paquete como utilizado
                            $this->changeUsePaquete($ExistsPaquete[0]->codigo);
                        }else{
                            //SI NO INGRESA ALGUNO DE LOS CODIGOS ENVIO AL FRONT
                            $problemasconCodigo[$contadorProblemasCodigos] = [
                                "codigo"            => $codigoActivacion,
                                "codigoUnion"       => $codigoDiagnostico,
                                "problema"          => $mensajeError
                            ];
                            $contadorProblemasCodigos++;
                        }
                    }
                    //====SI NO EXISTEN LOS CODIGOS==============
                    else{
                        if(empty($validarA)  && !empty($validarD)) { $noExisteA++;               $mensajeError = "Código de activación no existe";  }
                        if(!empty($validarA) && empty($validarD))  { $noExisteD++;               $mensajeError = "Código de diagnóstico no existe"; }
                        if(empty($validarA)  && empty($validarD))  { $noExisteA++; $noExisteD++; $mensajeError = "Ambos códigos no existen"; }
                        $problemasconCodigo[$contadorProblemasCodigos] = [
                            "codigo"            => $codigoActivacion,
                            "codigoUnion"       => $codigoDiagnostico,
                            "problema"          => $mensajeError
                        ];
                        $contadorProblemasCodigos++;
                    }
                }
                //codigos resumen
                $arregloResumen[$contadorResumen] = [
                    "codigoPaquete"     => $item->codigoPaquete,
                    "codigosHijos"      => $problemasconCodigo,
                    "mensaje"           => empty($ExistsPaquete) ? 1 : '0',
                    "ingresoA"          => $contadorA,
                    "ingresoD"          => $contadorD,
                    "noExisteA"         => $noExisteA,
                    "noExisteD"         => $noExisteD
                ];
                $contadorResumen++;
            }else{
                $getProblemaPaquete = $this->getExistsPaquete($item->codigoPaquete);
                $arregloProblemaPaquetes [$contadorErrPaquetes] = [
                    "paquete"   => $item->codigoPaquete,
                    "problema" => count($getProblemaPaquete) > 0 ? 'Paquete utilizado':'Paquete no existe'
                ];
                $contadorErrPaquetes++;
            }
        }
        if(count($codigoConProblemas) == 0){
            return [
                "arregloResumen"                   => $arregloResumen,
                "codigoConProblemas"               => [],
                "arregloErroresPaquetes"           => $arregloProblemaPaquetes,
            ];
        }else{
            return [
                "arregloResumen"                   => $arregloResumen,
                "codigoConProblemas"               => array_merge(...$codigoConProblemas->all()),
                "arregloErroresPaquetes"           => $arregloProblemaPaquetes,
            ];
        }
    }
    public function guardarPaquete2(Request $request){
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);
        $miArrayDeObjetos               = json_decode($request->data_codigos);
        //variables
        $usuario_editor                 = $request->id_usuario;
        $periodo_id                     = $request->periodo_id;
        $arregloProblemaPaquetes        = [];
        $arregloResumen                 = [];
        $codigoConProblemas             = collect();
        $contadorErrPaquetes            = 0;
        $contadorResumen                = 0;
        $institucion_id                 = 0;
        //====PROCESO===================================
        foreach($miArrayDeObjetos as $key => $item){
            //variables
            $problemasconCodigo         = [];
            $contadorProblemasCodigos   = 0;
            $contadorA                  = 0;
            $contadorB                  = 0;
            $noExisteA                  = 0;
            $noExisteB                  = 0;
            //VALIDAR QUE EL CODIGO DE PAQUETE EXISTE
            $ExistsPaquete = $this->getPaquete(strtoupper($item->codigoPaquete));
            if(!empty($ExistsPaquete)){
                foreach($item->codigosHijos as $key2 => $tr){
                    $codigoA                = strtoupper($tr->codigo);
                    $errorA                 = 1;
                    $errorB                 = 1;
                    $comentario             = "Se agrego al paquete ".strtoupper($item->codigoPaquete);
                    //validar si el codigo existe
                    $validarA               = CodigosLibros::Where('codigo',$codigoA)->get();
                    if(count($validarA) > 0){
                        $codigoB        =  strtoupper($validarA[0]->codigo_union);
                        $validarB       = CodigosLibros::Where('codigo',$codigoB)->get();
                        if(count($validarB) > 0){
                            //VARIABLES  PARA EL PROCESO
                            //validar que el codigo de paquete sea nulo
                            $ifcodigo_paqueteA           = $validarA[0]->codigo_paquete;
                            //codigo de union
                            $codigo_unionA               = strtoupper($validarA[0]->codigo_union);
                            //======Diagnostico=====
                            //validar que el codigo de paquete sea nulo
                            $ifcodigo_paqueteB           = $validarB[0]->codigo_paquete;
                            //codigo de union
                            $codigo_unionB               = strtoupper($validarB[0]->codigo_union);
                            //===VALIDACION====validarB
                            //error 0 => no hay error; 1 hay error
                            if($ifcodigo_paqueteA == null && (($codigo_unionA == $codigoB) || ($codigo_unionA  == null || $codigo_unionA == "" || $codigo_unionA == "0")) )    $errorA = 0;
                            if($ifcodigo_paqueteB == null && (($codigo_unionB == $codigoA)  || ($codigo_unionB == null || $codigo_unionB == "" || $codigo_unionB == "0")) )    $errorB = 0;
                            //===MENSAJE VALIDACION====
                            if($errorA == 1 && $errorB == 0) { $mensajeError = "Problema con el código de activación";  $codigoConProblemas->push($validarA); }
                            if($errorA == 0 && $errorB == 1) { $mensajeError = "Problema con el código de diagnóstico"; $codigoConProblemas->push($validarB); }
                            if($errorA == 1 && $errorB == 1) { $mensajeError = "Ambos códigos tienen problemas";        $codigoConProblemas->push($validarA); $codigoConProblemas->push($validarB);}
                            //SI AMBOS CODIGOS PASAN LA VALIDACION GUARDO
                            if($errorA == 0 && $errorB == 0){
                                $old_valuesA    = $validarA;
                                $ingresoA       = $this->updatecodigosPaquete(strtoupper($item->codigoPaquete),$codigoA,$codigoB);
                                $old_valuesB    = $validarB;
                                $ingresoB       = $this->updatecodigosPaquete(strtoupper($item->codigoPaquete),$codigoB,$codigoA);
                                //si se guarda codigo de activacion
                                if($ingresoA){ $contadorA++; $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoA,$usuario_editor,$comentario,$old_valuesA,null); }
                                //si se guarda codigo de diagnostico
                                if($ingresoB){ $contadorB++; $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoB,$usuario_editor,$comentario,$old_valuesB,null); }
                                //colocar el paquete como utilizado
                                $this->changeUsePaquete($ExistsPaquete[0]->codigo);
                            }else{
                                //SI NO INGRESA ALGUNO DE LOS CODIGOS ENVIO AL FRONT
                                $problemasconCodigo[$contadorProblemasCodigos] = [
                                    "codigo"            => $codigoA,
                                    "codigoUnion"       => $codigoB,
                                    "problema"          => $mensajeError
                                ];
                                $contadorProblemasCodigos++;
                            }
                        }else{
                            $noExisteB++;
                            $mensajeError = "No existe el código de union";
                            $problemasconCodigo[$contadorProblemasCodigos] = [
                                "codigo"            => $codigoA,
                                "codigoUnion"       => $codigoB,
                                "problema"          => $mensajeError
                            ];
                            $contadorProblemasCodigos++;
                        }
                    }else{
                        $noExisteA++;
                        $mensajeError = "No existe el código";
                        $problemasconCodigo[$contadorProblemasCodigos] = [
                            "codigo"            => $codigoA,
                            "codigoUnion"       => "",
                            "problema"          => $mensajeError
                        ];
                        $contadorProblemasCodigos++;
                    }
                }
                //codigos resumen
                $arregloResumen[$contadorResumen] = [
                    "codigoPaquete"     => $item->codigoPaquete,
                    "codigosHijos"      => $problemasconCodigo,
                    "mensaje"           => empty($ExistsPaquete) ? 1 : '0',
                    "ingresoA"          => $contadorA,
                    "ingresoD"          => $contadorB,
                    "noExisteA"         => $noExisteA,
                    "noExisteD"         => $noExisteB
                ];
                $contadorResumen++;
            }else{
                $getProblemaPaquete = $this->getExistsPaquete($item->codigoPaquete);
                $arregloProblemaPaquetes [$contadorErrPaquetes] = [
                    "paquete"   => $item->codigoPaquete,
                    "problema"  => count($getProblemaPaquete) > 0 ? 'Paquete utilizado':'Paquete no existe'
                ];
                $contadorErrPaquetes++;
            }
        }
        if(count($codigoConProblemas) == 0){
            return [
                "arregloResumen"                   => $arregloResumen,
                "codigoConProblemas"               => [],
                "arregloErroresPaquetes"           => $arregloProblemaPaquetes,
            ];
        }else{
            $getProblemas = [];
            $arraySinCorchetes = array_map(function ($item) { return json_decode(json_encode($item)); }, $codigoConProblemas->all());
            // return reset($arreglo);
            $getProblemas =  array_merge(...$arraySinCorchetes);
            // $preArray = (array)$codigoConProblemas->all();
            return [
                "arregloResumen"                   => $arregloResumen,
                "codigoConProblemas"               => $getProblemas,
                "arregloErroresPaquetes"           => $arregloProblemaPaquetes,
            ];
        }
    }
    public function changeUsePaquete($codigo){
        $paq = CodigosPaquete::findOrFail($codigo);
        $paq->estado = "0";
        $paq->save();
    }
    public function updatecodigosPaquete($codigoPaquete,$codigo,$codigo_union){
        $fecha = date("Y-m-d H:i:s");
        $codigo = DB::table('codigoslibros')
            ->where('codigo', '=', $codigo)
            // ->where('estado_liquidacion', '=', '1')
            // ->where('estado', '<>', '2')
            // ->whereNull('codigo_paquete')
            //(SE QUITARA PARA AHORA EL ESTUDIANTE YA ENVIA LEIDO) ->where('bc_estado', '=', '1')
            ->update([
                'codigo_paquete'            => $codigoPaquete,
                'fecha_registro_paquete'    => $fecha,
                'codigo_union'              => $codigo_union
            ]);
        return $codigo;
    }
    public function importPaqueteGestion(Request $request){
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);
        $miArrayDeObjetos           = json_decode($request->data_codigos);
        //variables
        $usuario_editor             = $request->id_usuario;
        $institucion_id             = 0;
        $periodo_id                 = $request->periodo_id;
        $arregloResumen             = [];
        $contadorResumen            = 0;
        $codigoConProblemas         = collect();
        $arregloProblemaPaquetes    = [];
        $contadorErrPaquetes        = 0;
        $tipoProceso                = $request->regalado;
        $factura                    = "";
        $obsevacion                 = $request->comentario;
        $tipoBodega                 = $request->tipoBodega;
        //====PROCESO===================================
        foreach($miArrayDeObjetos as $key => $item){
            $problemasconCodigo         = [];
            $contadorProblemasCodigos   = 0;
            $ExistsPaquete              = [];
            $contadorA                  = 0;
            $contadorD                  = 0;
            $noExisteA                  = 0;
            $noExisteD                  = 0;
            //estadoPaquete => 0 utilizado; 1 => abierto;
            $estadoPaquete              = 0;
            //VALIDAR QUE EL CODIGO DE PAQUETE ESTE UTILIZADO
            $ExistsPaquete      = $this->getPaquete($item->codigoPaquete);
            if(empty($ExistsPaquete)) { $estadoPaquete = 0; }
            //validar
            $getExistsPaquete   = $this->getExistsPaquete($item->codigoPaquete);
            if(!empty($getExistsPaquete)){
                //codigos hijos del paquete
                $codigosHijos =  $this->getCodigos($item->codigoPaquete,0,3);
                foreach($codigosHijos as $key2 => $tr){
                    $validarA               = [];
                    $codigoActivacion       = "";
                    $codigoDiagnostico      = "";
                    $codigoActivacion       = strtoupper($tr->codigo);
                    $codigoDiagnostico      = strtoupper($tr->codigo_union);
                    $errorA                 = 1;
                    $errorD                 = 1;
                    $mensajeError           = "";
                    //validacion
                    $validarA               = [$tr];
                    $validarD               = $this->getCodigos($codigoDiagnostico,0);
                    $comentario             = "Se agrego al paquete ".$item->codigoPaquete . " - " .$obsevacion;
                    //======si ambos codigos existen========
                    if(count($validarA) > 0 && count($validarD) > 0){
                        $validate = $this->paqueteRepository->validateGestion($tipoProceso,$estadoPaquete,$validarA,$validarD,$item,$codigoActivacion,$codigoDiagnostico,$request);
                        $errorA = $validate["errorA"]; $errorD = $validate["errorD"]; $factura = $validate["factura"];
                        //===MENSAJE VALIDACION====
                        if($errorA == 1 && $errorD == 0) { $mensajeError = "Problema con el código de activación";  $codigoConProblemas->push($validarA); }
                        if($errorA == 0 && $errorD == 1) { $mensajeError = "Problema con el código de diagnóstico"; $codigoConProblemas->push($validarD); }
                        if($errorA == 1 && $errorD == 1) { $mensajeError = "Ambos códigos tienen problemas";        $codigoConProblemas->push($validarA); $codigoConProblemas->push($validarD);}
                        //SI AMBOS CODIGOS PASAN LA VALIDACION GUARDO
                        if($errorA == 0 && $errorD == 0){
                            $old_valuesA    = CodigosLibros::Where('codigo',$codigoActivacion)->get();
                            $old_valuesD    = CodigosLibros::findOrFail($codigoDiagnostico);
                            //tipoProceso => 0 = Usan y liquidan; 1 =  regalado; 2 = regalado y bloqueado ; 3 = bloqueado ; 4 = guia; 5 = regalado sin institucion ; 6 = bloqueado y regalado sin institucion; 7 = bloqueado sin institucion ; 8 =  guia sin institucion
                            $ingreso = $this->paqueteRepository->procesoGestionBodega($tipoProceso,$codigoActivacion,$codigoDiagnostico,$request,$factura,$item->codigoPaquete,$tipoBodega);
                            //si se guarda codigo de activacion
                            if($ingreso == 1){
                                $contadorA++;
                                $contadorD++;
                                //====CODIGO====
                                //ingresar en el historico codigo
                                $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoActivacion,$usuario_editor,$comentario,$old_valuesA,null);
                                 //====CODIGO UNION=====
                                $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoDiagnostico,$usuario_editor,$comentario,$old_valuesD,null);
                                //colocar el paquete como utilizado
                                $this->changeUsePaquete($getExistsPaquete[0]->codigo);
                            }else{
                                //SI NO INGRESA ALGUNO DE LOS CODIGOS ENVIO AL FRONT
                                $problemasconCodigo[$contadorProblemasCodigos] = [
                                    "codigoActivacion"  => $codigoActivacion,
                                    "codigoDiagnostico" => $codigoDiagnostico,
                                    "problema"          => "No se pudo guardar"
                                ];
                                $contadorProblemasCodigos++;
                            }
                        }else{
                            //SI NO INGRESA ALGUNO DE LOS CODIGOS ENVIO AL FRONT
                            $problemasconCodigo[$contadorProblemasCodigos] = [
                                "codigoActivacion"  => $codigoActivacion,
                                "codigoDiagnostico" => $codigoDiagnostico,
                                "problema"          => $mensajeError
                            ];
                            $contadorProblemasCodigos++;
                        }
                    }
                    //====SI NO EXISTEN LOS CODIGOS==============
                    else{
                        if(empty($validarA)  && !empty($validarD)) { $noExisteA++;               $mensajeError = "Código de activación no existe";  }
                        if(!empty($validarA) && empty($validarD))  { $noExisteD++;               $mensajeError = "Código de diagnóstico no existe"; }
                        if(empty($validarA)  && empty($validarD))  { $noExisteA++; $noExisteD++; $mensajeError = "Ambos códigos no existen"; }
                        $problemasconCodigo[$contadorProblemasCodigos] = [
                            "codigoActivacion"  => $codigoActivacion,
                            "codigoDiagnostico" => $codigoDiagnostico,
                            "problema"          => $mensajeError
                        ];
                        $contadorProblemasCodigos++;
                    }
                }
                //codigos resumen
                $arregloResumen[$contadorResumen] = [
                    "codigoPaquete"     => $item->codigoPaquete,
                    "codigosHijos"      => $problemasconCodigo,
                    "mensaje"           => empty($getExistsPaquete) ? 1 : '0',
                    "ingresoA"          => $contadorA,
                    "ingresoD"          => $contadorD,
                    "noExisteA"         => $noExisteA,
                    "noExisteD"         => $noExisteD
                ];
                $contadorResumen++;
            }else{
                $getProblemaPaquete = $this->getExistsPaquete($item->codigoPaquete);
                $arregloProblemaPaquetes [$contadorErrPaquetes] = [
                    "paquete"   => $item->codigoPaquete,
                    "problema" => count($getProblemaPaquete) > 0 ? 'Paquete utilizado':'Paquete no existe'
                ];
                $contadorErrPaquetes++;
            }
        }
        if(count($codigoConProblemas) == 0){
            return [
                "arregloResumen"                   => $arregloResumen,
                "codigoConProblemas"               => [],
                "arregloErroresPaquetes"           => $arregloProblemaPaquetes,
            ];
        }else{
            return [
                "arregloResumen"                   => $arregloResumen,
                "codigoConProblemas"               => array_merge(...$codigoConProblemas->all()),
                "arregloErroresPaquetes"           => $arregloProblemaPaquetes,
            ];
        }
    }

    public function store(Request $request)
    {
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);
        $codigos                            = explode(",", $request->codigo);
        $porcentajeAnterior                 = 0;
        $codigosNoIngresadosAnterior        = [];
        //only codigos
        $resultado                          = $this->save_Codigos($request,$codigos);
        $porcentajeAnterior                 = $resultado["porcentaje"];
        $codigosNoIngresadosAnterior        = $resultado["codigosNoIngresados"];
        $codigosGuardados                   = $resultado["codigosGuardados"];
        return[
            "porcentajeAnterior"            => $porcentajeAnterior,
            "codigosNoIngresadosAnterior"   => $codigosNoIngresadosAnterior,
            "codigosGuardados"              => $codigosGuardados,
        ];
    }
    public function save_Codigos($request,$codigos){
        $tam                = sizeof($codigos);
        $porcentaje         = 0;
        $codigosError       = [];
        $codigosGuardados   = [];
        $contador           = 0;
        for( $i=0; $i<$tam; $i++ ){
            $codigos_libros                             = new CodigosPaquete();
            $codigos_libros->user_created               = $request->user_created;
            $codigo_verificar                           = $codigos[$i];
            $verificar_codigo  = $this->getExistsPaquete($codigo_verificar);
            if( count($verificar_codigo) > 0 ){
                $codigosError[$contador] = [
                    "codigo" =>  $codigo_verificar
                ];
                $contador++;
            }else{
                $codigos_libros->codigo = $codigos[$i];
                $codigos_libros->save();
                $codigosGuardados[$porcentaje] = [
                    "codigo" =>  $codigos[$i]
                ];
                $porcentaje++;
            }
        }
        return ["porcentaje" =>$porcentaje ,"codigosNoIngresados" => $codigosError,"codigosGuardados" => $codigosGuardados] ;
    }
    public function generarCodigosPaquete(Request $request){
        $resp_search            = array();
        $codigos_validacion     = array();
        $longitud               = $request->longitud;
        $code                   = $request->code;
        $cantidad               = $request->cantidad;
        $codigos = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $caracter   = $this->makeid($longitud);
            $codigo     = $code.$caracter;
            // valida repetidos en generacion
            $valida_gen = 1;
            $cant_int   = 0;
            while ( $valida_gen == 1 ) {
                $caracter = $this->makeid($longitud);
                $codigo = $code.$caracter;
                $valida_gen = 0;
                for( $k=0; $k<count($codigos_validacion); $k++ ){
                    if( $codigo == $codigos_validacion[$k] ){
                        array_push($resp_search, $codigo);
                        $valida_gen = 1;
                        break;
                    }
                }
                $cant_int++;
                if( $cant_int == 10 ){
                    $codigo = "no_disponible";
                    $valida_gen = 0;
                }
            }
            if( $codigo != 'no_disponible' ){
                // valida repetidos en DB
                $validar  = $this->getExistsPaquete($codigo);
                $cant_int = 0;
                $codigo_disponible = 1;
                while ( count($validar) > 0 ) {
                    // array_push($repetidos, $codigo);
                    $caracter = $this->makeid($longitud);
                    $codigo = $code.$caracter;
                    $validar  = $this->getExistsPaquete($codigo);
                    $cant_int++;
                    if( $cant_int == 10 ){
                        $codigo_disponible = 0;
                        $validar = ['repetido' => 'repetido'];
                    }
                }
                if( $codigo_disponible == 1 ){
                    array_push($codigos_validacion, $codigo);
                    array_push($codigos, ["codigo" => $codigo]);
                }
            }
        }
        return ["codigos" => $codigos, "repetidos" => $resp_search];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //api/get/paquetes/paquetes/id
    public function show($paquete)
    {
        $query = DB::SELECT("SELECT pq.*,
        CONCAT(u.nombres, ' ', u.apellidos) as editor
        FROM codigos_paquetes pq
        LEFT JOIN usuario u ON pq.user_created = u.idusuario
        WHERE pq.codigo LIKE '%$paquete%'
        ");
        $datos = [];
        foreach($query as $key => $item){
            $codigosPaquetes = [];
            $codigosPaquetes = $this->getCodigosXPaquete($item->codigo);
            $datos[$key] = [
                "paquete"       => $item->codigo,
                "editor"        => $item->editor,
                "user_created"  => $item->user_created,
                "estado"        => $item->estado,
                "created_at"    => $item->created_at,
                "codigos"       => $codigosPaquetes
            ];
        }
        return $datos;
    }
    public function getCodigosXPaquete($paquete){
        $query = DB::SELECT("SELECT codigo,libro FROM codigoslibros c
        WHERE c.codigo_paquete = '$paquete'
        ");
        return $query;
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
    public function PaqueteModificar(Request $request){
      if($request->cleanPaquete)    { return $this->cleanPaquete($request); }
      if($request->eliminarPaquete) { return $this->eliminarPaquete($request); }
      if($request->bloquearPaquete) { return $this->bloquearPaquete($request); }
    }
    public function cleanPaquete($request){
        $arrayCodigos       = json_decode($request->data_codigos);
        $codigo             = CodigosPaquete::findOrFail($request->paquete);
        $user_created       = $codigo->user_created;
        $comentario         = $request->comentario;
        $usuario_editor     = $request->user_created;
        $periodo_id         = $request->periodo_id;
        $institucion_id     = 0;
        if($codigo->estado == 2){
            return ["status" => "0", "message" => "El paquete esta bloqueado"];
        }
        //historico codigos
        foreach($arrayCodigos as $key => $item){
            $oldvalues = [];
            $oldvalues = CodigosLibros::where('codigo',$item->codigo)->get();
            $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$item->codigo,$usuario_editor,$comentario,$oldvalues[0],null);
        }
        codigoslibros::where('codigo_paquete',$request->paquete)
        ->update([
            'codigo_paquete'            => null,
            'fecha_registro_paquete'    => null,
        ]);
        //dejamos el paquete en estado abierto
        $codigoPaquete = CodigosPaquete::Where('codigo',$request->paquete)
        ->update([
            'estado' => '1'
        ]);
        //guardar en historico paquetes
        $this->save_historico_paquetes([
            "codigo_paquete"    => $codigo,
            "user_created"      => $user_created,
            "observacion"       => $comentario,
            "old_values"        => json_encode($codigo)
        ]);
        return ["status" => "1", "message" => "Se limpio el paquete"];
    }
    public function eliminarPaquete($request){
        $codigo             = CodigosPaquete::findOrFail($request->paquete);
        $user_created       = $codigo->user_created;
        $comentario         = $request->comentario;
        if($codigo->estado == 0){
            return ["status" => "1", "message" => "No se puede eliminar el paquete, ya fue utilizado"];
        }
        else{
            $codigo->delete();
            //guardar en historico
            $this->save_historico_paquetes([
                "codigo_paquete"    => $request->paquete,
                "user_created"      => $user_created,
                "observacion"       => $comentario,
                "old_values"        => json_encode($codigo)
            ]);
            return ["status" => "1", "message" => "Se elimino el paquete"];
        }
    }
    public function bloquearPaquete($request){
        $codigo             = CodigosPaquete::findOrFail($request->paquete);
        $user_created       = $codigo->user_created;
        $comentario         = $request->comentario;
        if($codigo->estado == 0){
            return ["status" => "1", "message" => "No se puede eliminar el paquete, ya fue utilizado"];
        }else{
            $codigo->estado = 2;
            $codigo->save();
            //guardar en historico
            $this->save_historico_paquetes([
                "codigo_paquete"    => $request->paquete,
                "user_created"      => $user_created,
                "observacion"       => $comentario,
                "old_values"        => json_encode($codigo)
            ]);
            return ["status" => "1", "message" => "Se bloqueo el paquete"];
        }
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
    //API:POST/paquetes/revision
    public function revision(Request $request){
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);
        $miArrayDeObjetos           = json_decode($request->data_codigos);
        $arregloProblemaPaquetes    = [];
        $informacion                = [];
        $contadorErrPaquetes        = 0;
        $contadorResumen            = 0;
        //====PROCESO===================================
        foreach($miArrayDeObjetos as $key => $item){
            $problemasconCodigo         = [];
            $contadorProblemasCodigos   = 0;
            $contadorA                  = 0;
            $contadorD                  = 0;
            $noExisteA                  = 0;
            $noExisteD                  = 0;
            $getExistsPaquete   = $this->getExistsPaquete($item->codigoPaquete);
            if(!empty($getExistsPaquete)){
                //codigos hijos del paquete
                $codigosHijos =  $this->getCodigos($item->codigoPaquete,0,3);
                foreach($codigosHijos as $key2 => $tr){
                    $validarA               = [];
                    $validarD               = [];
                    $codigoActivacion       = "";
                    $codigoDiagnostico      = "";
                    $codigoActivacion       = $tr->codigo;
                    $codigoDiagnostico      = $tr->codigo_union;
                    $mensajeError           = "";
                    //validacion
                    $validarA               = [$tr];
                    $validarD               = $this->getCodigos($codigoDiagnostico,0);
                    //======si ambos codigos existen========
                    if(count($validarA) > 0 && count($validarD) > 0){
                        $informacion[]      = $validarA[0];
                        $informacion[]      = $validarD[0];
                        //que existen ambos codigos
                        $contadorA++;
                        $contadorD++;
                    }
                    //====SI NO EXISTEN LOS CODIGOS==============
                    else{
                        if(empty($validarA)  && !empty($validarD)) { $noExisteA++;               $mensajeError = "Código de activación no existe";  }
                        if(!empty($validarA) && empty($validarD))  { $noExisteD++;               $mensajeError = "Código de diagnóstico no existe"; }
                        if(empty($validarA)  && empty($validarD))  { $noExisteA++; $noExisteD++; $mensajeError = "Ambos códigos no existen"; }
                        $problemasconCodigo[$contadorProblemasCodigos] = [
                            "codigoActivacion"  => $codigoActivacion,
                            "codigoDiagnostico" => $codigoDiagnostico,
                            "problema"          => $mensajeError
                        ];
                        $contadorProblemasCodigos++;
                    }
                }
                //codigos resumen
                $arregloResumen[$contadorResumen] = [
                    "codigoPaquete"     => $item->codigoPaquete,
                    "codigosHijos"      => $problemasconCodigo,
                    "mensaje"           => empty($getExistsPaquete) ? 1 : '0',
                    "ingresoA"          => $contadorA,
                    "ingresoD"          => $contadorD,
                    "noExisteA"         => $noExisteA,
                    "noExisteD"         => $noExisteD
                ];
                $contadorResumen++;
            }else{
                $arregloProblemaPaquetes [$contadorErrPaquetes] = [
                    "paquete"   => $item->codigoPaquete,
                    "problema" => 'Paquete no existe'
                ];
                $contadorErrPaquetes++;
            }
        }
        return [
            "arregloResumen"                   => $arregloResumen,
            "informacion"                      => $informacion,
            "arregloErroresPaquetes"           => $arregloProblemaPaquetes,
        ];
    }
    //API:POST/paquetes/devolucion_paquete
    public function devolucion_paquete(Request $request){
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);
        $miArrayDeObjetos           = json_decode($request->data_codigos);
        $arregloProblemaPaquetes    = [];
        $codigoConProblemas         = collect();
        $contadorErrPaquetes        = 0;
        $contadorResumen            = 0;
        $institucion_id             = $request->institucion_id;
        $periodo_id                 = $request->periodo_id;
        $usuario_editor             = $request->id_usuario;
        $comentario                 = $request->observacion;
        $fecha                      = date('Y-m-d H:i:s');
        //====PROCESO===================================
        foreach($miArrayDeObjetos as $key => $item){
            $problemasconCodigo         = [];
            $contadorProblemasCodigos   = 0;
            $contadorA                  = 0;
            $contadorD                  = 0;
            $noExisteA                  = 0;
            $noExisteD                  = 0;
            $getExistsPaquete   = $this->getExistsPaquete($item->codigoPaquete);
            if(!empty($getExistsPaquete)){
                $codigosHijos       = [];
                $arrayDiagnosticos   = collect();
                //codigos hijos del paquete activacion
                $codigosHijos = $this->getCodigos($item->codigoPaquete,0,3);
                //Codigos hijos del paquete diagnostico
                $arrayDiagnosticos   = collect($this->getCodigos($item->codigoPaquete,0,4));
                if(count($codigosHijos) > 0){
                    foreach($codigosHijos as $key2 => $tr){
                        $validarA               = [];
                        $validarD               = [];
                        $errorA                 = 1;
                        $errorD                 = 1;
                        $codigoActivacion       = "";
                        $codigoDiagnostico      = "";
                        $codeDiagnostico        = [];
                        $setContrato            = null;
                        $verificacion_liquidada = null;
                        //proceso
                        $codigoActivacion       = strtoupper($tr->codigo);
                        $codigoDiagnostico      = strtoupper($tr->codigo_union);
                        $codeDiagnostico        = $arrayDiagnosticos->filter(function($value, $key) use($tr){
                            return $value->codigo == $tr->codigo_union;
                        })->values();
                        $mensajeError           = "";
                        //validacion
                        $validarA               = [$tr];
                        $validarD               = $codeDiagnostico;
                        //======si ambos codigos existen========
                        if(count($validarA) > 0 && count($validarD) > 0){
                            $mensajeFront               = "";
                            //====Activacion=====
                            //validar si el codigo se encuentra liquidado
                            $ifDevueltoA                = $validarA[0]->estado_liquidacion;
                            $ifliquidado_regaladoA      = $validarA[0]->liquidado_regalado;
                            $ifContratoA                = $validarA[0]->contrato;
                            //numero de verificacion
                            $ifVerificacion             = $validarA[0]->verificacion;
                            //liquidado regalado
                            //======Diagnostico=====
                            //validar si el codigo se encuentra liquidado
                            $ifDevueltoD                = $validarD[0]->estado_liquidacion;
                            $ifliquidado_regaladoD      = $validarD[0]->liquidado_regalado;
                            //===VALIDACION====

                            if($request->dLiquidado ==  '1'){
                                $setContrato            = $ifContratoA;
                                $verificacion_liquidada = $ifVerificacion;
                                //VALIDACION AUNQUE ESTE LIQUIDADO
                                if($ifDevueltoA == '0' || $ifDevueltoA == '1' || $ifDevueltoA == '2' || $ifDevueltoA == '4') { $errorA = 0; }
                                if($ifDevueltoD == '0' || $ifDevueltoD == '1' || $ifDevueltoD == '2' || $ifDevueltoD == '4') { $errorD = 0; }
                            }else{
                                //VALIDACION QUE NO SEA LIQUIDADO
                                if(($ifDevueltoA == '1' || $ifDevueltoA == '2' || $ifDevueltoA == '4') && $ifliquidado_regaladoA == '0' ) { $errorA = 0; }
                                if(($ifDevueltoD == '1' || $ifDevueltoD == '2' || $ifDevueltoD == '4') && $ifliquidado_regaladoD == '0' ) { $errorD = 0; }
                            }
                            //===MENSAJE VALIDACION====
                            if($errorA == 1 && $errorD == 0) { $mensajeError = "Problema con el código de activación".$mensajeFront;  $codigoConProblemas->push($validarA); }
                            if($errorA == 0 && $errorD == 1) { $mensajeError = "Problema con el código de diagnóstico".$mensajeFront; $codigoConProblemas->push($validarD); }
                            if($errorA == 1 && $errorD == 1) { $mensajeError = "Ambos códigos tienen problemas";        $codigoConProblemas->push($validarA); $codigoConProblemas->push($validarD);}
                            //SI AMBOS CODIGOS PASAN LA VALIDACION GUARDO
                            if($errorA == 0 && $errorD == 0){
                                $old_valuesA    = json_encode($validarA);
                                $old_valuesD    = json_encode($validarD);
                                //tipoProceso => 0 = Usan y liquidan; 1 =  regalado; 2 = regalado y bloqueado ; 3 = bloqueado
                                //ACTIVACION CON CODIGO DE UNION
                                $ingreso =  $this->codigosRepository->updateDevolucion($codigoActivacion,$codigoDiagnostico,$validarD,$request);
                                //si se guarda codigo de activacion
                                if($ingreso == 1){
                                    $contadorA++;
                                    $contadorD++;
                                    //====CODIGO====
                                    //ingresar en el historico codigo de actvacion
                                    $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoActivacion,$usuario_editor,$comentario,$old_valuesA,null,$setContrato,$verificacion_liquidada);
                                    $this->codigosRepository->saveDevolucion($codigoActivacion,$request->cliente,$request->institucion_id,$request->periodo_id,$fecha,$request->observacion,$request->id_usuario);
                                    //====CODIGO UNION=====
                                    $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoDiagnostico,$usuario_editor,$comentario,$old_valuesD,null,$setContrato,$verificacion_liquidada);
                                    $this->codigosRepository->saveDevolucion($codigoDiagnostico,$request->cliente,$request->institucion_id,$request->periodo_id,$fecha,$request->observacion,$request->id_usuario);
                                }else{
                                    //SI NO INGRESA ALGUNO DE LOS CODIGOS ENVIO AL FRONT
                                    $problemasconCodigo[$contadorProblemasCodigos] = [
                                        "codigoActivacion"  => $codigoActivacion,
                                        "codigoDiagnostico" => $codigoDiagnostico,
                                        "problema"          => "No se pudo guardar"
                                    ];
                                    $contadorProblemasCodigos++;
                                }
                            }else{
                                //SI NO INGRESA ALGUNO DE LOS CODIGOS ENVIO AL FRONT
                                $problemasconCodigo[$contadorProblemasCodigos] = [
                                    "codigoActivacion"      => $codigoActivacion,
                                    "codigoDiagnostico"     => $codigoDiagnostico,
                                    "problema"              => $mensajeError
                                ];
                                $contadorProblemasCodigos++;
                            }
                        }
                        //====SI NO EXISTEN LOS CODIGOS==============
                        else{
                            if(empty($validarA)  && !empty($validarD)) { $noExisteA++;               $mensajeError = "Código de activación no existe";  }
                            if(!empty($validarA) && empty($validarD))  { $noExisteD++;               $mensajeError = "Código de diagnóstico no existe"; }
                            if(empty($validarA)  && empty($validarD))  { $noExisteA++; $noExisteD++; $mensajeError = "Ambos códigos no existen"; }
                            $problemasconCodigo[$contadorProblemasCodigos] = [
                                "codigoActivacion"  => $codigoActivacion,
                                "codigoDiagnostico" => $codigoDiagnostico,
                                "problema"          => $mensajeError
                            ];
                            $contadorProblemasCodigos++;
                        }
                    }
                }
                //codigos resumen
                $arregloResumen[$contadorResumen] = [
                    "codigoPaquete"     => $item->codigoPaquete,
                    "codigosHijos"      => $problemasconCodigo,
                    "mensaje"           => empty($getExistsPaquete) ? 1 : '0',
                    "ingresoA"          => $contadorA,
                    "ingresoD"          => $contadorD,
                    "noExisteA"         => $noExisteA,
                    "noExisteD"         => $noExisteD
                ];
                $contadorResumen++;
            }else{
                $arregloProblemaPaquetes [$contadorErrPaquetes] = [
                    "paquete"   => $item->codigoPaquete,
                    "problema" => 'Paquete no existe'
                ];
                $contadorErrPaquetes++;
            }
        }
        if(count($codigoConProblemas) == 0){
            return [
                "arregloResumen"                   => $arregloResumen,
                "codigoConProblemas"               => [],
                "arregloErroresPaquetes"           => $arregloProblemaPaquetes,
            ];
        }else{
            $resultado = collect($codigoConProblemas);
            $send      = $resultado->flatten(10);
            return [
                "arregloResumen"                   => $arregloResumen,
                "codigoConProblemas"               => $send,
                "arregloErroresPaquetes"           => $arregloProblemaPaquetes,
            ];
        }

    }
    //API:POST/paquetes/activar_devolucion_paquete
    public function activar_devolucion_paquete(Request $request){
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);
        $miArrayDeObjetos           = json_decode($request->data_codigos);
        $arregloProblemaPaquetes    = [];
        $informacion                = [];
        $codigoConProblemas         = collect();
        $contadorErrPaquetes        = 0;
        $contadorResumen            = 0;
        $periodo_id                 = $request->periodo_id;
        $usuario_editor             = $request->id_usuario;
        $comentario                 = $request->observacion;
        //====PROCESO===================================
        foreach($miArrayDeObjetos as $key => $item){
            $problemasconCodigo         = [];
            $contadorProblemasCodigos   = 0;
            $contadorA                  = 0;
            $contadorD                  = 0;
            $noExisteA                  = 0;
            $noExisteD                  = 0;
            $getExistsPaquete   = $this->getExistsPaquete($item->codigoPaquete);
            if(!empty($getExistsPaquete)){
                $codigosHijos       = [];
                $arrayDiagnosticos   = collect();
                //codigos hijos del paquete activacion
                $codigosHijos = $this->getCodigos($item->codigoPaquete,0,3);
                //Codigos hijos del paquete diagnostico
                $arrayDiagnosticos   = collect($this->getCodigos($item->codigoPaquete,0,4));
                if(count($codigosHijos)){
                    foreach($codigosHijos as $key2 => $tr){
                        $validarA               = [];
                        $validarD               = [];
                        $errorA                 = 1;
                        $errorD                 = 1;
                        $codigoActivacion       = "";
                        $codigoDiagnostico      = "";
                        $codeDiagnostico        = [];
                        $codigo_unionA          = "";
                        //proceso
                        $codigoActivacion       = strtoupper($tr->codigo);
                        $codigoDiagnostico      = strtoupper($tr->codigo_union);
                        $codeDiagnostico        = $arrayDiagnosticos->filter(function($value, $key) use($tr){
                            return $value->codigo == $tr->codigo_union;
                        })->values();
                        $mensajeError           = "";
                        //validacion
                        $validarA               = [$tr];
                        $validarD               = $codeDiagnostico;
                        //======si ambos codigos existen========
                        if(count($validarA) > 0 && count($validarD) > 0){
                            $mensajeFront               = "";
                            //====Activacion=====
                            //codigo de union
                            $codigo_unionA              = strtoupper($validarA[0]->codigo_union);
                            //validar si el codigo se encuentra liquidado
                            $ifDevueltoA                = $validarA[0]->estado_liquidacion;
                            $ifliquidado_regaladoA      = $validarA[0]->liquidado_regalado;
                            //que el paquete sea vacio
                            $codigo_paqueteA            = $validarA[0]->codigo_paquete;
                            //estado codigo de activacion
                            $estadoA                    = $validarA[0]->estado;
                            //liquidado regalado
                            //======Diagnostico=====
                            //codigo de union
                            $codigo_unionD              = strtoupper($validarD[0]->codigo_union);
                            //validar si el codigo se encuentra liquidado
                            $ifDevueltoD                = $validarD[0]->estado_liquidacion;
                            $ifliquidado_regaladoD      = $validarD[0]->liquidado_regalado;
                            //que el paquete sea vacio
                            $codigo_paqueteD            = $validarD[0]->codigo_paquete;
                            //estado codigo de diagnostico
                            $estadoD                    = $validarD[0]->estado;
                            //===VALIDACION====
                            //error 0 => no hay error; 1 hay error
                            // if($codigo_unionA == $codigoDiagnostico && $ifDevueltoA == 3 )    { $errorA = 0; }
                            // if($codigo_unionD == $codigoActivacion  && $ifDevueltoD == 3 )    { $errorD = 0; }
                            //OMITIR CUANDO SEA REGALADO ; BLOQUEADO; GUIA
                            $ifOmitirA = false;
                            $ifOmitirD = false;
                            //si el codigo de activacion esta  regalado  GUIA o bloqueado
                            if( $ifDevueltoA == '2' || $ifDevueltoA == '4' || $estadoA == '2' ) { $ifOmitirA = true; }
                            //si el codigo de diagnostico esta regalado GUIA o bloqueado
                            if( $ifDevueltoD == '2' || $ifDevueltoD == '4' || $estadoD == '2' ) { $ifOmitirD = true; }
                            //===========VALIDACION====
                            if($ifOmitirA){
                                $mensajeFront   = "_(No esta bloqueado o regalado o no tiene guia)";
                                if($ifDevueltoA !=0 && $ifliquidado_regaladoA == '0' && $ifOmitirA)   { $errorA = 0; }
                                if($ifDevueltoD !=0 && $ifliquidado_regaladoD == '0' && $ifOmitirD)   { $errorD = 0; }
                            }else{
                                if($ifDevueltoA !=0 && $ifliquidado_regaladoA == '0')   { $errorA = 0; }
                                if($ifDevueltoD !=0 && $ifliquidado_regaladoD == '0')   { $errorD = 0; }
                            }

                            //===MENSAJE VALIDACION====
                            if($errorA == 1 && $errorD == 0) { $mensajeError = "Problema con el código de activación".$mensajeFront;  $codigoConProblemas->push($validarA); }
                            if($errorA == 0 && $errorD == 1) { $mensajeError = "Problema con el código de diagnóstico".$mensajeFront; $codigoConProblemas->push($validarD); }
                            if($errorA == 1 && $errorD == 1) { $mensajeError = "Ambos códigos tienen problemas";        $codigoConProblemas->push($validarA); $codigoConProblemas->push($validarD);}
                            //SI AMBOS CODIGOS PASAN LA VALIDACION GUARDO
                            if($errorA == 0 && $errorD == 0){
                                $old_valuesA    = json_encode($validarA);
                                $old_valuesD    = json_encode($validarD);
                                //tipoProceso => 0 = Usan y liquidan; 1 =  regalado; 2 = regalado y bloqueado ; 3 = bloqueado
                                //ACTIVACION CON CODIGO DE UNION
                                $ingreso =  $this->codigosRepository->updateActivacion($codigoActivacion,$codigoDiagnostico,$validarD,$ifOmitirA,0);
                                //si se guarda codigo de activacion
                                if($ingreso == 1){
                                    $contadorA++;
                                    $contadorD++;
                                    //====CODIGO====
                                    //ingresar en el historico codigo
                                     $this->GuardarEnHistorico(0,0,$periodo_id,$codigoActivacion,$usuario_editor,$comentario,$old_valuesA,null);
                                    //====CODIGO UNION=====
                                    $this->GuardarEnHistorico(0,0,$periodo_id,$codigoDiagnostico,$usuario_editor,$comentario,$old_valuesD,null);
                                }else{
                                    //SI NO INGRESA ALGUNO DE LOS CODIGOS ENVIO AL FRONT
                                    $problemasconCodigo[$contadorProblemasCodigos] = [
                                        "codigoActivacion"  => $codigoActivacion,
                                        "codigoDiagnostico" => $codigoDiagnostico,
                                        "problema"          => "No se pudo guardar"
                                    ];
                                    $contadorProblemasCodigos++;
                                }
                            }else{
                                //SI NO INGRESA ALGUNO DE LOS CODIGOS ENVIO AL FRONT
                                $problemasconCodigo[$contadorProblemasCodigos] = [
                                    "codigoActivacion"      => $codigoActivacion,
                                    "codigoDiagnostico"     => $codigoDiagnostico,
                                    "problema"              => $mensajeError
                                ];
                                $contadorProblemasCodigos++;
                            }
                        }
                        //====SI NO EXISTEN LOS CODIGOS==============
                        else{
                            if(empty($validarA)  && !empty($validarD)) { $noExisteA++;               $mensajeError = "Código de activación no existe";  }
                            if(!empty($validarA) && empty($validarD))  { $noExisteD++;               $mensajeError = "Código de diagnóstico no existe"; }
                            if(empty($validarA)  && empty($validarD))  { $noExisteA++; $noExisteD++; $mensajeError = "Ambos códigos no existen"; }
                            $problemasconCodigo[$contadorProblemasCodigos] = [
                                "codigoActivacion"  => $codigoActivacion,
                                "codigoDiagnostico" => $codigoDiagnostico,
                                "problema"          => $mensajeError
                            ];
                            $contadorProblemasCodigos++;
                        }
                    }
                }
                //codigos resumen
                $arregloResumen[$contadorResumen] = [
                    "codigoPaquete"     => $item->codigoPaquete,
                    "codigosHijos"      => $problemasconCodigo,
                    "mensaje"           => empty($getExistsPaquete) ? 1 : '0',
                    "ingresoA"          => $contadorA,
                    "ingresoD"          => $contadorD,
                    "noExisteA"         => $noExisteA,
                    "noExisteD"         => $noExisteD
                ];
                $contadorResumen++;
            }else{
                $arregloProblemaPaquetes [$contadorErrPaquetes] = [
                    "paquete"   => $item->codigoPaquete,
                    "problema" => 'Paquete no existe'
                ];
                $contadorErrPaquetes++;
            }
        }
        if(count($codigoConProblemas) == 0){
            return [
                "arregloResumen"                   => $arregloResumen,
                "codigoConProblemas"               => [],
                "arregloErroresPaquetes"           => $arregloProblemaPaquetes,
            ];
        }else{
            $resultado = collect($codigoConProblemas);
            $send      = $resultado->flatten(10);
            return [
                "arregloResumen"                   => $arregloResumen,
                "codigoConProblemas"               => $send,
                "arregloErroresPaquetes"           => $arregloProblemaPaquetes,
            ];
        }

    }
    //API:POST/paquetes/ingreso
    public function importPaqueteIngreso(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos           = json_decode($request->data_codigos);
        $id_usuario        = $request->id_usuario;
        $datos             = [];
        $NoIngresados      = [];
        $porcentaje        = 0;
        $contador          = 0;
        foreach($codigos as $key => $item){
            $consulta = $this->getExistsPaquete($item->codigo);
            //si ya existe el codigo lo mando a un array
            if(count($consulta) > 0){
               $datos[] = $consulta[0];
            }else{
                //si no existen los agrego
                $codigos_libros                             = new CodigosPaquete();
                $codigos_libros->user_created               = $id_usuario;
                $codigos_libros->codigo                     = $item->codigo;
                $codigos_libros->save();
                if($codigos_libros){
                    $porcentaje++;
                }else{
                    $NoIngresados[$contador] =[
                        "codigo" => $item->codigo
                    ];
                    $contador++;
                }
            }
        }
        $data = [
            "cambiados"             => $porcentaje,
            "CodigosExisten"        => $datos,
            "CodigosNoIngresados"   => $NoIngresados,
        ];
        return $data;
    }
    public function ImporteliminarPaquete(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos                = json_decode($request->data_codigos);
        $id_usuario             = $request->id_usuario;
        $NoEliminados           = [];
        $porcentaje             = 0;
        $contador               = 0;
        $codigosNoExisten       = [];
        $contadorNoExisten      = 0;
        $contadorNoEliminados   = 0;
        $institucion_id         = 0;
        $observacion            = $request->observacion;
        $periodo_id             = $request->periodo_id;
        foreach($codigos as $key => $item){
            $consulta = $this->getExistsPaquete($item->codigo);
            //si ya existe el codigo lo mando a un array
            if(count($consulta) > 0){
               $estadoPaquete = $consulta[0]->estado;
               //estado 0 => paquete utilizado; 1 => paquete no utilizado
                if($estadoPaquete == 1 || $estadoPaquete == 2){
                    $codigos_libros = CodigosPaquete::findOrFail($item->codigo);
                    $codigos_libros->delete();
                    $porcentaje++;
                    //guardar en historico
                    $this->save_historico_paquetes([
                        "codigo_paquete"    => $item->codigo,
                        "user_created"      => $id_usuario,
                        "observacion"       => $observacion,
                        "old_values"        => json_encode($consulta[0])
                    ]);
                }
                //paquetes utilizados
                else{
                    $NoEliminados[$contadorNoEliminados] =[
                        "codigo" => $item->codigo,
                        "problema" => "Paquete utilizado"
                    ];
                    $contadorNoEliminados++;
                }
            }
            //paquete no existen
            else{
                $codigosNoExisten[$contadorNoExisten] =[
                    "codigo" => $item->codigo,
                    "problema" => "No existe"
                ];
                $contadorNoExisten++;
            }
        }
        $data = [
            "porcentaje"            => $porcentaje,
            "codigosNoExisten"      => $codigosNoExisten,
            "NoEliminados"          => $NoEliminados,
            "contadorNoExisten"     => $contadorNoExisten,
            "contadorNoEliminados"  => $contadorNoEliminados,
        ];
        return $data;
    }
    //api:post/paquetes/limpiar
    public function ImportLimpiarPaquete(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        try{
            //transaccion
            DB::beginTransaction();
            $codigos                = json_decode($request->data_codigos);
            $id_usuario             = $request->id_usuario;
            $NoEliminados           = [];
            $porcentaje             = 0;
            $contador               = 0;
            $codigosNoExisten       = [];
            $codigosBloqueados      = [];
            $contadorNoExisten      = 0;
            $contadorNoEliminados   = 0;
            $contadorBloqueados     = 0;
            $observacion            = $request->observacion;
            $periodo_id             = $request->periodo_id;
            $institucion_id         = 0;
            foreach($codigos as $key => $item){
                $consulta = $this->getExistsPaquete($item->codigo);
                //si ya existe el codigo lo mando a un array
                if(count($consulta) > 0){
                    $estadoPaquete = $consulta[0]->estado;
                    //si el estado es 0 es que el paquete esta utilizado
                    if($estadoPaquete != 2){
                        //estado 0 => paquete utilizado; 1 => paquete no utilizado; 2 => paquete bloqueado
                        $arrayCodigos = codigoslibros::where('codigo_paquete', $item->codigo)->get();
                        //historico codigos
                        foreach($arrayCodigos as $key2 => $item2){
                            $oldvalues = [];
                            $oldvalues = CodigosLibros::where('codigo', $item2->codigo)->get();
                            $comentario = $observacion."_".$item->codigo;
                            $this->GuardarEnHistorico(0, $institucion_id, $periodo_id, $item2->codigo, $id_usuario, $comentario, $oldvalues[0], null);
                        }
                        //limpiar el paquete de los codigos
                        codigoslibros::where('codigo_paquete', $item->codigo)->update([
                            'codigo_paquete'         => null,
                            'fecha_registro_paquete' => null,
                        ]);
                        $porcentaje++;
                        //dejamos el paquete en estado abierto
                        CodigosPaquete::Where('codigo',$item->codigo)
                        ->update([
                            'estado' => '1'
                        ]);
                        //guardar en historico
                        $this->save_historico_paquetes([
                            "codigo_paquete"    => $item->codigo,
                            "user_created"      => $id_usuario,
                            "observacion"       => $observacion,
                            "old_values"        => json_encode($consulta[0])
                        ]);
                    }
                    //paquetes bloqueados
                    else{
                        $codigosBloqueados[$contadorBloqueados] =[
                            "codigo" => $item->codigo,
                            "problema" => "Paquete bloqueado"
                        ];
                        $contadorBloqueados++;
                    }
                }
                //paquete no existen
                else{
                    $codigosNoExisten[$contadorNoExisten] =[
                        "codigo" => $item->codigo,
                        "problema" => "No existe"
                    ];
                    $contadorNoExisten++;
                }
            }
            $data = [
                "porcentaje"            => $porcentaje,
                "codigosNoExisten"      => $codigosNoExisten,
                "NoEliminados"          => $NoEliminados,
                "contadorNoExisten"     => $contadorNoExisten,
                "contadorNoEliminados"  => $contadorNoEliminados,
                "contadorBloqueados"    => $contadorBloqueados,
                "codigosBloqueados"     => $codigosBloqueados
            ];
            DB::commit();
            return $data;
        }
        catch(\Exception $ex){
            DB::rollback();
            return ["status" => "0", "message" => "Error al limpiar paquete", "error" => "error".$ex];
        }

    }
    //api:post/paquetes/bloquear
    public function ImportBloquearPaquete(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos                = json_decode($request->data_codigos);
        $id_usuario             = $request->id_usuario;
        $noBloqueados           = [];
        $porcentaje             = 0;
        $contador               = 0;
        $codigosNoExisten       = [];
        $contadorNoExisten      = 0;
        $contadorBloqueados     = 0;
        $institucion_id         = 0;
        $observacion            = $request->observacion;
        $periodo_id             = $request->periodo_id;
        foreach($codigos as $key => $item){
            $consulta = $this->getExistsPaquete($item->codigo);
            //si ya existe el codigo lo mando a un array
            if(count($consulta) > 0){
               $estadoPaquete = $consulta[0]->estado;
               //estado 0 => paquete utilizado; 1 => paquete no utilizado
                if($estadoPaquete == 1 || $estadoPaquete == 2){
                    $codigos_libros         = CodigosPaquete::findOrFail($item->codigo);
                    $codigos_libros->estado = 2;
                    $codigos_libros->save();
                    $porcentaje++;
                    //guardar en historico
                    $this->save_historico_paquetes([
                        "codigo_paquete"    => $item->codigo,
                        "user_created"      => $id_usuario,
                        "observacion"       => $observacion,
                        "old_values"        => json_encode($consulta[0])
                    ]);
                }
                //paquetes utilizados
                else{
                    $noBloqueados[$contadorBloqueados] =[
                        "codigo" => $item->codigo,
                        "problema" => "Paquete utilizado"
                    ];
                    $contadorBloqueados++;
                }
            }
            //paquete no existen
            else{
                $codigosNoExisten[$contadorNoExisten] =[
                    "codigo" => $item->codigo,
                    "problema" => "No existe"
                ];
                $contadorNoExisten++;
            }
        }
        $data = [
            "porcentaje"            => $porcentaje,
            "codigosNoExisten"      => $codigosNoExisten,
            "noBloqueados"          => $noBloqueados,
            "contadorNoExisten"     => $contadorNoExisten,
            "contadorBloqueados"    => $contadorBloqueados,
        ];
        return $data;
    }
    ///guardar en historico
    public function save_historico_paquetes($data){
        $historico                  = new HistoricoPaquetes();
        $historico->codigo          = $data["codigo_paquete"];
        $historico->user_created    = $data["user_created"];
        $historico->observacion     = $data["observacion"];
        $historico->old_values      = $data["old_values"];
        $historico->save();
    }
}
