<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ReporteUsurio;
use DB;

class ReporteUsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $docentes = DB::SELECT("SELECT * FROM usuario WHERE institucion_idInstitucion  = 157");
        foreach ($docentes as $key => $post) {
            $data['items'][$key] = [
                'docente' => $post,
                'libros' =>  $this->cursosDocente($post->idusuario) ,
            ];
        }
        return $data;
    }

    public function cursosDocente($idusuario){
        $data = [];
        $cursos = DB::SELECT("SELECT * FROM curso WHERE idusuario = ?",[$idusuario]);
        foreach ($cursos as $key => $post) {
            if($this->cursosEstudiante($post->codigo) != null){
                array_push($data, $this->cursosEstudiante($post->codigo));
            }else{

            }
        }
        return $data;
    }

    public function cursosEstudiante($codigo){
        $data = [];
        $estudiantes = DB::SELECT("SELECT * FROM estudiante WHERE estudiante.codigo = ?",[$codigo]);
        // if($estudiantes != []){
            foreach ($estudiantes as $key => $value) {
                if($value == true){
                    return $this -> estudianteLibros($value->usuario_idusuario);
                }else{

                }
            }
        // }else{

        // }
    }

    public function estudianteLibros($idusuario)
    {
        $data = [];
        $libros = DB::SELECT("SELECT * FROM `codigoslibros` WHERE `idusuario` = ?",[$idusuario]);
        foreach ($libros as $key => $value) {
            if($value == true){
                return $value;
            }else{

            }
        }
    }

    public function recuperar(){
        $cont = 0;
        $datop = DB::SELECT("CALL `recuperacion` ();");
        foreach ($datop as $key) {
            // echo $key->codigo;
            // echo $key->idusuario;
            $estudiantes = DB::SELECT("SELECT * FROM estudiante");
            foreach ($estudiantes as $es) {
                if($key->idusuario==$es->usuario_idusuario && $key->codigo == $es->codigo){
                    $cont++;
                    echo "1";
                    echo "Codigo:".$key->codigo;
                    echo "Id: ".$key->idusuario;
                    echo "<br>";
                }else{

                }
            }
        }
        return $cont;
    }

    public function buscar(Request $request)
    {
        if (!empty($request->ini) && $request->fin =="undefined") {
            $users = DB::SELECT('SELECT usuario.cedula,UPPER(usuario.nombres) as nombre, UPPER(usuario.apellidos) as apellido,registro_usuario.ip, registro_usuario.hora_ingreso_usuario FROM registro_usuario join usuario on usuario.idusuario = usuario_idusuario WHERE DATE(hora_ingreso_usuario)=?',[$request->ini]);
        }else{
            if ($request->ini<=$request->fin) {
                $ini = $request->ini;
               $fin = $request->fin;
               }else{
                $ini = $request->fin;
                $fin = $request->ini;
               }
            if (!empty($ini) && !empty($fin)) {
                $users = DB::SELECT('SELECT usuario.cedula,UPPER(usuario.nombres) as nombre, UPPER(usuario.apellidos) as apellido,registro_usuario.ip, registro_usuario.hora_ingreso_usuario FROM registro_usuario join usuario on usuario.idusuario = usuario_idusuario WHERE DATE(hora_ingreso_usuario) BETWEEN ? AND ?',[$ini,$fin]);
            }
        }

        return $users;
    }

    public function reporte_redaccion($fecha_ini, $fecha_fin)
    {

        $redactores = DB::SELECT("SELECT
        CONCAT(u.nombres, ' ', u.apellidos, ' ', u.cedula) AS redactor, u.idusuario, u.estado_idEstado, u.id_group, u.cedula,
        (SELECT COUNT(j.id_juego) FROM j_juegos j WHERE j.id_docente = u.idusuario AND j.created_at BETWEEN '$fecha_ini' AND '$fecha_fin') AS cant_juegos,
        (SELECT COUNT(p.id) FROM preguntas p WHERE p.idusuario = u.idusuario AND p.created_at BETWEEN '$fecha_ini' AND '$fecha_fin') AS cant_preguntas
                FROM  usuario u
                WHERE u.id_group = 1
                GROUP BY u.idusuario;");

        return $redactores;
    }

    public function cursosInstitucion($id){
        $cursos = DB::SELECT("SELECT curso.*,asignatura.nombreasignatura FROM usuario join curso on curso.idusuario = usuario.idusuario left join asignatura on asignatura.idasignatura = curso.id_asignatura WHERE usuario.institucion_idInstitucion = ?",[$id]);
        return $cursos;
    }
}
