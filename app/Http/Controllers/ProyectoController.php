<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\ProyectoAsignatura;
use App\Models\ProyectoCurso;
use App\Models\ProyectoRespuesta;
use Illuminate\Http\Request;
use DB;

class ProyectoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
     
        //PARA TRAER LOS CURSOS
          if($request->curso){
            $cursos  = $this->TraerCursos($request->curso);
            return $cursos;
        }
        //PARA TRAER LOS CURSOS DEL PROYECTO
        if($request->respuestas){
            $cursoRespuesta  = $this->cursoRespuesta($request->proyecto_id,$request->curso_id);
            return $cursoRespuesta;
        }
         //PARA TRAER LOS ARCHIVOS QE SUBIERON LOS ESTUDIANTES PARA PODER ELIMINARLOS
         if($request->archivosEstudiante){
            $files  = $this->archivosEstudiante($request->proyecto_id,$request->curso_id);
            return $files;
        }
        //PARA TRAER LAS RESPUESTA DEL ESTUDIANTE
        if($request->respuestaEstudiante){
            $respuesta = $this->RespuestaEstudiante($request->proyecto_id,$request->idusuario);
            return $respuesta;
        }
        else{
            //PARA TRAER LOS PROYECTOS
            if($request->docente == "yes"){
                $proyectos = DB::SELECT("SELECT p.*, CONCAT(u.nombres, ' ',u.apellidos) as responsable
                FROM proyectos p 
                LEFT JOIN usuario u ON p.idusuario = u.idusuario
                WHERE p.estado = '1'
                ORDER BY p.id DESC
                ");
            }
            else{
                $proyectos = DB::SELECT("SELECT p.id, p.nombre, p.descripcion, p.idusuario, p.grupo_usuario, p.estado,p.created_at,p.updated_at,
                CONCAT(u.nombres, ' ',u.apellidos) as responsable
                FROM proyectos p 
                LEFT JOIN usuario u ON p.idusuario = u.idusuario
                WHERE p.estado = '1'
                AND p.grupo_usuario = '$request->grupo'
                ORDER BY p.id DESC
                ");
            }
            $datos = [];
            $asignaturas = [];
            // //traer las asignaturas de los proyectos
            foreach($proyectos as $key => $item){
                $asignaturas = DB::SELECT("SELECT pa.*,nombreasignatura
                FROM proyecto_asignatura pa
                LEFT JOIN asignatura a ON a.idasignatura = pa.asignatura_id
                WHERE  pa.proyecto_id = '$item->id'
                ");
                foreach($asignaturas as $i => $valor){
                    $asignaturas[$i]= [
                        "pasignatura_id" => $valor->pasignatura_id,
                        "asignatura_id"  => $valor->asignatura_id,
                        "nombreasignatura" => $valor->nombreasignatura
                    ];
                }
                //TRAER LOS FILES DEL PROYECTO
                $files = DB::SELECT("SELECT f.*
                    FROM proyecto_files f
                    WHERE  f.proyecto_id = '$item->id'
                    AND f.respuesta = '0'
                "); 
                $datos[$key] = [
                    "id" =>           $item->id,
                    "idusuario" =>    $item->idusuario,
                    "responsable" =>  $item->responsable,
                    "grupo_usuario"=> $item->grupo_usuario,
                    "nombre" =>       $item->nombre,
                    "descripcion" =>  $item->descripcion,
                    "asignaturas" =>  $asignaturas,
                    "files" =>        $files,
                    "created_at" =>   $item->created_at,
                    "updated_at" =>   $item->updated_at
                ];   
            }
             return $datos;
        }
       
    }
    public function RespuestaEstudiante($proyecto_id,$idusuario){
        $respuesta = DB::SELECT("SELECT r.*, CONCAT(u.nombres, ' ',u.apellidos) as responsable
        FROM proyecto_respuesta r
        LEFT JOIN usuario u ON r.idusuario = u.idusuario
        WHERE r.proyecto_id = '$proyecto_id'
        AND r.idusuario = '$idusuario'
        ");
          $datos = [];
          foreach($respuesta as $key => $item){
              //TRAER LOS FILES DEL PROYECTO
              $files = DB::SELECT("SELECT f.*
                  FROM proyecto_files f
                  WHERE  f.proyecto_id = '$item->id'
                  AND f.respuesta = '1'
              "); 
              $datos[$key] = [
                  "id" =>           $item->id,
                  "proyecto_id" =>  $item->proyecto_id,
                  "idusuario" =>    $item->idusuario,
                  "responsable" =>  $item->responsable,
                  "introduccion" => $item->introduccion,
                  "tarea" =>        $item->tarea,
                  "proceso" =>      $item->proceso,
                  "recurso" =>      $item->recurso,
                  "evaluacion" =>   $item->evaluacion,
                  "conclusion" =>   $item->conclusion,
                  "calificacion" => $item->calificacion,
                  "comentario_docente" => $item->comentario_docente,
                  "files" =>        $files,
                  "created_at" =>   $item->created_at,
                  "updated_at" =>   $item->updated_at
              ];   
        }
          return $datos;
    }
    public function TraerCursos($curso){
        $cursos = DB::SELECT("SELECT c.*, p.nombre,p.descripcion, a.nombreasignatura
        FROM proyecto_curso c 
        LEFT JOIN proyectos p on c.proyecto_id = p.id
        LEFT JOIN asignatura a On a.idasignatura = c.asignatura_id
        WHERE curso = '$curso'
        ORDER BY id DESC
        ");
        return $cursos;
        
    }
    public function archivosEstudiante($proyecto,$curso){
        $files = DB::SELECT("SELECT *
         FROM proyecto_files
         where proyecto_id = '$proyecto'
         AND curso = '$curso'
         ");
         return $files;
    }
    public function cursoRespuesta($proyecto,$curso){
        $respuesta = DB::SELECT("SELECT r.*,CONCAT(u.nombres, ' ',u.apellidos) as estudiante,u.nombres,u.apellidos,u.email,u.cedula
        FROM proyecto_respuesta r
        LEFT JOIN usuario u ON r.idusuario = u.idusuario
        WHERE r.curso = '$curso'
        AND r.proyecto_id = '$proyecto'
        ");
          $datos = [];
          foreach($respuesta as $key => $item){
              //TRAER LOS FILES DEL PROYECTO
              $files = DB::SELECT("SELECT f.*
                  FROM proyecto_files f
                  WHERE  f.proyecto_id = '$proyecto'
                  AND  f.curso = '$curso'
                  AND f.idusuario  = '$item->idusuario'
                  AND f.respuesta = '1'
              "); 
              $datos[$key] = [
                  "id" =>           $item->id,
                  "proyecto_id" =>  $item->proyecto_id,
                  "idusuario" =>    $item->idusuario,
                  "estudiante" =>   $item->estudiante,
                  "nombres" =>      $item->nombres,
                  "apellidos" =>    $item->apellidos,
                  "email" =>        $item->email,
                  "cedula" =>       $item->cedula,
                  "introduccion" => $item->introduccion,
                  "tarea" =>        $item->tarea,
                  "proceso" =>      $item->proceso,
                  "recurso" =>      $item->recurso,
                  "evaluacion" =>   $item->evaluacion,
                  "conclusion" =>   $item->conclusion,
                  "calificacion" => $item->calificacion,
                  "comentario_docente" => $item->comentario_docente,
                  "files" =>        $files,
                  "created_at" =>   $item->created_at,
                  "updated_at" =>   $item->updated_at
              ];   
        }
        return $datos;
    }
    public function store(Request $request)
    {   
      //guardar el curso
       if($request->guardarCurso){
           $curso = new ProyectoCurso();
           $curso->proyecto_id = $request->proyecto_id;
           $curso->asignatura_id = $request->asignatura_id;
           $curso->curso = $request->curso;
           $curso->estado = '1';
           $curso->save();
          
       }
       //para cambiar de estado al curso
       if($request->cambiarEstado){
           $curso = ProyectoCurso::findOrFail($request->id);
           $curso->estado = $request->estado;
           $curso->save();
       }
       if($request->guardarCalificacion){
            $curso = ProyectoRespuesta::findOrFail($request->id);
            $curso->comentario_docente = $request->comentario_profesor;
            $curso->calificacion =       $request->calificacion;
            $curso->save();
       }
       if($curso){
           return ["status" => "1", "message"=>"Se guardo correctamente"];
       }else{
           return ["status" => "0", "message"=>"No se pudo guardar"];
       }

    }

    public function proyectoImagen($imagen){
        return $imagen;
    }
    public function upload(Request $request){
        "good";
    }

    public function show($id)
    {
        $proyectos = DB::SELECT("SELECT p.* 
        FROM proyectos p 
        where p.id = '$id'
        AND p.estado = '1'
        ORDER BY p.id DESC
        ");
        $datos = [];
        $asignaturas = [];
        if(count($proyectos)){
            //TRAER LAS ASIGNATURAS LAS ASIGNATURAS DEL PROYECTO
            $asignaturas = DB::SELECT("SELECT pa.* ,a.nombreasignatura
            FROM proyecto_asignatura pa
            LEFT JOIN asignatura a ON a.idasignatura = pa.asignatura_id
            WHERE  pa.proyecto_id = '$id'
            ");  
            foreach($asignaturas as $i => $valor){
                $asignaturas[$i]= [
                    "pasignatura_id" => $valor->pasignatura_id,
                    "asignatura_id"  => $valor->asignatura_id,
                    "nombreasignatura" => $valor->nombreasignatura
                ];
            }
            //TRAER LOS FILES DEL PROYECTO
            $files = DB::SELECT("SELECT f.*
            FROM proyecto_files f
            WHERE  f.proyecto_id = '$id'
            AND f.respuesta = '0'
            "); 
            $datos = [
                "id" =>           $proyectos[0]->id,
                "nombre" =>       $proyectos[0]->nombre,
                "grupo_usuario"=> $proyectos[0]->grupo_usuario,
                "descripcion" =>  $proyectos[0]->descripcion,
                "introduccion" => $proyectos[0]->introduccion,
                "tarea" =>        $proyectos[0]->tarea,
                "proceso" =>      $proyectos[0]->proceso,
                "recurso" =>      $proyectos[0]->recurso,
                "evaluacion" =>   $proyectos[0]->evaluacion,
                "conclusion" =>   $proyectos[0]->conclusion,
                "creditos" =>     $proyectos[0]->creditos,
                "asignaturas" =>  $asignaturas,
                "files" =>        $files,
                "created_at" =>   $proyectos[0]->created_at,
                "updated_at" =>   $proyectos[0]->updated_at
            ];
            return $datos;
        }
       
    }
    //api::post>>/proyectos/eliminar
    public function eliminar(Request $request){
        if($request->eproyecto){
            $proyecto = Proyecto::find($request->id);
            $proyecto->estado = "0";
            $proyecto->save();
            if($proyecto){
                return ["status" => "1", "message" => "Se elimino correctamente"];
            }else{
                return ["status" => "0", "message" => "No se pudo eliminar"];
            }
        }
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
        ProyectoAsignatura::findOrFail($id)->delete();
    }
}
