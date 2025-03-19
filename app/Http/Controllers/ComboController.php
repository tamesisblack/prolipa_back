<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CodigosLibros;
use App\Models\CodigosLibrosDevolucionSon;
use App\Models\CombosCodigos;
use App\Models\LibroSerie;
use App\Traits\Codigos\TraitCodigosGeneral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ComboController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    use TraitCodigosGeneral;
    //api:get/copmbos
    public function index(Request $request)
    {
        if($request->getComboConfigurado){ return $this->getComboConfigurado($request); }
    }

    //api:get/combos?getComboConfigurado=1
    public function getComboConfigurado($request)
    {
        $getCombos = DB::SELECT("SELECT * FROM codigos_configuracion
        WHERE id = '2'
        ");
        return $getCombos;
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
    //api:post/combos
    public function store(Request $request)
    {
        if($request->has('AsignarCombo'))                 { return $this->AsignarCombo($request); }
        if($request->has('AsignarComboCodigosLibrosSon')) { return $this->AsignarComboCodigosLibrosSon($request); }
    }

    //api:post/combos?AsignarCombo=1
    public function AsignarCombo(Request $request)
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);

        $id_usuario             = $request->id_usuario;
        $periodo_id             = $request->periodo_id;
        $codigosConProblemas    = [];
        // Validación del request
        $request->validate([
            'data_codigos' => 'required|json',
        ]);

        $codigos = json_decode($request->data_codigos);

        DB::beginTransaction();
        try {
            // Obtener los códigos a buscar
            $codigosBuscar = array_column($codigos, 'codigo');

            // Buscar códigos en la base de datos
            $busqueda = CodigosLibros::whereIn('codigo', $codigosBuscar)->get();

            // Extraer los códigos encontrados
            $codigosEncontrados = $busqueda->pluck('codigo')->toArray();

            // Determinar los códigos que no fueron encontrados
            $codigosNoEncontrados = array_values(array_diff($codigosBuscar, $codigosEncontrados));

            $contadorEditados = 0;

            foreach ($codigos as $item) {
                $codigo     = $busqueda->firstWhere('codigo', $item->codigo);
                $combo      = $item->combo;
                if ($codigo) {

                    //validra si el combo existe
                    $validateCombo = LibroSerie::where('codigo_liquidacion', $combo)->first();
                    if ($validateCombo) {
                        $comboAnterior  = $codigo->combo;
                        $getCodigoUnion = $codigo->codigo_union;
                        //si el combo es nulo vacio o es igual al combo anterior guardar
                        if ($comboAnterior == "" || $combo == $comboAnterior) {
                            // Si el código tiene `codigo_union`, actualizar
                            if ($getCodigoUnion && $getCodigoUnion != "") {
                                $codigoUnion = CodigosLibros::where('codigo', $getCodigoUnion)->first();
                                if ($codigoUnion) {
                                    $this->actualizarComboYGuardarHistorico($codigoUnion, $combo, $periodo_id, $id_usuario, "Se asigna el combo $combo");
                                }
                            }
                            // Actualizar el código principal
                            $this->actualizarComboYGuardarHistorico($codigo, $combo, $periodo_id, $id_usuario, "Se asigna el combo $combo");

                            $contadorEditados++;
                        }else{
                            //error porque el comobo ya existe y es distinto al anterior
                            $item->error = "El código ya existe con el combo $comboAnterior";
                            $codigosConProblemas[] = $item;
                        }
                    }else{
                        //error porque el combo no existe
                        $item->error = "El combo $combo no existe";
                        $codigosConProblemas[] = $item;
                    }
                }
            }

            DB::commit();

            return response()->json([
                "status"                => 1,
                "message"               => "Operación exitosa",
                "codigoNoExiste"        => $codigosNoEncontrados,
                "cambiados"             => $contadorEditados,
                "codigosConProblemas"   => $codigosConProblemas
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status"  => 0,
                "message" => $e->getMessage()
            ], 200); // Status de error
        }
    }
    //api:post/combos?AsignarComboCodigosLibrosSon=1
    public function AsignarComboCodigosLibrosSon(Request $request)
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);
        $codigosConProblemas    = [];
        // Validación del request
        $request->validate([
            'data_codigos' => 'required|json',
        ]);

        $codigos = json_decode($request->data_codigos);

        DB::beginTransaction();
        try {
            // Obtener los códigos a buscar
            $codigosBuscar = array_column($codigos, 'codigo');

            // Buscar códigos en la base de datos
            $busqueda = CodigosLibrosDevolucionSon::whereIn('codigo', $codigosBuscar)->get();
            // Extraer los códigos encontrados
            $codigosEncontrados = $busqueda->pluck('codigo')->toArray();

            // Determinar los códigos que no fueron encontrados
            $codigosNoEncontrados = array_values(array_diff($codigosBuscar, $codigosEncontrados));

            $contadorEditados = 0;

            foreach ($codigos as $item) {
                $codigo     = $busqueda->firstWhere('codigo', $item->codigo);
                $combo      = $item->combo;
                if ($codigo) {
                    $estado     = $codigo->estado;
                    if($estado == 0){
                         //validar si el combo existe
                         $validateCombo = LibroSerie::where('codigo_liquidacion', $combo)->first();
                         if ($validateCombo) {
                             $comboAnterior  = $codigo->combo;
                             //si el combo es nulo vacio o es igual al combo anterior guardar
                             if ($comboAnterior == "" || $combo == $comboAnterior) {
                                // Si el código tiene `codigo_union`, actualizar
                                //Actualizar codigslibroson si tiene estado 0
                                $getCodigosLibrosSon = CodigosLibrosDevolucionSon::where('codigo', $item->codigo)
                                ->where('estado', '0');

                                if ($getCodigosLibrosSon->exists()) {
                                    $getCodigosLibrosSon->update(['combo' => $combo]);
                                    $contadorEditados++;
                                }else{
                                    //error porque el codigo ya no esta en estado creado
                                    $item->error = "El código ya no esta en en estado creado";
                                    $codigosConProblemas[] = $item;
                                }

                             }else{
                                //error porque el comobo ya existe y es distinto al anterior
                                $item->error = "El código ya existe con el combo $comboAnterior";
                                $codigosConProblemas[] = $item;
                             }
                         }else{
                             //error porque el combo no existe
                             $item->error = "El combo $combo no existe";
                             $codigosConProblemas[] = $item;
                         }
                    }
                    else{
                        //error porque el codiog ya no esta en estado creado
                        $item->error = "El código ya no esta en en estado creado";
                        $codigosConProblemas[] = $item;
                    }
                }
            }

            DB::commit();

            return response()->json([
                "status"                => 1,
                "message"               => "Operación exitosa",
                "codigoNoExiste"        => $codigosNoEncontrados,
                "cambiados"             => $contadorEditados,
                "codigosConProblemas"   => $codigosConProblemas
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status"  => 0,
                "message" => $e->getMessage()
            ], 200); // Status de error
        }
    }
    /**
     * Actualiza el combo y guarda en el histórico.
     */
    private function actualizarComboYGuardarHistorico($codigo, $combo, $periodo_id, $id_usuario, $comentario)
    {
        $oldValues = $codigo->getAttributes(); // Capturar valores actuales
        $oldValues = json_encode($oldValues);
        $codigo->combo = $combo; // Actualizar combo
        if ($codigo->save()) {
            $this->GuardarEnHistorico(0, 0, $periodo_id, $codigo->codigo, $id_usuario, $comentario, $oldValues, json_encode($codigo->getAttributes()) );
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

    //api:post/guardarAsignacionCodigo
    public function guardarAsignacionCodigo(Request $request)
    {
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);

        $miArrayDeObjetos           = json_decode($request->data_codigos);
        //variables
        $usuario_editor             = $request->id_usuario;
        $periodo_id                 = $request->periodo_id;
        $arregloProblemaCombos      = [];
        $arregloResumen             = [];
        $codigoConProblemas         = collect();
        $contadorErrCombos          = 0;
        $contadorResumen            = 0;
        $institucion_id             = 0;
        $variosCodigos              = $request->varios_codigos;

        try {
            DB::beginTransaction();

            //====PROCESO===================================
            foreach ($miArrayDeObjetos as $key => $item) {
                //variables
                $problemasconCodigo         = [];
                $contadorProblemasCodigos   = 0;
                $contadorA                  = 0;
                $contadorB                  = 0;
                $noExisteA                  = 0;
                $noExisteB                  = 0;
                $codigoCombo = strtoupper($item->codigoCombo);

                //VALIDAR QUE EL CODIGO DE COMBO EXISTE
                $ExistsCombo = $this->getExistsCombo($codigoCombo);
                if (!empty($ExistsCombo)) {
                    $getEstadoCombo = $ExistsCombo[0]->estado;
                    //si es estado 2 esta bloqueado
                    if($getEstadoCombo == 2){
                        $arregloProblemaCombos[$contadorErrCombos] = [
                            "combo"     => $codigoCombo,
                            "problema"  => 'Combo bloqueado'
                        ];
                        $contadorErrCombos++;
                        continue;
                    }
                    foreach ($item->codigosHijos as $key2 => $tr) {
                        $codigoA                = '';
                        $codigoB                = '';
                        $libroSeleccionado      = $tr->libroSeleccionado;
                        $tipoRegalado           = $tr->tipoRegalado;
                        $errorA                 = 1;
                        $errorB                 = 1;
                        $mensajeRegalado        = '';
                        if($libroSeleccionado == '1'){
                            $mensajeRegalado = '_Regalado';
                        }
                        IF($tipoRegalado == '2'){
                            $mensajeRegalado = '_Regalado_y_bloqueado';
                        }
                        $comentario             = "Se agregó al combo " . $codigoCombo.$mensajeRegalado;
                        if($variosCodigos == '1'){
                            $codigoA            = strtoupper($tr->codigoActivacion);
                            $codigoB            = strtoupper($tr->codigoDiagnostico);
                        }
                        if($variosCodigos == '0'){
                            $codigoA            = strtoupper($tr->codigo);
                        }

                        //validar si el codigo existe
                        $validarA = CodigosLibros::where('codigo', $codigoA)->get();
                        if (count($validarA) > 0) {
                            if($variosCodigos == 0){ $codigoB = strtoupper($validarA[0]->codigo_union); }
                            $validarB           = CodigosLibros::where('codigo', $codigoB)->get();
                            if (count($validarB) > 0) {
                                $comboIgualAnteriorA = false;
                                $comboIgualAnteriorB = false;
                                //VARIABLES PARA EL PROCESO
                                //CODIGO A
                                $ifcodigo_comboA            = strtoupper($validarA[0]->codigo_combo);
                                $codigo_unionA              = strtoupper($validarA[0]->codigo_union);
                                $ifestado                   = $validarA[0]->estado;
                                $if_estado_liquidacionA     = $validarA[0]->estado_liquidacion;
                                $if_liquidado_regaladoA     = $validarA[0]->liquidado_regalado;
                                $if_bc_institucionA         = $validarA[0]->bc_institucion;
                                $venta_lista_institucionA   = $validarA[0]->venta_lista_institucion;

                                //CODIGO B
                                $ifcodigo_comboB            = strtoupper($validarB[0]->codigo_combo);
                                $codigo_unionB              = strtoupper($validarB[0]->codigo_union);
                                $ifestado                   = $validarB[0]->estado;
                                $if_estado_liquidacionB     = $validarB[0]->estado_liquidacion;
                                $if_liquidado_regaladoB     = $validarB[0]->liquidado_regalado;
                                $if_bc_institucionB         = $validarB[0]->bc_institucion;
                                $venta_lista_institucionB   = $validarB[0]->venta_lista_institucion;

                                //===VALIDACION COMBO NORMAL====
                                if (($ifcodigo_comboA == null || $ifcodigo_comboA == $codigoCombo) && (($codigo_unionA == $codigoB) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0"))) { $mensajeError = CodigosLibros::CODIGO_ACTIVACION;  $errorA = 0; }
                                if (($ifcodigo_comboB == null || $ifcodigo_comboB == $codigoCombo) && (($codigo_unionB == $codigoA) || ($codigo_unionB == null || $codigo_unionB == "" || $codigo_unionB == "0"))) { $mensajeError = CodigosLibros::CODIGO_DIAGNOSTICA; $errorB = 0; }
                                $comboIgualAnteriorA = ($ifcodigo_comboA == $codigoCombo);
                                $comboIgualAnteriorB = ($ifcodigo_comboB == $codigoCombo);
                                //===VALIDACION SI ES libroSeleccionado 1 porque se va a colocar como regalado===
                                // if($libroSeleccionado == '1'){
                                    //codigo A
                                    if($if_estado_liquidacionA == 0)    { $mensajeError = CodigosLibros::CODIGO_LIQUIDADO;  $errorA = 1; }
                                    //CODIGO_LIQUIDADO_REGALADO
                                    if($if_liquidado_regaladoA == 1)    { $mensajeError = CodigosLibros::CODIGO_LIQUIDADO_REGALADO;  $errorA = 1; }
                                    //bloqueado
                                    // if($ifestado == 2)                  { $mensajeError = CodigosLibros::CODIGO_BLOQUEADO;  $errorA = 1; }
                                    //if($if_estado_liquidacionA == 3){ $mensajeError = CodigosLibros::CODIGO_DEVUELTO;   $errorA = 1; }
                                    if($if_estado_liquidacionA == 4)    { $mensajeError = CodigosLibros::CODIGO_GUIA;       $errorA = 1; }
                                    //codigo B
                                    if($if_estado_liquidacionB == 0)    { $mensajeError = CodigosLibros::CODIGO_LIQUIDADO;  $errorB = 1; }
                                    //CODIGO_LIQUIDADO_REGALADO
                                    if($if_liquidado_regaladoB == 1)    { $mensajeError = CodigosLibros::CODIGO_LIQUIDADO_REGALADO;  $errorB = 1; }
                                    //bloqueado
                                    // if($ifestado == 2 && $if_estado_liquidacionA !=3)  { $mensajeError = CodigosLibros::CODIGO_BLOQUEADO;  $errorB = 1; }
                                    // if($if_estado_liquidacionB == 3){ $mensajeError = CodigosLibros::CODIGO_DEVUELTO;   $errorB = 1; }
                                    if($if_estado_liquidacionB == 4)    { $mensajeError = CodigosLibros::CODIGO_GUIA;       $errorB = 1; }
                                    //institucion si bc_institucion o venta_lista_institucion es mayor a 0  seria mensajeError
                                    // if($if_bc_institucionA > 0 || $venta_lista_institucionA > 0){ $mensajeError = CodigosLibros::CODIGO_CON_INSTITUCION;  $errorA = 1; }
                                    // if($if_bc_institucionB > 0 || $venta_lista_institucionB > 0){ $mensajeError = CodigosLibros::CODIGO_CON_INSTITUCION;  $errorB = 1; }
                                //}
                                //===MENSAJE VALIDACION====
                                if ($errorA == 1 && $errorB == 0) {
                                    $codigoConProblemas->push($validarA);
                                }
                                if ($errorA == 0 && $errorB == 1) {
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
                                    $ingresoA       = $this->updatecodigosCombo($codigoCombo, $codigoA, $codigoB, $libroSeleccionado, $tipoRegalado, $comboIgualAnteriorA);
                                    $old_valuesB    = $validarB;
                                    $ingresoB       = $this->updatecodigosCombo($codigoCombo, $codigoB, $codigoA, $libroSeleccionado, $tipoRegalado,$comboIgualAnteriorB);

                                    if($ingresoA && $ingresoB){
                                        // if($comboIgualAnteriorA){}
                                        // else{ $this->GuardarEnHistorico(0, $institucion_id, $periodo_id, $codigoB, $usuario_editor, $comentario, $old_valuesB, null); }
                                        // if($comboIgualAnteriorB){}
                                        // else{ $this->GuardarEnHistorico(0, $institucion_id, $periodo_id, $codigoA, $usuario_editor, $comentario, $old_valuesA, null); }
                                        $this->GuardarEnHistorico(0, $institucion_id, $periodo_id, $codigoB, $usuario_editor, $comentario, $old_valuesB, null);
                                        $this->GuardarEnHistorico(0, $institucion_id, $periodo_id, $codigoA, $usuario_editor, $comentario, $old_valuesA, null); 
                                        $contadorA++;
                                        $contadorB++;
                                    }
                                    else{
                                        $problemasconCodigo[$contadorProblemasCodigos] = [
                                            "codigoActivacion"  => $codigoA,
                                            "codigoDiagnostico" => $codigoB,
                                            "problema"          => "No se guardaron los codigos"
                                        ];
                                        $contadorProblemasCodigos++;
                                    }
                                } else {
                                    //SI NO INGRESA ALGUNO DE LOS CODIGOS ENVIO AL FRONT
                                    $problemasconCodigo[$contadorProblemasCodigos] = [
                                        "codigoActivacion"      => $codigoA,
                                        "codigoDiagnostico"     => $codigoB,
                                        "problema"              => $mensajeError
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
                        "codigoPaquete"             => $codigoCombo,
                        "codigosHijos"              => $problemasconCodigo,
                        "mensaje"                   => empty($ExistsCombo) ? 1 : '0',
                        "ingresoA"                  => $contadorA,
                        "ingresoD"                  => $contadorB,
                        "noExisteA"                 => $noExisteA,
                        "noExisteD"                 => $noExisteB,
                        "contadorProblemasCodigos"  => $contadorProblemasCodigos,
                    ];
                    $contadorResumen++;
                } else {
                    $arregloProblemaCombos[$contadorErrCombos] = [
                        "combo"     => $codigoCombo,
                        "problema"  => 'Combo no existe'
                    ];
                    $contadorErrCombos++;
                }
                //si contadorA es mayor a cero marco el combo como usado
                if($contadorA > 0){
                    //colocar el combo como utilizado
                    $this->changeUseCombo($codigoCombo);
                }
            }

            DB::commit(); // Commit the transaction

            if (count($codigoConProblemas) == 0) {
                return [
                    "arregloResumen"            => $arregloResumen,
                    "codigoConProblemas"        => [],
                    "arregloErroresCombos"      => $arregloProblemaCombos,
                ];
            } else {
                $getProblemas = [];
                $arraySinCorchetes = array_map(function ($item) { return json_decode(json_encode($item)); }, $codigoConProblemas->all());
                $getProblemas = array_merge(...$arraySinCorchetes);
                return [
                    "arregloResumen"            => $arregloResumen,
                    "codigoConProblemas"        => $getProblemas,
                    "arregloErroresCombos"      => $arregloProblemaCombos,
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback the transaction
            return response()->json(['status' => '0', 'message' => 'Ocurrió un error al guardar el combo: ' . $e->getMessage()], 200);
        }
    }
    public function getCombo($combo){
        $query = DB::SELECT("SELECT * FROM codigos_combos p
        WHERE p.codigo = '$combo'
        AND p.estado   = '1'
        ");
        return $query;
    }
    public function getExistsCombo($combo){
        $query = DB::SELECT("SELECT * FROM codigos_combos p WHERE p.codigo = '$combo'");
        return $query;
    }
    public function changeUseCombo($codigo){
        $combo = CombosCodigos::findOrFail($codigo);
        $combo->estado = "0";
        $combo->save();
    }
    public function updatecodigosCombo($codigoCombo,$codigo,$codigo_union, $libroSeleccionado, $tipoRegalado,$comboIgualAnterior){
        // if($comboIgualAnterior){
        //     return 1;
        // }
        $codigoLibro = CodigosLibros::where('codigo', '=', $codigo)->first();
        $fecha = date('Y-m-d H:i:s');
        if ($codigoLibro) {
            $codigoLibro->codigo_combo              = $codigoCombo;
            $codigoLibro->fecha_registro_combo      = $fecha;
            $codigoLibro->codigo_union              = $codigo_union;
            if($libroSeleccionado == '1'){
                $codigoLibro->estado_liquidacion    = '2';
                //tipoRegalado = 1 => regalado ; 2 => regalado y bloqueado
                if($tipoRegalado == '2')            { $codigoLibro->estado = '2'; }
            }
            // Guarda los cambios
            $guardado = $codigoLibro->save();

            return $guardado ? 1 : 0; // Retorna 1 si se guardó, 0 si no
        } else {
            return 0; // Retorna 0 si no se encontró el código
        }
    }
}
