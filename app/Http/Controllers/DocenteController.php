<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Agenda;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\InstitucionFueraProlipa;
use DB;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

class DocenteController extends Controller
{

    //para contar la cantidad de evaluacion del docente :api>>get/cantEvaluacionesDocente
    public function cantEvaluacionesDocente(Request $request){
          ///Para buscar el periodo

          $verificarperiodoinstitucion = DB::table('periodoescolar_has_institucion')
          ->select('periodoescolar_has_institucion.periodoescolar_idperiodoescolar')

          ->where('periodoescolar_has_institucion.institucion_idInstitucion','=',$request->id_institucion)
          ->get();

           foreach($verificarperiodoinstitucion  as $clave=>$item){
              $verificarperiodos =DB::SELECT("SELECT p.idperiodoescolar
              FROM periodoescolar p
              WHERE p.estado = '1'
              and p.idperiodoescolar = $item->periodoescolar_idperiodoescolar
              ");
           }

         if(count($verificarperiodoinstitucion) <=0){
              return ["status"=>"0", "message" => "No existe el periodo lectivo por favor, asigne un periodo a esta institucion"];
          }

           //verificar que el periodo exista
          if(count($verificarperiodos) <= 0){

              return ["status"=>"0", "message" => "No existe el periodo lectivo por favor, asigne un periodo a esta institucion"];

           }

        //fin de busqueda del periodo
           //almancenar el periodo
          $periodo =  $verificarperiodos[0]->idperiodoescolar;

          $contarEvaluaciones = DB::SELECT("SELECT  e.*,c.id_periodo FROM evaluaciones e, curso c
          WHERE  c.codigo = e.codigo_curso
          AND e.id_docente = '$request->idusuario'
          AND c.id_periodo = '$periodo'
          ");
          return $contarEvaluaciones;
    }


    public function index(Request $request)
    {
        if($request->grupo == 1){
            $usuarios = User::where('institucion_idInstitucion', 66)->get(['idusuario', 'cedula', 'nombres', 'apellidos', 'name_usuario', 'email', 'telefono', 'estado_idEstado', 'id_group', 'institucion_idInstitucion', 'estado_idEstado','foto_user']);
        }else{
            $usuarios = User::where('id_group',$request->grupo)->get(['idusuario', 'cedula', 'nombres', 'apellidos', 'name_usuario', 'email', 'telefono', 'estado_idEstado', 'id_group', 'institucion_idInstitucion', 'estado_idEstado','foto_user']);
        }
        return $usuarios;
    }
    //api:get>>/getUserAdmin
    public function getUserAdmin(Request $request){
        $usuarios = User::where('id_group',$request->grupo)->get(['idusuario', 'cedula', 'nombres', 'apellidos', 'name_usuario', 'email', 'telefono', 'estado_idEstado', 'id_group', 'institucion_idInstitucion', 'estado_idEstado','foto_user']);
        return $usuarios;
    }
    public function docentesInstitucion($id)
    {
        $usuarios = User::select(
            'usuario.idusuario', 
            'usuario.cedula', 
            'usuario.nombres', 
            'usuario.apellidos', 
            'usuario.name_usuario', 
            'usuario.email', 
            'usuario.telefono', 
            'usuario.estado_idEstado', 
            'usuario.id_group', 
            'usuario.institucion_idInstitucion', 
            'usuario.foto_user',
            'i.idInstitucion',
            DB::RAW('MAX(se.id_evaluacion) AS id_evaluacion')
        )
        ->leftJoin('salle_evaluaciones as se', 'se.id_usuario', '=', 'usuario.idusuario')
        ->leftjoin('institucion as i', 'i.idInstitucion', '=', 'usuario.institucion_idInstitucion')
        ->where('institucion_idInstitucion', $id)
        ->whereIn('id_group', [6, 13])
        ->groupBy(
            'usuario.idusuario', 
            'usuario.cedula', 
            'usuario.nombres', 
            'usuario.apellidos', 
            'usuario.name_usuario', 
            'usuario.email', 
            'usuario.telefono', 
            'usuario.estado_idEstado', 
            'usuario.id_group', 
            'usuario.institucion_idInstitucion', 
            'usuario.foto_user',
            'i.idInstitucion'
        )
        ->get();
        
        return $usuarios;
    }

    public function tareas(Request $request){
        $tareas = DB::SELECT("SELECT tarea.* FROM curso JOIN tarea ON tarea.curso_idcurso = curso.idcurso WHERE curso.idusuario = ? AND curso.estado = '1' AND tarea.estado = '1'",[$request->idusuario]);
        return $tareas;
    }

    public function contenidos(Request $request){
        $contenido = DB::SELECT("SELECT contenido.* FROM curso JOIN contenido ON contenido.curso_idcurso = curso.idcurso WHERE curso.idusuario = ? AND curso.estado = '1' AND contenido.estado = '1'",[$request->idusuario]);
        return $contenido;
    }
    public function cant_contenido($user){
        $archivos = DB::SELECT("SELECT ct.idcontenido from contenido ct, curso cu where ct.curso_idcurso = cu.idcurso and cu.idusuario = $user");
        return $archivos;
    }
    public function cant_evaluaciones($id){
        $eval = DB::SELECT("SELECT id_docente FROM evaluaciones WHERE estado = '1' and id_docente = $id");
        return $eval;
    }

    public function get_agenda_docente($docente)
    {
        $agenda = DB::SELECT("SELECT a.periodo_id,a.institucion_id_temporal,a.estado,i.nombreInstitucion, a.id,
        a.id_usuario, a.nombre_institucion_temporal,a.estado_institucion_temporal,a.fecha_que_visita,
         CONCAT(a.title, ' (', a.hora_inicio, ' - ', a.hora_fin, ')' ) as title, a.label, a.classes,
          a.startDate, a.endDate, a.hora_inicio, a.hora_fin, a.url, a.opciones, a.institucion_id
          FROM agenda_usuario a
         LEFT JOIN institucion i ON a.institucion_id = i.idInstitucion
         WHERE a.id_usuario = $docente
         AND a.estado  <> '2'
         ");
        return $agenda;
    }

    public function save_agenda_docente(Request $request)
    {
        if( $request->id != 0 ){
            $agenda = Agenda::find($request->id);
            if($request->finalizar){
                $agenda->estado            = "1";
                $agenda->fecha_que_visita  = $request->fecha_que_visita;
            }

        }else{
            $agenda = new Agenda();
        }
        //si crean una insitucion temporal
        if($request->estado_institucion_temporal == 1){
            $agenda->periodo_id = $request->periodo_id_temporal;
            $agenda->institucion_id_temporal = $request->institucion_id_temporal;
            $agenda->nombre_institucion_temporal = $request->nombreInstitucion;
            $agenda->institucion_id = "";
        }
        if($request->estado_institucion_temporal == 0){
            $agenda->institucion_id = $request->institucion_id;
            $agenda->institucion_id_temporal = "";
            $agenda->nombre_institucion_temporal = "";
               //para traer el periodo
            $buscarPeriodo = $this->traerPeriodo($request->institucion_id);
            if($buscarPeriodo["status"] == "1"){
                $obtenerPeriodo = $buscarPeriodo["periodo"][0]->periodo;
                $agenda->periodo_id = $obtenerPeriodo;

            }
        }
        $agenda->id_usuario = $request->idusuario;
        $agenda->usuario_creador = $request->idusuario;
        $agenda->title = $request->title;
        $agenda->label = $request->label;
        $agenda->classes = $request->classes;
        $agenda->startDate = $request->startDate;
        $agenda->endDate = $request->endDate == null || $request->endDate == "null" ? null : $request->endDate;
        $agenda->hora_inicio = $request->hora_inicio;
        $agenda->hora_fin = $request->hora_fin;
        $agenda->url = $request->url;
        $agenda->opciones = $request->opciones;
        $agenda->estado_institucion_temporal =$request->estado_institucion_temporal;


        $agenda->save();
        //guardar en caso que sea otra editorial
        if($request->otraEditorial == "true"){

            //para editar
            if( $request->id != 0 ){

               $validar = DB::SELECT("SELECT * FROM  institucion_fuera_prolipa where  asesor_planificacion_id = '$agenda->id'");
               if(count($validar) == 0){
                $this->guardarInstitucionFueraProlipa($request->nombre_editorial,$request->idusuario,$agenda->id,$request->estado_institucion_temporal,$agenda->periodo_id,$request->institucion_id_temporal,$request->nombreInstitucion,$request->institucion_id);
               }else{
                    if($request->estado_institucion_temporal == 1 ){
                        DB::table('institucion_fuera_prolipa')
                        ->where('asesor_planificacion_id', $agenda->id)
                        ->update([
                            'estado_institucion_temporal' =>  $request->estado_institucion_temporal,
                            'periodo_id' =>  $agenda->periodo_id,
                            'nombre_editorial' =>  $request->nombre_editorial,
                            'institucion_id_temporal' =>  $request->institucion_id_temporal,
                            'nombre_institucion_temporal' =>  $request->nombreInstitucion,
                            'institucion_id' =>  "",
                        ]);
                    }

                    if($request->estado_institucion_temporal == 0){
                        DB::table('institucion_fuera_prolipa')
                        ->where('asesor_planificacion_id', $agenda->id)
                        ->update([
                            'nombre_editorial' =>  $request->nombre_editorial,
                            'estado_institucion_temporal' =>  $request->estado_institucion_temporal,
                            'periodo_id' =>   $agenda->periodo_id,
                            'institucion_id' =>  $request->institucion_id,
                            'institucion_id_temporal' =>  "",
                            'nombre_institucion_temporal' =>  "",
                        ]);
                    }
               }
            }else{

                $this->guardarInstitucionFueraProlipa($request->nombre_editorial,$request->idusuario,$agenda->id,$request->estado_institucion_temporal,$agenda->periodo_id,$request->institucion_id_temporal,$request->nombreInstitucion,$request->institucion_id);
            }
        }
        return
        $agenda;
    }



    public function guardarInstitucionFueraProlipa($nombre_editorial,$idusuario,$id,$estado_institucion_temporal,$periodo_id,$institucion_id_temporal,$nombreInstitucion,$institucion_id){
        $otra_institucion = new InstitucionFueraProlipa;
        //si crean una insitucion temporal
        $otra_institucion->nombre_editorial = $nombre_editorial;
        $otra_institucion->asesor_id = $idusuario;
        $otra_institucion->asesor_planificacion_id = $id;
        $otra_institucion->estado_institucion_temporal =$estado_institucion_temporal;
        $otra_institucion->periodo_id = $periodo_id;
        //si es institucion no esta registrada en  prolipa
       if($estado_institucion_temporal == 1 ){
           $otra_institucion->institucion_id_temporal = $institucion_id_temporal;
           $otra_institucion->nombre_institucion_temporal = $nombreInstitucion;
           $otra_institucion->institucion_id = "";
       }
       //si es institucion  registrada en  prolipa
       if($estado_institucion_temporal == 0){
           $otra_institucion->institucion_id = $institucion_id;
           $otra_institucion->institucion_id_temporal = "";
           $otra_institucion->nombre_institucion_temporal = "";

       }
       $otra_institucion->save();
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
    public function delete_agenda_docente($id_agenda)
    {
        DB::DELETE("DELETE FROM `agenda_usuario` WHERE `id` = $id_agenda");
        $validar = DB::SELECT("SELECT * FROM  institucion_fuera_prolipa where  asesor_planificacion_id = '$id_agenda'");
        if(count($validar) > 0){
            DB::DELETE("DELETE FROM `institucion_fuera_prolipa` WHERE `asesor_planificacion_id` = $id_agenda");
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Docente  $docente
     * @return \Illuminate\Http\Response
     */
    public function edit(Docente $docente)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Docente  $docente
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Docente $docente)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Docente  $docente
     * @return \Illuminate\Http\Response
     */
    public function destroy(Docente $docente)
    {
        //
    }
}
