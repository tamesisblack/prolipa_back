<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Simulador;
use DB;

class SimuladorController extends Controller
{
 
    public function index(Request $request)
    {
        
        //==========PARA EL DOCENTE=================
        if($request->asignatura){
             //para traer la data de simulador
             $simulador = DB::select("SELECT s.*, a.nombreasignatura from simulador  s
             LEFT JOIN asignatura a ON a.idasignatura = s.asignatura_id 
             WHERE a.idasignatura = $request->idasignatura
             AND s.estado = '1'
             ORDER BY s.simulador_id DESC
             
            "); 
            return $simulador;  
        }

        ///========FIN PARA EL DOCENTE============
        //=========PARA EL ESTUDIANTE============

        if($request->estudiante){
            $simulador = DB::select("SELECT s.*
             from simulador  s,ma_cursos_has_simulador ma
           
            WHERE  ma.simulador_id = s.simulador_id
            AND ma.codigo_curso = '$request->codigo_curso'
            AND ma.estado = '1'
            AND s.estado  = '1'
            ORDER BY s.simulador_id DESC
            
           "); 
           return $simulador;  
        }

        //========FIN PARA EL ESTUDIANTE==========
        //para cambiar de estado los  simuladores
        if($request->cambiarEstado){
            
            $simulador = Simulador::findOrFail($request->simulador_id);
            $simulador->estado = $request->estado;
            $simulador->save();

            if($simulador){
                return "Se cambio de estado correctamente";
            }else{
                return "No se pudo cambiar de estado ";
            }

        }
        
        else{

            //para traer la data de simulador
            $simulador = DB::select("SELECT s.*, a.nombreasignatura from simulador  s
            
             LEFT JOIN asignatura a ON a.idasignatura = s.asignatura_id 
              ORDER BY s.simulador_id DESC
             
            ");

            //para traer las asignaturas
            $asignaturas = DB::select("SELECT * FROM asignatura WHERE estado = '1'
            AND tipo_asignatura = '1'
            ");
            return ["simulador" => $simulador, "asignaturas" => $asignaturas];
        }
     



    }

    public function cursosLibrosSimulador(Request $request){
        $cursos = DB::SELECT("SELECT c.* 
            FROM curso c, periodoescolar p
            WHERE c.idusuario = $request->id_usuario
            AND c.id_asignatura = $request->id_asignatura
            AND c.id_periodo = p.idperiodoescolar
            AND c.estado = '1'
            AND p.estado = '1'
            GROUP BY c.codigo
        ");
        $simuladores = DB::SELECT("SELECT * FROM ma_cursos_has_simulador WHERE simulador_id = '$request->simulador_id' AND estado = '1'");

        return ["cursos"=>$cursos,"simuladores"=>$simuladores];
    }
    public function store(Request $request)
    {
        //para editar
        if($request->simulador_id){
       
            $simulador = Simulador::findOrFail($request->simulador_id);
            $simulador->asignatura_id = $request->asignatura_id;
            $simulador->nombre = $request->nombre;
            $simulador->descripcion = $request->descripcion;
            $simulador->link = $request->link; 
            if($request->link_tutorial == "null"){
            $simulador->link_tutorial = "";
            }else{
            $simulador->link_tutorial = $request->link_tutorial;            
            }
            
        //para guardar
        }else{
            $simulador = new Simulador();
            $simulador->asignatura_id = $request->asignatura_id;
            $simulador->nombre = $request->nombre;
            $simulador->descripcion = $request->descripcion;
            $simulador->link = $request->link;
            $simulador->link_tutorial = $request->link_tutorial;
           
        }
        $simulador->save();
        if($simulador){
            return "Se guardo correctamente";
        }else{
            return "No se pudo guardar/actualizar";
        }
    }

    public function asignarSimulador(Request $request){
        $cursos= DB::SELECT("SELECT * FROM ma_cursos_has_simulador cm WHERE cm.codigo_curso = '$request->codigo_curso' AND cm.simulador_id = $request->simulador_id AND cm.estado ='1'");

        if( empty($cursos) ){
            $juegos= DB::INSERT("INSERT INTO `ma_cursos_has_simulador`(`codigo_curso`, `simulador_id`) VALUES ('$request->codigo_curso', $request->simulador_id)");

        
            return "Asignado correctamente";
        }else{
            return "Este material ya se encuentra asignado a este curso";
        }
    }

    public function quitarSimulador(Request $request){
     

            DB::table('ma_cursos_has_simulador')
            ->where('simulador_curso_id', $request->simulador_curso_id)
            ->update(['estado' => '0']);


            return "Se ha quitado el simulador correctamente";
        
    }

 
    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        //
    }

 
}
