<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\Encuesta;
use App\Models\EncuestaEscuela;
use App\Models\EncuestaPreguntas;
use App\Models\EncuestaRespuesta;
use App\Models\EncuestaRespuestaDetalles;
use App\Models\User;

class EncuestaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->validarEncuesta) return $this->validarEncuesta($request->institucion_id,$request->id);
        if($request->getEncuestas) return $this->getEncuestas();
        if($request->getPreguntas) return $this->getPreguntas($request->encuesta_id);
        if($request->getEncuestaEscuela) return $this->getEncuestaEscuela();
    }
    public function validarEncuesta($institucion_id,$id){
         //trear el periodo actual de la institucion
         $buscarPeriodo = $this->traerPeriodo($institucion_id);
         if($buscarPeriodo["status"] == "1"){
             $periodo = $buscarPeriodo["periodo"][0]->periodo;
             $periodoDescripcion = $buscarPeriodo["periodo"][0]->descripcion;
         }else{
             return ["status" => "0","message" => "La institucion no tiene periodo"];
         }
         $fecha = date("Y-m-d");
         //validar que el formulario es
         $validate = DB::SELECT("SELECT * FROM encuestas_escuela ec
         WHERE ec.institucion_id = '$institucion_id'
         AND ec.periodo_id = '$periodo'
         AND ec.id = '$id'
         AND ec.estado = '1'
         ");
         if(empty($validate)){
             return ["status" => "0","message" => "ESTE FORMULARIO DE INSCRIPCIÓN NO ESTA DISPONIBLE"];
         }else{
             //traer nombre Institucion
             $getInstitucion = DB::SELECT("SELECT i.nombreInstitucion FROM institucion i
             WHERE i.idInstitucion = '$institucion_id'
             ");
             return ["encuesta_id"=>$validate[0]->encuesta_id,"periodoDescripcion" => $periodoDescripcion,"nombreInstitucion" => $getInstitucion,"periodo_id" => $periodo];
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
    public function getEncuestas(){
        $encuestas = DB::SELECT("SELECT * 
        FROM encuesta 
        ORDER BY id DESC
        ");
        return $encuestas;
    }
    public function getPreguntas($encuesta_id){
        $opciones = DB::SELECT("SELECT * FROM encuesta_opciones WHERE encuesta_id = '$encuesta_id' ORDER BY id DESC");
        return $opciones;
    }
    public function getEncuestaEscuela(){
        $encuestasEscuelas = DB::SELECT("SELECT ec.*,i.nombreInstitucion,
            (
              SELECT COUNT(er.id)  as contador 
              FROM encuesta_respuesta er
              WHERE er.encuestas_escuela_id = ec.id
              AND er.estado = '1'
            ) AS contador,
            p.periodoescolar AS periodo, 
            CONCAT(u.nombres,' ',u.apellidos) AS editor,
            e.descripcion AS encuesta
                FROM encuestas_escuela ec
            LEFT JOIN periodoescolar p ON ec.periodo_id = p.idperiodoescolar
            LEFT JOIN institucion i ON ec.institucion_id = i.idInstitucion
            LEFT JOIN usuario u ON ec.user_created = u.idusuario
            LEFT JOIN encuesta e ON ec.encuesta_id = e.id
            WHERE p.estado = '1' 
            ORDER BY ec.id DESC
        ");
        return $encuestasEscuelas;
    }
    
    public function verInfoCedulaEncuesta(Request $request){

        //validar que existe el usuario
        $validate = DB::SELECT("SELECT u.idusuario,u.nombres,u.apellidos,
        u.cedula,u.telefono,u.email,u.id_group,u.institucion_idInstitucion
         FROM usuario u
         WHERE u.cedula = '$request->cedula'
        ");
        if(empty($validate)){
            $opciones =   $this->getOpciones($request->encuesta_id);
            return ["status" => "0","preguntas"=>$opciones,"infoUsuario" => [],"encuestaDocente" => []];
        }else{
            //id del docente
            $idusuario          = $validate[0]->idusuario;
            //validar que el usuario sea un docente
            $ifDocente          = $validate[0]->id_group;
            //validar si la institucion es donde se encuentra el usuario
            $ifBelongsInstitute = $validate[0]->institucion_idInstitucion;
            if($ifDocente != 24)                                return ["status" => "3","message" => "Su cédula no pertenece a un usuario padre de familia, comuniquese con soporte por favor"];
            if($ifBelongsInstitute != $request->institucion_id) return ["status" => "3","message" => "Su cédula No pertenece a esta Institución, comuniquese con soporte por favor"];
            //===si el padre de familia ya ha dado la encuesta====
            return $this->validateIfFullEncuesta($request->encuesta_id,$idusuario,$validate);
            // return  $this->traerLibrosDocente($request->institucion_id,$idusuario,$request->periodo_id,$validate);
        }
    }
    public function validateIfFullEncuesta($encuesta_id,$idusuario,$datos){
        $validate = $this->validateSiLlenoEncuesta($idusuario,$encuesta_id);
        //si no ha llenado la encuesta cargo las preguntas
        if(empty($validate)){
          $opciones =   $this->getOpciones($encuesta_id);
          return ["status" => "1","preguntas"=>$opciones,"infoUsuario" => $datos,"encuestaDocente" =>$validate];
        }else{
            return ["status" => "3", "message" => "Estimado padre de familia usted ya lleno esta encuesta"];
        }
    }
    public function validateSiLlenoEncuesta($idusuario,$encuesta_id){
        $validate = DB::SELECT("SELECT * FROM encuesta_respuesta er
        WHERE er.idusuario = '$idusuario'
        AND er.encuesta_id = '$encuesta_id'
        ");
        return $validate;
    }
    public function getOpciones($encuesta_id){
        $preguntas = DB::SELECT("SELECT * FROM encuesta_opciones ep
        WHERE ep.encuesta_id = '$encuesta_id'
        ");
        return $preguntas;
    }
    public function guardarRespuestaEncuesta(Request $request){
        $arreglorespuestas = json_decode($request->respuestas);
        //Si el usuario ya existe
        if($request->nuevoUser == "no"){
            $user = User::findOrFail($request->idusuario);
        }else{
            //Si no existe el docente se crea
            $user = $this->saveUser($request->cedula,$request->nombres,$request->apellidos,$request->email,$request->telefono,$request->institucion_id);
        }  
        //validar si ya lleno la encuesta el usuario
        $validate = $this->validateSiLlenoEncuesta($user->idusuario,$request->encuesta_id);
        //si no ha llenado la encuesta cargo las preguntas
        if(empty($validate)){
            $encuestaRespuesta = new EncuestaRespuesta();
            $encuestaRespuesta->idusuario               = $user->idusuario;
            $encuestaRespuesta->encuestas_escuela_id    = $request->encuestas_escuela_id;
            $encuestaRespuesta->encuesta_id             = $request->encuesta_id;
            $encuestaRespuesta->save();
            foreach($arreglorespuestas as $key => $item){
                $valor = 0;
                if($item->valor) $valor = 1;
                $respuesta = new EncuestaRespuestaDetalles();
                $respuesta->encuesta_respuesta_id   = $encuestaRespuesta->id;
                $respuesta->idusuario               = $user->idusuario;
                $respuesta->encuesta_opcion_id      = $item->id;
                $respuesta->valor                   = $valor;
                $respuesta->save();
            }
            return ["status" => "1", "message" => "Se guardo correctamente"];     
        }else{
            return ["status" => "0", "message" => "La encuesta ya ha sido llenada"];     
        }
       
    }
    public function guardarAsignacion(Request $request){
        //validar que no este asignado la encuesta a la escuela
        $validate = DB::SELECT("SELECT * FROM encuestas_escuela ec
        WHERE ec.institucion_id = '$request->institucion_id'
        AND ec.periodo_id = '$request->periodo_id'
        AND ec.encuesta_id = '$request->encuesta_id'
        ");
        if(empty($validate)){
            $asignar = new EncuestaEscuela();
            $asignar->institucion_id    = $request->institucion_id;
            $asignar->periodo_id        = $request->periodo_id;
            $asignar->encuesta_id       = $request->encuesta_id;
            $asignar->user_created      = $request->user_created;
            $asignar->save();
            //generate link
            $this->generateLink($asignar,$request->link);
            if($asignar){
                return ["status" => "1", "message" => "Se guardo correctamente"];
            }else{
                return ["status" => "0", "message" => "No se pudo guardar"];
            }
        }else{
            return ["status" => "3", "message" => "La encuesta para esa institución ya existe"];
        }
       
    }
    public function saveUser($cedula,$nombres,$apellidos,$email,$telefono,$institucion){
        $user = new User();
        $user->cedula                       = $cedula;
        $user->nombres                      = $nombres;
        $user->apellidos                    = $apellidos;
        $user->email                        = $email;
        $user->name_usuario                 = $email;
        $user->telefono                     = $telefono;
        $user->id_group                     = "24";
        $user->estado_idEstado              = "1";
        $user->institucion_idInstitucion    = $institucion;
        $user->password                     = sha1(md5($cedula));
        $user->save();
        return $user;
    }
    public function generateLink($dato,$link){
        $generar = EncuestaEscuela::findOrFail($dato->id);
        $generar->link =  $link.$dato->id;
        $generar->save();
    }
    public function store(Request $request)
    {
        if($request->guardarOpciones == "yes"){
            return $this->guardarOpciones($request->id,$request->encuesta_id,$request->pregunta,$request->user_created);
        }
        if($request->eliminarPregunta){
            return $this->eliminarPregunta($request->id);
        }
        if($request->eliminarEncuestaAsignada){
            return $this->eliminarEncuestaAsignada($request->id);
        }
        if($request->changeEstado){
            return $this->changeEstado($request->id,$request->estado);
        }
        else{
            if($request->id > 0){
                $encuesta = Encuesta::findOrFail($request->id);
            }else{
                $encuesta = new Encuesta();
                $encuesta->periodo_id   = $request->periodo_id;
            }
                $encuesta->descripcion  = $request->descripcion;
                $encuesta->estado       = $request->estado;
                $encuesta->user_created = $request->user_created;
                $encuesta->save();
                if($encuesta){
                    return ["status" => "1", "message" => "Se guardo correctamente","datos" => $encuesta];
                }else{
                    return ["status" => "0", "message" => "No se pudo guardar"];
                }
        }
    }
    public function guardarOpciones($id,$encuesta_id,$pregunta,$idusuario){
        if($id > 0){
            $opciones = EncuestaPreguntas::findOrFail($id);
        }else{
            $opciones = new EncuestaPreguntas();
        }
            $opciones->encuesta_id  = $encuesta_id;
            $opciones->pregunta     = $pregunta;
            $opciones->user_created = $idusuario;
            $opciones->save();
            if($opciones){
                return ["status" => "1", "message" => "Se guardo correctamente"];
            }else{
                return ["status" => "0", "message" => "No se pudo guardar"];
            }
    }
    public function eliminarPregunta($id)
    {
        DB::DELETE("DELETE FROM encuesta_opciones WHERE id = '$id' ");
    }
    public function eliminarEncuestaAsignada($id)
    {
        DB::DELETE("DELETE FROM encuestas_escuela WHERE id = '$id' ");
    }
    public function changeEstado($id,$estado){
        $change = EncuestaEscuela::findOrFail($id);
        $change->estado = $estado;
        $change->save();
        if($change){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "0", "message" => "No se pudo guardar"];
        }
    }
    public function getResultadoEncuestas(Request $request){
        $resultados = DB::SELECT("SELECT o.*,
            (
                SELECT COUNT(rd.id)  AS siR
                FROM encuesta_respuesta_detalles rd
                LEFT JOIN encuesta_respuesta er ON rd.encuesta_respuesta_id = er.id
                WHERE er.encuestas_escuela_id = '$request->encuestas_escuela_id'
                AND rd.encuesta_opcion_id  = o.id
                AND rd.valor = 1
            ) AS siR,
            (
                SELECT COUNT(rd.id)  AS siR
                FROM encuesta_respuesta_detalles rd
                LEFT JOIN encuesta_respuesta er ON rd.encuesta_respuesta_id = er.id
                WHERE er.encuestas_escuela_id = '$request->encuestas_escuela_id'
                AND rd.encuesta_opcion_id  = o.id
                AND rd.valor = 0
            ) AS noR
            FROM encuesta_opciones o
            WHERE o.encuesta_id = '$request->encuesta_id'
        ");
        return $resultados;
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
        DB::DELETE("DELETE FROM encuesta WHERE id = '$id' ");
    }
}
