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

use function Symfony\Component\VarDumper\Dumper\esc;

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
        $query = DB::SELECT("SELECT * FROM codigos_paquetes p WHERE p.codigo = '$paquete'");
        return $query;
    }
    public function getExistsCombo($combo){
        $query = DB::SELECT("SELECT * FROM codigos_combos p WHERE p.codigo = '$combo'");
        return $query;
    }
    //paquetes/guadarPaquete
    public function guardarPaquete(Request $request){
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);
        $miArrayDeObjetos           = json_decode($request->data_codigos);
        // Variables
        $usuario_editor             = $request->id_usuario;
        $institucion_id             = 0;
        $periodo_id                 = $request->periodo_id;
        $arregloResumen             = [];
        $contadorResumen            = 0;
        $codigoConProblemas         = collect();
        $arregloProblemaPaquetes    = [];
        $contadorErrPaquetes        = 0;

        //====PROCESO===================================
        DB::beginTransaction(); // Inicia la transacción
        try {
            foreach ($miArrayDeObjetos as $key => $item) {
                $problemasconCodigo = [];
                $contadorProblemasCodigos = 0;
                $ExistsPaquete = [];
                $contadorA = 0;
                $contadorD = 0;
                $noExisteA = 0;
                $noExisteD = 0;
                $codigoPaquete = strtoupper($item->codigoPaquete);

                // VALIDAR QUE EL CODIGO DE PAQUETE EXISTE
                $ExistsPaquete = $this->getPaquete($codigoPaquete);
                if (!empty($ExistsPaquete)) {
                    foreach ($item->codigosHijos as $key2 => $tr) {
                        $codigoActivacion = strtoupper($tr->codigoActivacion);
                        $codigoDiagnostico = strtoupper($tr->codigoDiagnostico);
                        $errorA = 1;
                        $errorD = 1;
                        $mensajeError = "";

                        // Validación
                        $validarA = $this->getCodigos($codigoActivacion, 0);
                        $validarD = $this->getCodigos($codigoDiagnostico, 0);
                        $comentario = "Se agregó al paquete " . $codigoPaquete;

                        //======si ambos codigos existen========
                        if (count($validarA) > 0 && count($validarD) > 0) {
                            // Activación
                            $ifcodigo_paqueteA = $validarA[0]->codigo_paquete;
                            $codigo_unionA = strtoupper($validarA[0]->codigo_union);

                            // Diagnóstico
                            $ifcodigo_paqueteD = $validarD[0]->codigo_paquete;
                            $codigo_unionD = strtoupper($validarD[0]->codigo_union);

                            //===VALIDACION====
                            if ($ifcodigo_paqueteA == null && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0"))) {
                                $errorA = 0;
                            }
                            if ($ifcodigo_paqueteD == null && (($codigo_unionD == $codigoActivacion) || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0"))) {
                                $errorD = 0;
                            }

                            //===MENSAJE VALIDACION====
                            if ($errorA == 1 && $errorD == 0) {
                                $mensajeError = "Problema con el código de activación";
                                $codigoConProblemas->push($validarA);
                            }
                            if ($errorA == 0 && $errorD == 1) {
                                $mensajeError = "Problema con el código de diagnóstico";
                                $codigoConProblemas->push($validarD);
                            }
                            if ($errorA == 1 && $errorD == 1) {
                                $mensajeError = "Ambos códigos tienen problemas";
                                $codigoConProblemas->push($validarA);
                                $codigoConProblemas->push($validarD);
                            }

                            // SI AMBOS CODIGOS PASAN LA VALIDACION GUARDO
                            if ($errorA == 0 && $errorD == 0) {
                                $old_valuesA = CodigosLibros::Where('codigo', $codigoActivacion)->get();
                                $ingresoA = $this->updatecodigosPaquete($codigoPaquete, $codigoActivacion, $codigoDiagnostico);
                                $old_valuesD = CodigosLibros::findOrFail($codigoDiagnostico);
                                $ingresoD = $this->updatecodigosPaquete($codigoPaquete, $codigoDiagnostico, $codigoActivacion);
                                if($ingresoA && $ingresoD){
                                    $this->GuardarEnHistorico(0, $institucion_id, $periodo_id, $codigoActivacion, $usuario_editor, $comentario, $old_valuesA, null);
                                    $this->GuardarEnHistorico(0, $institucion_id, $periodo_id, $codigoDiagnostico, $usuario_editor, $comentario, $old_valuesD, null);
                                    $contadorA++;
                                    $contadorD++;
                                }
                                else{
                                    $problemasconCodigo[$contadorProblemasCodigos] = [
                                        "codigoActivacion"  => $codigoActivacion,
                                        "codigoDiagnostico" => $codigoDiagnostico,
                                        "problema"          => "No se guardaron los codigos"
                                    ];
                                    $contadorProblemasCodigos++;
                                }

                            } else {
                                // SI NO INGRESA ALGUNO DE LOS CODIGOS ENVIO AL FRONT
                                $problemasconCodigo[$contadorProblemasCodigos] = [
                                    "codigoActivacion"    => $codigoActivacion,
                                    "codigoDiagnostico"   => $codigoDiagnostico,
                                    "problema"            => $mensajeError
                                ];
                                $contadorProblemasCodigos++;
                            }
                        } else {
                            // SI NO EXISTEN LOS CODIGOS
                            if (empty($validarA) && !empty($validarD)) {
                                $noExisteA++;
                                $mensajeError = "Código de activación no existe";
                            }
                            if (!empty($validarA) && empty($validarD)) {
                                $noExisteD++;
                                $mensajeError = "Código de diagnóstico no existe";
                            }
                            if (empty($validarA) && empty($validarD)) {
                                $noExisteA++;
                                $noExisteD++;
                                $mensajeError = "Ambos códigos no existen";
                            }
                            $problemasconCodigo[$contadorProblemasCodigos] = [
                                "codigoActivacion"  => $codigoActivacion,
                                "codigoDiagnostico" => $codigoDiagnostico,
                                "problema"          => $mensajeError
                            ];
                            $contadorProblemasCodigos++;
                        }
                    }
                    //si contadorA  es mayor a cero marco como utilizado
                    if($contadorA > 1){ $this->changeUsePaquete($codigoPaquete); }
                    // Codigos resumen
                    $arregloResumen[$contadorResumen]   = [
                        "codigoPaquete"                 => $codigoPaquete,
                        "codigosHijos"                  => $problemasconCodigo,
                        "mensaje"                       => empty($ExistsPaquete) ? 1 : '0',
                        "ingresoA"                      => $contadorA,
                        "ingresoD"                      => $contadorD,
                        "noExisteA"                     => $noExisteA,
                        "noExisteD"                     => $noExisteD,
                        "contadorProblemasCodigos"      => $contadorProblemasCodigos,
                    ];
                    $contadorResumen++;
                } else {
                    $getProblemaPaquete = $this->getExistsPaquete($codigoPaquete);
                    $arregloProblemaPaquetes[$contadorErrPaquetes] = [
                        "paquete" => $codigoPaquete,
                        "problema" => count($getProblemaPaquete) > 0 ? 'Paquete utilizado' : 'Paquete no existe'
                    ];
                    $contadorErrPaquetes++;
                }
            }

            DB::commit(); // Confirma la transacción

            if (count($codigoConProblemas) == 0) {
                return [
                    "arregloResumen" => $arregloResumen,
                    "codigoConProblemas" => [],
                    "arregloErroresPaquetes" => $arregloProblemaPaquetes,
                ];
            } else {
                return [
                    "arregloResumen" => $arregloResumen,
                    "codigoConProblemas" => array_merge(...$codigoConProblemas->all()),
                    "arregloErroresPaquetes" => $arregloProblemaPaquetes,
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack(); // Deshace la transacción en caso de error
            return response()->json(['status' => '0', 'message' => 'Ocurrió un error al guardar el paquete: ' . $e->getMessage()], 200);
        }
    }
    public function guardarPaquete2(Request $request) {
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);

        $miArrayDeObjetos           = json_decode($request->data_codigos);
        //variables
        $usuario_editor             = $request->id_usuario;
        $periodo_id                 = $request->periodo_id;
        $arregloProblemaPaquetes    = [];
        $arregloResumen             = [];
        $codigoConProblemas         = collect();
        $contadorErrPaquetes        = 0;
        $contadorResumen            = 0;
        $institucion_id             = 0;

        try {
            DB::beginTransaction();

            //====PROCESO===================================
            foreach ($miArrayDeObjetos as $key => $item) {
                //variables
                $problemasconCodigo = [];
                $contadorProblemasCodigos = 0;
                $contadorA = 0;
                $contadorB = 0;
                $noExisteA = 0;
                $noExisteB = 0;
                $codigoPaquete = strtoupper($item->codigoPaquete);

                //VALIDAR QUE EL CODIGO DE PAQUETE EXISTE
                $ExistsPaquete = $this->getPaquete($codigoPaquete);
                if (!empty($ExistsPaquete)) {
                    foreach ($item->codigosHijos as $key2 => $tr) {
                        $codigoA = strtoupper($tr->codigo);
                        $errorA = 1;
                        $errorB = 1;
                        $comentario = "Se agregó al paquete " . $codigoPaquete;

                        //validar si el codigo existe
                        $validarA = CodigosLibros::where('codigo', $codigoA)->get();
                        if (count($validarA) > 0) {
                            $codigoB = strtoupper($validarA[0]->codigo_union);
                            $validarB = CodigosLibros::where('codigo', $codigoB)->get();

                            if (count($validarB) > 0) {
                                //VARIABLES PARA EL PROCESO
                                $ifcodigo_paqueteA  = strtoupper($validarA[0]->codigo_paquete);
                                $codigo_unionA      = strtoupper($validarA[0]->codigo_union);
                                $ifcodigo_paqueteB  = strtoupper($validarB[0]->codigo_paquete);
                                $codigo_unionB      = strtoupper($validarB[0]->codigo_union);

                                //===VALIDACION====
                                if ($ifcodigo_paqueteA == null && (($codigo_unionA == $codigoB) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0"))) $errorA = 0;
                                if ($ifcodigo_paqueteB == null && (($codigo_unionB == $codigoA) || ($codigo_unionB == null || $codigo_unionB == "" || $codigo_unionB == "0"))) $errorB = 0;

                                //===MENSAJE VALIDACION====
                                if ($errorA == 1 && $errorB == 0) {
                                    $mensajeError = "Problema con el código de activación";
                                    $codigoConProblemas->push($validarA);
                                }
                                if ($errorA == 0 && $errorB == 1) {
                                    $mensajeError = "Problema con el código de diagnóstico";
                                    $codigoConProblemas->push($validarB);
                                }
                                if ($errorA == 1 && $errorB == 1) {
                                    $mensajeError = "Ambos códigos tienen problemas";
                                    $codigoConProblemas->push($validarA);
                                    $codigoConProblemas->push($validarB);
                                }

                                //SI AMBOS CODIGOS PASAN LA VALIDACION GUARDO
                                if ($errorA == 0 && $errorB == 0) {
                                    $old_valuesA    = $validarA;
                                    $ingresoA       = $this->updatecodigosPaquete($codigoPaquete, $codigoA, $codigoB);
                                    $old_valuesB    = $validarB;
                                    $ingresoB       = $this->updatecodigosPaquete($codigoPaquete, $codigoB, $codigoA);

                                    if($ingresoA && $ingresoB){
                                        $this->GuardarEnHistorico(0, $institucion_id, $periodo_id, $codigoA, $usuario_editor, $comentario, $old_valuesA, null);
                                        $this->GuardarEnHistorico(0, $institucion_id, $periodo_id, $codigoB, $usuario_editor, $comentario, $old_valuesB, null);
                                        $contadorA++;
                                        $contadorB++;
                                    }
                                    else{
                                        $problemasconCodigo[$contadorProblemasCodigos] = [
                                            "codigoActivacion"  => $codigoA,
                                            "codigoDiagnostico" => $codigoB,
                                            "problema"      => "No se guardaron los codigos"
                                        ];
                                        $contadorProblemasCodigos++;
                                    }
                                } else {
                                    //SI NO INGRESA ALGUNO DE LOS CODIGOS ENVIO AL FRONT
                                    $problemasconCodigo[$contadorProblemasCodigos] = [
                                        "codigoActivacion"    => $codigoA,
                                        "codigoDiagnostico"   => $codigoB,
                                        "problema"      => $mensajeError
                                    ];
                                    $contadorProblemasCodigos++;
                                }
                            } else {
                                $noExisteB++;
                                $mensajeError = "No existe el código de unión";
                                $problemasconCodigo[$contadorProblemasCodigos] = [
                                    "codigoActivacion"  => $codigoA,
                                    "codigoDiagnostico" => $codigoB,
                                    "problema"          => $mensajeError
                                ];
                                $contadorProblemasCodigos++;
                            }
                        } else {
                            $noExisteA++;
                            $mensajeError = "No existe el código";
                            $problemasconCodigo[$contadorProblemasCodigos] = [
                                "codigoActivacion"  => $codigoA,
                                "codigoDiagnostico" => "",
                                "problema"          => $mensajeError
                            ];
                            $contadorProblemasCodigos++;
                        }
                    }
                    //codigos resumen
                    $arregloResumen[$contadorResumen] = [
                        "codigoPaquete"             => $codigoPaquete,
                        "codigosHijos"              => $problemasconCodigo,
                        "mensaje"                   => empty($ExistsPaquete) ? 1 : '0',
                        "ingresoA"                  => $contadorA,
                        "ingresoD"                  => $contadorB,
                        "noExisteA"                 => $noExisteA,
                        "noExisteD"                 => $noExisteB,
                        "contadorProblemasCodigos"  => $contadorProblemasCodigos,
                    ];
                    $contadorResumen++;
                } else {
                    $getProblemaPaquete = $this->getExistsPaquete($codigoPaquete);
                    $arregloProblemaPaquetes[$contadorErrPaquetes] = [
                        "paquete"   => $codigoPaquete,
                        "problema"  => count($getProblemaPaquete) > 0 ? 'Paquete utilizado' : 'Paquete no existe'
                    ];
                    $contadorErrPaquetes++;
                }
                //si contadorA es mayor a cero marco el paquete como usado
                if($contadorA > 0){
                    //colocar el paquete como utilizado
                    $this->changeUsePaquete($codigoPaquete);
                }
            }

            DB::commit(); // Commit the transaction

            if (count($codigoConProblemas) == 0) {
                return [
                    "arregloResumen"            => $arregloResumen,
                    "codigoConProblemas"        => [],
                    "arregloErroresPaquetes"    => $arregloProblemaPaquetes,
                ];
            } else {
                $getProblemas = [];
                $arraySinCorchetes = array_map(function ($item) { return json_decode(json_encode($item)); }, $codigoConProblemas->all());
                $getProblemas = array_merge(...$arraySinCorchetes);
                return [
                    "arregloResumen"            => $arregloResumen,
                    "codigoConProblemas"        => $getProblemas,
                    "arregloErroresPaquetes"    => $arregloProblemaPaquetes,
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback the transaction
            return response()->json(['status' => '0', 'message' => 'Ocurrió un error al guardar el paquete: ' . $e->getMessage()], 200);
        }
    }

    public function changeUsePaquete($codigo){
        $paq = CodigosPaquete::findOrFail($codigo);
        $paq->estado = "0";
        $paq->save();
    }
    public function updatecodigosPaquete($codigoPaquete,$codigo,$codigo_union){
        // $fecha = date("Y-m-d H:i:s");
        // $codigo = DB::table('codigoslibros')
        //     ->where('codigo', '=', $codigo)
        //     ->update([
        //         'codigo_paquete'            => $codigoPaquete,
        //         'fecha_registro_paquete'    => $fecha,
        //         'codigo_union'              => $codigo_union
        //     ]);
        // return $codigo;
        $fecha = date("Y-m-d H:i:s");
        $codigoLibro = CodigosLibros::where('codigo', '=', $codigo)->first();

        if ($codigoLibro) {
            $codigoLibro->codigo_paquete            = $codigoPaquete;
            $codigoLibro->fecha_registro_paquete    = $fecha;
            $codigoLibro->codigo_union              = $codigo_union;

            // Guarda los cambios
            $guardado = $codigoLibro->save();

            return $guardado ? 1 : 0; // Retorna 1 si se guardó, 0 si no
        } else {
            return 0; // Retorna 0 si no se encontró el código
        }
    }
    //api:post/paquetes/importPaqueteGestion
    public function importPaqueteGestion(Request $request){
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);

        DB::beginTransaction();  // Inicia la transacción
        try {
            $miArrayDeObjetos = json_decode($request->data_codigos);
            // Variables
            $usuario_editor                         = $request->id_usuario;
            $institucion_id                         = $request->institucion_id;
            $periodo_id                             = $request->periodo_id;
            $arregloResumen                         = [];
            $contadorResumen                        = 0;
            $codigoConProblemas                     = collect();
            $arregloProblemaPaquetes                = [];
            $contadorErrPaquetes                    = 0;
            $tipoProceso                            = $request->regalado;
            $factura                                = "";
            $obsevacion                             = $request->comentario;
            // tipoBodega => 3 paquete; 4 = combo
            $tipoBodega                             = $request->tipoBodega;
            //tipoComboImportacion => 0 combo general; 1 = combo individual del excel
            $tipoComboImportacion                   = $request->tipoComboImportacion;
            $ifSetCombo                             = $request->ifSetCombo;
            $comboSelected                          = $request->comboSelected;
            $letraProceso                           = $tipoBodega == 3 ? 'paquete' : 'combo';
            $proforma_empresa                       = $request->proforma_empresa;
            $codigo_proforma                        = $request->codigo_proforma;
            $ifSetProforma                          = $request->ifSetProforma;
            $datosProforma                          = [
                "proforma_empresa" => $proforma_empresa,
                "codigo_proforma" => $codigo_proforma,
            ];

            //====PROCESO===================================
            foreach($miArrayDeObjetos as $key => $item){
                $problemasconCodigo = [];
                $contadorProblemasCodigos = 0;
                $contadorA = 0;
                $contadorD = 0;
                $noExisteA = 0;
                $noExisteD = 0;
                $mensajePadre = "";
                $comboIndividual = null;
                $msgComboIndividual = '';
                $codigoPaquete = strtoupper($item->codigoPaquete);
                if($ifSetCombo  == 1){
                    if($tipoComboImportacion == 1){
                        //combo del excel
                        if($item->combo){
                            $comboIndividual = strtoupper($item->combo);
                        }
                        $msgComboIndividual = '_el_'.$comboIndividual;
                    }else{
                        $msgComboIndividual = '_el_'.$comboSelected;
                    }
                }

                // Validar
                if($tipoBodega == 3){
                    $getExistsPaquete = $this->getExistsPaquete($codigoPaquete);
                }
                else{
                    $getExistsPaquete = $this->getExistsCombo($codigoPaquete);
                }

                if (!empty($getExistsPaquete)) {
                    // TipoBodega => 3 paquete; 4 = combo
                    $codigosHijos = $this->getCodigos($codigoPaquete, 0, $tipoBodega == 3 ? 3 : 5);

                    if(count($codigosHijos) > 0){
                        foreach($codigosHijos as $key2 => $tr){
                            $validarA = [];
                            $codigoActivacion = strtoupper($tr->codigo);
                            $codigoDiagnostico = strtoupper($tr->codigo_union);
                            $errorA = 1;
                            $errorD = 1;
                            $mensajeError = "";

                            // Validación
                            $validarA = [$tr];
                            $validarD = $this->getCodigos($codigoDiagnostico, 0);
                            $comentario = "Se agrego al $letraProceso ".$item->codigoPaquete .$msgComboIndividual . " - " .$obsevacion;
                            $ifChangeProforma = false;
                            $ifcodigo_proformaA = $validarA[0]->codigo_proforma;
                            $ifcodigo_proformaD = $validarD[0]->codigo_proforma;

                            // Si ambos códigos existen
                            if(count($validarA) > 0 && count($validarD) > 0){
                                $validate = $this->paqueteRepository->validateGestion($tipoProceso, $validarA, $validarD, $item, $codigoActivacion, $codigoDiagnostico, $request);
                                $errorA = $validate["errorA"]; $errorD = $validate["errorD"]; $factura = $validate["factura"]; $ifChangeProforma = $validate["ifChangeProforma"];
                                // === Mensaje validación ===
                                if($errorA == 1 && $errorD == 0) {
                                    $mensajeError = "Problema con el código de activación";
                                    if($ifSetProforma == 1 && $ifChangeProforma == false){
                                        $validarA[0]->errorProforma = 1;
                                        $validarA[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaA, Proforma a ingresar: $codigo_proforma";
                                        $codigoConProblemas->push($validarA);
                                    }else{
                                        $codigoConProblemas->push($validarA);
                                    }
                                }
                                if($errorA == 0 && $errorD == 1) {
                                    $mensajeError = "Problema con el código de diagnóstico";
                                    if($ifSetProforma == 1 && $ifChangeProforma == false){
                                        $validarD[0]->errorProforma = 1;
                                        $validarD[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaD, Proforma a ingresar: $codigo_proforma";
                                        $codigoConProblemas->push($validarD);
                                    }else{
                                        $codigoConProblemas->push($validarD);
                                    }
                                }
                                if($errorA == 1 && $errorD == 1) {
                                    $mensajeError = "Ambos códigos tienen problemas";
                                    if($ifSetProforma == 1 && $ifChangeProforma == false){
                                        $validarA[0]->errorProforma = 1;
                                        $validarA[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaA, Proforma a ingresar: $codigo_proforma";
                                        $validarD[0]->errorProforma = 1;
                                        $validarD[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaD, Proforma a ingresar: $codigo_proforma";
                                        $codigoConProblemas->push($validarA);
                                        $codigoConProblemas->push($validarD);
                                    }else{
                                        $codigoConProblemas->push($validarA);
                                        $codigoConProblemas->push($validarD);
                                    }
                                }

                                // Si ambos códigos pasan la validación, guardo
                                if($errorA == 0 && $errorD == 0){
                                    $old_valuesA = CodigosLibros::Where('codigo',$codigoActivacion)->get();
                                    $old_valuesD = CodigosLibros::findOrFail($codigoDiagnostico);
                                    $ingreso = $this->paqueteRepository->procesoGestionBodega($tipoProceso, $codigoActivacion, $codigoDiagnostico, $request, $factura, $item->codigoPaquete, $ifChangeProforma, $datosProforma, $comboIndividual);

                                    // Si se guarda código de activación
                                    if($ingreso == 1){
                                        $contadorA++;
                                        $contadorD++;
                                        // === Código ===
                                        $this->GuardarEnHistorico(0, $institucion_id, $periodo_id, $codigoActivacion, $usuario_editor, $comentario, $old_valuesA, null);
                                        // === Código Unión ===
                                        $this->GuardarEnHistorico(0, $institucion_id, $periodo_id, $codigoDiagnostico, $usuario_editor, $comentario, $old_valuesD, null);
                                    }else{
                                        $problemasconCodigo[$contadorProblemasCodigos] = [
                                            "codigoActivacion" => $codigoActivacion,
                                            "codigoDiagnostico" => $codigoDiagnostico,
                                            "problema" => "No se pudo guardar"
                                        ];
                                        $contadorProblemasCodigos++;
                                    }
                                }else{
                                    $problemasconCodigo[$contadorProblemasCodigos] = [
                                        "codigoActivacion" => $codigoActivacion,
                                        "codigoDiagnostico" => $codigoDiagnostico,
                                        "problema" => $mensajeError
                                    ];
                                    $contadorProblemasCodigos++;
                                }
                            }
                            // Si no existen los códigos
                            else{
                                if(empty($validarA) && !empty($validarD)) { $noExisteA++; $mensajeError = "Código de activación no existe"; }
                                if(!empty($validarA) && empty($validarD)) { $noExisteD++; $mensajeError = "Código de diagnóstico no existe"; }
                                if(empty($validarA) && empty($validarD)) { $noExisteA++; $noExisteD++; $mensajeError = "Ambos códigos no existen"; }
                                $problemasconCodigo[$contadorProblemasCodigos] = [
                                    "codigoActivacion" => $codigoActivacion,
                                    "codigoDiagnostico" => $codigoDiagnostico,
                                    "problema" => $mensajeError
                                ];
                                $contadorProblemasCodigos++;
                            }
                        }
                        if($contadorProblemasCodigos > 0){
                            $arregloProblemaPaquetes[$contadorErrPaquetes] = [
                                "paquete" => $codigoPaquete,
                                "problema" => "Existen problemas con los codigos"
                            ];
                            $contadorErrPaquetes++;
                        }
                    }else{
                        $mensajePadre = "El $letraProceso no tiene codigos";
                        $arregloProblemaPaquetes[$contadorErrPaquetes] = [
                            "paquete" => $codigoPaquete,
                            "problema" => "El $letraProceso no tiene codigos"
                        ];
                        $contadorErrPaquetes++;
                    }
                } else {
                    $arregloProblemaPaquetes[$contadorErrPaquetes] = [
                        "paquete" => $item->codigoPaquete,
                        "problema" => $letraProceso." no existe"
                    ];
                    $contadorErrPaquetes++;
                }

                // Codigos resumen
                $arregloResumen[$contadorResumen] = [
                    "codigoPaquete" => $codigoPaquete,
                    "codigosHijos" => $problemasconCodigo,
                    "mensaje" => empty($getExistsPaquete) ? 1 : '0',
                    "mensajePadre" => $mensajePadre,
                    "ingresoA" => $contadorA,
                    "ingresoD" => $contadorD,
                    "noExisteA" => $noExisteA,
                    "noExisteD" => $noExisteD,
                    "contadorProblemasCodigos" => $contadorProblemasCodigos,
                ];
                $contadorResumen++;
            }

            // Commit de la transacción si todo sale bien
            DB::commit();

            // Retorna el resultado
            return [
                "arregloResumen" => $arregloResumen,
                "codigoConProblemas" => count($codigoConProblemas) == 0 ? [] : array_merge(...$codigoConProblemas->all()),
                "arregloErroresPaquetes" => $arregloProblemaPaquetes,
            ];
        } catch (\Exception $e) {
            DB::rollBack();  // Revierte la transacción en caso de error
            return [
                "status" => 0,
                "message" => $e->getMessage(),
                "arregloResumen" => $arregloResumen,
                "codigoConProblemas" => [],
                "arregloErroresPaquetes" => $arregloProblemaPaquetes,
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
        $query = DB::SELECT("SELECT codigo,libro,codigo_proforma,factura,combo FROM codigoslibros c
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
            "codigo_paquete"    => $request->paquete,
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
        $tipoBodega                 = $request->tipoBodega;
        $arregloProblemaPaquetes    = [];
        $arregloResumen             = [];
        $informacion                = [];
        $arrayPaqueteComboSinHijos  = [];
        $contadorErrPaquetes        = 0;
        $contadorResumen            = 0;
        $letraProceso               = $tipoBodega == 3 ? 'paquete' : 'combo';
        //====PROCESO===================================
        foreach($miArrayDeObjetos as $key => $item){
            $problemasconCodigo         = [];
            $contadorProblemasCodigos   = 0;
            $contadorA                  = 0;
            $contadorD                  = 0;
            $noExisteA                  = 0;
            $noExisteD                  = 0;
            $codigoPaquete              = strtoupper($item->codigoPaquete);
            if($tipoBodega == 3){
                $getExistsPaquete = $this->getExistsPaquete($codigoPaquete);
            }else{
                $getExistsPaquete = $this->getExistsCombo($codigoPaquete);
            }
            if(!empty($getExistsPaquete)){
                //tipoBodega => 3 paquete; 4 = combo
                if($tipoBodega == 3){
                    $codigosHijos = $this->getCodigos($codigoPaquete, 0, 3);
                    $arrayDiagnosticos = collect($this->getCodigos($codigoPaquete, 0, 4));
                }else{
                    $codigosHijos = $this->getCodigos($codigoPaquete, 0, 5);
                    $arrayDiagnosticos = collect($this->getCodigos($codigoPaquete, 0, 6));
                }
                if(count($codigosHijos) == 0){
                    $arrayPaqueteComboSinHijos[] = [ "codigo" => $codigoPaquete];
                }
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
                    "codigoPaquete"             => $item->codigoPaquete,
                    "codigosHijos"              => $problemasconCodigo,
                    "mensaje"                   => empty($getExistsPaquete) ? 1 : '0',
                    "ingresoA"                  => $contadorA,
                    "ingresoD"                  => $contadorD,
                    "noExisteA"                 => $noExisteA,
                    "noExisteD"                 => $noExisteD,
                    "contadorProblemasCodigos"  => $contadorProblemasCodigos,
                ];
                $contadorResumen++;
            }else{
                $arregloProblemaPaquetes [$contadorErrPaquetes] = [
                    "paquete"  => $item->codigoPaquete,
                    "problema" => $letraProceso." no existe"
                ];
                $contadorErrPaquetes++;
            }
        }
        return [
            "arregloResumen"                   => $arregloResumen,
            "informacion"                      => $informacion,
            "arregloErroresPaquetes"           => $arregloProblemaPaquetes,
            "arrayPaqueteComboSinHijos"        => $arrayPaqueteComboSinHijos,
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
        $arregloResumen             = [];
        //====PROCESO===================================
        try{
            DB::beginTransaction();
            foreach($miArrayDeObjetos as $key => $item){
                $problemasconCodigo         = [];
                $contadorProblemasCodigos   = 0;
                $contadorA                  = 0;
                $contadorD                  = 0;
                $noExisteA                  = 0;
                $noExisteD                  = 0;
                $getExistsPaquete   = $this->getExistsPaquete($item->codigoPaquete);
                if(!empty($getExistsPaquete)){
                    $codigosHijos           = [];
                    $arrayDiagnosticos      = collect();
                    $mensajePadre           = "";
                    //codigos hijos del paquete activacion
                    $codigosHijos           = $this->getCodigos($item->codigoPaquete,0,3);
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
                            $ifErrorProformaA       = 0;
                            $messageProformaA       = "";
                            $ifsetProformaA         = 0;
                            $ifErrorProformaD       = 0;
                            $messageProformaD       = "";
                            $ifsetProformaD         = 0;
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
                                //numero de verificacion
                                $ifVerificacionA            = $validarA[0]->verificacion;
                                //para ver la empresa de la proforma
                                $ifproforma_empresaA        = $validarA[0]->proforma_empresa;
                                //para ver el estado devuelto proforma
                                $ifdevuelto_proformaA       = $validarA[0]->devuelto_proforma;
                                ///para ver el codigo de proforma
                                $ifcodigo_proformaA         = $validarA[0]->codigo_proforma;
                                //para ver el codigo de liquidacion
                                $ifcodigo_liquidacionA      = $validarA[0]->codigo_liquidacion;
                                //liquidado regalado
                                //======Diagnostico=====
                                //validar si el codigo se encuentra liquidado
                                $ifDevueltoD                = $validarD[0]->estado_liquidacion;
                                $ifliquidado_regaladoD      = $validarD[0]->liquidado_regalado;
                                //para ver la empresa de la proforma
                                $ifproforma_empresaD        = $validarD[0]->proforma_empresa;
                                //para ver el estado devuelto proforma
                                $ifdevuelto_proformaD       = $validarD[0]->devuelto_proforma;
                                ///para ver el codigo de proforma
                                $ifcodigo_proformaD         = $validarD[0]->codigo_proforma;
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
                                    $getIngreso         = $this->codigosRepository->updateDevolucion($codigoActivacion,$codigoDiagnostico,$validarD,$request,$ifsetProformaA,$ifcodigo_liquidacionA,$ifproforma_empresaA,$ifcodigo_proformaA);
                                    $ingreso            = $getIngreso["ingreso"];
                                    $messageIngreso     = $getIngreso["messageIngreso"];
                                    //si se guarda codigo de activacion
                                    if($ingreso == 1){
                                        $newValuesActivacion = CodigosLibros::where('codigo',$codigoActivacion)->get();
                                        $newValuesDiagnostico = CodigosLibros::where('codigo',$codigoDiagnostico)->get();
                                        $contadorA++;
                                        $contadorD++;
                                        //====CODIGO====
                                        //ingresar en el historico codigo de actvacion
                                        $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoActivacion,$usuario_editor,$comentario,$old_valuesA,$newValuesActivacion,$setContrato,$verificacion_liquidada);
                                        $this->codigosRepository->saveDevolucion($codigoActivacion,$request->cliente,$request->institucion_id,$request->periodo_id,$fecha,$request->observacion,$request->id_usuario);
                                        //====CODIGO UNION=====
                                        $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoDiagnostico,$usuario_editor,$comentario,$old_valuesD,$newValuesDiagnostico,$setContrato,$verificacion_liquidada);
                                        $this->codigosRepository->saveDevolucion($codigoDiagnostico,$request->cliente,$request->institucion_id,$request->periodo_id,$fecha,$request->observacion,$request->id_usuario);
                                    }else{
                                        //SI NO INGRESA ALGUNO DE LOS CODIGOS ENVIO AL FRONT
                                        $problemasconCodigo[$contadorProblemasCodigos] = [
                                            "codigoActivacion"  => $codigoActivacion,
                                            "codigoDiagnostico" => $codigoDiagnostico,
                                            "problema"          => $messageIngreso
                                        ];
                                        $contadorProblemasCodigos++;
                                    }
                                }else{
                                    ///si devuelve despues de enviar el pedido a perseo
                                    if($ifsetProformaA == 2){
                                        $old_valuesA    = json_encode($validarA);
                                        $old_valuesD    = json_encode($validarD);
                                        $this->setearCodigos($institucion_id,$periodo_id,$codigoActivacion,$codigoDiagnostico,$usuario_editor,$old_valuesA,$setContrato,$verificacion_liquidada,$old_valuesD,$ifcodigo_proformaA);
                                    }
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
                    }else{
                        $mensajePadre = "El paquete no tiene codigos";
                    }
                    //codigos resumen
                    $arregloResumen[$contadorResumen] = [
                        "codigoPaquete"             => $item->codigoPaquete,
                        "codigosHijos"              => $problemasconCodigo,
                        "mensajePadre"              => $mensajePadre,
                        "mensaje"                   => empty($getExistsPaquete) ? 1 : '0',
                        "ingresoA"                  => $contadorA,
                        "ingresoD"                  => $contadorD,
                        "noExisteA"                 => $noExisteA,
                        "noExisteD"                 => $noExisteD,
                        "contadorProblemasCodigos"  => $contadorProblemasCodigos,
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
            DB::commit();
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
        catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                "status"  => 0,
                'message' => $e->getMessage()
            ], 200);
        }
    }
    public function setearCodigos($institucion_id,$periodo_id,$codigoActivacion,$codigoDiagnostico,$usuario_editor,$old_valuesA,$setContrato,$verificacion_liquidada,$old_valuesD,$ifcodigo_proformaA){
        $comentario = "No se puede devolver el código porque la $ifcodigo_proformaA ya fue hecho pedido en perseo";
        //activacion
        DB::table('codigoslibros')
        ->where('codigo',$codigoActivacion)
        ->update(['devuelto_proforma' => 2]);
        //diagnostica
        DB::table('codigoslibros')
        ->where('codigo',$codigoDiagnostico)
        ->update(['devuelto_proforma' => 2]);
        $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoActivacion,$usuario_editor,$comentario,$old_valuesA,null,$setContrato,$verificacion_liquidada);
        //====CODIGO UNION=====
        $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoDiagnostico,$usuario_editor,$comentario,$old_valuesD,null,$setContrato,$verificacion_liquidada);
    }
    //API:POST/paquetes/activar_devolucion_paquete
    public function activar_devolucion_paquete(Request $request)
    {
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);

        // Start a database transaction
        DB::beginTransaction();

        try {
            $miArrayDeObjetos           = json_decode($request->data_codigos);
            $arregloProblemaPaquetes    = [];
            $codigoConProblemas         = collect();
            $contadorErrPaquetes        = 0;
            $arregloResumen             = [];
            $contadorResumen            = 0;
            $periodo_id                 = $request->periodo_id;
            $usuario_editor             = $request->id_usuario;
            $comentario                 = $request->observacion;
            //tipoBodega => 3 paquete; 4 = combo
            $tipoBodega                 = $request->tipoBodega;
            $activarRegalado            = $request->activarRegalado;
            $activarBloqueado           = $request->activarBloqueado;
            $activarRegaladoyBloqueado  = $request->activarRegaladoyBloqueado;
            $letraProceso               = $tipoBodega == 3 ? 'paquete' : 'combo';
            //====PROCESO===================================
            foreach ($miArrayDeObjetos as $item) {
                $problemasconCodigo = [];
                $contadorProblemasCodigos = 0;
                $contadorA = 0;
                $contadorD = 0;
                $noExisteA = 0;
                $noExisteD = 0;
                $mensajePadre = "";
                $codigoPaquete = strtoupper($item->codigoPaquete);
                if($tipoBodega == 3){
                    $getExistsPaquete = $this->getExistsPaquete($codigoPaquete);
                }else{
                    $getExistsPaquete = $this->getExistsCombo($codigoPaquete);
                }

                if (!empty($getExistsPaquete)) {
                    //tipoBodega => 3 paquete; 4 = combo
                    if($tipoBodega == 3){
                        $codigosHijos = $this->getCodigos($codigoPaquete, 0, 3);
                        $arrayDiagnosticos = collect($this->getCodigos($codigoPaquete, 0, 4));
                    }else{
                        $codigosHijos = $this->getCodigos($codigoPaquete, 0, 5);
                        $arrayDiagnosticos = collect($this->getCodigos($codigoPaquete, 0, 6));
                    }

                    if (count($codigosHijos)) {
                        foreach ($codigosHijos as $tr) {
                            $validarA = [];
                            $validarD = [];
                            $errorA = 0;
                            $errorD = 0;
                            $codigoActivacion = strtoupper($tr->codigo);
                            $codigoDiagnostico = strtoupper($tr->codigo_union);
                            $codeDiagnostico = $arrayDiagnosticos->filter(function($value) use ($tr) {
                                return $value->codigo == $tr->codigo_union;
                            })->values();

                            // Validation
                            $validarA = [$tr];
                            $validarD = $codeDiagnostico;

                            // Check if both codes exist
                            if (count($validarA) > 0 && count($validarD) > 0) {
                                // Activacion
                                $ifDevueltoA            = $validarA[0]->estado_liquidacion;
                                $estadoA                = $validarA[0]->estado;
                                $ifliquidado_regaladoA  = $validarA[0]->liquidado_regalado;

                                // Diagnostico
                                $ifDevueltoD            = $validarD[0]->estado_liquidacion;
                                $estadoD                = $validarD[0]->estado;
                                $ifliquidado_regaladoD  = $validarD[0]->liquidado_regalado;

                                $ifOmitirA              = false;
                                //si es es liquidado y liquidado regalado colocar error en 1
                                if($ifDevueltoA == '0' || $ifliquidado_regaladoA == '1' || $ifDevueltoA == '4'){
                                    $errorA = 1;
                                }
                                if($ifDevueltoD == '0' || $ifliquidado_regaladoD == '1' || $ifDevueltoD == '4'){
                                    $errorD = 1;
                                }

                                // Record problems
                                if ($errorA == 1 && $errorD == 0) {
                                    $codigoConProblemas->push($validarA);
                                }
                                if ($errorA == 0 && $errorD == 1) {
                                    $codigoConProblemas->push($validarD);
                                }
                                if ($errorA == 1 && $errorD == 1) {
                                    $codigoConProblemas->push($validarA);
                                    $codigoConProblemas->push($validarD);
                                }
                                //OMITIR REGALADOS
                                if($activarRegalado == '0' && ($ifDevueltoA == '2' && $estadoA == '0')){
                                    $ifOmitirA = true;
                                }
                                //OMITIR BLOQUEADOS
                                if($activarBloqueado == '0' && ($ifDevueltoA <> '2' && $estadoA == '2')){
                                    $ifOmitirA = true;
                                }
                                //OMITIR REGALADOS Y BLOQUEADOS
                                if($activarRegaladoyBloqueado == '0' && ($ifDevueltoA == '2' && $estadoA == '2')){
                                    $ifOmitirA = true;
                                }
                                // If both codes pass validation, save them
                                if ($errorA == 0 && $errorD == 0) {
                                    $old_valuesA = json_encode($validarA);
                                    $old_valuesD = json_encode($validarD);
                                    // Transactional operation
                                    $ingreso = $this->codigosRepository->updateActivacion($codigoActivacion, $codigoDiagnostico, $validarD, $ifOmitirA, 0,$request);

                                    if ($ingreso == 1) {
                                        $contadorA++;
                                        $contadorD++;
                                        $this->GuardarEnHistorico(0, 0, $periodo_id, $codigoActivacion, $usuario_editor, $comentario, $old_valuesA, null);
                                        $this->GuardarEnHistorico(0, 0, $periodo_id, $codigoDiagnostico, $usuario_editor, $comentario, $old_valuesD, null);
                                    } else {
                                        $problemasconCodigo[$contadorProblemasCodigos] = [
                                            "codigoActivacion" => $codigoActivacion,
                                            "codigoDiagnostico" => $codigoDiagnostico,
                                            "problema" => "No se pudo guardar"
                                        ];
                                        $contadorProblemasCodigos++;
                                    }
                                } else {
                                    $problemasconCodigo[$contadorProblemasCodigos] = [
                                        "codigoActivacion" => $codigoActivacion,
                                        "codigoDiagnostico" => $codigoDiagnostico,
                                        "problema" => "Problema con el activación o diagnóstico"
                                    ];
                                    $contadorProblemasCodigos++;
                                }
                            } else {
                                if (empty($validarA) && !empty($validarD)) {
                                    $noExisteA++;
                                }
                                if (!empty($validarA) && empty($validarD)) {
                                    $noExisteD++;
                                }
                                if (empty($validarA) && empty($validarD)) {
                                    $noExisteA++;
                                    $noExisteD++;
                                }
                                $problemasconCodigo[$contadorProblemasCodigos] = [
                                    "codigoActivacion" => $codigoActivacion,
                                    "codigoDiagnostico" => $codigoDiagnostico,
                                    "problema" => "Código no existe"
                                ];
                                $contadorProblemasCodigos++;
                            }
                        }
                    } else {
                        $mensajePadre = "El paquete no tiene códigos";
                    }

                    // Summary record
                    $arregloResumen[$contadorResumen]   = [
                        "codigoPaquete"                 => $codigoPaquete,
                        "codigosHijos"                  => $problemasconCodigo,
                        "mensaje"                       => empty($getExistsPaquete) ? 1 : '0',
                        "mensajePadre"                  => $mensajePadre,
                        "ingresoA"                      => $contadorA,
                        "ingresoD"                      => $contadorD,
                        "noExisteA"                     => $noExisteA,
                        "noExisteD"                     => $noExisteD,
                        "contadorProblemasCodigos"      => $contadorProblemasCodigos,
                    ];
                    $contadorResumen++;
                } else {
                    $arregloProblemaPaquetes[$contadorErrPaquetes] = [
                        "paquete" => $item->codigoPaquete,
                        "problema" => $letraProceso." no existe"
                    ];
                    $contadorErrPaquetes++;
                }
            }

            // Commit transaction
            DB::commit();

            return [
                "arregloResumen"            => $arregloResumen,
                "codigoConProblemas"        => $codigoConProblemas->isEmpty() ? [] : $codigoConProblemas->flatten(10),
                "arregloErroresPaquetes"    => $arregloProblemaPaquetes,
            ];
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            // Return error response
            return [
                'status' => 0,
                'message' => $e->getMessage()
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
