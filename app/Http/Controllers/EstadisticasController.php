<?php

namespace App\Http\Controllers;

use App\Models\Estadisticas;
use Illuminate\Http\Request;
use DB;
class EstadisticasController extends Controller
{
    public function contenidos(Request $request){
        $cursos = 0;
        $libros = 0;
        $cuadernos = 0;
        $guias = 0;
        $planlector = 0;
        $planificaciones = 0;
        $material = 0;
        $juegos = 0;
        $videos = 0;
        $teletareas = 0;
        $idinstitucion = $request->idinstitucion;
        $consulta=DB::select("CALL `docentes`(?);",[$idinstitucion]);
        foreach ($consulta as $key => $value) {
            $cursos = $cursos + $this->cursos($value->idusuario);            
            $libros = $libros + $this->libros($value->idusuario);            
            $cuadernos = $cuadernos + $this->cuadernos($value->idusuario);            
            $guias = $guias + $this->guias($value->idusuario);            
            $planlector = $planlector + $this->planlector($value->idusuario);            
            $planificaciones = $planificaciones + $this->planificaciones($value->idusuario);            
            $material = $material + $this->material($value->idusuario);            
            $juegos = $juegos + $this->juegos($value->idusuario);            
            $videos = $videos + $this->videos($value->idusuario);            
            $teletareas = $teletareas + $this->teletareas($value->idusuario);            
        }
        $data = [
            'cursos' => $cursos,
            'libros' => $libros,
            'cuadernos' => $cuadernos,
            'guias' => $guias,
            'planLector' => $planlector,
            'planificaciones' => $planificaciones,
            'material' => $material,
            'juegos' => $juegos,
            'videos' => $videos,
            'teletareas' => $teletareas,
        ];
        return $data;
    }

    public function libros($idusuario){
        $libro = DB::select('CALL datoslibrosd(?)',[$idusuario]);
        return count($libro);
    }

    public function cuadernos($idusuario)
    {
        $cuaderno = DB::select('CALL datoscuadernosd(?)',[$idusuario]);
        return  count($cuaderno);
    }

    public function guias($idusuario)
    {
        $guias = DB::select('CALL datosguiasd(?)',[$idusuario]);
        return  count($guias);
    }

    public function planlector($idusuario)
    {
        $planlector = DB::select('CALL datosplanlectorsd(?)',[$idusuario]);
        return  count($planlector);
    }

    public function planificaciones($idusuario)
    {
        $planificaciones = DB::select('CALL datosplanificaciond(?)',[$idusuario]);
        return  count($planificaciones);
    }

    public function material($idusuario)
    {
        $material = DB::select('CALL datosmateriald(?)',[$idusuario]);
        return  count($material);
    }

    public function juegos($idusuario)
    {
        $juegos = DB::SELECT("
        SELECT juegos . * ,asignatura.* FROM juegos
        JOIN asignaturausuario ON juegos.asignatura_idasignatura = asignaturausuario.asignatura_idasignatura
        JOIN asignatura ON asignatura.idasignatura = asignaturausuario.asignatura_idasignatura
        WHERE asignaturausuario.usuario_idusuario = ? ORDER BY asignatura.idasignatura
        ",[$idusuario]);
        return  count($juegos);
    }

    public function videos($idusuario){
        $videos = DB::select('CALL datosvideosd(?)',[$idusuario]);
        return  count($videos);
    }

    public function cursos($idusuario)
    {
        $cursos = DB::select("SELECT * FROM curso WHERE idusuario = $idusuario AND estado = '1'");
        return  count($cursos);
    }

    public function teletareas($idusuario)
    {
        $teletareas = DB::SELECT("SELECT contenido.*
            FROM asignaturausuario
            JOIN contenido ON asignaturausuario.asignatura_idasignatura = contenido.idasignatura
            WHERE asignaturausuario.usuario_idusuario = ?
            ",[$idusuario] );
        return  count($teletareas);
    }

}
