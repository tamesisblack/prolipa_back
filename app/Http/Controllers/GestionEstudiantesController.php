<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HistoricoChangeEstudiante;
use App\Models\Usuario;
use Illuminate\Http\Request;
use DB;
class GestionEstudiantesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->getHistorico){
            return $this->getHistorico($request->idusuario);
        }
        if($request->getEstudiantesInstitucion){
            return $this->getEstudiantesInstitucion($request->institucion_id, $request->periodo_id);
        }
        if($request->getEstudiantesxMateria){
            return $this->getEstudiantesxMateria($request->institucion_id, $request->periodo_id,$request->libro_id);
        }
        if($request->getCodigosxEstudiante){
            return $this->getCodigosxEstudiante($request->estudiante_id,$request->institucion_id, $request->periodo_id);
        }
    }
    public function getHistorico($idusuario){
        $consulta = DB::SELECT("SELECT h.*,
        iv.nombreInstitucion AS institucionAnterior,
        inw.nombreInstitucion AS institucionNueva,
        p.periodoescolar AS periodo,
        CONCAT(u.nombres, ' ',u.apellidos) AS editor
        FROM his_change_estudiantes h
        LEFT JOIN institucion iv ON iv.idInstitucion = h.institucion_old
        LEFT JOIN institucion inw ON inw.idInstitucion = h.institucion_new
        LEFT JOIN periodoescolar p ON h.periodo_id = p.idperiodoescolar
        LEFT JOIN usuario u ON h.user_created = u.idusuario
        WHERE  h.estudiante_id = '$idusuario'
        ORDER BY h.id desc
        ");
        return $consulta;
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
        //
    }
    //api:get>>/import/revision/estudiante
    public function importRevision(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $cedulas = json_decode($request->data_estudiantes);  
        $datos=[];
        $data=[];
        $cedulasNoExisten=[];
        $contador = 0;
        foreach($cedulas as $key => $item){
            $consulta = $this->getEstudiantes(trim($item->cedula));
            if(count($consulta) > 0){
               $datos[] = $consulta[0];
            }else{
                $cedulasNoExisten[$contador] = [
                    "cedula" => trim($item->cedula)
                ];
                $contador++;
            }       
        }
        $data = [
            "cedulasNoExisten" =>$cedulasNoExisten,
            "informacion" => $datos
        ];
        return $data;
    }
    //api:get>>/import/update/estudiante
    public function importUpdate(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $cedulas = json_decode($request->data_estudiantes);  
        $estudiantesOtrasInstituciones = [];
        $data=[];
        $cedulasNoExisten=[];
        $contador = 0;
        //antes que el asesor confirme el cambio
        if($request->cambioInstitucion == "no"){
            //bloquear todos los estudiantes de la institucion
            $allEstudiantes = DB::SELECT("SELECT idusuario FROM usuario u
                WHERE u.id_group = '4'
                AND u.institucion_idInstitucion = '$request->institucion_id'
                AND u.periodo_actualizacion     <> '$request->periodo_id'
            ");
            foreach($allEstudiantes as $k => $tr){
                $usuario = Usuario::findOrFail($tr->idusuario);
                $usuario->estado_idEstado       = '2';
                $usuario->save();
            }
            //fin bloqueo de los estudiantes 
            //Revision de cambiar de una institucion a otra
            foreach($cedulas as $key => $item){
                $consulta = $this->getEstudiantes($item->cedula);
                if(count($consulta) > 0) {
                    //si la institucion es diferente a la que envia el asesor se mandara al front
                    $institucionActual = $consulta[0]->institucion_idInstitucion;
                    if($institucionActual != $request->institucion_id){
                        $estudiantesOtrasInstituciones[] = $consulta[0];
                    }
                }else{
                    $cedulasNoExisten[$contador] = [
                        "cedula" => $item->cedula
                    ];
                    $contador++;
                }       
            }
            $data = [
                "cedulasNoExisten"              => $cedulasNoExisten,
                "estudiantesOtrasInstituciones" => $estudiantesOtrasInstituciones,
                "change"                        => "no"
            ];
            return $data;
        }
        ///el asesor confirma el cambio
        if($request->cambioInstitucion == "yes"){
            return $this->CambioInstitucion($cedulas,$request->institucion_id,$request->periodo_id,$request->user_created);
        }
    }
    public function CambioInstitucion($cedulas,$institucion,$periodo,$user_created){
        $cedulasNoExisten = [];
        $cedulasYaPertenecen = [];
        $contador = 0;
        $contadorChange = 0;
        $contadorPertenece = 0;
        foreach($cedulas as $key => $item){
            $consulta = $this->getEstudiantes($item->cedula);
            if(count($consulta) > 0) {
                //variables
                $estudiante_id      = $consulta[0]->idusuario;
                $institucion_old    = $consulta[0]->institucion_idInstitucion;
                //actualizar institucion y activar el estudiante
                $estudiante = DB::table('usuario')
                ->where('cedula', $item->cedula)
                ->update([
                    'periodo_actualizacion'     => $periodo,
                    'institucion_idInstitucion' => $institucion,
                    'estado_idEstado'           => '1'
                ]);
                if($estudiante){
                    $this->saveHistorico($estudiante_id,$institucion_old,$institucion,$periodo,$user_created);
                    $contadorChange++;
                }else{
                    $cedulasYaPertenecen[$contadorPertenece] = [
                        "cedula" => $item->cedula
                    ];
                    $contadorPertenece++;
                }
            }else{
                $cedulasNoExisten[$contador] = [
                    "cedula" => $item->cedula
                ];
                $contador++;
            }       
        }
        $data = [
            "cedulasYaPertenecen"   => $cedulasYaPertenecen,
            "cedulasNoExisten"      => $cedulasNoExisten,
            "contador"              => $contadorChange,
            "change"                => "yes"
        ];
        return $data;
    }
    public function saveHistorico($estudiante_id,$institucion_old,$institucion_new,$periodo_id,$user_created){
        $historico = new HistoricoChangeEstudiante();
        $historico->estudiante_id   = $estudiante_id;
        $historico->institucion_old = $institucion_old;
        $historico->institucion_new = $institucion_new;
        $historico->periodo_id      = $periodo_id;
        $historico->user_created    = $user_created;
        $historico->save();
    }
    public function getEstudiantes($cedula){
        $cedula = DB::SELECT("SELECT u.idusuario,
            CONCAT(u.nombres, ' ', u.apellidos) as estudiante,
            u.cedula, u.email,
            i.nombreInstitucion, u.institucion_idInstitucion,p.periodoescolar as periodo,
            IF(u.estado_idEstado = '1','Activo','Inactivo') AS estado,u.estado_idEstado
            FROM usuario u 
            LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
            LEFT JOIN periodoescolar p ON u.periodo_actualizacion = p.idperiodoescolar
            WHERE u.id_group = 4
            AND u.cedula = '$cedula'
        ");
        return $cedula;
    }
    public function getEstudiantesInstitucion($institucion,$periodo){
        $estudiantes = $this->getEstudiantesValidados($institucion,$periodo);
        if(empty($estudiantes)){
            return ["status" => "0", "message" => "No existe estudiantes asignados a esa institución"];
        }else{
            $datos = [];
            foreach($estudiantes as $key => $item){
                //traer los codigos del libro
                // $consulta2 = DB::SELECT("SELECT dl.*, l.nombrelibro,
                //     (
                //         SELECT COUNT(dr.id)
                //         FROM diagnostico_respuesta dr
                //         WHERE dr.diagnostico_libros_id = dl.id
                //     )  as cantidad_preguntas
                //     FROM diagnostico_libros dl
                //     LEFT JOIN libro l ON dl.libro_id  = l.idlibro
                //     WHERE dl.estudiante_id = '$item->idusuario'
                //     AND dl.institucion_id = '$institucion'
                //     AND dl.periodo_id = '$periodo'       
                // ");
                $consulta2 = DB::SELECT("SELECT dl.codigo
                FROM diagnostico_libros dl
                LEFT JOIN libro l ON dl.libro_id  = l.idlibro
                WHERE dl.estudiante_id = '$item->idusuario'
                AND dl.institucion_id = '$institucion'
                AND dl.periodo_id = '$periodo'       
                ");
                $datos[$key] = [
                    "idusuario"                 => $item->idusuario,
                    "estudiante"                => $item->estudiante,
                    "cedula"                    => $item->cedula,
                    "email"                     => $item->email,
                    "nombreInstitucion"         => $item->nombreInstitucion,
                    "institucion_idInstitucion" => $item->institucion_idInstitucion,
                    "periodo"                   => $item->periodo,
                    "estado"                    => $item->estado,
                    "contador"                  => $item->contador,
                    "estado_idEstado"           => $item->estado_idEstado,
                    "codigos"                   => $consulta2
                ];
            }
            return $datos;
        }
    }
    public function getEstudiantesValidados($institucion,$periodo){
        $estudiantes = DB::SELECT("SELECT u.idusuario,
            CONCAT(u.nombres, ' ', u.apellidos) as estudiante,
            u.cedula, u.email,
            i.nombreInstitucion, u.institucion_idInstitucion,p.periodoescolar as periodo,
            IF(u.estado_idEstado = '1','Activo','Inactivo') AS estado,u.estado_idEstado,
            (
                SELECT count(dl.id) AS contador FROM diagnostico_libros dl
                WHERE dl.estudiante_id = u.idusuario
                AND dl.institucion_id = '$institucion'
                AND dl.periodo_id = '$periodo'
            )as contador
            FROM usuario u 
            LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
            LEFT JOIN periodoescolar p ON u.periodo_actualizacion = p.idperiodoescolar
            WHERE u.id_group = '4'
            AND i.idInstitucion = '$institucion'
            AND u.periodo_actualizacion = '$periodo'
        ");
        return $estudiantes;
    }
    public function getEstudiantesxMateria($institucion,$periodo,$libro){
        $estudiantes = $this->getEstudiantesValidadosxLibro($institucion,$periodo);
        if(empty($estudiantes)){
            return ["status" => "0", "message" => "No existe estudiantes asignados a esa institución"];
        }else{
            $datos = [];
            foreach($estudiantes as $key => $item){
                //traer los codigos del libro
                $consulta2 = DB::SELECT("SELECT dl.*, l.nombrelibro,
                    (
                        SELECT COUNT(dr.id)
                        FROM diagnostico_respuesta dr
                        WHERE dr.diagnostico_libros_id = dl.id
                    )  as cantidad_preguntas
                    FROM diagnostico_libros dl
                    LEFT JOIN libro l ON dl.libro_id  = l.idlibro
                    WHERE dl.estudiante_id = '$item->idusuario'
                    AND dl.institucion_id = '$institucion'
                    AND dl.periodo_id = '$periodo'       
                    AND dl.libro_id = '$libro'
                ");
                $datos[$key] = [
                    "idusuario"                 => $item->idusuario,
                    "estudiante"                => $item->estudiante,
                    "cedula"                    => $item->cedula,
                    "email"                     => $item->email,
                    "nombreInstitucion"         => $item->nombreInstitucion,
                    "institucion_idInstitucion" => $item->institucion_idInstitucion,
                    "periodo"                   => $item->periodo,
                    "estado"                    => $item->estado,
                    "estado_idEstado"           => $item->estado_idEstado,
                    "statusCodigo"              => empty($consulta2) ? '0':'1',
                    "codigo"                    => empty($consulta2) ? 'No ingresado':$consulta2[0]->codigo,
                    "nombrelibro"               => empty($consulta2) ? ''   : $consulta2[0]->nombrelibro,
                    "calificacion_diagnostica"  => empty($consulta2) ? ''   : $consulta2[0]->calificacion_diagnostica,
                    "calificacion_final"        => empty($consulta2) ? ''   : $consulta2[0]->calificacion_diagnostica.'/'.$consulta2[0]->cantidad_preguntas,
                    "cantidad_preguntas"        => empty($consulta2) ? ''   : $consulta2[0]->cantidad_preguntas,
                    "diagnostico_libros_id"     => empty($consulta2) ? ''   : $consulta2[0]->id,
                    "prueba_diagnostica"        => empty($consulta2) ? ''   : $consulta2[0]->prueba_diagnostica,
                    "codigos"                   => $consulta2
                ];
            }
            return $datos;
        }
    }
    public function getEstudiantesValidadosxLibro($institucion,$periodo){
        $estudiantes = DB::SELECT("SELECT u.idusuario,
        CONCAT(u.nombres, ' ', u.apellidos) as estudiante,
        u.cedula, u.email,
        i.nombreInstitucion, u.institucion_idInstitucion,p.periodoescolar as periodo,
        IF(u.estado_idEstado = '1','Activo','Inactivo') AS estado,u.estado_idEstado
        FROM usuario u 
        LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
        LEFT JOIN periodoescolar p ON u.periodo_actualizacion = p.idperiodoescolar
        WHERE u.id_group = '4'
        AND i.idInstitucion = '$institucion'
        AND u.periodo_actualizacion = '$periodo'
        ");
        return $estudiantes;
    }
    public function getCodigosxEstudiante($estudiante_id,$institucion,$periodo){
        $consulta = DB::SELECT(" SELECT dl.*, l.nombrelibro,
            (
                SELECT COUNT(dr.id)
                FROM diagnostico_respuesta dr
                WHERE dr.diagnostico_libros_id = dl.id
            )  as cantidad_preguntas
            FROM diagnostico_libros dl
            LEFT JOIN libro l ON dl.libro_id  = l.idlibro
            WHERE dl.estudiante_id = '$estudiante_id'
            AND dl.institucion_id = '$institucion'
            AND dl.periodo_id = '$periodo'        
        ");
        //obtener la cantidad de preguntas
        return $consulta;
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
}
