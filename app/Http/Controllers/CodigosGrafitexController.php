<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CodigosLibros;
use App\Traits\Codigos\TraitCodigosGeneral;
use Illuminate\Http\Request;
use DB;

class CodigosGrafitexController extends Controller
{
    use TraitCodigosGeneral;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //grafitex/codigos
    public function index()
    {
        return "xd";
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
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);
        $codigos                    = json_decode($request->data_codigos);
        $contadorActivacion         = $request->contadorActivacion;
        $contadorDiagnostico        = $request->contadorDiagnostico;
        $codigosError               = [];
        $codigosGuardados           = [];
        $contadorError              = 0;
        $porcentajeA                = 0;
        $porcentajeD                = 0;
        $contadorUnion              = 0;
        $tipoCodigo                 = $request->tipoCodigo;
        foreach($codigos as $key => $item){
            $codigo_activacion      = "";
            $codigo_diagnostico     = "";
            $codigo_activacion      = $item->codigo_activacion;
            $codigo_diagnostico     = $item->codigo_diagnostico;
            $statusIngreso          = 0;
            $contadorCodigoA        = "";
            $contadorCodigoD        = "";
            //only activacion
            if($tipoCodigo == 1){
                $ingresoA               = $this->save_Codigos($request,$item,$codigo_activacion,$codigo_diagnostico,0,$contadorActivacion);
                $statusIngreso          = $ingresoA["contadorIngreso"];
                $contadorCodigoA        = $ingresoA["contador"];
            }
            //only activacion
            if($tipoCodigo == 2){
                $ingresoD               = $this->save_Codigos($request,$item,$codigo_diagnostico,$codigo_activacion,1,$contadorDiagnostico);
                $statusIngreso          = $ingresoD["contadorIngreso"];
                $contadorCodigoD        = $ingresoD["contador"];
            }
            //ambos
            if($tipoCodigo == 3){
                $ingresoA               = $this->save_Codigos($request,$item,$codigo_activacion,$codigo_diagnostico,0,$contadorActivacion);
                $ingresoD               = $this->save_Codigos($request,$item,$codigo_diagnostico,$codigo_activacion,1,$contadorDiagnostico);
                $contadorCodigoA        = $ingresoA["contador"];
                $contadorCodigoD        = $ingresoD["contador"];
                if($ingresoA["contadorIngreso"] == 1 && $ingresoD["contadorIngreso"] == 1)  $statusIngreso = 1;
                else                    $statusIngreso = 0;
            }
            //si ingresa el codigo de activacion y el codigo de diagnostico
            if($statusIngreso == 1){
                $contadorActivacion++;
                $contadorDiagnostico++;
                if($tipoCodigo == 1)  $porcentajeA++;
                if($tipoCodigo == 2)  $porcentajeD++;
                if($tipoCodigo == 3){
                    $porcentajeA++;
                    $porcentajeD++;
                }
                $codigosGuardados[$contadorUnion] = [
                    "codigo_activacion"  => $codigo_activacion,
                    "codigo_diagnostico" => $codigo_diagnostico,
                    "libro"              => $item->libro,
                    "anio"               => $item->anio,
                    "contadorCodigoA"    => $contadorCodigoA,
                    "contadorCodigoD"    => $contadorCodigoD,
                ];
                $contadorUnion++;
            }else{
                $codigosError[$contadorError] = [
                    "codigo_activacion"  => $codigo_activacion,
                    "codigo_diagnostico" => $codigo_diagnostico,
                    "message"            => "Problemas no se ingresaron bien"
                ];
                $contadorError++;
            }
        }
        return [
            "porcentajeA"           => $porcentajeA ,
            "porcentajeD"           => $porcentajeD ,
            "codigosNoIngresados"   => $codigosError,
            "codigosGuardados"      => $codigosGuardados,
        ];
    }
    public function save_Codigos($request,$item,$codigo,$codigo_union,$prueba_diagnostica,$contador){
        $contadorIngreso                            = 0;
        $codigos_libros                             = new CodigosLibros();
        $codigos_libros->serie                      = $item->serie;
        $codigos_libros->libro                      = $item->libro;
        $codigos_libros->anio                       = $item->anio;
        $codigos_libros->libro_idlibro              = $item->libro_idlibro;
        $codigos_libros->estado                     = 0;
        $codigos_libros->idusuario                  = 0;
        $codigos_libros->bc_estado                  = 1;
        $codigos_libros->idusuario_creador_codigo   = $request->user_created;
        $codigos_libros->prueba_diagnostica         = $prueba_diagnostica;
        $codigos_libros->codigo_union               = $codigo_union;
        $codigos_libros->creado_grafitex            = 1;
        $codigo_verificar                           = $codigo;
        $verificar_codigo = DB::SELECT("SELECT codigo from codigoslibros WHERE codigo = '$codigo_verificar'");
        if( $verificar_codigo ){
            $contadorIngreso = 0;
        }else{
            $codigos_libros->codigo = $codigo;
            $codigos_libros->contador = ++$contador;
            $codigos_libros->save();
            if($codigos_libros){
                $contadorIngreso = 1;
            }else{
                $contadorIngreso = 0;
            }
        }
        if($contadorIngreso == 1){
            return [
                "contadorIngreso" => $contadorIngreso,
                "contador"        => $codigos_libros->contador
            ];
        }else{
            return [
                "contadorIngreso" => $contadorIngreso,
                "contador"        => 0
            ];
        }

    }
    public function generarCodigosGrafitex(Request $request){
        $longitud               = $request->longitud;
        $codeA                  = $request->codeA;
        $codeD                  = $request->codeD;
        $cantidad               = $request->cantidad;
        $arregloCodigos         = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $codigo_activacion  =  $this->generateCodigos($codeA,$longitud);
            $codigo_diagnostico =  $this->generateCodigos($codeD,$longitud);
            $arregloCodigos[$i] = [
                "codigo_activacion"  => $codigo_activacion,
                "codigo_diagnostico" => $codigo_diagnostico
            ];
        }
        return ["codigos" => $arregloCodigos];
    }
    public function generateCodigos($code,$longitud){
        $caracter   = $this->makeid($longitud);
        $codigos_validacion     = array();
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
            $validar = DB::SELECT("SELECT codigo from codigoslibros WHERE codigo = '$codigo'");
            $cant_int = 0;
            $codigo_disponible = 1;
            while ( count($validar) > 0 ) {
                $caracter = $this->makeid($longitud);
                $codigo = $code.$caracter;
                $validar = DB::SELECT("SELECT codigo from codigoslibros WHERE codigo = '$codigo'");
                $cant_int++;
                if( $cant_int == 10 ){
                    $codigo_disponible = 0;
                    $validar = ['repetido' => 'repetido'];
                }
            }
            if( $codigo_disponible == 1 ){
                return $codigo;
            }
        }
    }
    //API:POST/grafitex/import/gestion
    public function importGestionGrafitex(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos            = json_decode($request->data_codigos);
        //variables
        $usuario_editor     = $request->id_usuario;
        $institucion_id     = $request->institucion_id;
        $comentario         = $request->comentario;
        $periodo_id         = $request->periodo_id;
        $contadorA          = 0;
        $contadorD          = 0;
        $TipoVenta          = $request->venta_estado;
        $codigoNoExiste     = [];
        $contadorNoExiste   = 0;
        // Supongamos que tienes una colección vacía
        $codigosNoExisten   = collect();
        $codigoConProblemas = collect();
        foreach($codigos as $key => $item){
            //validar si el codigo existe
            $validar                 = $this->getCodigos($item->codigo,0);
            //valida que el codigo existe
            if(count($validar)>0){
                //VARIABLES
                $codigoActivacion    = $item->codigo;
                //validar si tiene codigo de union
                $codigoDiagnostico   = $validar[0]->codigo_union;
                //validacion
                $validarA            = $this->getCodigos($codigoActivacion,0);
                $validarD            = $this->getCodigos($codigoDiagnostico,0);
                //====PROCESO================
                //======si ambos codigos existen========
                if(count($validarA) > 0 && count($validarD) > 0){
                    //====VARIABLES DE CODIGOS===
                    //====Activacion=====
                    //validar si el codigo ya esta liquidado
                    $ifLiquidadoA                = $validarA[0]->estado_liquidacion;
                    //validar si el codigo no este liquidado
                    $ifBloqueadoA                = $validarA[0]->estado;
                    //validar si tiene bc_institucion
                    $ifBc_InstitucionA           = $validarA[0]->bc_institucion;
                    //validar que el periodo del estudiante sea 0 o sea igual al que se envia
                    $ifid_periodoA               = $validarA[0]->id_periodo;
                    //validar si el codigo tiene venta_estado
                    $venta_estadoA               = $validarA[0]->venta_estado;
                    //venta lista
                    $ifventa_lista_institucionA  = $validarA[0]->venta_lista_institucion;
                    //======Diagnostico=====
                    //validar si el codigo ya esta liquidado
                    $ifLiquidadoD                = $validarD[0]->estado_liquidacion;
                    //validar si el codigo no este liquidado
                    $ifBloqueadoD                = $validarD[0]->estado;
                    //validar si tiene bc_institucion
                    $ifBc_InstitucionD           = $validarD[0]->bc_institucion;
                    //validar que el periodo del estudiante sea 0 o sea igual al que se envia
                    $ifid_periodoD               = $validarD[0]->id_periodo;
                    //validar si el codigo tiene venta_estado
                    $venta_estadoD               = $validarD[0]->venta_estado;
                    //venta lista
                    $ifventa_lista_institucionD  = $validarD[0]->venta_lista_institucion;
                    $old_valuesA = CodigosLibros::Where('codigo',$codigoActivacion)->get();
                    $old_valuesD = CodigosLibros::findOrFail($codigoDiagnostico);
                    //===VENTA DIRECTA====
                    if($TipoVenta == 1){
                        if(($ifid_periodoA  == $periodo_id || $ifid_periodoA == 0 ||  $ifid_periodoA == null  ||  $ifid_periodoA == "") && ( $ifBc_InstitucionA == 0 || $ifBc_InstitucionA == $institucion_id )   && $ifLiquidadoA == '1' && $ifBloqueadoA !=2 && ($venta_estadoA == 0  || $venta_estadoA == null || $venta_estadoA == "null")){
                            if(($ifid_periodoD  == $periodo_id || $ifid_periodoD == 0 ||  $ifid_periodoD == null  ||  $ifid_periodoD == "") && ( $ifBc_InstitucionD == 0 || $ifBc_InstitucionD == $institucion_id )   && $ifLiquidadoD == '1' && $ifBloqueadoD !=2 && ($venta_estadoD == 0  || $venta_estadoD == null || $venta_estadoD == "null")){
                            //Ingresar Union a codigo de activacion
                            $codigoA     =  $this->UpdateCodigo($codigoActivacion,$codigoDiagnostico,$request);
                            if($codigoA){  $contadorA++; $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoActivacion,$usuario_editor,$comentario,$old_valuesA,null); }
                            //Ingresar Union a codigo de prueba diagnostico
                            $codigoB = $this->UpdateCodigo($codigoDiagnostico,$codigoActivacion,$request);
                            if($codigoB){  $contadorD++; $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoDiagnostico,$usuario_editor,$comentario,$old_valuesD,null); }
                            }else{
                                $codigoConProblemas->push($validarD);
                            }
                        }else{
                            $codigoConProblemas->push($validarA);
                        }
                    }
                    //==VENTA LISTA=====
                    if($TipoVenta == 2){
                        if(($ifid_periodoA  == $periodo_id || $ifid_periodoA == 0 ||  $ifid_periodoA == null  ||  $ifid_periodoA == "") && ($venta_estadoA == 0  || $venta_estadoA == null || $venta_estadoA == "null") && $ifLiquidadoA == '1' && $ifBloqueadoA !=2 && $ifventa_lista_institucionA == '0'){
                            if(($ifid_periodoD  == $periodo_id || $ifid_periodoD == 0 ||  $ifid_periodoD == null  ||  $ifid_periodoD == "") && ($venta_estadoD == 0  || $venta_estadoD == null || $venta_estadoD == "null") && $ifLiquidadoD == '1' && $ifBloqueadoD !=2 && $ifventa_lista_institucionD == '0'){
                                //Ingresar Union a codigo de activacion
                                $codigoA        =  $this->UpdateCodigo($codigoActivacion,$codigoDiagnostico,$request);
                                if($codigoA){  $contadorA++; $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoActivacion,$usuario_editor,$comentario,$old_valuesA,null); }
                                //Ingresar Union a codigo de prueba diagnostico
                                $codigoB        = $this->UpdateCodigo($codigoDiagnostico,$codigoActivacion,$request);
                                if($codigoB){  $contadorD++; $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoDiagnostico,$usuario_editor,$comentario,$old_valuesD,null); }
                            }else{
                                $codigoConProblemas->push($validarD);
                            }
                        }
                        else{
                            $codigoConProblemas->push($validarA);
                        }
                    }
                }
                //Si uno de los 2 codigos no existen
                else{
                    //si no existe el codigo de activacion
                    if(count($validarA) == 0 && count($validarD) > 0){
                        $codigosNoExisten->push(['codigoNoExiste' => "activacion", 'codigoActivacion' => $codigoActivacion, 'codigoDiagnostico' => $codigoDiagnostico]);
                    }
                    //si no existe el codigo de diagnostico
                    if(count($validarD) == 0 && count($validarA) > 0){
                        $codigosNoExisten->push(['codigoNoExiste' => "diagnostico",'codigoActivacion' => $codigoActivacion, 'codigoDiagnostico' => $codigoDiagnostico]);
                    }
                    //si no existe ambos
                    if(count($validarA) == 0 && count($validarD) == 0){
                        $codigosNoExisten->push(['codigoNoExiste' => "ambos",      'codigoActivacion' => $codigoActivacion, 'codigoDiagnostico' => $codigoDiagnostico]);
                    }
                }
            }else{
                $codigoNoExiste[$contadorNoExiste] =[
                    "codigo" => $item->codigo
                ];
                $contadorNoExiste++;
            }


        }
        if(count($codigoConProblemas) == 0){
            return [
                "CodigosDiagnosticoNoexisten"      => $codigosNoExisten->all(),
                "codigoConProblemas"               => [],
                "contadorA"                        => $contadorA,
                "contadorD"                        => $contadorD,
                "codigosNoExisten"                 => $codigoNoExiste
            ];
        }else{
            return [
                "CodigosDiagnosticoNoexisten"      => $codigosNoExisten->all(),
                "codigoConProblemas"               => array_merge(...$codigoConProblemas->all()),
                "contadorA"                        => $contadorA,
                "contadorD"                        => $contadorD,
                "codigosNoExisten"                 => $codigoNoExiste
            ];
        }
    }
    public function UpdateCodigo($codigo,$union,$request){
        if($request->venta_estado == 1){
            return $this->updateCodigoVentaDirecta($codigo,$union,$request);
        }
        if($request->venta_estado == 2){
            return $this->updateCodigoVentaLista($codigo,$union,$request);
        }
    }
    public function updateCodigoVentaDirecta($codigo,$union,$request){
        $codigo = DB::table('codigoslibros')
            ->where('codigo', '=', $codigo)
            ->where('estado_liquidacion', '=', '1')
            //(SE QUITARA PARA AHORA EL ESTUDIANTE YA ENVIA LEIDO) ->where('bc_estado', '=', '1')
            ->update([
                'factura'           => $request->factura,
                'bc_institucion'    => $request->institucion_id,
                'bc_periodo'        => $request->periodo_id,
                'venta_estado'      => $request->venta_estado,
                'codigo_union'      => $union
            ]);
        return $codigo;
    }
    public function updateCodigoVentaLista($codigo,$union,$request){
        $codigo = DB::table('codigoslibros')
            ->where('codigo', '=', $codigo)
            ->where('estado_liquidacion', '=', '1')
            //(SE QUITARA PARA AHORA EL ESTUDIANTE YA ENVIA LEIDO) ->where('bc_estado', '=', '1')
            ->update([
                'factura'                   => $request->factura,
                'venta_lista_institucion'   => $request->institucion_id,
                'bc_periodo'                => $request->periodo_id,
                'venta_estado'              => $request->venta_estado,
                'codigo_union'              => $union
            ]);
        return $codigo;
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

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
