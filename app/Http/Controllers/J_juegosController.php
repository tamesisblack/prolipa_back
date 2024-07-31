<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\J_juegos;

class J_juegosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $juegos = DB::SELECT("SELECT * FROM j_juegos");

        return $juegos;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function juego_y_contenido($id)
    {
        $juego= DB::SELECT("SELECT * FROM j_juegos jj WHERE jj.id_juego = $id");
        if(!empty($juego)){
            foreach ($juego as $key => $value) {
                $preguntas = DB::SELECT("SELECT * FROM j_contenido_juegos WHERE id_juego = ?",[$value->id_juego]);

                $temas = DB::SELECT("SELECT t.nombre_tema, t.unidad, a.nombreasignatura FROM j_temas_por_juego jt, temas t, asignatura a WHERE jt.id_tema = t.id AND t.id_asignatura = a.idasignatura AND jt.id_juego = ? ORDER BY t.unidad",[$value->id_juego]);
                $data['items'][$key] = [
                    'id_juego' => $value->id_juego,
                    'id_tipo_juego' => $value->id_tipo_juego,
                    'id_docente' => $value->id_docente,
                    'puntos' => $value->puntos,
                    'duracion' => $value->duracion,
                    'nombre_juego' => $value->nombre_juego,
                    'descripcion_juego' => $value->descripcion_juego,
                    'imagen_juego' => $value->imagen_juego,
                    'fecha_inicio' => $value->fecha_inicio,
                    'fecha_fin' => $value->fecha_fin,
                    'bloque_curricular'=>$value->bloque_curricular,
                    'grado'=>$value->grado,
                    'destrezas'=>$value->destrezas,
                    'habilidades'=>$value->habilidades,
                    'elaborado_por'=>$value->elaborado_por,
                    'intencion_didactica'=>$value->intencion_didactica,
                    'consigna'=>$value->consigna,
                    'consideraciones'=>$value->consideraciones,
                    'preguntas'=>$preguntas,
                    'temas'=>$temas,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
        // return $juego;
    }
     /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function calificacion_estudiante(Request $request)
    {
        $calificacion= DB::SELECT("SELECT * FROM j_calificaciones jc WHERE jc.id_juego = $request->id_juego AND jc.id_usuario = $request->idusuario");

        return $calificacion;
    }


    public function asignar_cursos_juego(Request $request)
    {
        $cursos= DB::SELECT("SELECT * FROM `cursos_has_juego` WHERE `codigo_curso` = '$request->codigo_curso' AND `id_juego` = $request->id_juego");

        if( empty($cursos) ){
            $juegos= DB::INSERT("INSERT INTO `cursos_has_juego`(`codigo_curso`, `id_juego`) VALUES ('$request->codigo_curso', $request->id_juego)");

            return ["status" => "1", "message" => "Asignado correctamente"];
        }else{
            return ["status" => "0", "message" => "Este juego ya se encuentra asignado a este curso"];
        }

    }


    // public function calificacionPonchado(Request $request)
    // {
    //     $califica = DB::INSERT("INSERT INTO  j_calificaciones(id_juego, id_usuario, calificacion) VALUES (?,?,?)", [$request->id_juego, $request->id_usuario, $request->calificacion,]);

    // }

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
        if( $request->id_juego ){
            $juego = J_juegos::find($request->id_juego);
        }else{
            $juego = new J_juegos();
        }

        $juego->id_tipo_juego = $request->id_tipo_juego;
        $juego->id_docente = $request->id_docente;
        $juego->nombre_juego = $request->nombre_juego;
        $juego->descripcion_juego = $request->descripcion_juego;
        $juego->puntos = $request->puntos;
        $juego->duracion = $request->duracion;
        $juego->fecha_inicio = $request->fecha_inicio;
        $juego->fecha_fin = $request->fecha_fin;
        $juego->estado = $request->estado;

        $juego->save();

        return $juego;
    }


    public function guardarTemasJuego(Request $request)
    {
        $temas = explode(",", $request->id_temas);
        $tam = sizeof($temas);

        for( $i=0; $i<$tam; $i++ ){
            $tema= DB::INSERT("INSERT INTO j_temas_por_juego(id_juego, id_tema) VALUES (?,?)", [$request->id_juego, $temas[$i]]);
        }

    }


    public function eliminarTemasJuego($id)
    {
        $tema= DB::DELETE("DELETE FROM j_temas_por_juego WHERE id_juego=$id");

    }


    public function j_guardar_calificacion(Request $request)
    {
        $califica = DB::INSERT("INSERT INTO  j_calificaciones(id_juego, codigo_curso, id_usuario, calificacion) VALUES (?,?,?,?)", [$request->id_juego, $request->codigo_curso, $request->id_usuario, $request->calificacion]);

        return $califica;
        // $juego = new J_juegos();

        // $juego->id_juego = $request->id_juego;
        // $juego->id_usuario = $request->id_usuario;
        // $juego->calificacion = $request->calificacion;

        // $juego->save();

        // return $juego;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
         $juego= DB::SELECT("SELECT * FROM j_juegos WHERE id_juego = $id");

        return $juego;
    }



    // public function filtrar(Request $request){
    //     $filtrar = DB::SELECT("SELECT * FROM usuario WHERE NOMBRES LIKE '%$request->busqueda%'");
    //     return $filtrar;
    // }
    public function juegos_prolipa_admin_tipo($tipo)
    {
        $juego= DB::SELECT("SELECT DISTINCT j .*,j.estado as estado_juego, a.nombreasignatura, a.idasignatura,
         u.id_group ,
         IF(j.estado = '1','Activo','Inactivo') as statusJuego
         FROM j_juegos j, j_temas_por_juego jt, temas t, asignatura a, usuario u
         WHERE j.id_tipo_juego = $tipo
         AND j.estado = 1
         AND j.id_juego = jt.id_juego
         AND jt.id_tema = t.id AND t.id_asignatura = a.idasignatura
         AND j.id_docente = u.idusuario
         ORDER BY u.id_group
         ");

        if(!empty($juego)){
            foreach ($juego as $key => $value) {
                $temas = DB::SELECT("SELECT * FROM j_temas_por_juego tj, temas t, asignatura a WHERE tj.id_tema = t.id AND t.id_asignatura = a.idasignatura AND tj.id_juego = ?",[$value->id_juego]);
                $data['items'][$key] = [
                    'nombreasignatura' => $value->nombreasignatura,
                    'id_juego' => $value->id_juego,
                    "statusJuego" => $value->statusJuego,
                    'estado_juego' => $value->estado_juego,
                    'id_tipo_juego' => $value->id_tipo_juego,
                    'id_docente' => $value->id_docente,
                    'puntos' => $value->puntos,
                    'duracion' => $value->duracion,
                    'nombre_juego' => $value->nombre_juego,
                    'descripcion_juego' => $value->descripcion_juego,
                    'imagen_juego' => $value->imagen_juego,
                    'fecha_inicio' => $value->fecha_inicio,
                    'fecha_fin' => $value->fecha_fin,
                    'id_group' => $value->id_group,
                    'temas'=>$temas,
                ];
            }
        }else{
            $data = [];
        }
        return $data;

    }

    public function j_juegos_tipo(Request $request)
    {
        $juego= DB::SELECT("SELECT DISTINCT j . *
        FROM j_juegos j, j_temas_por_juego jt, temas t
        WHERE j.id_tipo_juego = $request->id_tipo_juego
        AND j.id_docente = $request->id_docente
        AND j.id_juego = jt.id_juego
        AND jt.id_tema = t.id
        AND t.id_asignatura = $request->id_asignatura");

        if(!empty($juego)){
            foreach ($juego as $key => $value) {
                $temas = DB::SELECT("SELECT *
                FROM j_temas_por_juego tj, temas t, asignatura a
                WHERE tj.id_tema = t.id
                AND t.id_asignatura = a.idasignatura
                AND tj.id_juego = ?",[$value->id_juego]);
                $data['items'][$key] = [
                    'id_juego' => $value->id_juego,
                    'id_tipo_juego' => $value->id_tipo_juego,
                    'id_docente' => $value->id_docente,
                    'puntos' => $value->puntos,
                    'duracion' => $value->duracion,
                    'nombre_juego' => $value->nombre_juego,
                    'descripcion_juego' => $value->descripcion_juego,
                    'imagen_juego' => $value->imagen_juego,
                    'fecha_inicio' => $value->fecha_inicio,
                    'fecha_fin' => $value->fecha_fin,
                    'estado_juego' => $value->estado,
                    'temas'=>$temas,
                ];
            }
        }else{
            $data = [];
        }
        return $data;

    }


    public function j_juegos_tipo_prolipa(Request $request)
    {
        $juego= DB::SELECT("SELECT DISTINCT j . *
        FROM j_juegos j, j_temas_por_juego jt, temas t, usuario u
        WHERE j.id_tipo_juego = $request->id_tipo_juego
        AND j.estado = 1
        AND j.id_juego = jt.id_juego
        AND jt.id_tema = t.id
        AND t.id_asignatura = $request->id_asignatura
        AND j.id_docente = u.idusuario AND u.id_group != 6
        ");
        if(!empty($juego)){
            foreach ($juego as $key => $value) {
                $temas = DB::SELECT("SELECT *
                FROM j_temas_por_juego tj, temas t, asignatura a
                WHERE tj.id_tema = t.id
                AND t.id_asignatura = a.idasignatura
                AND tj.id_juego = ?",[$value->id_juego]);
                $data['items'][$key] = [
                    'id_juego' => $value->id_juego,
                    'id_tipo_juego' => $value->id_tipo_juego,
                    'id_docente' => $value->id_docente,
                    'puntos' => $value->puntos,
                    'duracion' => $value->duracion,
                    'nombre_juego' => $value->nombre_juego,
                    'descripcion_juego' => $value->descripcion_juego,
                    'imagen_juego' => $value->imagen_juego,
                    'fecha_inicio' => $value->fecha_inicio,
                    'fecha_fin' => $value->fecha_fin,
                    'estado_juego' => $value->estado,
                    'temas'=>$temas,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }
    public function j_juegos_tipo_prolipaTodos(Request $request)
    {
        $juego= DB::SELECT("SELECT DISTINCT j . *,tp.nombre_tipo_juego
        FROM j_juegos j, j_temas_por_juego jt, temas t, usuario u,j_tipos_juegos tp
        WHERE j.estado      = 1
        AND j.id_juego      = jt.id_juego
        AND jt.id_tema      = t.id
        AND j.id_tipo_juego = tp.id_tipo_juego
        AND t.id_asignatura = '$request->id_asignatura'
        AND j.id_docente    = u.idusuario 
        AND u.id_group      != 6
        AND t.unidad        = '$request->unidad'
        ");
        if(!empty($juego)){
            foreach ($juego as $key => $value) {
                $temas = DB::SELECT("SELECT *
                FROM j_temas_por_juego tj, temas t, asignatura a
                WHERE tj.id_tema = t.id
                AND t.id_asignatura = a.idasignatura
                AND tj.id_juego = ?",[$value->id_juego]);
                $data['items'][$key] = [
                    'id_juego' => $value->id_juego,
                    'id_tipo_juego' => $value->id_tipo_juego,
                    'id_docente' => $value->id_docente,
                    'puntos' => $value->puntos,
                    'duracion' => $value->duracion,
                    'nombre_juego' => $value->nombre_juego,
                    'descripcion_juego' => $value->descripcion_juego,
                    'imagen_juego' => $value->imagen_juego,
                    'fecha_inicio' => $value->fecha_inicio,
                    'fecha_fin' => $value->fecha_fin,
                    'estado_juego' => $value->estado,
                    'nombre_tipo_juego'=> $value->nombre_tipo_juego,
                    'temas'=>$temas,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }

    public function j_juegos_tipo_curso_doc(Request $request)
    {

        $estudiante= DB::SELECT("SELECT DISTINCT e.usuario_idusuario, u.cedula, u.nombres, u.apellidos FROM estudiante e, usuario u WHERE e.codigo = '$request->codigo' AND e.usuario_idusuario = u.idusuario ORDER BY u.apellidos");

        if(!empty($estudiante)){
            foreach ($estudiante as $key => $value) {
                $calificaciones = DB::SELECT("SELECT jc.calificacion FROM j_calificaciones jc WHERE jc.id_usuario = ? AND jc.id_juego = ?",[$value->usuario_idusuario, $request->id_juego]);
                $data['items'][$key] = [
                    'nombres' => $value->nombres,
                    'apellidos' => $value->apellidos,
                    'cedula' => $value->cedula,
                    'usuario_idusuario' => $value->usuario_idusuario,
                    'calificaciones'=>$calificaciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }



    public function j_juegos_ficha(Request $request)
    {
        $juego = J_juegos::find($request->id_juego);

        $juego->bloque_curricular = $request->bloque_curricular;
        $juego->grado = $request->grado;
        $juego->destrezas = $request->destrezas;
        $juego->habilidades = $request->habilidades;
        $juego->elaborado_por = $request->elaborado_por;
        $juego->intencion_didactica = $request->intencion_didactica;
        $juego->consigna = $request->consigna;
        $juego->consideraciones = $request->consideraciones;

        $juego->save();

        return $juego;
    }


    public function calificaciones_estudiante_juego(Request $request)
    {
        $calificaciones = DB::SELECT("SELECT * FROM j_calificaciones jc WHERE jc.id_usuario = $request->id_usuario AND jc.id_juego = $request->id_juego");

        return $calificaciones;
    }


    public function juegos_has_curso($codigo_curso)
    {
        $juegos = DB::SELECT("SELECT j .*, jt.nombre_tipo_juego, jt.descripcion_tipo_juego, jt.imagen_juego FROM j_juegos j, cursos_has_juego cj, j_tipos_juegos jt WHERE j.id_juego = cj.id_juego AND j.id_tipo_juego = jt.id_tipo_juego AND cj.codigo_curso = '$codigo_curso' AND j.estado = 1 ORDER BY j.id_juego DESC");
        return $juegos;
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



    public function j_juegos_eliminar($id)
    {
        $juego = J_juegos::find($id);
        $juego->estado = 0;
        $juego->save();
        return $juego;
    }


    public function destroy($id)
    {
        $juego = J_juegos::find($id);
        $juego->delete();
    }


    public function juego_preguntas_opciones($id_juego)
    {
        $preguntas= DB::SELECT("SELECT c.id_contenido_juego, c.imagen, c.pregunta, c.respuesta, c.descripcion, c.puntaje FROM j_juegos j, j_contenido_juegos c WHERE j.id_juego = $id_juego AND j.id_juego = c.id_juego");

        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT * FROM j_opciones_contenidos WHERE id_contenido_juegos = ?",[$value->id_contenido_juego]);
                $data['items'][$key] = [
                    'pregunta' => $value,
                    'opciones'=>$opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }




    public function save_juegos_administrables(Request $request)
    {
        $ruta = public_path('departamentos/');
        if( $request->id != '' ){

            if($request->file('img_portada') && $request->file('img_portada') != null && $request->file('img_portada')!= 'null'){
                $file = $request->file('img_portada');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
                if( file_exists('departamentos/'.$request->img_old) && $request->img_old != '' ){
                    unlink('departamentos/'.$request->img_old);
                }
            }else{
                $fileName = $request->img_old;
            }

            DB::UPDATE("UPDATE `juegos_administrables` SET `img_portada`='?', `titulo`='?', `subtitulo`='?', `descripcion`='?', `tipo_juego`='?' WHERE `id` = ?", [$fileName, $request->titulo, $request->subtitulo, $request->descripcion, $request->tipo_juego, $request->id]);

            return "modificado";
        }else{

            if($request->file('img_portada')){
                $file = $request->file('img_portada');
                $ruta = public_path('departamentos');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta, $fileName);
            }else{
                $fileName = '';
            }

            DB::INSERT("INSERT INTO `juegos_administrables`(`img_portada`, `titulo`, `subtitulo`, `descripcion`, `tipo_juego`, `id_usuario`) VALUES (?,?,?,?,?,?)", [$fileName, $request->titulo, $request->subtitulo, $request->descripcion, $request->tipo_juego, $request->id_usuario]);
            return "creado";
        }
    }



}
