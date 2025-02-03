<?php

namespace App\Http\Controllers;

use App\Models\Seminario;
use App\Models\SeminarioCapacitador;
use App\Models\SeminarioEncuesta;
use App\Models\SeminarioHasUsuario;
use App\Models\Seminarios;
use Illuminate\Http\Request;
// use DB;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
class SeminarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //contar los webinars registrados asistentes
        if($request->contar){
            $registrados = DB::SELECT("SELECT count(*) as registrados  from seminario_has_usuario
             WHERE seminario_id = '$request->seminario_id'
             ");
            $asistentes = DB::SELECT("SELECT count(*) as asistentes  from seminario_has_usuario
                 WHERE seminario_id = '$request->seminario_id'
                 AND asistencia = '1'
                 ");
            $total_encuestas = DB::SELECT("SELECT DISTINCT s.* FROM seminario_has_usuario  s
            LEFT JOIN seminario_respuestas r ON s.seminario_id = r.id_seminario
            WHERE s.asistencia = '1'
            AND s.seminario_id = '$request->seminario_id'
            AND s.usuario_id = r.id_usuario
               ");

             return [
                 "registrados" => $registrados,
                 "asistentes" => $asistentes,
                 "encuestas" => count($total_encuestas),

             ];
        }else{
            $seminario = DB::SELECT("SELECT * FROM seminario WHERE estado = '1' order by fecha_inicio desc;");
            return $seminario;
        }

    }

    public function buscarSeminario(Request $request){
        $curso = DB::SELECT("SELECT * FROM seminario WHERE idcurso = ?",[$request->idcurso]);
        $registrados = DB::SELECT("SELECT COUNT(*) as registrados FROM inscripcion join seminario on seminario.idseminario = inscripcion.seminario_idseminario WHERE seminario.idcurso = ?",[$request->idcurso]);
        $data = [
            'curso' => $curso,
            'total' => $registrados,
        ];
        return $data;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }
    public function eliminarSeminario(Request $request)
    {
        DB::UPDATE("UPDATE `seminario`
        SET
        `estado` = '0'
        WHERE `idseminario` = ? ;",[$request->idcurso]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(!$request->idseminario){
            $seminario = new Seminario();
            $id = uniqid();
            $seminario->nombre = $request->nombre;
            $seminario->descripcion = $request->descripcion;
            $seminario->fecha_inicio = $request->fecha_inicio;
            $seminario->hora_inicio = $request->hora_inicio;
            $seminario->link_presentacion = $request->link_presentacion;
            $seminario->cantidad_participantes = (int) $request->cantidad_particiantes;
            $seminario->link_registro = "https://prolipadigital.com.ec/inscripciones/public/?curso=".$id;
            $seminario->idcurso = $id;
            $seminario->tiempo_curso = $request->tiempo_curso;
            $seminario->save();
        }else{
            $seminario = Seminario::find($request->idseminario);
            $seminario->nombre = $request->nombre;
            $seminario->descripcion = $request->descripcion;
            $seminario->fecha_inicio = $request->fecha_inicio;
            $seminario->hora_inicio = $request->hora_inicio;
            $seminario->link_presentacion = $request->link_presentacion;
            $seminario->cantidad_participantes = (int) $request->cantidad_particiantes;
            $seminario->tiempo_curso = $request->tiempo_curso;
            $seminario->save();
        }


        return $seminario;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\seminario  $seminario
     * @return \Illuminate\Http\Response
     */
    public function show($seminario)
    {
        $seminario = DB::SELECT("SELECT ec.cedula, s.*
        FROM encuestas_certificados ec, seminario s
        WHERE s.estado = '1'
        and ec.id_seminario = s.idseminario
        and ec.cedula = $seminario
        order by fecha_inicio desc;");
        return $seminario;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\seminario  $seminario
     * @return \Illuminate\Http\Response
     */
    public function edit(seminario $seminario)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\seminario  $seminario
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, seminario $seminario)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\seminario  $seminario
     * @return \Illuminate\Http\Response
     */
    public function destroy(seminario $seminario)
    {
        return $seminario;
    }

    public function encuesta_certificados($id){
        $encuesta = DB::SELECT("SELECT ec.*, u.nombres, u.apellidos, u.email, u.telefono, i.nombreInstitucion, g.deskripsi as grupo
        FROM encuestas_certificados ec, usuario u, institucion i, sys_group_users g
        WHERE id_seminario = $id
        and ec.cedula = u.cedula
        and u.id_group = g.id
        and u.institucion_idInstitucion = i.idInstitucion");
        return $encuesta;
    }

    public function seminariosDocente($id)
    {
        $seminarios = DB::SELECT("SELECT s.*, i.* FROM seminario s, inscripcion i WHERE s.idseminario = i.seminario_idseminario AND i.cedula LIKE '$id' ORDER BY s.fecha_inicio DESC");
        return $seminarios;
    }


    ///SEMINARIOS V2
    public function get_seminarios($id_periodo){

        $seminarios = DB::SELECT("SELECT s.*, i.nombreInstitucion AS nombre_institucion,
            c.nombre AS nombre_ciudad, t.ciudad,
            te.tema,a.nombrearea, CONCAT(u.nombres,' ',u.apellidos) as asesor ,u.cedula,i.nombreInstitucion,
            (case when (s.estado_institucion_temporal = 1) then s.nombre_institucion_temporal  else i.nombreInstitucion end) as institucionFinal,
            (case when (s.estado_capacitacion = 2) then 'Realizada' when (s.estado_capacitacion = 1) then 'Pendiente' else 'Cancelada' end) as estadoCapacitacion,
            COUNT(sr.id_seminario) AS cant_respuestas,
            CONCAT(cap.nombres,' ',cap.apellidos) as capacitador, s.capacitador as capacitadores
            FROM seminarios s
            LEFT JOIN institucion i ON s.id_institucion = i.idInstitucion
            LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
            LEFT JOIN seminario_respuestas sr ON s.id_seminario = sr.id_seminario
            LEFT JOIN seguimiento_institucion_temporal t on s.institucion_id_temporal = t.institucion_temporal_id
            LEFT JOIN capacitacion_temas te ON  s.tema_id = te.id
            LEFT JOIN area a on te.area = a.idarea
            LEFT JOIN usuario u ON s.id_usuario = u.idusuario
            LEFT JOIN usuario cap ON s.capacitador_id = cap.idusuario
            WHERE s.estado = '1'
            and  s.periodo_id = '$id_periodo'
            GROUP BY s.id_seminario
            ORDER BY s.id_seminario DESC
       ");
        return $seminarios;
    }
    //traer las instituciones temporales
    public function institucionesTemporalesWebinar(Request $request){
        $temporales = DB::SELECT("SELECT *, CONCAT(t.nombre_institucion,' - ',t.ciudad) as nombreInstitucion
        FROM seguimiento_institucion_temporal t
        WHERE t.tipo = '1'
        AND t.region = '$request->region'
        ORDER BY institucion_temporal_id DESC
        ");
        return $temporales;
    }

    //para traer los webinars
    public function obtenerWebinars(Request $request){
        //listado de capacitaciones del docente que pertenezca a una institucion registrada por prolipa
        if($request->capacitacionInstitucion){
            $capacitacion = DB::SELECT("SELECT s.*, CONCAT(s.descripcion, ' - ' ,s.nombre,' - ',i.nombreInstitucion) as webinar,
            i.nombreInstitucion AS nombre_institucion,
             c.nombre AS nombre_ciudad, COUNT(sr.id_seminario) AS cant_respuestas
                FROM seminarios s
                LEFT  JOIN institucion i ON s.id_institucion = i.idInstitucion
                LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
                LEFT JOIN seminario_respuestas sr ON s.id_seminario = sr.id_seminario
                WHERE s.estado = 1
                AND  s.tipo_webinar = '2'
                and s.estado_capacitacion <> 0
                AND s.id_institucion = '$request->institucion_id'
                GROUP BY s.id_seminario
                ORDER BY s.id_seminario DESC
                ");
            return $capacitacion;
        }
        if($request->capacitaciones){
            $capacitacion = DB::SELECT("SELECT s.*,
            (case when (s.estado_institucion_temporal = 0) then  CONCAT(s.descripcion, ' - ' ,s.nombre,' - ',i.nombreInstitucion)
            when (s.estado_institucion_temporal = 1) then  CONCAT(s.descripcion, ' - ' ,s.nombre,' - ',s.nombre_institucion_temporal)
            end) as webinar,
            i.nombreInstitucion AS nombre_institucion,
             c.nombre AS nombre_ciudad, COUNT(sr.id_seminario) AS cant_respuestas
                FROM seminarios s
                LEFT  JOIN institucion i ON s.id_institucion = i.idInstitucion
                LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
                LEFT JOIN seminario_respuestas sr ON s.id_seminario = sr.id_seminario
                WHERE s.estado = 1
                AND  s.tipo_webinar = '2'
                and s.estado_capacitacion <> 0
                GROUP BY s.id_seminario
                ORDER BY s.id_seminario DESC
            ");
            return $capacitacion;
        }else{
            $todate  = date('Y-m-d');
            // return $todate;
            $webinars = DB::SELECT("SELECT s.*, CONCAT(s.descripcion, ' - ' ,s.nombre) as webinar, i.nombreInstitucion AS nombre_institucion, c.nombre AS nombre_ciudad, COUNT(sr.id_seminario) AS cant_respuestas
            FROM seminarios s
            LEFT  JOIN institucion i ON s.id_institucion = i.idInstitucion
            LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
             LEFT JOIN seminario_respuestas sr ON s.id_seminario = sr.id_seminario
             WHERE s.estado = 1
             AND  (s.tipo_webinar = '1')
             AND  s.fecha_fin > '$todate'
             GROUP BY s.id_seminario
             ORDER BY s.id_seminario DESC
             ");
             return  $webinars;
        }


    }

    public function sumarEncuestasDescargadas(Request $request){

        $certificados = DB::SELECT("SELECT * FROM seminario_has_usuario
        WHERE seminario_id ='$request->seminario_id'
        AND usuario_id = '$request->usuario_id'
        ");

        if(count($certificados) == 0){

        }else{
            $extraerContador = $certificados[0]->certificado_cont;
            $extraerId = $certificados[0]->seminario_has_usuario_id;

            $certificado =  SeminarioHasUsuario::findOrFail($extraerId);
            $certificado->certificado_cont = $extraerContador + 1;
            $certificado->save();

        }



    }

    public function resumenWebinar($periodo){
        $webinars = DB::SELECT("SELECT s.*, CONCAT(s.descripcion, ' - ' ,s.nombre) as webinar, i.nombreInstitucion AS nombre_institucion, c.nombre AS nombre_ciudad, COUNT(sr.id_seminario) AS cant_respuestas
        FROM seminarios s
        LEFT JOIN institucion i ON s.id_institucion = i.idInstitucion
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        LEFT JOIN seminario_respuestas sr ON s.id_seminario = sr.id_seminario
        INNER JOIN periodoescolar_has_institucion pi on i.idInstitucion = pi.institucion_idInstitucion
        INNER JOIN periodoescolar p ON pi.periodoescolar_idperiodoescolar = p.idperiodoescolar
        WHERE s.estado = 1
        AND s.tipo_webinar = '1'
        AND p.estado = '1'
        AND p.idperiodoescolar = $periodo
        GROUP BY s.id_seminario");

        $datos = [];
        $data = array();
        $arr_respuestas = array();
        foreach($webinars as $key => $item){

            $registrados = DB::SELECT("SELECT * from seminario_has_usuario
            WHERE seminario_id = '$item->id_seminario'
            ");

            $total_encuestas = DB::SELECT("SELECT DISTINCT s.*, r.respuestas FROM seminario_has_usuario  s
                LEFT JOIN seminario_respuestas r ON s.seminario_id = r.id_seminario
                WHERE s.asistencia = '1'
                AND s.seminario_id = '$item->id_seminario'
                AND s.usuario_id = r.id_usuario
            ");

            $respuestas_encuestas = DB::SELECT("SELECT DISTINCT r.* FROM seminario_has_usuario s LEFT JOIN seminario_respuestas r ON s.seminario_id = r.id_seminario WHERE s.asistencia = '1' AND s.seminario_id = ? AND s.usuario_id = r.id_usuario;", [$item->id_seminario]);
            // $respuestas_encuestas_1 = json_decode($respuestas_encuestas[0]->respuestas);
            // return response()->json(array('response' => $item->id_seminario));

            $val_preg_7 = 0;
            $cant_op_preg_7 = [ "1" => 0, "2" => 0, "3" => 0, "4" => 0, "5" => 0 ];
            foreach($respuestas_encuestas as $keyr => $value){ //iteracion de seminarios
                $respuestas_json = json_decode($value->respuestas);
                foreach($respuestas_json as $key1 => $value1){ // iteacion de respuestas
                    $cont = 0;
                    foreach($value1 as $key2 => $value2){
                        $cont++;
                        if( $cont == 7 ){
                            $val_preg_7 += $value2;
                            $cant_op_preg_7[$value2]++;
                        }
                    }
                }
            }

            $totr_resp_7 = 1;
            if( count($respuestas_encuestas) != 0 ){
                $totr_resp_7 = count($respuestas_encuestas);
            }

            $val_preg_7 = $val_preg_7/$totr_resp_7;

            $asistentes = DB::SELECT("SELECT *  from seminario_has_usuario
                WHERE seminario_id = '$item->id_seminario'
                AND asistencia = '1'
            ");

            $datos[$key] = [
                "seminario_id" => $item->id_seminario,
                "seminario" => $item->nombre,
                "descripcion" => $item->descripcion,
                "capacitador" => $item->capacitador,
                "val_preg_7" => floatval($val_preg_7),
                "cant_op_preg_7" => $cant_op_preg_7,
                "respuestas_encuestas" => count($respuestas_encuestas),
                "registrados" => count($registrados) ,
                "asistentes" => count($asistentes),
                "encuestas_llenadas" => count($total_encuestas),
            ];

        }

        $informacion = [
            "seminarios" => $datos,
        ];
        return $informacion;

    }

    public function get_seminarios_docente($id){
        $seminarios = DB::SELECT("SELECT s.*, sr.respuestas FROM seminarios s LEFT JOIN usuario u ON s.id_institucion = u.institucion_idInstitucion LEFT JOIN seminario_respuestas sr ON s.id_seminario = sr.id_seminario AND sr.id_usuario = $id WHERE u.idusuario = $id AND s.estado = 1 AND s.tipo_webinar = 0 GROUP BY s.id_seminario ORDER BY s.id_seminario DESC");
        return $seminarios;
    }

    public function obtener_seminarios_docente(Request $request){
        $seminarios = DB::SELECT("SELECT s.* FROM seminarios s
        WHERE s.id_institucion = '$request->institucion_id'
        AND s.tipo_webinar = '0'
        AND s.estado = '1'
        ORDER BY s.id_seminario DESC

        ");

        $datos = [];
        $data = [];
        foreach($seminarios as $key => $item){
            $asistencia = DB::SELECT("SELECT asistencia FROM seminario_has_usuario
            WHERE usuario_id = '$request->idusuario'
            AND seminario_id = '$item->id_seminario'
            ");
            $respuestas = DB::SELECT("SELECT * FROM seminario_respuestas
            WHERE id_seminario = '$item->id_seminario'
            AND id_usuario = '$request->idusuario'
            ORDER BY id_respuesta DESC

            ");

            //PARA LA ASISTENCIA
            if(count($asistencia) ==  0){
                $Rasistencia = 0;
            }

            if(count($asistencia) > 0){
                $Rasistencia = $asistencia[0]->asistencia;
            }else{
                $Rasistencia = 0;
            }

            //PARA LA RESPUESTA
            if(count($respuestas) ==  0){
                $Rrespuestas = 0;
            }

            if(count($respuestas) > 0){
                $Rrespuestas = $respuestas[0];
            }else{
                $Rrespuestas = 0;
            }
            $datos[$key] = [
                "id_seminario" => $item->id_seminario,
                "nombre" => $item->nombre,
                "descripcion" => $item->descripcion,
                "fecha_inicio" => $item->fecha_inicio,
                "fecha_fin" => $item->fecha_fin,
                "link_reunion" => $item->link_reunion,
                "id_institucion" => $item->id_institucion,
                "estado" => $item->estado,
                "capacitador" => $item->capacitador,
                "cant_asistentes" => $item->cant_asistentes,
                "asistencia_activa" => $item->asistencia_activa,
                "tipo_webinar" => $item->tipo_webinar,
                "asistencia" => $Rasistencia,
                "respuestas" => $Rrespuestas
            ];

        }

        return $datos;
    }

    public function obtener_webinars_docente(Request $request){

        $seminarios = DB::SELECT("SELECT s.*, sr.respuestas FROM seminarios s
        LEFT JOIN seminario_respuestas sr ON s.id_seminario = sr.id_seminario
        AND sr.id_usuario = '$request->idusuario'
        WHERE s.tipo_webinar = 1
        AND s.estado = 1
        GROUP BY s.id_seminario");

        $datos = [];
        $data = [];
        foreach($seminarios as $key => $item){
            $asistencia = DB::SELECT("SELECT asistencia FROM seminario_has_usuario
            WHERE usuario_id = '$request->idusuario'
            AND seminario_id = '$item->id_seminario'
            ");


            //PARA LA ASISTENCIA
            if(count($asistencia) ==  0){
                $Rasistencia = 0;
            }

            if(count($asistencia) > 0){
                $Rasistencia = $asistencia[0]->asistencia;
            }else{
                $Rasistencia = 0;
            }


            $datos[$key] = [
                "id_seminario" => $item->id_seminario,
                "nombre" => $item->nombre,
                "descripcion" => $item->descripcion,
                "fecha_inicio" => $item->fecha_inicio,
                "fecha_fin" => $item->fecha_fin,
                "link_reunion" => $item->link_reunion,
                "id_institucion" => $item->id_institucion,
                "estado" => $item->estado,
                "capacitador" => $item->capacitador,
                "cant_asistentes" => $item->cant_asistentes,
                "asistencia_activa" => $item->asistencia_activa,
                "tipo_webinar" => $item->tipo_webinar,
                "asistencia" => $Rasistencia,
                "respuestas" => $item->respuestas
            ];

        }

        return $datos;
    }


    public function get_seminarios_webinar($id){
        $seminarios = DB::SELECT("SELECT s.*, sr.respuestas FROM seminarios s
        LEFT JOIN seminario_respuestas sr ON s.id_seminario = sr.id_seminario
        AND sr.id_usuario = $id
        WHERE s.tipo_webinar = 1
        AND s.estado = 1
        GROUP BY s.id_seminario");
        return $seminarios;
    }


    public function get_webinars(Request $request){
        //verificar si hay encuestas
        $encuestas = DB::SELECT("SELECT * FROM seminario_respuestas r
        WHERE r.id_usuario = '$request->idusuario'
        ");
        //si hay encuestas pero no estan registrados
        if(count($encuestas) < 0){
            $webinars = DB::SELECT("SELECT DISTINCT sm.seminario_has_usuario_id,sm.asistencia, sm.usuario_id, s.* , sr.respuestas,
             (case when (s.estado_institucion_temporal = 1) then s.nombre_institucion_temporal  else i.nombreInstitucion end) as institucionFinal
                FROM seminario_has_usuario sm
                LEFT JOIN seminarios s ON sm.seminario_id = s.id_seminario
                LEFT JOIN institucion i ON s.id_institucion = i.idInstitucion
                LEFT JOIN seminario_respuestas sr ON s.id_seminario = sr.id_seminario
                AND sr.id_usuario = '$request->idusuario' WHERE sm.usuario_id = '$request->idusuario'
                AND s.estado = 1
                AND (s.tipo_webinar = '1' OR s.tipo_webinar = '2')
            ");

            //verificar si hay encuestas
            $encuestas = DB::SELECT("SELECT * FROM seminario_respuestas r
            WHERE r.id_usuario = '$request->idusuario'
            ");
        }
        //si hay encuestas pero no estan registrados
        if(count($encuestas) < 0){
            $webinars = DB::SELECT("SELECT DISTINCT sm.seminario_has_usuario_id,sm.asistencia, sm.usuario_id, s.* , sr.respuestas
                FROM seminario_has_usuario sm
                LEFT JOIN seminarios s ON sm.seminario_id = s.id_seminario
                LEFT JOIN seminario_respuestas sr ON s.id_seminario = sr.id_seminario
                AND sr.id_usuario = '$request->idusuario' WHERE sm.usuario_id = '$request->idusuario'
                AND s.estado = 1
                AND s.tipo_webinar = 1
                ORDER BY sm.seminario_has_usuario_id DESC
                ");
                return $webinars;

        }else{
            //SI TODO ESTA BIEN
            $usuario = DB::SELECT("SELECT * FROM usuario where idusuario = '$request->idusuario'");
            $cedula = $usuario[0]->cedula;
            $institucion = $usuario[0]->institucion_idInstitucion;
            foreach($encuestas as $key => $item){
                $registroEncuesta = DB::SELECT("SELECT * FROM seminario_has_usuario u
                WHERE u.usuario_id = '$request->idusuario'
                AND u.seminario_id = '$item->id_seminario'
                ");
                if(count($registroEncuesta) == 0){
                    $seminario =  new  SeminarioHasUsuario();
                    $seminario->usuario_id =  $item->id_usuario;
                    $seminario->cedula =      $cedula;
                    $seminario->seminario_id = $item->id_seminario;
                    $seminario->institucion_id = $institucion;
                    $seminario->asistencia = "1";
                    $seminario->save();
                }
            }

            $webinars = DB::SELECT("SELECT DISTINCT sm.seminario_has_usuario_id,sm.asistencia, sm.usuario_id, s.* , sr.respuestas,
             (case when (s.estado_institucion_temporal = 1) then s.nombre_institucion_temporal  else i.nombreInstitucion end) as institucionFinal
            FROM seminario_has_usuario sm
            LEFT JOIN seminarios s ON sm.seminario_id = s.id_seminario
            LEFT JOIN institucion i ON s.id_institucion = i.idInstitucion
            LEFT JOIN seminario_respuestas sr ON s.id_seminario = sr.id_seminario
            AND sr.id_usuario = '$request->idusuario' WHERE sm.usuario_id = '$request->idusuario'
            AND s.estado = 1
            AND (s.tipo_webinar = '1' OR s.tipo_webinar = '2')
            ORDER BY sm.seminario_has_usuario_id DESC
            ");
            return $webinars;
        }
    }

    public function webinarAsistencia(Request $request){
        $seminario =  SeminarioHasUsuario::findOrFail($request->seminario_has_usuario_id);
        $seminario->asistencia = "1";
        $seminario->save();

        if($seminario){
            return ["status" =>"1","message" => "Asistencia registrada correctamente"];
        }else{
            return ["status" =>"0","message" => "No se pudo registrar la asistencia"];
        }
    }
    public function SeminarioAsistencia(Request $request)
    {
        $BuscarUsuarioSeminario = DB::SELECT("SELECT s.* FROM seminario_has_usuario s
        WHERE cedula = '$request->cedula'
        AND seminario_id = '$request->seminario_id'
        ");
        if(count($BuscarUsuarioSeminario) >0){
            $idAsistencia = $BuscarUsuarioSeminario[0]->seminario_has_usuario_id;
            $seminario =  SeminarioHasUsuario::findOrFail($idAsistencia);
            $seminario->asistencia = "1";
            $seminario->save();
            if($seminario){
                return ["status" =>"1","message" => "Asistencia registrada correctamente"];
            }else{
                return ["status" =>"0","message" => "No se pudo registrar la asistencia"];
            }
        }else{
            $seminario =  new  SeminarioHasUsuario();
            $seminario->usuario_id =  $request->usuario_id;
            $seminario->cedula =      $request->cedula;
            $seminario->seminario_id = $request->seminario_id;
            $seminario->institucion_id = $request->institucion_id;
            $seminario->asistencia = "1";
            $seminario->save();
            if($seminario){
                return ["status" =>"1","message" => "Asistencia registrada correctamente"];
            }else{
                return ["status" =>"0","message" => "No se pudo registrar la asistencia"];
            }
        }
    }
    public function get_instituciones(){
        $instituciones = DB::SELECT("SELECT DISTINCT i.idInstitucion AS id_institucion, CONCAT(i.nombreInstitucion, ' - ', c.nombre) AS nombre_institucion, p.idperiodoescolar, p.estado FROM institucion i, periodoescolar_has_institucion phi, periodoescolar p, ciudad c WHERE i.idInstitucion = phi.institucion_idInstitucion AND i.ciudad_id = c.idciudad AND phi.periodoescolar_idperiodoescolar = p.idperiodoescolar AND p.estado = '1' AND i.estado_idEstado = 1 ORDER BY c.nombre");
        return $instituciones;
    }
    public function guardar_seminario(Request $request){
        //CAPACITACIONES
        $getCantidadCapacitaciones = DB::SELECT("SELECT * FROM seminarios_configuracion");
        $pre_Capacitadores        = $getCantidadCapacitaciones[0]->cantidad_capacitadores;
        $cant_Capacitadores       = $pre_Capacitadores - 1;
        if($request->capacitacion == "yes"){
            $datos = json_decode($request->capacitadores);
            foreach($datos as $key => $item){
                //validar que el capacitador solo pueda 2 capacitaciones por dia
                $validate  = $this->buscarCapacitacionesXCapacitador($item->idusuario,substr($request->fecha_inicio, 0, 10),$request->id_seminario); // Extraer la subcadena
                if(sizeof($validate) > $cant_Capacitadores){
                    return ["status" => "0", "message" => "El capacitador $item->capacitador ya tiene $pre_Capacitadores capacitaciones en el mismo día"];
                }
            }
            //editar
            if($request->id_seminario > 0){
                $capacitacion = Seminarios::findOrFail($request->id_seminario);
            }
            //crear
            else{
                $capacitacion = new Seminarios();
                $capacitacion->id_usuario                = $request->idusuario;
                $capacitacion->nombre                    = $request->nombre;
                $capacitacion->label                     = 'Baja';
                $capacitacion->classes                   = "event-success";
                $capacitacion->tipo                      = $request->tipo;
                $capacitacion->tema_id                   = $request->tema_id;
            }
           //si crean una insitucion temporal
            if($request->estado_institucion_temporal == 1){
                // $capacitacion->periodo_id = $request->periodo_id_temporal;
                $capacitacion->institucion_id_temporal = $request->institucion_id_temporal;
                $capacitacion->nombre_institucion_temporal = $request->nombreInstitucion;
                $capacitacion->id_institucion = "";
            }
            if($request->estado_institucion_temporal == 0){
                $capacitacion->id_institucion = $request->institucion_id;
                $capacitacion->institucion_id_temporal = "";
                $capacitacion->nombre_institucion_temporal = "";
            }
           $capacitacion->periodo_id                    = $request->periodo_id;
           $capacitacion->descripcion                   = $request->fecha_inicio;
           $capacitacion->tipo_webinar                  = "2";
           $capacitacion->estado_institucion_temporal   = $request->estado_institucion_temporal;
           $capacitacion->fecha_inicio                  = $request->fecha_inicio;
           $capacitacion->fecha_fin                     = $request->fecha_fin;
           $capacitacion->hora_inicio                   = $request->hora_inicio;
           $capacitacion->hora_fin                      = $request->hora_fin;
           $capacitacion->cant_asistentes               = $request->cant_asistentes;
           $capacitacion->observacion_admin             = $request->observacion;
           $capacitacion->link_reunion                  = $request->link_reunion;
           $capacitacion->estado_capacitacion           = $request->estado_capacitacion;
           $capacitacion->asistencia_activa             = $request->asistencia_activa;
           $capacitacion->capacitador                   = $request->capacitador;
           $capacitacion->editor_id                     = $request->editor_id;
           $capacitacion->save();
           return $this->crearCapacitadores($request,$capacitacion);
           if($capacitacion){
            return ["status" => "1","message" => "Se actualizo correctamente"];
           }else{
            return ["status" => "0","message" => "No se pudo actualizar"];
           }

        }
        //SEMINARIOS
        if( $request->id_seminario ){
            DB::UPDATE("UPDATE `seminarios` SET `nombre`=?,`descripcion`=?,`fecha_inicio`=?,`fecha_fin`=?,`id_institucion`=?, `link_reunion`=?,`capacitador`=?,`cant_asistentes`=?,`asistencia_activa`=?,`tipo_webinar`=?,`link_recurso`=?,`clave_recurso`=?,`editor_id`=? WHERE `id_seminario` = ?", [$request->nombre,$request->descripcion,$request->fecha_inicio,$request->fecha_fin,$request->id_institucion,$request->link_reunion,$request->capacitador,$request->cant_asistentes,$request->asistencia_activa,$request->tipo_webinar,$request->link_recurso,$request->clave_recurso,$request->editor_id,$request->id_seminario]);
        }
        else{
            DB::INSERT("INSERT INTO `seminarios`(`nombre`, `descripcion`, `fecha_inicio`, `fecha_fin`, `link_reunion`, `id_institucion`, `capacitador`, `cant_asistentes`, `asistencia_activa`, `tipo_webinar`,`periodo_id`, `link_recurso`,`clave_recurso`,`editor_id`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)", [$request->nombre,$request->descripcion,$request->fecha_inicio,$request->fecha_fin,$request->link_reunion,$request->id_institucion,$request->capacitador,$request->cant_asistentes,$request->asistencia_activa,$request->tipo_webinar,$request->periodo_id,$request->link_recurso,$request->clave_recurso,$request->editor_id]);
        }
    }
    public function buscarCapacitacionesXCapacitador($idusuario,$fecha,$id_seminario){
        $query = DB::SELECT("SELECT sc.*
        FROM seminarios_capacitador sc
        LEFT JOIN seminarios s ON s.id_seminario = sc.seminario_id
        WHERE sc.idusuario = '$idusuario'
        AND DATE(s.fecha_inicio) = '$fecha'
        AND s.estado = '1'
        AND s.id_seminario <> '$id_seminario'
        ");
        return $query;
    }
    public function getCapacitadoresXCapacitacion($id_seminario){
        $getCapacitadores = DB::SELECT("SELECT c.*,
        CONCAT(u.nombres,' ',u.apellidos) AS capacitador
        FROM seminarios_capacitador c
        LEFT JOIN usuario u ON c.idusuario = u.idusuario
        WHERE c.seminario_id = '$id_seminario'
        ");
        return $getCapacitadores;
    }
    public function crearCapacitadores($request,$arreglo){
        $datos = json_decode($request->capacitadores);
        //eliminar si ya han quitado al capacitador
        $getCapacitadores = $this->getCapacitadoresXCapacitacion($arreglo->id_seminario);
        if(sizeOf($getCapacitadores) > 0){
            foreach($getCapacitadores as $key => $item){
                $capacitador        = "";
                $capacitador        = $item->idusuario;
                $searchCapacitador  = collect($datos)->filter(function ($objeto) use ($capacitador) {
                    // Condición de filtro
                    return $objeto->idusuario == $capacitador;
                });
                if(sizeOf($searchCapacitador) == 0){
                    DB::DELETE("DELETE FROM seminarios_capacitador
                      WHERE seminario_id = '$arreglo->id_seminario'
                      AND idusuario = '$capacitador'
                    ");
                }
            }
        }
        //guardar los capacitadores
        foreach($datos as $key => $item){
            $query = DB::SELECT("SELECT * FROM seminarios_capacitador c
            WHERE c.idusuario = '$item->idusuario'
            AND c.seminario_id = '$arreglo->id_seminario'");
            if(empty($query)){
                $capacitador = new SeminarioCapacitador();
                $capacitador->idusuario      = $item->idusuario;
                $capacitador->seminario_id   = $arreglo->id_seminario;
                $capacitador->save();
            }
        }
    }
    public function traerPeriodo($institucion_id){
        $periodoInstitucion = DB::SELECT("SELECT idperiodoescolar AS periodo , periodoescolar AS descripcion FROM periodoescolar WHERE idperiodoescolar = (
            SELECT  pir.periodoescolar_idperiodoescolar as id_periodo
            from institucion i,  periodoescolar_has_institucion pir
            WHERE i.idInstitucion = pir.institucion_idInstitucion
            AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
            WHERE phi.institucion_idInstitucion = i.idInstitucion
            AND i.idInstitucion = '$institucion_id'))
        ");
        if(count($periodoInstitucion)>0){
            return ["status" => "1", "message"=>"correcto","periodo" => $periodoInstitucion];
        }else{
            return ["status" => "0", "message"=>"no hay periodo"];
        }
    }

    public function get_preguntas_seminario(){


        $preguntas = DB::SELECT("SELECT p.*, s.nombre_seccion FROM seminario_preguntas p, seminario_secciones s WHERE p.estado = 1 AND p.seccion_pregunta = s.id_seccion ORDER BY p.id_pregunta");

        return $preguntas;
    }

    public function save_encuesta(Request $request){
        // return $request->respuestas;
        DB::INSERT("INSERT INTO `seminario_respuestas`(`id_seminario`, `id_usuario`, `respuestas`) VALUES (?,?,?)", [$request->id_seminario, $request->id_usuario, $request->respuestas]);

    }
    public function eliminar_seminario($id_seminario){
        DB::UPDATE("UPDATE `seminarios` SET `estado` = 0 WHERE `id_seminario` = $id_seminario");
    }

    public function asistentes_seminario($id_seminario){
        $asistentes = DB::SELECT("SELECT u.nombres, u.apellidos, u.email, u.cedula, i.nombreInstitucion, s.asistencia , g.deskripsi as rol
        FROM seminario_has_usuario s, usuario u, institucion i,  sys_group_users g
         WHERE s.seminario_id = $id_seminario
         AND s.usuario_id = u.idusuario
         AND u.id_group = g.id
         AND u.institucion_idInstitucion = i.idInstitucion");

        return $asistentes;
    }

    public function reporte_seminario($id){

        $seminario = DB::SELECT("SELECT * FROM seminario_respuestas sr WHERE sr.id_seminario = $id");

        $data = array();
        $arr_respuestas = array();

        $i = 0;
        foreach ($seminario as $key => $value) {
            $arr_resp = json_decode($value->respuestas);
            foreach ($arr_resp as $key_1 => $value_1) {

                foreach ($value_1 as $key_2 => $value_2) {
                    $data_pregunta = DB::SELECT("SELECT * FROM `seminario_preguntas` WHERE `id_pregunta` = $key_2");
                    $data['items'][$i] = [
                        "tipo_pregunta" => $data_pregunta[0]->tipo_pregunta,
                        "id_pregunta" => $key_2,
                        "nombre_pregunta" => $data_pregunta[0]->nombre_pregunta,
                        "respuesta" => $value_2
                    ];
                    $i++;
                }

            }
        }

        return $data;

    }


    public function get_periodos_seminarios(){
        $periodos = DB::SELECT("SELECT *,
        IF(p.estado = '1',CONCAT(p.periodoescolar,' ','activo'),CONCAT(p.periodoescolar,' ','desactivado')) AS periodo
         FROM periodoescolar p
        ORDER BY  p.idperiodoescolar
        desc");
        return $periodos;
    }

    public function actualiza_periodo_seminario(){
        $periodos = DB::SELECT("SELECT * FROM `seminarios`");
        foreach($periodos as $key => $value){

            $periodo_inst = DB::SELECT("SELECT pi.periodoescolar_idperiodoescolar FROM institucion i, periodoescolar_has_institucion pi, periodoescolar p WHERE i.idInstitucion = pi.institucion_idInstitucion AND pi.periodoescolar_idperiodoescolar = p.idperiodoescolar AND p.estado = '1' and i.idinstitucion = ?", [$value->id_institucion]);
            if($periodo_inst){
                DB::UPDATE("UPDATE seminarios s SET s.periodo_id = ? WHERE s.id_seminario = ?", [$periodo_inst[0]->periodoescolar_idperiodoescolar, $value->id_seminario]);
            }

        }
    }

    public function editar_codigos_masivos(){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $cont = ''; $cant = 0;
        $codigos = [];


        for( $i=0; $i<count($codigos); $i++ ){
            $edicion = DB::UPDATE("UPDATE `codigoslibros` SET `idusuario` = 60608, `id_periodo` = 16 WHERE `codigo` = ?  AND (`idusuario` = 0 OR `idusuario` IS NULL OR `idusuario` = '')", [$codigos[$i]]);
            if( $edicion ){ $cont .= ($codigos[$i].'_');  $cant++;}
        }

        return '*****CODIGOS EDITADOS: ' . $cont . '*****CANTIDAD: ' . $cant;

    }

}
