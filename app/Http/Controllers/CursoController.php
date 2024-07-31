<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\RepresentanteEconomico;
use App\Models\RepresentanteLegal;
use App\Models\CuotasPorCobrar;
use App\Models\CursosParalelos;
use App\Models\NivelesPeriodosInstitucion;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Dirape\Token\Token;
use DB;
use DateTime;
use App\Models\EstudianteMatriculado;
use App\Models\NivelInstitucion;
use App\Models\PeriodoInstitucion;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

class CursoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $curso = DB::select("SELECT DISTINCT c. * FROM curso c, institucion_has_usuario iu, periodoescolar_has_institucion phi, periodoescolar p WHERE c.idusuario =  $request->idusuario AND c.estado = '1' AND c.idusuario = iu.usuario_idusuario AND iu.institucion_idInstitucion = phi.institucion_idInstitucion AND phi.periodoescolar_idperiodoescolar = p.idperiodoescolar AND p.estado = '1'");

        return $curso;
    }


    public function cursos_evaluaciones($id_usuario)
    {
        // $curso = DB::select("SELECT DISTINCT c. *, a.nombreasignatura, a.area_idarea, a.idasignatura, a.nivel_idnivel, a.tipo_asignatura FROM curso c, asignatura a, institucion_has_usuario iu, periodoescolar_has_institucion phi, periodoescolar p WHERE c.idusuario = $id_usuario AND c.estado = '1' AND c.idusuario = iu.usuario_idusuario AND iu.institucion_idInstitucion = phi.institucion_idInstitucion AND phi.periodoescolar_idperiodoescolar = p.idperiodoescolar AND p.estado = '1' AND c.id_asignatura = a.idasignatura");

        $cursos = DB::SELECT("SELECT DISTINCT c. *,
            a.nombreasignatura, a.area_idarea, a.idasignatura, a.nivel_idnivel,
            a.tipo_asignatura,
            (SELECT COUNT(e.id) AS estudiantes FROM estudiante e
            LEFT JOIN usuario us ON e.usuario_idusuario = us.idusuario
            WHERE e.codigo = c.codigo
            AND us.estado_idEstado = '1'
            AND e.estado = '1') as estudiantes
            FROM curso c, asignatura a, usuario u, periodoescolar_has_institucion phi, periodoescolar p
            WHERE c.idusuario = u.idusuario
            AND c.idusuario = '$id_usuario'
            AND c.estado = '1'
            AND u.institucion_idInstitucion = phi.institucion_idInstitucion
            AND phi.periodoescolar_idperiodoescolar = p.idperiodoescolar
            AND p.estado = '1'
            AND c.id_asignatura = a.idasignatura
            ORDER BY c.idcurso DESC
        ");

        return $cursos;
    }


    public function cursos_evaluaciones_libro($id_usuario, $id_libro)
    {
        $cursos = DB::SELECT("SELECT DISTINCT c. *,
        a.nombreasignatura, a.area_idarea, a.idasignatura, a.nivel_idnivel,
        a.tipo_asignatura,
        (SELECT COUNT(e.id) AS estudiantes FROM estudiante e
        LEFT JOIN usuario us ON e.usuario_idusuario = us.idusuario
        WHERE e.codigo = c.codigo
        AND us.estado_idEstado = '1'
        AND e.estado = '1') as estudiantes, l.idlibro, l.nombrelibro
        FROM curso c, asignatura a, usuario u, periodoescolar_has_institucion phi, periodoescolar p, libro l
        WHERE c.idusuario = u.idusuario
        AND c.idusuario = $id_usuario
        AND c.estado = '1'
        AND u.institucion_idInstitucion = phi.institucion_idInstitucion
        AND phi.periodoescolar_idperiodoescolar = p.idperiodoescolar
        AND p.estado = '1'
        AND l.asignatura_idasignatura = c.id_asignatura
        AND c.id_asignatura = a.idasignatura
        AND l.idlibro = $id_libro
        ORDER BY c.idcurso DESC;
        ");

        return $cursos;
    }

    public function cursos_evaluaciones_asignatura_doc($id_usuario, $id_asignatura)
    {
        $cursos = DB::SELECT("SELECT DISTINCT c. *,
        a.nombreasignatura, a.area_idarea, a.idasignatura, a.nivel_idnivel,
        a.tipo_asignatura,
        (SELECT COUNT(e.id) AS estudiantes FROM estudiante e
        LEFT JOIN usuario us ON e.usuario_idusuario = us.idusuario
        WHERE e.codigo = c.codigo
        AND us.estado_idEstado = '1'
        AND e.estado = '1') as estudiantes
        FROM curso c, asignatura a, usuario u, periodoescolar_has_institucion phi, periodoescolar p
        WHERE c.idusuario = u.idusuario
        AND c.idusuario = $id_usuario
        AND c.estado = '1'
        AND u.institucion_idInstitucion = phi.institucion_idInstitucion
        AND phi.periodoescolar_idperiodoescolar = p.idperiodoescolar
        AND p.estado = '1'
        AND c.id_asignatura = a.idasignatura
        AND c.id_asignatura = $id_asignatura
        ORDER BY c.idcurso DESC;
        ");

        return $cursos;
    }


    //api:get/cursosEstudiante/{curso}
    public function cursosEstudiante(Request $request){
        if($request->bloquear){
            DB::UPDATE("UPDATE usuario SET estado_idEstado = '2' WHERE idusuario = '$request->idusuario'");
            return "se bloqueo el estudiante correctamente";
        }else{
            $estudiantes = DB::SELECT("SELECT e.*, CONCAT(u.nombres, ' ', u.apellidos) AS student, u.cedula, i.nombreInstitucion,
            u.institucion_idInstitucion, c.nombre AS ciudad,u.idusuario
            FROM estudiante e
            LEFT JOIN usuario u ON e.usuario_idusuario = u.idusuario
            LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
            LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
            WHERE e.codigo = '$request->curso'
            AND u.estado_idEstado = '1'
                AND e.estado = '1'
            ");
            return $estudiantes;
        }

    }
    public function curso_asig_docente($id)
    {
        $curso = DB::select("SELECT * FROM curso c WHERE c.id_asignatura = $id AND c.estado = '1'");

        return $curso;
    }


    public function cursos_jugaron(Request $request)
    {
        $codigos = DB::SELECT("SELECT c.codigo, c.nombre, c.seccion, c.materia, c.aula FROM curso c WHERE c.idusuario = $request->id_docente");
        if(!empty($codigos)){
            foreach ($codigos as $key => $value) {
                $calificaciones = DB::SELECT("SELECT COUNT(*) as cantidad FROM j_calificaciones jc, estudiante e WHERE jc.id_juego = ? AND jc.id_usuario = e.usuario_idusuario AND e.codigo = ?",[$request->id_juego, $value->codigo]);

                $data['items'][$key] = [
                    'codigo' => $value->codigo,
                    'nombre' => $value->nombre,
                    'seccion' => $value->seccion,
                    'materia' => $value->materia,
                    'aula' => $value->aula,
                    'calificaciones'=>$calificaciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // $curso = Curso::create([
        //     'nombre' => $request->nombre,
        //     'seccion' => $request->seccion,
        //     'materia' => $request->materia,
        //     'aula' => $request->aula,
        //     'codigo' => (new Token())->Unique('curso', 'codigo', 8),
        //     'idusuario'=> auth()->user()->idusuario
        // ]);
        // return $curso;
    }

    public function store(Request $request)
    {

        $periodo=DB::SELECT("SELECT u.idusuario, i.idInstitucion, MAX(pi.periodoescolar_idperiodoescolar) AS periodo FROM usuario u, institucion i, periodoescolar_has_institucion pi WHERE u.institucion_idInstitucion = i.idInstitucion AND i.idInstitucion = pi.institucion_idInstitucion AND u.idusuario = $request->idusuario");

        if(empty($request->idcurso)){
            $curso = Curso::create([
                'nombre' => $request->nombre,
                'id_asignatura'=> $request->id_asignatura,
                'seccion' => $request->seccion,
                'materia' => $request->materia,
                'aula' => $request->aula,
                'codigo' => $this->codigo(8),
                'idusuario'=> $request->idusuario,
                'id_periodo'=> $periodo[0]->periodo,
            ]);
        }else{
            $curso=DB::update("UPDATE curso SET nombre=?,seccion=?,materia=?,aula=?, id_asignatura=? WHERE idcurso=?",[$request->nombre,$request->seccion,$request->materia,$request->aula,$request->id_asignatura,$request->idcurso]);
        }
        return $periodo[0]->periodo;
    }

    function codigo($count)
    {
        // This string MUST stay FS safe, avoid special chars
        $base = 'ABCDEFGHKMNPRSTUVWXYZ123456789';
        $ret = '';
        $strlen = \strlen($base);
        for ($i = 0; $i < $count; ++$i) {
            $ret .= $base[random_int(0, $strlen - 1)];
        }

        return $ret;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Curso  $curso
     * @return \Illuminate\Http\Response
     */
    public function show(Curso $curso)
    {
        return $curso;
    }


    public function curso_libro_docente(Request $request){
        $cursos = DB::SELECT("SELECT c.*,
        CONCAT(c.nombre,' - ',c.codigo) as nombreCurso
        FROM curso c
        WHERE c.idusuario = '$request->id_usuario'
        AND c.id_asignatura = '$request->id_asignatura'
        AND c.id_periodo = '$request->periodo_id'
         AND c.estado = '1'
         GROUP BY c.codigo");
        return $cursos;
    }

    public function addTareaContenido(Request $request){
        $idusuario = $request->idusuario;
        $comentario = $request->comentario_estudiante;
        $file = $request->file('archivo');
        $ruta = '/var/www/vhosts/prolipadigital.com.ec/httpdocs/software/PlataformaProlipa/public';
        $fileName = uniqid().$file->getClientOriginalName();
        $file->move($ruta,$fileName);
        $request->session()->flash('notificacion','Archivo Subido');
        DB::INSERT("INSERT INTO usuario_tarea(nombre, url,tarea_idtarea,curso_idcurso,usuario_idusuario,comentario_estudiante) VALUES (?,?,?,?,?,?)",[$file->getClientOriginalName(),$fileName,$request->idtarea,$request->idcurso,$idusuario,$comentario]);
    }

    public function addContenido(Request $request){
        $file = $request->file('archivo');
        $ruta = '/var/www/vhosts/prolipadigital.com.ec/httpdocs/software/PlataformaProlipa/public';
        $fileName = uniqid().$file->getClientOriginalName();
        $file->move($ruta,$fileName);
        DB::INSERT("INSERT INTO contenido(nombre, url, curso_idcurso) VALUES (?,?,?)",[$file->getClientOriginalName(),$fileName,$request->idcurso]);
    }

    public function getContenido(Request $request){
        $date = new DateTime();
        $fecha = $date->format('y-m-d');
        if($request->idasignatura != 'null' ){
            $asig = DB::SELECT("SELECT *
            FROM contenido
            WHERE contenido.idasignatura = ? AND contenido.estado = '1'
            ",[$request->idasignatura] );
        }else{
            $asig = DB::SELECT("SELECT *
            FROM contenido
            WHERE contenido.curso_idcurso = ? AND contenido.estado = '1'
            ",[$request->idcurso] );
        }
        if(empty($request->idasignatura)){
            $asig = DB::SELECT("SELECT *
                FROM contenido
                WHERE contenido.curso_idcurso = ? AND contenido.estado = '1'
                ",[$request->idcurso] );
        }
        return $asig;
    }

    public function getContenidoTodo(Request $request){
        if(empty($request->idasignatura) && empty($request->unidad) ){
            $asig = DB::SELECT("SELECT contenido.*
            FROM asignaturausuario
            JOIN contenido ON asignaturausuario.asignatura_idasignatura = contenido.idasignatura
            WHERE asignaturausuario.usuario_idusuario = ?
            ",[$request->idusuario] );
            foreach ($asig as $key => $post) {
                try {
                    $respuesta = DB::SELECT("SELECT temas.nombre_tema FROM temas_has_contenido JOIN temas ON temas.id = temas_has_contenido.temas_id WHERE temas_has_contenido.contenido_idcontenido = ? ",[$post->idcontenido]);
                    $data['items'][$key] = [
                        'idcontenido' => $post->idcontenido,
                        'nombre' => $post->nombre,
                        'url' => $post->url,
                        'unidad' => $post->unidad,
                        'updated_at' => $post->updated_at,
                        'idasignatura' => $post->idasignatura,
                        'temas'=>$respuesta,
                    ];
                } catch (\Throwable $th) {
                    $data['items'][$key] = [
                        'idcontenido' => $post->idcontenido,
                        'nombre' => $post->nombre,
                        'url' => $post->url,
                        'unidad' => $post->unidad,
                        'updated_at' => $post->updated_at,
                        'idasignatura' => $post->idasignatura,
                        'temas'=>[],
                    ];
                }
            }

        }else{
            if (empty($request->idasignatura) || $request->idasignatura == 'null') {
                $asig = DB::SELECT("SELECT contenido.*
                FROM asignaturausuario
                JOIN contenido ON asignaturausuario.asignatura_idasignatura = contenido.idasignatura
                WHERE asignaturausuario.usuario_idusuario = ? AND contenido.unidad = ?
                ",[$request->idusuario,$request->unidad] );
                foreach ($asig as $key => $post) {
                try {
                    $respuesta = DB::SELECT("SELECT temas.nombre_tema FROM temas_has_contenido JOIN temas ON temas.id = temas_has_contenido.temas_id WHERE temas_has_contenido.contenido_idcontenido = ? ",[$post->idcontenido]);
                    $data['items'][$key] = [
                        'idcontenido' => $post->idcontenido,
                        'nombre' => $post->nombre,
                        'url' => $post->url,
                        'unidad' => $post->unidad,
                        'updated_at' => $post->updated_at,
                        'idasignatura' => $post->idasignatura,
                        'temas'=>$respuesta,
                    ];
                } catch (\Throwable $th) {
                    $data['items'][$key] = [
                        'idcontenido' => $post->idcontenido,
                        'nombre' => $post->nombre,
                        'url' => $post->url,
                        'unidad' => $post->unidad,
                        'updated_at' => $post->updated_at,
                        'idasignatura' => $post->idasignatura,
                        'temas'=>[],
                    ];
                }
            }


            }else{
                if (empty($request->unidad)) {
                    $asig = DB::SELECT("SELECT contenido.*
                    FROM asignaturausuario
                    JOIN contenido ON asignaturausuario.asignatura_idasignatura = contenido.idasignatura
                    WHERE asignaturausuario.usuario_idusuario = ? AND contenido.idasignatura = ?
                    ",[$request->idusuario,$request->idasignatura] );
                    foreach ($asig as $key => $post) {
                try {
                    $respuesta = DB::SELECT("SELECT temas.nombre_tema FROM temas_has_contenido JOIN temas ON temas.id = temas_has_contenido.temas_id WHERE temas_has_contenido.contenido_idcontenido = ? ",[$post->idcontenido]);
                    $data['items'][$key] = [
                        'idcontenido' => $post->idcontenido,
                        'nombre' => $post->nombre,
                        'url' => $post->url,
                        'unidad' => $post->unidad,
                        'updated_at' => $post->updated_at,
                        'idasignatura' => $post->idasignatura,
                        'temas'=>$respuesta,
                    ];
                } catch (\Throwable $th) {
                    $data['items'][$key] = [
                        'idcontenido' => $post->idcontenido,
                        'nombre' => $post->nombre,
                        'url' => $post->url,
                        'unidad' => $post->unidad,
                        'updated_at' => $post->updated_at,
                        'idasignatura' => $post->idasignatura,
                        'temas'=>[],
                    ];
                }
            }


                } else {
                    $asig = DB::SELECT("SELECT contenido.*
                    FROM asignaturausuario
                    JOIN contenido ON asignaturausuario.asignatura_idasignatura = contenido.idasignatura
                    WHERE asignaturausuario.usuario_idusuario = ? AND contenido.idasignatura = ? AND contenido.unidad = ?
                    ",[$request->idusuario,$request->idasignatura,$request->unidad] );
                    foreach ($asig as $key => $post) {
                try {
                    $respuesta = DB::SELECT("SELECT temas.nombre_tema FROM temas_has_contenido JOIN temas ON temas.id = temas_has_contenido.temas_id WHERE temas_has_contenido.contenido_idcontenido = ? ",[$post->idcontenido]);
                    $data['items'][$key] = [
                        'idcontenido' => $post->idcontenido,
                        'nombre' => $post->nombre,
                        'url' => $post->url,
                        'unidad' => $post->unidad,
                        'updated_at' => $post->updated_at,
                        'idasignatura' => $post->idasignatura,
                        'temas'=>$respuesta,
                    ];
                } catch (\Throwable $th) {
                    $data['items'][$key] = [
                        'idcontenido' => $post->idcontenido,
                        'nombre' => $post->nombre,
                        'url' => $post->url,
                        'unidad' => $post->unidad,
                        'updated_at' => $post->updated_at,
                        'idasignatura' => $post->idasignatura,
                        'temas'=>[],
                    ];
                }
            }


                }
            }

        }
        return $data;
    }

    public function getEstudiantes(Request $request){
        $contenido = DB::SELECT("CALL estudianteCurso (?);",["$request->codigo"]);
        return $contenido;
    }

    public function Calificacion(Request $request){
        $estudiantes = DB::SELECT("CALL `estudianteCurso`(?);",["$request->codigo"]);
        if(!empty($estudiantes)){
            foreach ($estudiantes as $key => $value) {
                $total = DB::SELECT("SELECT * FROM tarea  WHERE tarea.curso_idcurso = ? AND tarea.estado = '1' ",[$value->idcurso]);
                $tareas = DB::SELECT("SELECT usuario_tarea.nota  FROM tarea join usuario_tarea on usuario_tarea.tarea_idtarea = tarea.idtarea WHERE tarea.curso_idcurso = ? and usuario_tarea.usuario_idusuario = ? ",[$value->idcurso,$value->idusuario]);
                $data['items'][$key] = [
                    'idusuario' => $value->idusuario,
                    'cedula' => $value->cedula,
                    'foto_user' => $value->foto_user,
                    'nombres' => $value->nombres,
                    'apellidos' => $value->apellidos,
                    'tareas'=>$this->TareasCalificadas($value->idcurso,$value->idusuario),
                    'total'=>$total,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }

    public function TareasCalificadas($idcurso,$idusuario){
        $nota = '0';
        $notas=[];
        $total = DB::SELECT("SELECT * FROM tarea  WHERE tarea.curso_idcurso = ? AND tarea.estado = '1' ",[$idcurso]);
        foreach ($total as $key => $value) {
            $tareas = DB::SELECT("SELECT usuario_tarea.nota  FROM tarea join usuario_tarea on usuario_tarea.tarea_idtarea = tarea.idtarea WHERE usuario_tarea.tarea_idtarea = ? and usuario_tarea.usuario_idusuario = ? ",[$value->idtarea,$idusuario]);
            if(!empty($tareas)){
                foreach ($tareas as $key => $value) {
                    $nota = [
                        'nota'=> $value->nota
                    ];
                }
            }else{
                $nota = [
                    'nota'=> 0
                ];
            }
            array_push($notas, $nota);
        }
        return $notas;

    }



    public function eliminarContenido(Request $request){
        DB::UPDATE("UPDATE `contenido` SET `estado`='0' WHERE `idcontenido`=?",[$request->id]);
    }

    public function guardarTarea(Request $request){

        $idEst = explode(",", $request->idestudiantes);
        $tam = sizeof($idEst);

        if(empty($request->idtarea)){
            if($request->idestudiantes == '' ){
                DB::INSERT("INSERT INTO tarea(fecha_inicio, fecha_final, descripcion, contenido_idcontenido, curso_idcurso) VALUES (?,?,?,?,?)",[$request->finicial,$request->ffinal,$request->descripcion,$request->idcontenido,$request->idcurso]);
            }else{
                for( $i=0; $i<$tam; $i++ ){
                    $idusuarioEst = $idEst[$i];
                    DB::INSERT("INSERT INTO tarea(fecha_inicio, fecha_final, descripcion, contenido_idcontenido, curso_idcurso, usuario_idusuario) VALUES (?,?,?,?,?,?)",[$request->finicial,$request->ffinal,$request->descripcion,$request->idcontenido,$request->idcurso,$idusuarioEst]);
                }
            }
        }else{
            $tarea = DB::UPDATE("UPDATE `tarea` SET `fecha_inicio`=?,`fecha_final`=?,`descripcion`=?,`contenido_idcontenido`=?,`curso_idcurso`=? WHERE `idtarea` = ?",[$request->finicial,$request->ffinal,$request->descripcion,$request->idcontenido,$request->idcurso,$request->idtarea]);

            return $tarea;
        }
        //return $idEst[1];
    }

    public function librosCurso(Request $request){
        $libro = DB::SELECT("SELECT libro_has_curso.id_libro_has_curso as id ,libro.* FROM libro_has_curso join libro on libro.idlibro = libro_has_curso.libro_idlibro   WHERE curso_idcurso = ? AND libro_has_curso.estado = '1' ORDER BY id desc ",[$request->idcurso]);
        return $libro;
    }

    public function librosCursoEliminar(Request $request){
        DB::UPDATE("UPDATE libro_has_curso SET estado='0' WHERE id_libro_has_curso = ?",[$request->id]);
    }

    public function getTareas(Request $request){
        $idusuario = auth()->user()->idusuario;
        if(empty($request->fecha)){
            $tareas = DB::SELECT("CALL getTareas (?);",[$request->idcurso]);
        }else{
            $tareas = DB::SELECT("CALL getTareasFecha (?,?);",[$request->idcurso,$request->fecha]);
        }
        if(!empty($tareas)){
            foreach ($tareas as $key => $post) {
                $respuesta = DB::SELECT("SELECT * FROM usuario_tarea WHERE tarea_idtarea = ? && usuario_idusuario = ? ",[$post->idtarea,$idusuario]);
                $total = DB::SELECT("SELECT count(*) as cantidad FROM usuario_tarea WHERE tarea_idtarea = ? && usuario_idusuario = ? ",[$post->idtarea,$idusuario]);
                $data['items'][$key] = [
                    'tarea' => $post,
                    'total'=>$total,
                    'respuesta'=>$respuesta,
                ];
            }
            return $data;
        }else{
            $data = [];
            return $data;
        }
    }

    public function postCalificacion(Request $request){
        DB::UPDATE("UPDATE usuario_tarea set nota=?, observacion=? WHERE id=?",[$request->nota,$request->observacion,$request->id]);
        $respuesta = DB::SELECT("SELECT * FROM usuario_tarea join usuario on usuario_idusuario = idusuario  WHERE tarea_idtarea = ? ",[$request->idtarea]);
        return $respuesta;
    }

    public function getTareasDocentes(Request $request){
        $tareas = DB::SELECT("CALL getTareas (?);",[$request->idcurso]);
        if(!empty($tareas)){
            foreach ($tareas as $key => $post) {
                $respuesta = DB::SELECT("SELECT * FROM usuario_tarea join usuario on usuario.idusuario = usuario_tarea.usuario_idusuario WHERE tarea_idtarea = ? ",[$post->idtarea]);
                $total = DB::SELECT("SELECT count(*) as cantidad FROM usuario_tarea WHERE tarea_idtarea = ? ",[$post->idtarea]);
                $data['items'][$key] = [
                    'tarea' => $post,
                    'total'=>$total,
                    'respuesta'=>$respuesta,
                ];
            }
            return $data;
        }else{
            $data = [];
            return $data;
        }
    }

    public function quitarTareaEntregada(Request $request){
        DB::DELETE("DELETE FROM usuario_tarea WHERE id=$request->id AND tarea_idtarea = $request->idtarea;");

        $respuesta = DB::SELECT("SELECT * FROM usuario_tarea join usuario on usuario_idusuario = idusuario  WHERE tarea_idtarea = ? ",[$request->idtarea]);

        return $respuesta;
    }

    public function eliminarTarea(Request $request){
        DB::UPDATE("UPDATE `tarea` SET estado='0' WHERE `idtarea` = ?",[$request->id]);
    }

    public function eliminarAlumno(Request $request){
        $respuesta = DB::UPDATE("UPDATE `estudiante` SET `estado`='0' WHERE  usuario_idusuario= ? AND codigo = ?",[$request->id,$request->codigo]);
        return $respuesta;
    }

    public function postLibroCurso(Request $request){
        DB::INSERT("INSERT INTO libro_has_curso(libro_idlibro, curso_idcurso) VALUES (?,?)",[$request->idlibro,$request->idcurso]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Curso  $curso
     * @return \Illuminate\Http\Response
     */
    public function edit(Curso $curso)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Curso  $curso
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        DB::update("UPDATE curso SET nombre=?,seccion=?,materia=?,aula=? WHERE idcurso=?",[$request->nombre,$request->seccion,$request->materia,$request->aula,$request->idcurso]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Curso  $curso
     * @return \Illuminate\Http\Response
     */
    public function eliminarCurso(Request $request)
    {
        $idcurso = $request->idcurso;
        DB::UPDATE("UPDATE curso SET estado = '0' WHERE idcurso = $idcurso");
    }


    public function verif_asignatura_por_curso($id)
    {
        $cursos = DB::SELECT("SELECT *, a.nombreasignatura as label FROM curso c LEFT JOIN asignatura a ON c.id_asignatura = a.idasignatura WHERE c.idusuario = $id AND c.id_asignatura = 0 AND c.estado = '1'");
        return $cursos;
    }


    public function cargar_asignatura_curso(Request $request)
    {
        $curso = Curso::find($request->id_curso);

        $curso->id_asignatura = $request->id_asignatura;

        $curso->save();

        return $curso;
    }

    // CONSULTAS EXTRAS PARA LA SECCION DEL ADMINISTRADOR - inicio 05 de marzo
    public function buscarCursoCodigo($codigo)
    {
        $curso = DB::SELECT("SELECT  c.*,
        u.cedula as cedulaDocente, u.nombres as nombreDocente, u.apellidos as apellidoDocente, u.name_usuario, u.email, u.date_created as creacionUsuario,
        i.nombreInstitucion, g.deskripsi as grupoUsuario, e.nombreestado as estadoInstitucion
        FROM curso c, usuario u, institucion i, sys_group_users g, estado e
        WHERE c.idusuario = u.idusuario
        and u.institucion_idInstitucion = i.idInstitucion
        and i.estado_idEstado =  e.idEstado
        and u.id_group = g.id
        and c.codigo like '$codigo%'");
        return $curso;
    }
    // -- a.idasignatura, a.nombreasignatura, a.tipo_asignatura, a.updated_atA,
    // --and c.id_asignatura = a.idasignatura
    public function cursos_x_usuario($usuario)
    {
        $curso = DB::SELECT("SELECT u.cedula, u.idusuario, u.nombres, u.apellidos, u.name_usuario, u.email, u.date_created, u.estado_idEstado, u.id_group, u.institucion_idInstitucion, c.*, i.idInstitucion, i.nombreInstitucion
        FROM  usuario u, curso c, institucion i
        Where u.idusuario = c.idusuario and u.institucion_idInstitucion = i.idInstitucion and u.name_usuario like '$usuario%'");
        return $curso;
    }
    public function restaurarCurso(Request $request){

        $curso = curso::find($request->id);
        $curso->idcurso = $request->id;
        $curso->estado = $request->estado;
        $curso->save();

        return $curso;
    }
    public function cursos_x_estudiante($email)
    {
        $curso = DB::SELECT("SELECT u.cedula,  u.nombres, u.apellidos, u.name_usuario, u.email, u.date_created, u.estado_idEstado, u.id_group, i.nombreInstitucion, e.id as idestudiante, e.usuario_idusuario, e.codigo, e.updated_at, c.*, g.deskripsi as grupoNombre
        FROM  estudiante e, usuario u, institucion i, curso c, sys_group_users g
        Where u.id_group = g.id and u.idusuario = e.usuario_idusuario and e.codigo = c.codigo and u.institucion_idInstitucion = i.idInstitucion and u.email LIKE '$email%'");
        return $curso;
    }
    ///cursos por docente por asignatura seleccionada, para la seccion de PROYECTOS INTEGRADOS
    public function cursos_asignatura_docente(Request $request)
    {
        $curso = DB::SELECT(" SELECT DISTINCT c.* , a.*
        FROM curso c, institucion_has_usuario iu, periodoescolar_has_institucion pi, periodoescolar p, asignatura a
        WHERE c.idusuario = $request->idusuario
        AND a.idasignatura = $request->idasignatura
        AND c.id_asignatura = a.idasignatura
        AND c.idusuario = iu.usuario_idusuario
        AND iu.institucion_idInstitucion = pi.institucion_idInstitucion
        AND pi.periodoescolar_idperiodoescolar = p.idperiodoescolar
        AND p.estado = 1 ");
        return $curso;
    }

    //api para traer los cursos de una institucion
    //api:get>>/cursosInstitucion
    public function cursosInstitucion(Request $request){
        $cursosInstitucion = DB::SELECT("SELECT DISTINCT c. *, a.nombreasignatura, a.area_idarea, a.idasignatura, a.nivel_idnivel, a.tipo_asignatura ,
        u.idusuario, u.institucion_idInstitucion
        FROM curso c, asignatura a, usuario u, periodoescolar_has_institucion phi, periodoescolar p
         WHERE c.idusuario = u.idusuario
          AND c.estado = '1'
          AND u.institucion_idInstitucion = phi.institucion_idInstitucion
           AND phi.periodoescolar_idperiodoescolar = p.idperiodoescolar
            AND p.estado = '1'
            AND c.id_asignatura = a.idasignatura
            AND u.institucion_idInstitucion = '$request->institucion_id'
        ");
    }

    //api para la matricula de los estudiantes
    //api:get>>/estudiante/matricula
    public function estudianteMatricula(Request $request){
        //para traer los niveles
        if($request->todoNiveles){
            $niveles = DB::select("SELECT * FROM nivel WHERE orden <> 0 AND orden IS NOT NULL
           order by orden + 0
            ");
            return $niveles;
        }

        //para traer el nivel actual
        if($request->NivelActual){

               //verificar si existe mas de 1 periodo
               $verificarPeriodos = DB::SELECT("SELECT p.* FROM periodoescolar_has_institucion p

               LEFT JOIN periodoescolar per ON p.periodoescolar_idperiodoescolar = per.idperiodoescolar
               WHERE p.institucion_idInstitucion = '$request->institucion_id'
               AND per.estado = '1'

               ");
                if(count($verificarPeriodos) > 1){
                    return ["status" => "0","message" => "Existe mas de un periodo lectivo comuniquese con soporte"];
                 }

            $nivel = DB::select("SELECT n.orden, n.nombrenivel,  ni.valor , ni.matricula,np.fecha_inicio_pension
            FROM nivel n
            LEFT JOIN mat_niveles_institucion ni ON n.orden = ni.nivel_id
            LEFT JOIN periodoescolar_has_institucion np ON ni.periodo_id = np.periodoescolar_idperiodoescolar
            WHERE n.orden = '$request->NivelActual'
            AND ni.institucion_id = '$request->institucion_id'
            AND ni.periodo_id = '$request->periodo_id'
            AND np.periodoescolar_idperiodoescolar = '$request->periodo_id'
            AND np.institucion_idInstitucion = '$request->institucion_id'
            ");
            if(count($nivel) == 0){
                return ["status" => "0","message" => "El nivel  no se encuentra con un  valor establecido / o a llegado al máximo nivel"];
            }
            return $nivel;
        }else{

            $estudiantesReserva = DB::SELECT("SELECT DISTINCT
            u.idusuario, u.nombres, u.apellidos, u.cedula, u.email,u.update_datos, i.nombreInstitucion, n.nombrenivel, n.orden,

            rc.nombres AS nombres_rc, rc.apellidos AS apellidos_rc, rc.cedula AS cedula_rc,
            rc.telefono_casa AS telefono_casa_rc, rc.telefono_celular AS telefono_celular_rc, rc.sexo as sexo_rc, rc.nacionalidad as nacionalidad_rc,
            rc.parentesco as parentesco_rc,   rc.direccion AS direccion_rc, rc.rep_economico_id , rc.empresa AS empresa_rc ,
            rc.profesion AS profesion_rc, rc.direccion_trabajo AS direccion_trabajo_rc ,rc.email AS  email_rc,

            rl.nombres AS nombres_rl, rl.apellidos AS apellidos_rl, rl.cedula AS cedula_rl,
            rl.telefono_casa AS telefono_casa_rl, rl.telefono_celular AS telefono_celular_rl, rl.sexo as sexo_rl, rl.nacionalidad as nacionalidad_rl,
            rl.parentesco as parentesco_rl,  rl.direccion AS direccion_rl, rl.rep_legal_id , rl.empresa AS empresa_rl ,
            rl.profesion AS profesion_rl, rl.direccion_trabajo AS direccion_trabajo_rl ,rl.email AS  email_rl,
            rl.email_institucional as email_institucional_rl,

            (SELECT periodoescolar_idperiodoescolar AS periodo FROM periodoescolar_has_institucion
            WHERE id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi, institucion i
             WHERE phi.institucion_idInstitucion = i.idInstitucion
            AND i.idInstitucion = '$request->institucion_id')) as periodo

            FROM usuario u
            LEFT JOIN institucion i ON  u.institucion_idInstitucion = i.idInstitucion
            LEFT JOIN mat_representante_economico rc ON  u.cedula = rc.c_estudiante
            LEFT JOIN mat_representante_legal rl ON  u.cedula = rl.c_estudiante
            -- LEFT JOIN estado_cuenta_colegio ec ON  u.cedula = ec.cedula
            LEFT JOIN nivel n ON u.curso = n.orden
            LEFT JOIN mat_niveles_institucion ni ON u.curso = ni.nivel_id

            WHERE u.cedula = '$request->cedula'

            ");
            return $estudiantesReserva;
        }

    }

    //api::get>>/valores/pensiones

    public function valoresPensiones(Request $request){



        $obtenerUltimaMatricula =DB::SELECT("SELECT MAX(id_matricula) AS matricula FROM mat_estudiantes_matriculados
        WHERE id_estudiante = '$request->idusuario'

        ");


        $matriculaActual = $obtenerUltimaMatricula[0]->matricula;


        $buscarMatricula = DB::SELECT("SELECT m.* FROM mat_estudiantes_matriculados m

        WHERE m.id_estudiante = '$request->idusuario'

        AND id_matricula = '$matriculaActual'
        ");


        if(count($buscarMatricula) == 0){
            return ["status" => "0","message" => "No se encontro la matricula del estudiante"];
        }

        //obtener el id de la matricula del estudiante
        $idmatricula = $buscarMatricula[0]->id_matricula;

        //traer los valores de las pensiones
        $traerPensiones = DB::SELECT("SELECT m.* FROM mat_cuotas_por_cobrar m
        WHERE m.id_matricula = '$idmatricula'
        AND m.num_cuota <> '0'
        ");

        return ["buscarEstudianteReserva" => $buscarMatricula,"traerPensiones" =>$traerPensiones];


    }

    //api:get>>/estudianteParalelo
    public function estudianteParalelo(Request $request){
        if($request->reserverEstudiante){
            $reserverEstudiante = DB::SELECT("SELECT em.* ,CONCAT(u.nombres, ' ' , u.apellidos) AS estudiante,u.cedula ,n.nombrenivel, ni.nivel_id
            from mat_estudiantes_matriculados  em, usuario u
            LEFT JOIN mat_niveles_institucion ni ON u.curso  = ni.nivel_id
            LEFT JOIN nivel n ON u.curso = n.orden
            -- LEFT JOIN mat_paralelos p ON em.paralelo = p.paralelo_id
            WHERE em.id_periodo = '$request->periodo_id'
            AND u.institucion_idInstitucion = '$request->institucion_id'
            AND u.idusuario = em.id_estudiante
            -- AND em.estado_matricula = '2'
            -- AND (em.paralelo  IS  NULL OR em.paralelo  = '')
            AND ni.institucion_id = '$request->institucion_id'
            AND ni.periodo_id = '$request->periodo_id'



            ");
            return $reserverEstudiante;
        }
        if($request->EstudianteLegal){
            $EstudianteLegal = DB::SELECT("SELECT em.* from mat_estudiantes_matriculados  em, usuario u
            WHERE em.id_periodo = '$request->periodo_id'
            AND u.institucion_idInstitucion = '$request->institucion_id'
            AND u.idusuario = em.id_estudiante
            AND (em.paralelo  IS NOT NULL OR em.paralelo !='')


            ");
            return $EstudianteLegal;
        }
        //paralelos para asignar al estudiante
        if($request->traerParaleloParaEstudiante){
            $paralelos= DB::SELECT("SELECT mp.* , p.descripcion
            FROM mat_cursos_paralelos  mp
            LEFT JOIN mat_paralelos p ON mp.paralelo_id = p.paralelo_id
            WHERE  mp.periodo_id = '$request->periodo_id'
            AND mp.institucion_id = '$request->institucion_id'
            AND nivel_id = '$request->nivel_id'
            ");
            return $paralelos;

        }
    }

    //api:get>>/nivelesInstitucion
    public function nivelesInstitucion(Request $request){
        $niveles = DB::SELECT("SELECT ni.nivelInstitucion_id, ni.matricula, ni.valor, n.nombrenivel, n.orden
        FROM mat_niveles_institucion ni
       LEFT JOIN nivel n ON ni.nivel_id = n.orden
       WHERE ni.institucion_id = '$request->institucion_id'
       AND ni.periodo_id = '$request->periodo_id'
       ORDER BY n.orden +0");

       return $niveles;
    }
    //api:post >>/editarNiveles
    public function editarNiveles(Request $request){

        if($request->clave_periodo){

            $periodo = PeriodoInstitucion::findOrFail($request->clave_periodo);
            $periodo->fecha_inicio_pension = $request->fecha_inicio_pension;

            $periodo->save();
            if($periodo){
                return ["status" => "1","message" => "Se actualizo correctamente"];
            }else{
                return ["status" => "0","message" => "No se pudo actualizar"];
            }

        }else{
            $nivel = NivelInstitucion::findOrFail($request->nivelInstitucion_id);
            $nivel->valor = $request->valor;
            $nivel->matricula = $request->matricula;
            $nivel->save();

            if($nivel){
                return ["status" => "1","message" => "Se actualizo correctamente"];
            }else{
                return ["status" => "0","message" => "No se pudo actualizar"];
            }
        }

    }

    public function validarPagos(Request $request){

        $buscarMatricula = DB::SELECT("SELECT m.* FROM mat_estudiantes_matriculados m

        WHERE m.id_estudiante = '$request->idusuario'
        AND nivel = '$request->nivel'
        ");



        if(count($buscarMatricula) < 0){
            return ["status" => "0","message"=>"El estudiante no tiene un nivel asociado"];

        }

        $matricula = $buscarMatricula[0]->id_matricula;


        $validarPagado = DB::SELECT("SELECT m.* FROM mat_cuotas_por_cobrar m
        WHERE m.id_matricula = '$matricula'


        ");
        $cuota1 = $validarPagado[0]->valor_pendiente;
        $cuota2 = $validarPagado[1]->valor_pendiente;
        $cuota3 = $validarPagado[2]->valor_pendiente;
        $cuota4 = $validarPagado[3]->valor_pendiente;
        $cuota5 = $validarPagado[4]->valor_pendiente;
        $cuota6 = $validarPagado[5]->valor_pendiente;
        $cuota7 = $validarPagado[6]->valor_pendiente;
        $cuota8 = $validarPagado[7]->valor_pendiente;
        $cuota9 = $validarPagado[8]->valor_pendiente;
        $cuota10 = $validarPagado[9]->valor_pendiente;
        $cuota11 = $validarPagado[10]->valor_pendiente;
        $cuota12 = $validarPagado[11]->valor_pendiente;

        if($cuota1 == '0' && $cuota2 == '0' && $cuota3 == '0' && $cuota4 == '0' && $cuota5 == '0' && $cuota6 == '0' && $cuota7 == '0' && $cuota8 == '0' && $cuota9 == '0' && $cuota10 == '0' && $cuota11 == '0' &&$cuota12 == '0'){

            //CAMBIAR ESTADO A RESERVADO
            // $reservado = EstudianteMatriculado::findOrFail($matricula);
            // $reservado->estado_matricula = "2";
            // $reservado->save();
            // return ["status" => "1","message" => "Su pagos se encuentran al dia"];


        }else{
            //para ver la cantidad  que debe el estudiante
            $totalDebe = DB::SELECT("SELECT  SUM(m.valor_pendiente) AS debe FROM mat_cuotas_por_cobrar m
            WHERE m.id_matricula ='$matricula'

            GROUP BY m.id_matricula
            ");

              //CAMBIAR ESTADO A PRE RESERVADO
            //   $reservado = EstudianteMatriculado::findOrFail($matricula);
            //   $reservado->estado_matricula = "4";
            //   $reservado->save();

            return ["status" => "0", "message" => "Usted No se puede  reservar la matrícula porque tiene pagos pendientes","totalDebe"=>$totalDebe];
        }
    }



    public function LegalizarMatricula(Request $request){

        //si existe mas de 1 periodo
         //verificar si existe mas de 1 periodo
         $verificarPeriodos = DB::SELECT("SELECT p.* FROM periodoescolar_has_institucion p

         LEFT JOIN periodoescolar per ON p.periodoescolar_idperiodoescolar = per.idperiodoescolar
         WHERE p.institucion_idInstitucion = '$request->institucion'
         AND per.estado = '1'

         ");

         //validar si existe mas de un periodo
          if(count($verificarPeriodos) > 1){
              return ["status" => "0","message" => "Existe mas de un periodo lectivo comuniquese con soporte"];
           }




        $obtenerUltimaMatricula =DB::SELECT("SELECT MAX(id_matricula) AS matricula FROM mat_estudiantes_matriculados
        WHERE id_estudiante = '$request->idusuario'
        AND estado_matricula = '1'
        ");


        $matriculaActual = $obtenerUltimaMatricula[0]->matricula;


        $buscarMatricula = DB::SELECT("SELECT m.* FROM mat_estudiantes_matriculados m

        WHERE m.id_estudiante = '$request->idusuario'

        AND id_matricula = '$matriculaActual'
        ");

        $periodoActualMatricula = $buscarMatricula[0]->id_periodo;
        $nivelActual = $buscarMatricula[0]->nivel;
        $nivelProximo = $nivelActual +1;


        $data = $this->traerData($request->idusuario,$request->institucion);


        if(count($data) == 0){
            return ["status" => "0","message"=>"No se encontro informacion del estudiante verifique que tenga el nivel bien"];
        }

        $fechaa = $data[0]->fecha_inicio_pension;


        //validar que exista una fecha  de inicio de pension
        if($fechaa == null || $fechaa == ""){
            return ["status" => "0","message" => "No existe una fecha de inicio para el periodo lectivo comuniquese con la institución"];
         }



        if(count($buscarMatricula) < 0){
            return ["status" => "0","message"=>"El estudiante no tiene un nivel asociado"];

        }

        $matricula = $buscarMatricula[0]->id_matricula;
        // $matriculaActual = $buscarMatriculaActual[0]->id_matricula;

        $validarPagado = DB::SELECT("SELECT m.* FROM mat_cuotas_por_cobrar m
        WHERE m.id_matricula = '$matricula'


        ");
        $cuota1 = $validarPagado[0]->valor_pendiente;
        $cuota2 = $validarPagado[1]->valor_pendiente;
        $cuota3 = $validarPagado[2]->valor_pendiente;
        $cuota4 = $validarPagado[3]->valor_pendiente;
        $cuota5 = $validarPagado[4]->valor_pendiente;
        $cuota6 = $validarPagado[5]->valor_pendiente;
        $cuota7 = $validarPagado[6]->valor_pendiente;
        $cuota8 = $validarPagado[7]->valor_pendiente;
        $cuota9 = $validarPagado[8]->valor_pendiente;
        $cuota10 = $validarPagado[9]->valor_pendiente;
        $cuota11 = $validarPagado[10]->valor_pendiente;
        $cuota12 = $validarPagado[11]->valor_pendiente;

        if($cuota1 == '0' && $cuota2 == '0' && $cuota3 == '0' && $cuota4 == '0' && $cuota5 == '0' && $cuota6 == '0' && $cuota7 == '0' && $cuota8 == '0' && $cuota9 == '0' && $cuota10 == '0' && $cuota11 == '0' &&$cuota12 == '0'){
            //matricular


            //Validar que ya no este matriculado
            $usuario = $data[0]->idusuario;
            $periodoMatricula =  $data[0]->periodo;
            $buscarSiEstudianteYaMatriculado = DB::SELECT("SELECT *
            FROM mat_estudiantes_matriculados
            WHERE id_estudiante = '$usuario'
            AND id_periodo = '$periodoMatricula'

             ");

             if(count($buscarSiEstudianteYaMatriculado) >0){
                 return ["status" => "0","message" => "El estudiante ya se encuentra matriculado en el período actual"];
             }




            //actualizar el nivel al usuario y cambiar el update de datos para que el estudiante pueda ver sus valores de pensiones
            $estudiante = Usuario::findOrFail($request->idusuario);
            $estudiante->curso  = $nivelProximo;
            $estudiante->update_datos = "2";
            $estudiante->save();


            //Parar matricular al estudiante
                $fecha  = date('Y-m-d');
                $matricula = new EstudianteMatriculado();
                $matricula->id_estudiante = $data[0]->idusuario;
                $matricula->id_periodo = $data[0]->periodo;
                $matricula->fecha_matricula = $fecha;
                $matricula->estado_matricula = "1";
                $matricula->nivel  = $nivelProximo;
                $matricula->save();

            // //Para registrar las cuotas


                // $cont =0;
                // $couta = intval($request->cuotas);

                $fecha_configuracion = $data[0]->fecha_inicio_pension;
                // if($couta == 2){
                   $fecha0= date("Y-m-d",strtotime($fecha_configuracion."- 1 month"));

                   $fecha2= date("Y-m-d",strtotime($fecha_configuracion."+ 1 month"));
                   $fecha3= date("Y-m-d",strtotime($fecha_configuracion."+ 2 month"));
                   $fecha4= date("Y-m-d",strtotime($fecha_configuracion."+ 3 month"));
                   $fecha5= date("Y-m-d",strtotime($fecha_configuracion."+ 4 month"));
                   $fecha6= date("Y-m-d",strtotime($fecha_configuracion."+ 5 month"));
                   $fecha7= date("Y-m-d",strtotime($fecha_configuracion."+ 6 month"));
                   $fecha8= date("Y-m-d",strtotime($fecha_configuracion."+ 7 month"));
                   $fecha9= date("Y-m-d",strtotime($fecha_configuracion."+ 8 month"));
                   $fecha10= date("Y-m-d",strtotime($fecha_configuracion."+ 9 month"));
                   $fecha11= date("Y-m-d",strtotime($fecha_configuracion."+ 10 month"));


                    // $dividirCuota = $request->valor * 10;
                    $dividirCuota = $data[0]->valor;
                    $decimalCuota = number_format($dividirCuota,2);


                     //COUTA 0 PARA VALORES PENDIENTES ANTERIORES
                     $cuotas0=new CuotasPorCobrar;
                     $cuotas0->id_matricula=$matricula->id_matricula;
                     $cuotas0->valor_cuota=0;
                     $cuotas0->valor_pendiente=0;
                     $cuotas0->fecha_a_pagar = $fecha0;
                     $cuotas0->num_cuota = 0;
                     $cuotas0->save();

                       //matricula
                       $cuotas=new CuotasPorCobrar;
                       $cuotas->id_matricula=$matricula->id_matricula;
                       $cuotas->valor_cuota=$data[0]->matricula;
                       $cuotas->valor_pendiente=$data[0]->matricula;
                       $cuotas->fecha_a_pagar = $data[0]->fecha_inicio_pension;
                       $cuotas->num_cuota = 1;
                       $cuotas->save();
                   //pensiones
                       $cuotas1=new CuotasPorCobrar;
                       $cuotas1->id_matricula=$matricula->id_matricula;
                       $cuotas1->valor_cuota=$decimalCuota;
                       $cuotas1->valor_pendiente=$decimalCuota;
                       $cuotas1->fecha_a_pagar = $fecha2;
                       $cuotas1->num_cuota = 2;
                       $cuotas1->save();

                       $cuotas2=new CuotasPorCobrar;
                       $cuotas2->id_matricula=$matricula->id_matricula;
                       $cuotas2->valor_cuota=$decimalCuota;
                       $cuotas2->valor_pendiente=$decimalCuota;
                       $cuotas2->fecha_a_pagar = $fecha3;
                       $cuotas2->num_cuota = 3;
                       $cuotas2->save();

                       $cuotas3=new CuotasPorCobrar;
                       $cuotas3->id_matricula=$matricula->id_matricula;
                       $cuotas3->valor_cuota=$decimalCuota;
                       $cuotas3->valor_pendiente=$decimalCuota;
                       $cuotas3->fecha_a_pagar = $fecha4;
                       $cuotas3->num_cuota = 4;
                       $cuotas3->save();

                       $cuotas4=new CuotasPorCobrar;
                       $cuotas4->id_matricula=$matricula->id_matricula;
                       $cuotas4->valor_cuota=$decimalCuota;
                       $cuotas4->valor_pendiente=$decimalCuota;
                       $cuotas4->fecha_a_pagar = $fecha5;
                       $cuotas4->num_cuota = 5;
                       $cuotas4->save();

                       $cuotas5=new CuotasPorCobrar;
                       $cuotas5->id_matricula=$matricula->id_matricula;
                       $cuotas5->valor_cuota=$decimalCuota;
                       $cuotas5->valor_pendiente=$decimalCuota;
                       $cuotas5->fecha_a_pagar = $fecha6;
                       $cuotas5->num_cuota = 6;
                       $cuotas5->save();

                       $cuotas6=new CuotasPorCobrar;
                       $cuotas6->id_matricula=$matricula->id_matricula;
                       $cuotas6->valor_cuota=$decimalCuota;
                       $cuotas6->valor_pendiente=$decimalCuota;
                       $cuotas6->fecha_a_pagar = $fecha7;
                       $cuotas6->num_cuota = 7;
                       $cuotas6->save();

                       $cuotas7=new CuotasPorCobrar;
                       $cuotas7->id_matricula=$matricula->id_matricula;
                       $cuotas7->valor_cuota=$decimalCuota;
                       $cuotas7->valor_pendiente=$decimalCuota;
                       $cuotas7->fecha_a_pagar = $fecha8;
                       $cuotas7->num_cuota = 8;
                       $cuotas7->save();

                       $cuotas8=new CuotasPorCobrar;
                       $cuotas8->id_matricula=$matricula->id_matricula;
                       $cuotas8->valor_cuota=$decimalCuota;
                       $cuotas8->valor_pendiente=$decimalCuota;
                       $cuotas8->fecha_a_pagar = $fecha9;
                       $cuotas8->num_cuota = 9;
                       $cuotas8->save();

                       $cuotas9=new CuotasPorCobrar;
                       $cuotas9->id_matricula=$matricula->id_matricula;
                       $cuotas9->valor_cuota=$decimalCuota;
                       $cuotas9->valor_pendiente=$decimalCuota;
                       $cuotas9->fecha_a_pagar = $fecha10;
                       $cuotas9->num_cuota = 10;
                       $cuotas9->save();

                       $cuotas10=new CuotasPorCobrar;
                       $cuotas10->id_matricula=$matricula->id_matricula;
                       $cuotas10->valor_cuota=$decimalCuota;
                       $cuotas10->valor_pendiente=$decimalCuota;
                       $cuotas10->fecha_a_pagar = $fecha11;
                       $cuotas10->num_cuota = 11;
                       $cuotas10->save();





            return ["status" => "1","message" => "Su reserva de matrícula ha sido legalizada"];

        }else{
            return ["status" => "0", "message" => "Usted No se puede matricular porque tiene pagos pendientes"];
        }



    }

    public function traerData($usuario,$institucion){

        $ultimoPeriodo = DB::SELECT("SELECT periodoescolar_idperiodoescolar AS periodo FROM periodoescolar_has_institucion
        WHERE id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi, institucion i
        WHERE phi.institucion_idInstitucion = i.idInstitucion
        AND i.idInstitucion = '$institucion')
        ");

        $obtenerPeriodoUltimo = $ultimoPeriodo[0]->periodo;


        $data = DB::SELECT("SELECT DISTINCT  ni.valor, ni.matricula,
                u.idusuario, u.nombres, u.apellidos, u.cedula, u.email,u.update_datos, u.curso ,u.institucion_idInstitucion,
                    i.nombreInstitucion, n.nombrenivel, n.orden, per.fecha_inicio_pension,

                    rc.nombres AS nombres_rc, rc.apellidos AS apellidos_rc, rc.cedula AS cedula_rc,
                    rc.telefono_casa AS telefono_casa_rc, rc.telefono_celular AS telefono_celular_rc,
                    rc.parentesco as parentesco_rc,   rc.direccion AS direccion_rc, rc.rep_economico_id , rc.empresa AS empresa_rc ,
                    rc.profesion AS profesion_rc, rc.direccion_trabajo AS direccion_trabajo_rc ,rc.email AS  email_rc,

                    rl.nombres AS nombres_rl, rl.apellidos AS apellidos_rl, rl.cedula AS cedula_rl,
                    rl.telefono_casa AS telefono_casa_rl, rl.telefono_celular AS telefono_celular_rl,
                    rl.parentesco as parentesco_rl,  rl.direccion AS direccion_rl, rl.rep_legal_id , rl.empresa AS empresa_rl ,
                    rl.profesion AS profesion_rl, rl.direccion_trabajo AS direccion_trabajo_rl ,rl.email AS  email_rl,
                    rl.email_institucional as email_institucional_rl,


                (SELECT periodoescolar_idperiodoescolar AS periodo FROM periodoescolar_has_institucion
                WHERE id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi, institucion i
                WHERE phi.institucion_idInstitucion = i.idInstitucion
                AND i.idInstitucion = '$institucion')) as periodo

                FROM usuario u
                LEFT JOIN institucion i ON  u.institucion_idInstitucion = i.idInstitucion
                LEFT JOIN mat_representante_economico rc ON  u.cedula = rc.c_estudiante
                LEFT JOIN mat_representante_legal rl ON  u.cedula = rl.c_estudiante
                -- LEFT JOIN estado_cuenta_colegio ec ON  u.cedula = ec.cedula
                LEFT JOIN nivel n ON u.curso = n.orden
                LEFT JOIN periodoescolar_has_institucion per ON u.institucion_idInstitucion = per.institucion_idInstitucion
                LEFT JOIN mat_niveles_institucion ni ON u.curso = ni.nivel_id


            WHERE  ni.institucion_id = '$institucion'
                AND u.institucion_idInstitucion = '$institucion'
                AND u.id_group = '4'
                AND ni.periodo_id = '$obtenerPeriodoUltimo'
                AND u.idusuario = '$usuario'
                AND per.periodoescolar_idperiodoescolar = '$obtenerPeriodoUltimo'
                ORDER BY u.apellidos ASC

        ");

        return $data;
    }
    //api::post>>/updateEstudiante
    public function updateEstudiante(Request $request){

         //verificar si existe mas de 1 periodo
            $verificarPeriodos = DB::SELECT("SELECT p.* FROM periodoescolar_has_institucion p

            LEFT JOIN periodoescolar per ON p.periodoescolar_idperiodoescolar = per.idperiodoescolar
            WHERE p.institucion_idInstitucion = '$request->institucion'
            AND per.estado = '1'

            ");
            if(count($verificarPeriodos) > 1){
            return ["status" => "0","message" => "Existe mas de un periodo lectivo comuniquese con soporte"];
            }


        $buscarSiEstudianteYaMatriculado = DB::SELECT("SELECT *
        FROM mat_estudiantes_matriculados
        WHERE id_estudiante = $request->idusuario
        AND id_periodo = $request->periodo

         ");

         if(count($buscarSiEstudianteYaMatriculado) <0){
             return ["status" => "0","message" => "No se encontro la matricula del estudiante"];
         }

            //para la solicitud de  pre Reserva
        if($request->reserva == "yes"){
                //ACTUALIZAR A PRE RESERVA
                DB::table('mat_estudiantes_matriculados')
                ->where('id_estudiante', $request->idusuario)
                ->where('id_periodo', $request->periodo)
                ->update(['estado_matricula' => '2']);
                return ["status" => "1","message" => "Se proceso correctamente"];
        }

        //Par actualizar los datos
        if($request->idusuario){

            DB::table('mat_estudiantes_matriculados')
            ->where('id_estudiante', $request->idusuario)
            ->where('id_periodo', $request->periodo)
            ->update(['estado_matricula' => '1']);


            $estudiante = Usuario::findOrFail($request->idusuario);

            $estudiante->update_datos = "1";
            // $estudiante->curso  = $request->ordenactual;
            $estudiante->save();

            if($estudiante){

                $reconomico = RepresentanteEconomico::findOrFail($request->rep_economico_id);
                $reconomico->nombres = $request->nombres_rc;
                $reconomico->apellidos = $request->apellidos_rc;
                $reconomico->telefono_casa = $request->telefono_casa_rc;
                $reconomico->telefono_celular = $request->telefono_celular_rc;
                $reconomico->direccion = $request->direccion_rc;
                $reconomico->empresa = $request->empresa_rc;
                $reconomico->profesion = $request->profesion_rc;
                $reconomico->direccion_trabajo = $request->direccion_trabajo_rc;
                $reconomico->email = $request->email_rc;
                $reconomico->save();

                $rlegal = RepresentanteLegal::findOrFail($request->rep_legal_id);
                $rlegal->nombres = $request->nombres_rl;
                $rlegal->apellidos = $request->apellidos_rl;
                $rlegal->telefono_casa = $request->telefono_casa_rl;
                $rlegal->telefono_celular = $request->telefono_celular_rl;
                $rlegal->direccion = $request->direccion_rl;
                $rlegal->empresa = $request->empresa_rl;
                $rlegal->profesion = $request->profesion_rl;
                $rlegal->direccion_trabajo = $request->direccion_trabajo_rl;
                $rlegal->email = $request->email_rl;
                $rlegal->email_institucional = $request->email_institucional_rl;
                $rlegal->save();




                return ["status" => "0" ,"message" => "Se actualizo correctamente"];
            }else{
                return ["status" => "0" ,"message" => "No se pudo actualizar"];
            }



        }
    }

    //api::post//>/updateEstudianteAdministrador

    public function updateEstudianteAdministrador(Request $request){


         //actualizar estudiante
         $usuario = Usuario::findOrFail($request->idusuario);
         $usuario->nombres  = $request->nombres;
         $usuario->apellidos = $request->apellidos;
         $usuario->email = $request->email;
         $usuario->save();

        if($request->rep_economico_id == null || $request->rep_economico_id == ""){


                $reconomico = new RepresentanteEconomico();
                $reconomico->c_estudiante = $request->cedula;
                $reconomico->parentesco = $request->parentesco_rc;
                $reconomico->nombres = $request->nombres_rc;
                $reconomico->apellidos = $request->apellidos_rc;
                $reconomico->telefono_casa = $request->telefono_casa_rc;
                $reconomico->telefono_celular = $request->telefono_celular_rc;
                $reconomico->direccion = $request->direccion_rc;
                $reconomico->empresa = $request->empresa_rc;
                $reconomico->cedula = $request->cedula_rc;
                $reconomico->profesion = $request->profesion_rc;
                $reconomico->direccion_trabajo = $request->direccion_trabajo_rc;
                $reconomico->email = $request->email_rc;
                $reconomico->sexo = $request->sexo_rc;

                $reconomico->save();
        }

        else{

            $reconomico = RepresentanteEconomico::findOrFail($request->rep_economico_id);
            $reconomico->parentesco = $request->parentesco_rc;
            $reconomico->nombres = $request->nombres_rc;
            $reconomico->apellidos = $request->apellidos_rc;
            $reconomico->telefono_casa = $request->telefono_casa_rc;
            $reconomico->telefono_celular = $request->telefono_celular_rc;
            $reconomico->direccion = $request->direccion_rc;
            $reconomico->empresa = $request->empresa_rc;
            $reconomico->cedula = $request->cedula_rc;
            $reconomico->profesion = $request->profesion_rc;
            $reconomico->direccion_trabajo = $request->direccion_trabajo_rc;
            $reconomico->email = $request->email_rc;
            $reconomico->sexo = $request->sexo_rc;
            $reconomico->nacionalidad = $request->nacionalidad_rc;
            $reconomico->save();
        }


        if($request->rep_legal_id == null || $request->rep_legal_id == ""){

                $rlegal = new RepresentanteLegal();
                $rlegal->c_estudiante = $request->cedula;
                $rlegal->parentesco = $request->parentesco_rl;
                $rlegal->nombres = $request->nombres_rl;
                $rlegal->apellidos = $request->apellidos_rl;
                $rlegal->telefono_casa = $request->telefono_casa_rl;
                $rlegal->telefono_celular = $request->telefono_celular_rl;
                $rlegal->direccion = $request->direccion_rl;
                $rlegal->empresa = $request->empresa_rl;
                $rlegal->cedula = $request->cedula_rl;
                $rlegal->profesion = $request->profesion_rl;
                $rlegal->direccion_trabajo = $request->direccion_trabajo_rl;
                $rlegal->email = $request->email_rl;
                $rlegal->email_institucional = $request->email_institucional_rl;
                $rlegal->sexo = $request->sexo_rl;
                $rlegal->nacionalidad = $request->nacionalidad_rl;
                $rlegal->save();

        } else{
                $rlegal = RepresentanteLegal::findOrFail($request->rep_legal_id);
                $rlegal->parentesco = $request->parentesco_rl;
                $rlegal->nombres = $request->nombres_rl;
                $rlegal->apellidos = $request->apellidos_rl;
                $rlegal->telefono_casa = $request->telefono_casa_rl;
                $rlegal->telefono_celular = $request->telefono_celular_rl;
                $rlegal->direccion = $request->direccion_rl;
                $rlegal->empresa = $request->empresa_rl;
                $rlegal->cedula = $request->cedula_rl;
                $rlegal->profesion = $request->profesion_rl;
                $rlegal->direccion_trabajo = $request->direccion_trabajo_rl;
                $rlegal->email = $request->email_rl;
                $rlegal->email_institucional = $request->email_institucional_rl;
                $rlegal->sexo = $request->sexo_rl;
                $rlegal->nacionalidad = $request->nacionalidad_rl;
                $rlegal->save();
        }


        return ["status" => "1" ,"message" => "Se actualizo correctamente"];
    }

//NivelesPeriodosInstitucion
    //para guardar los niveles
    //api:post>>/guardarInformacionNiveles
    public function guardarInformacionNiveles(Request $request){

        $BuscarSiHayValores = DB::SELECT("SELECT p.* FROM periodoescolar_has_institucion p
        WHERE p.institucion_idInstitucion = '$request->institucion_id'
        AND p.periodoescolar_idperiodoescolar = '$request->periodo_id'
        AND (p.fecha_inicio_pension IS NOT  NULL OR p.fecha_inicio_pension != '')
        ");


        if(count($BuscarSiHayValores) > 0){
            return ["status"=>"0","message" => "Ya existe valores de pensiones  para este año lectivo"];
        }

        if($request->guardar){

            $validarQueExistePeriodo = DB::SELECT("SELECT p.* FROM periodoescolar_has_institucion p
            WHERE p.institucion_idInstitucion = '$request->institucion_id'
            AND p.periodoescolar_idperiodoescolar = '$request->periodo_id'
            ");

            if(count($validarQueExistePeriodo) == 0 ){
                return ["status" => "0", "message" => "No existe periodo para la institución"];
            }

            //para guardar el id del periodo institucion
            $nivlesxPeriodo = $validarQueExistePeriodo[0]->id;


            DB::table('periodoescolar_has_institucion')
            ->where('periodoescolar_idperiodoescolar', $request->periodo_id)
            ->where('institucion_idInstitucion', $request->institucion_id)
            ->update([
                'fecha_inicio_pension' => $request->fecha_inicio_pension,

            ]);



            //==EDUCACION INICIAL=====
            if($request->inicial){
                    //inicial1
                    $nivel1  = new NivelInstitucion();
                    $nivel1->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                    $nivel1->nivel_id = $request->ordenInicial1;
                    $nivel1->valor = $request->precio_inicial1;
                    $nivel1->matricula = $request->matricula_inicial1;
                    $nivel1->institucion_id = $request->institucion_id;
                    $nivel1->periodo_id = $request->periodo_id;
                    $nivel1->save();
                    //inicial2
                    $nivel2  = new NivelInstitucion();
                    $nivel2->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                    $nivel2->nivel_id = $request->ordenInicial2;
                    $nivel2->valor = $request->precio_inicial2;
                    $nivel2->matricula = $request->matricula_inicial2;
                    $nivel2->institucion_id = $request->institucion_id;
                    $nivel2->periodo_id = $request->periodo_id;
                    $nivel2->save();

            }

              //==EDUCACION EDUCACION BASICA=====
              if($request->basica){
                //primero
                $nivel3  = new NivelInstitucion();
                $nivel3->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                $nivel3->nivel_id = $request->orden_primero;
                $nivel3->valor = $request->precio_primero;
                $nivel3->matricula = $request->matricula_primero;
                $nivel3->institucion_id = $request->institucion_id;
                $nivel3->periodo_id = $request->periodo_id;
                $nivel3->save();
                //segundo
                $nivel4  = new NivelInstitucion();
                $nivel4->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                $nivel4->nivel_id = $request->orden_segundo;
                $nivel4->valor = $request->precio_segundo;
                $nivel4->matricula = $request->matricula_segundo;
                $nivel4->institucion_id = $request->institucion_id;
                $nivel4->periodo_id = $request->periodo_id;
                $nivel4->save();
                //tercero
                $nivel5  = new NivelInstitucion();
                $nivel5->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                $nivel5->nivel_id = $request->orden_tercero;
                $nivel5->valor = $request->precio_tercero;
                $nivel5->matricula = $request->matricula_tercero;
                $nivel5->institucion_id = $request->institucion_id;
                $nivel5->periodo_id = $request->periodo_id;
                $nivel5->save();
                //cuarto
                $nivel6  = new NivelInstitucion();
                $nivel6->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                $nivel6->nivel_id = $request->orden_cuarto;
                $nivel6->valor = $request->precio_cuarto;
                $nivel6->matricula = $request->matricula_cuarto;
                $nivel6->institucion_id = $request->institucion_id;
                $nivel6->periodo_id = $request->periodo_id;
                $nivel6->save();
                //quinto
                $nivel7  = new NivelInstitucion();
                $nivel7->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                $nivel7->nivel_id = $request->orden_quinto;
                $nivel7->valor = $request->precio_quinto;
                $nivel7->matricula = $request->matricula_quinto;
                $nivel7->institucion_id = $request->institucion_id;
                $nivel7->periodo_id = $request->periodo_id;
                $nivel7->save();
                //sexto
                $nivel8  = new NivelInstitucion();
                $nivel8->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                $nivel8->nivel_id = $request->orden_sexto;
                $nivel8->valor = $request->precio_sexto;
                $nivel8->matricula = $request->matricula_sexto;
                $nivel8->institucion_id = $request->institucion_id;
                $nivel8->periodo_id = $request->periodo_id;
                $nivel8->save();
                //septimo
                $nivel9  = new NivelInstitucion();
                $nivel9->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                $nivel9->nivel_id = $request->orden_septimo;
                $nivel9->valor = $request->precio_septimo;
                $nivel9->matricula = $request->matricula_septimo;
                $nivel9->institucion_id = $request->institucion_id;
                $nivel9->periodo_id = $request->periodo_id;
                $nivel9->save();


             }

             //==EDUCACION EDUCACION SECUNDARIA=====
                if($request->secundaria){
                    //octavo
                    $nivel10  = new NivelInstitucion();
                    $nivel10->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                    $nivel10->nivel_id = $request->orden_octavo;
                    $nivel10->valor = $request->precio_octavo;
                    $nivel10->matricula = $request->matricula_octavo;
                    $nivel10->institucion_id = $request->institucion_id;
                    $nivel10->periodo_id = $request->periodo_id;
                    $nivel10->save();
                    //noveno
                    $nivel11  = new NivelInstitucion();
                    $nivel11->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                    $nivel11->nivel_id = $request->orden_noveno;
                    $nivel11->valor = $request->precio_noveno;
                    $nivel11->matricula = $request->matricula_noveno;
                    $nivel11->institucion_id = $request->institucion_id;
                    $nivel11->periodo_id = $request->periodo_id;
                    $nivel11->save();
                    //decimo
                    $nivel12  = new NivelInstitucion();
                    $nivel12->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                    $nivel12->nivel_id = $request->orden_decimo;
                    $nivel12->valor = $request->precio_decimo;
                    $nivel12->matricula = $request->matricula_decimo;
                    $nivel12->institucion_id = $request->institucion_id;
                    $nivel12->periodo_id = $request->periodo_id;
                    $nivel12->save();



                 }

            //==EDUCACION EDUCACION SUPERIOR=====
              if($request->superior){
                  //primero_bgu
                  $nivel13  = new NivelInstitucion();
                  $nivel13->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                  $nivel13->nivel_id = $request->orden_primero_bgu;
                  $nivel13->valor = $request->precio_primero_bgu;
                  $nivel13->matricula = $request->matricula_primero_bgu;
                  $nivel13->institucion_id = $request->institucion_id;
                  $nivel13->periodo_id = $request->periodo_id;
                  $nivel13->save();
                  //segundo_bgu
                  $nivel14  = new NivelInstitucion();
                  $nivel14->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                  $nivel14->nivel_id = $request->orden_segundo_bgu;
                  $nivel14->valor = $request->precio_segundo_bgu;
                  $nivel14->matricula = $request->matricula_segundo_bgu;
                  $nivel14->institucion_id = $request->institucion_id;
                  $nivel14->periodo_id = $request->periodo_id;
                  $nivel14->save();
                  //tercero_bgu
                  $nivel15  = new NivelInstitucion();
                  $nivel15->mat_niveles_periodos_institucion_id = $nivlesxPeriodo;
                  $nivel15->nivel_id = $request->orden_tercero_bgu;
                  $nivel15->valor = $request->precio_tercero_bgu;
                  $nivel15->matricula = $request->matricula_tercero_bgu;
                  $nivel15->institucion_id = $request->institucion_id;
                  $nivel15->periodo_id = $request->periodo_id;
                  $nivel15->save();


             }

             return ["status" => "1", "message" => "Se genero correctamente los valores para cada pensión"];


        }else{
            $traerNiveles = DB::select("SELECT * FROM nivel
            WHERE orden >= $request->inicio
            AND orden <= $request->hasta
            ORDER BY orden + 0
            ");
            return $traerNiveles;
        }

    }

    //api para traer el periodo por institucion
    public function institucionTraerPeriodo(Request $request){

        //para traer la informacion de la institucion
        if($request->dataInstitucion){
            $institucion = DB::SELECT("SELECT * FROM institucion WHERE idInstitucion = $request->institucion_id");
            return $institucion;
        }
        //para traer los periodos de una institucion
        if($request->periodosAll){
            $periodos = DB::SELECT("SELECT pin.*, p.periodoescolar
            FROM periodoescolar_has_institucion pin
            LEFT JOIN periodoescolar p ON pin.periodoescolar_idperiodoescolar = p.idperiodoescolar
            WHERE pin.institucion_idInstitucion = '$request->institucion_id'
            ORDER BY id DESC");

            return $periodos;
        }

        //para traer el periodo de una institucion
        $periodoInstitucion = DB::SELECT("SELECT idperiodoescolar AS periodo ,estado,
        (SELECT nombreInstitucion FROM institucion where idInstitucion = '$request->institucion_id' ) as nombreInstitucion,
         periodoescolar AS descripcion ,region_idregion as region,
        (SELECT imgenInstitucion FROM institucion where idInstitucion = '$request->institucion_id' ) as imgenInstitucion
        FROM periodoescolar WHERE idperiodoescolar = (
            SELECT  pir.periodoescolar_idperiodoescolar as id_periodo
            from institucion i,  periodoescolar_has_institucion pir
            WHERE i.idInstitucion = pir.institucion_idInstitucion
            AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
            WHERE phi.institucion_idInstitucion = i.idInstitucion
            AND i.idInstitucion = '$request->institucion_id'))
        ");

        return $periodoInstitucion;
    }



    //api:get//nivelPeriodoInstitucion
    public function nivelPeriodoInstitucion(Request $request){

        //para traer los paralelos
        if($request->paralelos){

           $paralelos =  DB::SELECT("SELECT * FROM mat_paralelos");
            return $paralelos;
        }

        //para traer los paralelos asignados
        if($request->paraleloAsignado){

           $p_asignados =  DB::select("SELECT m.*, p.descripcion FROM mat_cursos_paralelos  m
            LEFT JOIN mat_paralelos p ON m.paralelo_id = p.paralelo_id
            WHERE m.nivelInstitucion_id = '$request->nivelInstitucion_id'
            ");
            return $p_asignados;
        }

        //para traer los valores de las pensiones
        if($request->valoresPensiones){
            $valorPension = DB::SELECT("SELECT ma.* ,  p.periodoescolar AS periodo ,n.nombrenivel FROM mat_niveles_institucion ma
            LEFT JOIN periodoescolar p ON ma.periodo_id = p.idperiodoescolar
            LEFT JOIN nivel n ON  ma.nivel_id = n.orden
            WHERE institucion_id = '$request->institucion_id'
            AND periodo_id = '$request->periodo_id'
            ORDER BY ma.nivel_id + 0
            ");

            return $valorPension;

        }else{
            //para traer los por periodo de la institucion
            $nivelesPeriodos = DB::SELECT("SELECT m.*, p.periodoescolar AS periodo , i.nombreInstitucion
            FROM mat_niveles_periodos_institucion m
            LEFT JOIN periodoescolar p ON m.periodo_id = p.idperiodoescolar
            LEFT JOIN institucion i ON m.institucion_id = i.idInstitucion
            WHERE m.institucion_id = '$request->institucion_id'

            ");

            return $nivelesPeriodos;
        }


    }

    public function guardarParalelos(Request $request){

        //para asignar a cada estudiante el paralelo
        if($request->editarParaleloIndividual){
            $paraleloIndividual = EstudianteMatriculado::findOrFail($request->id_matricula);
            $paraleloIndividual->paralelo = $request->paralelo;
            $paraleloIndividual->save();
            if($paraleloIndividual){
                return ["status" => "1","message" => "Asignado correctamente"];
            }else{
                return ["status" => "0","message" => "No se pudo asignar correctamente"];
            }
        }

        //para cambiar el estado de la matricula
        if($request->cambiarEstado){
            $paraleloIndividual = EstudianteMatriculado::findOrFail($request->id_matricula);
            $paraleloIndividual->estado_matricula = $request->estado;
            $paraleloIndividual->save();
            if($paraleloIndividual){
                return ["status" => "1","message" => "Estado matrícula cambiado correctamente"];
            }else{
                return ["status" => "0","message" => "No se pudo cambiar de estado"];
            }
        }

        else{

            $cursos= DB::SELECT("SELECT * FROM mat_cursos_paralelos cm
            WHERE cm.nivelInstitucion_id = '$request->nivelInstitucion_id'
            AND cm.paralelo_id = $request->paralelo
           ");

           if(empty($cursos) ){
               $paralelos= DB::INSERT("INSERT INTO `mat_cursos_paralelos`(`nivelInstitucion_id`, `nivel_id`,`paralelo_id`, `institucion_id`, `periodo_id`) VALUES ('$request->nivelInstitucion_id', '$request->nivel_id','$request->paralelo', '$request->institucion_id', '$request->periodo_id')");

               return ["status" => "1","message" => "Asignado correctamente"];

           }else{
               return ["status" => "0","message" => "Este paralelo  ya se encuentra asignado a este curso"];

           }

        }


    }

    public function eliminarParalelo($id){
        $data = CursosParalelos::find($id);
        $data->delete();
        return $data;
    }

    public function cambiarEstadoMatricula(Request $request){
        $usuario = Usuario::findOrFail($request->idusuario);
        $usuario->id_group = $request->grupo;
        $usuario->save();
        if($usuario){
            return ["status" => "1","message","Se cambio de estado correctamente"];
        }else{
            return ["status" => "0","message","No se pudo cambiar de estado"];
        }
    }

    public function makeid(){
        $characters = '123456789abcdefghjkmnpqrstuvwxyz';
        $charactersLength = strlen($characters);

        $randomString = '';
        for ($i = 0; $i < 5; $i++) {
            for ($i = 0; $i < 16; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;


         }
    }

    public function series_libros_doc($id){
        $series = DB::SELECT("SELECT s.nombre_serie, ls.id_libro_serie, ls.id_serie, ls.idLibro, ls.nombre, ls.version, ls.iniciales FROM asignaturausuario au INNER JOIN libro l ON au.asignatura_idasignatura = l.asignatura_idasignatura INNER JOIN libros_series ls ON ls.idLibro = l.idlibro INNER JOIN series s ON s.id_serie = ls.id_serie WHERE au.usuario_idusuario = $id GROUP BY s.nombre_serie");

        return $series;
    }

    public function ver_areas_serie($id_serie, $id_usuario){
        $areas = DB::SELECT("SELECT ar.nombrearea AS nombre, ar.idarea AS iniciales, l.portada FROM libros_series ls INNER JOIN libro l ON ls.idLibro = l.idlibro INNER JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura INNER JOIN area ar ON a.area_idarea = ar.idarea INNER JOIN asignaturausuario au ON a.idasignatura = au.asignatura_idasignatura WHERE ls.id_serie = $id_serie AND au.usuario_idusuario = $id_usuario GROUP BY ar.idarea");
        return $areas;
    }

    public function get_libros_area($usuario, $area, $serie,$region,$periodo){
        //region-> 1 => sierra; 2 => costa;
        $series = DB::SELECT("SELECT l.*,a.*
        FROM libro l
        INNER JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
        INNER JOIN asignaturausuario au ON a.idasignatura = au.asignatura_idasignatura
        INNER JOIN libros_series ls ON l.idlibro = ls.idLibro
        WHERE a.area_idarea = $area
        AND au.usuario_idusuario = $usuario
        AND ls.id_serie = $serie
        AND au.periodo_id = '$periodo'
        AND l.Estado_idEstado = '1'
        ORDER BY a.nivel_idnivel;
        ");
        $datos=[];
        foreach($series as $key => $item){
            //variables por defecto
            $weblibro       = $item->weblibro;
            $portada        = $item->portada;
            $pdfsinguia     = $item->pdfsinguia;
            $pdfconguia     = $item->pdfconguia;
            $guiadidactica  = $item->guiadidactica;
            //sierra
            if($region == 1){
                //si no hay libro de costa asignado tomamos el por defecto
                if($item->s_weblibro != null || $item->s_weblibro != ""){
                    $weblibro       = $item->s_weblibro;
                    $portada        = $item->s_portada;
                    $pdfsinguia     = $item->s_pdfsinguia;
                    $pdfconguia     = $item->s_pdfconguia;
                    $guiadidactica  = $item->s_guiadidactica;
                }
            }
            //costa
            if($region == 2){
                //si no hay libro de costa asignado tomamos el por defecto
                if($item->c_weblibro != null || $item->c_weblibro != ""){
                    $weblibro       = $item->c_weblibro;
                    $portada        = $item->c_portada;
                    $pdfsinguia     = $item->c_pdfsinguia;
                    $pdfconguia     = $item->c_pdfconguia;
                    $guiadidactica  = $item->c_guiadidactica;
                }
            }
            $datos[$key] =[
                "idlibro"                   => $item->idlibro,
                "nombrelibro"               => $item->nombrelibro,
                "descripcionlibro"          => $item->descripcionlibro,
                "serie"                     => $item->serie,
                "titulo"                    => $item->titulo,
                "portada"                   => $portada,
                "weblibro"                  => $weblibro,
                "pdfsinguia"                => $pdfsinguia,
                "pdfconguia"                => $pdfconguia,
                "guiadidactica"             => $guiadidactica,
                "Estado_idEstado"           => $item->Estado_idEstado,
                "asignatura_idasignatura"   => $item->asignatura_idasignatura,
                "libroFechaModificacion"    => $item->libroFechaModificacion,
                "grupo"                     => $item->grupo,
                "puerto"                    => $item->puerto,
                "creado_at"                 => $item->creado_at,
                "actualizado_at"            => $item->actualizado_at,
                "idasignatura"              => $item->idasignatura,
                "nombreasignatura"          => $item->nombreasignatura,
                "area_idarea"               => $item->area_idarea,
                "nivel_idnivel"             => $item->nivel_idnivel,
                "tipo_asignatura"           => $item->tipo_asignatura,
                "estado"                    => $item->estado,
            ];
        }
        return $datos;
    }

    public function get_libros_serie($usuario, $serie,$region,$periodo){
        $series = DB::SELECT("SELECT l.*,a.*
        FROM libro l
        INNER JOIN libros_series ls ON l.idLibro = ls.idLibro
        INNER JOIN asignatura a ON a.idasignatura = l.asignatura_idasignatura
        INNER JOIN asignaturausuario au ON a.idasignatura = au.asignatura_idasignatura
        WHERE au.usuario_idusuario = $usuario
        AND ls.id_serie = $serie
        AND au.periodo_id = '$periodo'
        AND l.Estado_idEstado = '1'
        ORDER BY a.nivel_idnivel
        ");
        $datos=[];
        foreach($series as $key => $item){
            //variables por defecto
            $weblibro       = $item->weblibro;
            $portada        = $item->portada;
            $pdfsinguia     = $item->pdfsinguia;
            $pdfconguia     = $item->pdfconguia;
            $guiadidactica  = $item->guiadidactica;
            //sierra
            if($region == 1){
                //si no hay libro de costa asignado tomamos el por defecto
                if($item->s_weblibro != null || $item->s_weblibro != ""){
                    $weblibro       = $item->s_weblibro;
                    $portada        = $item->s_portada;
                    $pdfsinguia     = $item->s_pdfsinguia;
                    $pdfconguia     = $item->s_pdfconguia;
                    $guiadidactica  = $item->s_guiadidactica;
                }
            }
            //costa
            if($region == 2){
                //si no hay libro de costa asignado tomamos el por defecto
                if($item->c_weblibro != null || $item->c_weblibro != ""){
                    $weblibro       = $item->c_weblibro;
                    $portada        = $item->c_portada;
                    $pdfsinguia     = $item->c_pdfsinguia;
                    $pdfconguia     = $item->c_pdfconguia;
                    $guiadidactica  = $item->c_guiadidactica;
                }
            }
            $datos[$key] =[
                "idlibro"                   => $item->idlibro,
                "nombrelibro"               => $item->nombrelibro,
                "descripcionlibro"          => $item->descripcionlibro,
                "serie"                     => $item->serie,
                "titulo"                    => $item->titulo,
                "portada"                   => $portada,
                "weblibro"                  => $weblibro,
                "pdfsinguia"                => $pdfsinguia,
                "pdfconguia"                => $pdfconguia,
                "guiadidactica"             => $guiadidactica,
                "Estado_idEstado"           => $item->Estado_idEstado,
                "asignatura_idasignatura"   => $item->asignatura_idasignatura,
                "libroFechaModificacion"    => $item->libroFechaModificacion,
                "grupo"                     => $item->grupo,
                "puerto"                    => $item->puerto,
                "creado_at"                 => $item->creado_at,
                "actualizado_at"            => $item->actualizado_at,
                "idasignatura"              => $item->idasignatura,
                "nombreasignatura"          => $item->nombreasignatura,
                "area_idarea"               => $item->area_idarea,
                "nivel_idnivel"             => $item->nivel_idnivel,
                "tipo_asignatura"           => $item->tipo_asignatura,
                "estado"                    => $item->estado,
            ];
        }
        return $datos;
        // return $series;
    }

    public function get_libros_series($serie){
        $series = DB::SELECT("SELECT l.* FROM libro l INNER JOIN libros_series ls ON l.idLibro = ls.idLibro WHERE ls.id_serie = $serie ORDER BY l.nombrelibro");

        return $series;
    }
    public function reporteMesTareas(Request $request){
        $reporteMes = DB::SELECT("SELECT
        sum(case when month(fecha) = 1 then 1 else 0 end) Ene
       , sum(case when month(fecha) = 2 then 1 else 0 end) Feb
       , sum(case when month(fecha) = 3 then 1 else 0 end) Mar
       , sum(case when month(fecha) = 4 then 1 else 0 end) Abr
       , sum(case when month(fecha) = 5 then 1 else 0 end) May
       , sum(case when month(fecha) = 6 then 1 else 0 end) Jun
       , sum(case when month(fecha) = 7 then 1 else 0 end) Jul
       , sum(case when month(fecha) = 8 then 1 else 0 end) Ago
       , sum(case when month(fecha) = 9 then 1 else 0 end) Sep
       , sum(case when month(fecha) = 10 then 1 else 0 end) Oct
       , sum(case when month(fecha) = 11 then 1 else 0 end) Nov
       , sum(case when month(fecha) = 12 then 1 else 0 end) Dic
            from usuario_tarea t
            where YEAR(t.fecha) = '$request->anio'
        ");
        $data = [];
        $data[0] = [
            "Ene" =>$reporteMes[0]->Ene == NULL ? '0':$reporteMes[0]->Ene,
            "Feb" =>$reporteMes[0]->Feb == NULL ? '0':$reporteMes[0]->Feb,
            "Mar" =>$reporteMes[0]->Mar == NULL ? '0':$reporteMes[0]->Mar,
            "Abr" =>$reporteMes[0]->Abr == NULL ? '0':$reporteMes[0]->Abr,
            "May" =>$reporteMes[0]->May == NULL ? '0':$reporteMes[0]->May,
            "Jun" =>$reporteMes[0]->Jun == NULL ? '0':$reporteMes[0]->Jun,
            "Jul" =>$reporteMes[0]->Jul == NULL ? '0':$reporteMes[0]->Jul,
            "Ago" =>$reporteMes[0]->Ago == NULL ? '0':$reporteMes[0]->Ago,
            "Sep" =>$reporteMes[0]->Sep == NULL ? '0':$reporteMes[0]->Sep,
            "Oct" =>$reporteMes[0]->Oct == NULL ? '0':$reporteMes[0]->Oct,
            "Nov" =>$reporteMes[0]->Nov == NULL ? '0':$reporteMes[0]->Nov,
            "Dic" =>$reporteMes[0]->Feb == NULL ? '0':$reporteMes[0]->Dic
        ];
        return $data;
    }
    public function reporteMesTareasAnios(Request $request){
        $formato = [
            "Enero",
            "Febrero",
            "Marzo",
            "Abrir",
            "Mayo",
            "Junio",
            "Julio",
            "Agosto",
            "Septiembre",
            "Octobre",
            "Noviembre",
            "Diciembre"
        ];
        $reporteMesActual = DB::SELECT("SELECT
        sum(case when month(fecha) = 1 then 1 else 0 end) Ene
        , sum(case when month(fecha) = 2 then 1 else 0 end) Feb
        , sum(case when month(fecha) = 3 then 1 else 0 end) Mar
        , sum(case when month(fecha) = 4 then 1 else 0 end) Abr
        , sum(case when month(fecha) = 5 then 1 else 0 end) May
        , sum(case when month(fecha) = 6 then 1 else 0 end) Jun
        , sum(case when month(fecha) = 7 then 1 else 0 end) Jul
        , sum(case when month(fecha) = 8 then 1 else 0 end) Ago
        , sum(case when month(fecha) = 9 then 1 else 0 end) Sep
        , sum(case when month(fecha) = 10 then 1 else 0 end) Oct
        , sum(case when month(fecha) = 11 then 1 else 0 end) Nov
        , sum(case when month(fecha) = 12 then 1 else 0 end) Dic
            from usuario_tarea t
            where YEAR(t.fecha) = '$request->anioActual'
        ");
        $reporteMenosUno = DB::SELECT("SELECT
        sum(case when month(fecha) = 1 then 1 else 0 end) Ene
        , sum(case when month(fecha) = 2 then 1 else 0 end) Feb
        , sum(case when month(fecha) = 3 then 1 else 0 end) Mar
        , sum(case when month(fecha) = 4 then 1 else 0 end) Abr
        , sum(case when month(fecha) = 5 then 1 else 0 end) May
        , sum(case when month(fecha) = 6 then 1 else 0 end) Jun
        , sum(case when month(fecha) = 7 then 1 else 0 end) Jul
        , sum(case when month(fecha) = 8 then 1 else 0 end) Ago
        , sum(case when month(fecha) = 9 then 1 else 0 end) Sep
        , sum(case when month(fecha) = 10 then 1 else 0 end) Oct
        , sum(case when month(fecha) = 11 then 1 else 0 end) Nov
        , sum(case when month(fecha) = 12 then 1 else 0 end) Dic
            from usuario_tarea t
            where YEAR(t.fecha) = '$request->anioMenosUno'
        ");
        $reporteMenosDos = DB::SELECT("SELECT
           sum(case when month(fecha) = 1 then 1 else 0 end) Ene
          , sum(case when month(fecha) = 2 then 1 else 0 end) Feb
          , sum(case when month(fecha) = 3 then 1 else 0 end) Mar
          , sum(case when month(fecha) = 4 then 1 else 0 end) Abr
          , sum(case when month(fecha) = 5 then 1 else 0 end) May
          , sum(case when month(fecha) = 6 then 1 else 0 end) Jun
          , sum(case when month(fecha) = 7 then 1 else 0 end) Jul
          , sum(case when month(fecha) = 8 then 1 else 0 end) Ago
          , sum(case when month(fecha) = 9 then 1 else 0 end) Sep
          , sum(case when month(fecha) = 10 then 1 else 0 end) Oct
          , sum(case when month(fecha) = 11 then 1 else 0 end) Nov
          , sum(case when month(fecha) = 12 then 1 else 0 end) Dic
               from usuario_tarea t
               where YEAR(t.fecha) = '$request->anioMenosDos'
        ");
        //FORMATO ACTUAL
        $data = [];
        $data[0] = [
            "Ene" =>$reporteMesActual[0]->Ene == NULL ? '0':$reporteMesActual[0]->Ene,
            "Feb" =>$reporteMesActual[0]->Feb == NULL ? '0':$reporteMesActual[0]->Feb,
            "Mar" =>$reporteMesActual[0]->Mar == NULL ? '0':$reporteMesActual[0]->Mar,
            "Abr" =>$reporteMesActual[0]->Abr == NULL ? '0':$reporteMesActual[0]->Abr,
            "May" =>$reporteMesActual[0]->May == NULL ? '0':$reporteMesActual[0]->May,
            "Jun" =>$reporteMesActual[0]->Jun == NULL ? '0':$reporteMesActual[0]->Jun,
            "Jul" =>$reporteMesActual[0]->Jul == NULL ? '0':$reporteMesActual[0]->Jul,
            "Ago" =>$reporteMesActual[0]->Ago == NULL ? '0':$reporteMesActual[0]->Ago,
            "Sep" =>$reporteMesActual[0]->Sep == NULL ? '0':$reporteMesActual[0]->Sep,
            "Oct" =>$reporteMesActual[0]->Oct == NULL ? '0':$reporteMesActual[0]->Oct,
            "Nov" =>$reporteMesActual[0]->Nov == NULL ? '0':$reporteMesActual[0]->Nov,
            "Dic" =>$reporteMesActual[0]->Feb == NULL ? '0':$reporteMesActual[0]->Dic
        ];
        //FORMATO MENOS UNO
        $data1 = [];
        $data1[0] = [
            "Ene" =>$reporteMenosUno[0]->Ene == NULL ? '0':$reporteMenosUno[0]->Ene,
            "Feb" =>$reporteMenosUno[0]->Feb == NULL ? '0':$reporteMenosUno[0]->Feb,
            "Mar" =>$reporteMenosUno[0]->Mar == NULL ? '0':$reporteMenosUno[0]->Mar,
            "Abr" =>$reporteMenosUno[0]->Abr == NULL ? '0':$reporteMenosUno[0]->Abr,
            "May" =>$reporteMenosUno[0]->May == NULL ? '0':$reporteMenosUno[0]->May,
            "Jun" =>$reporteMenosUno[0]->Jun == NULL ? '0':$reporteMenosUno[0]->Jun,
            "Jul" =>$reporteMenosUno[0]->Jul == NULL ? '0':$reporteMenosUno[0]->Jul,
            "Ago" =>$reporteMenosUno[0]->Ago == NULL ? '0':$reporteMenosUno[0]->Ago,
            "Sep" =>$reporteMenosUno[0]->Sep == NULL ? '0':$reporteMenosUno[0]->Sep,
            "Oct" =>$reporteMenosUno[0]->Oct == NULL ? '0':$reporteMenosUno[0]->Oct,
            "Nov" =>$reporteMenosUno[0]->Nov == NULL ? '0':$reporteMenosUno[0]->Nov,
            "Dic" =>$reporteMenosUno[0]->Feb == NULL ? '0':$reporteMenosUno[0]->Dic
        ];
        //FORMATO MENOS DOS
        $data2 = [];
        $data2[0] = [
            "Ene" =>$reporteMenosDos[0]->Ene == NULL ? '0':$reporteMenosDos[0]->Ene,
            "Feb" =>$reporteMenosDos[0]->Feb == NULL ? '0':$reporteMenosDos[0]->Feb,
            "Mar" =>$reporteMenosDos[0]->Mar == NULL ? '0':$reporteMenosDos[0]->Mar,
            "Abr" =>$reporteMenosDos[0]->Abr == NULL ? '0':$reporteMenosDos[0]->Abr,
            "May" =>$reporteMenosDos[0]->May == NULL ? '0':$reporteMenosDos[0]->May,
            "Jun" =>$reporteMenosDos[0]->Jun == NULL ? '0':$reporteMenosDos[0]->Jun,
            "Jul" =>$reporteMenosDos[0]->Jul == NULL ? '0':$reporteMenosDos[0]->Jul,
            "Ago" =>$reporteMenosDos[0]->Ago == NULL ? '0':$reporteMenosDos[0]->Ago,
            "Sep" =>$reporteMenosDos[0]->Sep == NULL ? '0':$reporteMenosDos[0]->Sep,
            "Oct" =>$reporteMenosDos[0]->Oct == NULL ? '0':$reporteMenosDos[0]->Oct,
            "Nov" =>$reporteMenosDos[0]->Nov == NULL ? '0':$reporteMenosDos[0]->Nov,
            "Dic" =>$reporteMenosDos[0]->Feb == NULL ? '0':$reporteMenosDos[0]->Dic
        ];
        $datos = array_merge($data2,$data1,$data);
        return ["datos" => $datos, "formato" => $formato];
    }

    public function quitarEstudianteDeCurso(Request $request){
        $quitar = DB::table('estudiante')
        ->where('codigo',$request->codigo)
        ->where('usuario_idusuario',$request->idusuario)
        ->delete();
        return $quitar;

        // ->update(['estado'=>'0']);
    }

}
