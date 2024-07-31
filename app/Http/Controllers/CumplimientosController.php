<?php

namespace App\Http\Controllers;

use App\Models\Cumplimientos;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class CumplimientosController extends Controller
{

    public function index()
    {
        
    }

    public function show($id)
    {
        $cumplimiento = DB::SELECT("SELECT * FROM `ctrl_cumplimientos` WHERE `id_ctrl` = '$id'");
        return $cumplimiento;
    }

    public function get_redactores()
    {
        $redactores = DB::SELECT("SELECT `idusuario`, `cedula`, CONCAT(`nombres`, ' ', `apellidos`) AS nombre_redactor FROM `usuario` WHERE `id_group` = 1 AND `estado_idEstado` = 1;");
        return $redactores;
    }

    public function get_actividades_cumplimiento()
    {
        $actividades = DB::SELECT("SELECT * FROM `ctrl_actividades` WHERE `estado` = 1");
        return $actividades;
    }

    public function get_areas_cumplimiento($id_actividad)
    {
        $areas = DB::SELECT("SELECT ar.idarea, ar.nombrearea FROM area ar INNER JOIN ctrl_parametrizaciones c ON ar.idarea = c.id_area WHERE ar.tipoareas_idtipoarea = 3 AND c.id_actividad = $id_actividad AND ar.estado = 1;");
        return $areas;
    }
    public function get_series_cumplimiento()
    {
        $series = DB::SELECT("SELECT * FROM `series`");
        return $series;
    }
    public function get_libros_cumplimiento($id_serie)
    {
        $libros = DB::SELECT("SELECT l.idLibro, l.nombrelibro FROM libros_series s 
        INNER JOIN libro l ON s.idLibro = l.idlibro
        WHERE s.id_serie = $id_serie AND s.estado = 1 AND l.Estado_idEstado = 1");
        return $libros;
    }
    public function get_periodos_cumplimiento()
    {
        $periodos = DB::SELECT("SELECT * FROM `periodoescolar` WHERE `estado` = 1");
        return $periodos;
    }

    public function create()
    {
        //
    }


    public function store(Request $request)
    {   
        $query = "SELECT * FROM `ctrl_avances` WHERE `id_usuario` = ".$request->id_usuario." AND `id_area` = ".$request->id_area." AND `id_actividad` = ".$request->id_actividad." AND `unidad`= ".$request->unidad." AND `id_periodo` = ".$request->id_periodo." AND `id_libro` =  ".$request->id_libro." AND `fecha` = '".$request->fecha."'";

        // return $query;
        $avance = DB::SELECT($query);

        if( $avance ){
            $avance = Cumplimientos::find($avance[0]->id_avance);
        }else{
            $avance = new Cumplimientos();
        }

        $avance->id_actividad = $request->id_actividad;
        $avance->id_serie     = $request->id_serie;
        $avance->id_libro     = $request->id_libro;
        $avance->unidad     = $request->unidad;
        $avance->id_periodo   = $request->id_periodo;
        $avance->id_area      = $request->id_area;
        $avance->fecha        = $request->fecha;
        $avance->cant_avance  = $request->cantidad;
        $avance->observacion  = $request->observacion;
        $avance->id_usuario   = $request->id_usuario;

        $avance->save();
        return $avance;
    }


    public function update(Request $request)
    {
        //
    }


    public function get_cumplimiento_redactor_normal($fecha_ini, $fecha_fin, $id_redactor, $id_serie, $id_actividad, $id_libro, $unidad) 
    {
        $actividades = DB::SELECT("SELECT * FROM `ctrl_actividades` WHERE `categoria` != 'edicion' AND `estado` = 1");
        $data = array();
        foreach ($actividades as $key => $value) {
            
            $query_areas = "SELECT ar.idarea, ar.nombrearea FROM area ar INNER JOIN ctrl_parametrizaciones c ON ar.idarea = c.id_area WHERE ar.tipoareas_idtipoarea = 3 AND c.id_actividad = ".$value->id_actividades." AND ar.estado = 1;";
            $areas = DB::SELECT($query_areas);

            $data_areas = array();
            foreach ($areas as $key_ar => $value_ar) {
                $query_libro = "";
                if( $id_libro != 0 && $id_libro != 'null' ){
                    $query_libro = " AND a.id_libro = ".$id_libro. " ";
                }
                $query_unidad = "";
                if( $unidad != 0 && $unidad != 'null' ){
                    $query_unidad = " AND a.unidad = ".$unidad. " ";
                }
                $query = "SELECT a.fecha, a.cant_avance, pe.periodoescolar, a.observacion AS observacion_redactor, p.cant_diaria, l.nombrelibro, a.unidad FROM ctrl_avances a INNER JOIN ctrl_parametrizaciones p ON a.id_area = p.id_area AND a.id_actividad = p.id_actividad LEFT JOIN libro l ON a.id_libro = l.idlibro LEFT JOIN periodoescolar pe ON a.id_periodo = pe.idperiodoescolar WHERE a.id_usuario = ".$id_redactor." AND a.id_area = ".$value_ar->idarea." AND a.id_actividad = ".$value->id_actividades." AND a.fecha >= '".$fecha_ini."' AND a.fecha <= '".$fecha_fin."' AND a.id_serie = " . $id_serie . $query_libro . $query_unidad . " ORDER BY a.fecha ASC";
                $avances = DB::SELECT($query);
                
                $data_areas[$key_ar] = [
                    'nombre_area' => $value_ar->nombrearea,
                    'avances' => $avances,
                ];
            }

            $data[$key] = [
                'id_actividad' => $value->id_actividades,
                'actividad' => $value->nombre,
                'areas' => $data_areas,
            ];
        }
        return $data;
    }


    public function get_cumplimiento_redactor_edicion($fecha_ini, $fecha_fin, $id_redactor, $id_serie, $id_actividad, $id_libro, $unidad)
    {
        $actividades = DB::SELECT("SELECT * FROM `ctrl_actividades` WHERE `categoria` = 'edicion' AND `estado` = 1");
        $data = array();
        foreach ($actividades as $key => $value) {
            $query_libro = "";
            if( $id_libro != 0 && $id_libro != 'null' ){
                $query_libro = " AND a.id_libro = ".$id_libro;
            }
            $query_unidad = "";
            if( $unidad != 0 && $unidad != 'null' ){
                $query_unidad = " AND a.unidad = ".$unidad;
            }
            $query = "SELECT a.fecha, a.cant_avance, pe.periodoescolar, a.observacion AS observacion_redactor, p.cant_diaria, l.nombrelibro, a.unidad FROM ctrl_avances a INNER JOIN ctrl_parametrizaciones p ON a.id_area = p.id_area AND a.id_actividad = p.id_actividad LEFT JOIN libro l ON a.id_libro = l.idlibro LEFT JOIN periodoescolar pe ON a.id_periodo = pe.idperiodoescolar WHERE a.id_usuario = ".$id_redactor." AND a.id_actividad = ".$value->id_actividades." AND a.fecha >= '".$fecha_ini."' AND a.fecha <= '".$fecha_fin."' AND a.id_serie = ".$id_serie . $query_libro . $query_unidad ." ORDER BY a.fecha ASC";
            $avances = DB::SELECT($query);
            
            $data[$key] = [
                'id_actividad' => $value->id_actividades,
                'actividad' => $value->nombre,
                'avances' => $avances,
            ];

        }
        return $data;
    }

}
