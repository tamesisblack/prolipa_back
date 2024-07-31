<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use DB;
use App\Quotation;
class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->getVideos){
            $getVideos = DB::SELECT("SELECT v.* ,
            a.nombreasignatura,t.nombre_tema, t.unidad,CONCAT('unidad',t.unidad,' ',t.nombre_tema) as tema,
            u.nombre_unidad
            FROM video  v
            LEFT JOIN asignatura a ON v.asignatura_idasignatura = a.idasignatura
            LEFT JOIN temas t ON v.id_tema = t.id
            LEFT JOIN unidades_libros u ON t.id_unidad = u.id_unidad_libro
            ORDER BY v.idvideo DESC
            ");
            return $getVideos;
        }else{
            if (!$request->ajax()) return redirect('/') ;
            $buscar = $request->buscar;
            $criterio = $request->criterio;
            $idusuario = auth()->user()->idusuario;
            $idInstitucion = auth()->user()->institucion_idInstitucion;
            if($idInstitucion == 66){
                $videos = DB::select("SELECT * FROM video INNER JOIN asignatura ON video.asignatura_idasignatura = asignatura.idasignatura");
            }else{
                $videos = DB::select('CALL datosvideosd(?)',[$idusuario]);
            }
            return $videos;
        }

    }
    //api:get/getAsignaturas
    public function getAsignaturas(Request $request){
        $asignaturas = DB::SELECT("SELECT DISTINCT a.idasignatura, a.nombreasignatura
        FROM asignatura  a
        join area on area.idarea = a.area_idarea
         LEFT JOIN libro l ON l.asignatura_idasignatura = a.idasignatura
        LEFT JOIN libros_series ls ON l.idlibro = ls.idLibro
        WHERE a.estado = '1'
        AND (ls.version <> 'PLUS' OR ls.version IS NULL)
        AND a.tipo_asignatura = '1'
        ");
        return $asignaturas;
    }
    //api:Get/temasxAsignatura/{unidad}
    public function temasxUnidades($id){
        $themes = DB::SELECT("SELECT t.*, CONCAT('unidad',t.unidad,' ',t.nombre_tema) as tema FROM temas t
        WHERE t.id_unidad = '$id'
        ORDER BY t.unidad ASC
        ");
        return $themes;
    }

    // public function aplicativo(Request $request)
    // {
    //     $videos = Video::join('asignaturausuario','video.asignatura_idasignatura','=','asignaturausuario.asignatura_idasignatura')
    //     ->join('asignatura','asignatura.idasignatura','=','asignaturausuario.asignatura_idasignatura')
    //     ->where('asignaturausuario.usuario_idusuario', '=',$request->idusuario)->paginate(3);
    //     return [
    //         'pagination' => [
    //             'current_page' => $videos->currentPage(),
    //             'per_page'     => $videos->perPage(),
    //             'last_page'    => $videos->lastPage(),
    //             'from'         => $videos->firstItem(),
    //             'to'           => $videos->lastItem(),
    //         ],
    //         'videos' => $videos
    //     ];
    // }

    public function aplicativo(Request $request)
    {
        $videos = DB::select('CALL datosvideosd(?)',[$request->idusuario]);
        return $videos;
    }


    public function video(Request $request)
    {
        $video = DB::select('SELECT * FROM video');
        return $video;
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
        // Video::create($request->all());
        if($request->id > 0){
            //EDITAR
            $link = Video::findOrFail($request->id);
        }else{
            //GUARDAR
            $link = new Video();
        }
            $link->nombrevideo              = $request->nombrevideo;
            $link->descripcionvideo         = $request->descripcionvideo;
            $link->webvideo                 = $request->webvideo;
            $link->asignatura_idasignatura  = $request->asignatura_idasignatura;
            $link->id_tema                  = $request->id_tema;
            $link->user_created             = $request->user_created;
            $link->Estado_idEstado          = $request->Estado_idEstado;
            $link->unidad_id                = $request->unidad_id;
            $link->save();
            if($link){
                return ["status" => "1", "message" => "Se guardo correctamente"];
            }else{
                return ["status" => "1", "message" => "No se pudo guardar"];
            }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function show(Video $video)
    {
        //
    }



    public function videos_libro_unidad($id)
    {
        $videos = DB::SELECT("SELECT v . * FROM video v, temas t WHERE v.id_tema = t.id AND t.id_unidad = $id");

        return $videos;
    }

    public function videos_libro_tema($id)
    {
        $videos = DB::SELECT("SELECT * FROM video WHERE id_tema = $id");

        return $videos;
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function edit(Video $video)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Video $video)
    {
        $respuesta=DB::update('UPDATE video SET nombrevideo = ? ,descripcionvideo = ? ,webvideo = ? ,Estado_idEstado = ? ,asignatura_idasignatura = ?   WHERE idvideo = ?',[$request->nombrevideo,$request->descripcionvideo,$request->webvideo,$request->Estado_idEstado,$request->asignatura_idasignatura,$request->idvideo]);
        return $respuesta;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::delete("DELETE FROM video WHERE idvideo ='$id'");
        return "Se elimino correctamente";
    }
}
