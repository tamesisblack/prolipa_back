<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PedidoFormato;
use Illuminate\Support\Facades\DB;
use App\Models\Series;
use App\Models\PedidoSeriesBasicas;
class SeriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Series::all();
    }


    public function series_formato_periodo($periodo)
    {//SOLO FORMATOS YA CONFIGURADOS INNER
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $series = DB::SELECT("SELECT * FROM series s WHERE s.id_serie != 6 ORDER BY s.nombre_serie DESC"); // omitir plan lector
        $datos = array();
        foreach($series as $key => $value){
            $areas = DB::SELECT("SELECT DISTINCT ar.idarea, ar.nombrearea, 
            t.nombretipoarea, pf.*
            FROM area ar
            INNER JOIN asignatura a ON ar.idarea = a.area_idarea
            INNER JOIN libro l ON a.idasignatura = l.asignatura_idasignatura
            INNER JOIN libros_series ls ON l.idlibro = ls.idLibro
            INNER JOIN tipoareas t ON ar.tipoareas_idtipoarea = t.idtipoarea
            INNER JOIN pedidos_formato pf ON pf.id_area = ar.idarea
            WHERE ls.id_serie = ?
            AND pf.id_periodo = ? 
            AND pf.id_serie = ?
            AND ar.estado = '1' 
            AND a.estado = '1'
            AND pf.pvp <> 0
            AND l.Estado_idEstado = 1
            GROUP BY ar.idarea
            ORDER BY pf.orden ASC", [$value->id_serie, $periodo, $value->id_serie]);
            if( $areas ){
                $datos[$key] = [
                    "id_serie" => $value->id_serie,
                    "nombre_serie" => $value->nombre_serie,
                    "areas" => $areas
                ];
            }
        }
        return $datos;
    }

    public function series_formato_full($periodo)
    {//TODAS LAS SERIES LEFT
        $series = DB::SELECT("SELECT * FROM series s WHERE s.id_serie != 6"); // omitir plan lector
        $datos = array();
        foreach($series as $key => $value){
            $areas = DB::SELECT("SELECT DISTINCT ar.idarea, ar.nombrearea, t.nombretipoarea, pc.* FROM area ar
            INNER JOIN asignatura a ON ar.idarea = a.area_idarea
            INNER JOIN libro l ON a.idasignatura = l.asignatura_idasignatura
            INNER JOIN libros_series ls ON l.idlibro = ls.idLibro
            INNER JOIN tipoareas t ON ar.tipoareas_idtipoarea = t.idtipoarea
            LEFT JOIN pedidos_formato pc ON ar.idarea = pc.id_area
            WHERE ls.id_serie = ? AND ar.estado = '1' AND a.estado = '1' AND l.Estado_idEstado = 1 AND pc.id_periodo = $periodo
            ORDER BY ar.tipoareas_idtipoarea;", [$value->id_serie]);

            $datos[$key] = [
                "id_serie" => $value->id_serie,
                "nombre_serie" => $value->nombre_serie,
                "areas" => $areas
            ];

        }

        return $datos;
    }


    public function series_full()
    {//TODAS LAS SERIES LEFT
        $series = DB::SELECT("SELECT * FROM series s WHERE s.id_serie != 6"); // omitir plan lector
        $datos = array();
        foreach($series as $key => $value){
            $areas = DB::SELECT("SELECT DISTINCT ar.idarea, ar.nombrearea, t.nombretipoarea FROM area ar
            INNER JOIN asignatura a ON ar.idarea = a.area_idarea
            INNER JOIN libro l ON a.idasignatura = l.asignatura_idasignatura
            INNER JOIN libros_series ls ON l.idlibro = ls.idLibro
            INNER JOIN tipoareas t ON ar.tipoareas_idtipoarea = t.idtipoarea
            WHERE ls.id_serie = ? AND ar.estado = '1' AND a.estado = '1' AND l.Estado_idEstado = 1
            ORDER BY ar.tipoareas_idtipoarea;", [$value->id_serie]);

            $datos[$key] = [
                "id_serie" => $value->id_serie,
                "nombre_serie" => $value->nombre_serie,
                "areas" => $areas
            ];
        }
        return $datos;
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
        if($request->id > 0){
            $serie = Series::findOrFail($request->id);
        }else{
            $serie = new Series();
        }
        $serie->nombre_serie        = $request->nombre_serie;
        $serie->longitud_numeros    = $request->longitud_numeros;
        $serie->longitud_letras     = $request->longitud_letras;
        $serie->longitud_codigo     = $request->longitud_codigo;
        $serie->save();
        if($serie){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "1", "message" => "No se pudo guardar"];
        }
    }
    //api:post/traspasarFormatoPedidos
    public function traspasarFormatoPedidos(Request $request){
        //Transpaso de series basicas 
        $periodoAnterior    = $request->periodoAnterior;
        $periodoTranspaso   = $request->periodoTranspaso;
        $query = DB::SELECT("SELECT * FROM pedidos_series_basicas sb
        WHERE sb.periodo_id = '$periodoAnterior'
        ");
        foreach($query as $key => $item){
            DB::table('pedidos_series_basicas')
            ->where('periodo_id', $periodoTranspaso)
            ->where('id_serie', $item->id_serie)
            ->update(['serie_basica' => $item->serie_basica]);
        }
        //transpaso precios formato
        $query2 = DB::SELECT("SELECT * FROM pedidos_formato pf
        WHERE pf.id_periodo = '$periodoAnterior'
        ");
        foreach($query2 as $key => $item2){
            //validate si esta creado lo edito si no creo
            $validate = DB::SELECT("SELECT * FROM pedidos_formato pf
                WHERE pf.id_periodo = '$periodoTranspaso'
                AND pf.id_serie = '$item2->id_serie'
                AND pf.id_area  = '$item2->id_area'
                AND pf.id_libro = '$item2->id_libro'
            ");
            //CREAR
            if(empty($validate)){
                $formato = new PedidoFormato();
            }
            //editar
            else{
                $id = $validate[0]->id;
                $formato = PedidoFormato::findOrFail($id);
            }
            $formato->orden         = $item2->orden;
            $formato->id_periodo    = $periodoTranspaso;
            $formato->id_serie      = $item2->id_serie;
            $formato->id_area       = $item2->id_area;
            $formato->pvp           = $item2->pvp;
            $formato->id_libro      = $item2->id_libro;
            $formato->n1            = $item2->n1;
            $formato->n2            = $item2->n2;
            $formato->n3            = $item2->n3;
            $formato->n4            = $item2->n4;
            $formato->n5            = $item2->n5;
            $formato->n6            = $item2->n6;
            $formato->n7            = $item2->n7;
            $formato->n8            = $item2->n8;
            $formato->n9            = $item2->n9;
            $formato->n10           = $item2->n10;
            $formato->save();
        }
        return ["status" => "1", "message" => "Se guardo correctamente"];
    }
    //api:get>>/generarSeriesBasicasPeriodo
    public function generarSeriesBasicasPeriodo(Request $request){
        $series = Series::all();
        //validar si esta guardado
        foreach($series as $key => $item){
            $validate = DB::SELECT("SELECT * FROM pedidos_series_basicas sb
            WHERE sb.periodo_id = '$request->periodo_id'
            AND sb.id_serie = '$item->id_serie'
            ");
            if(empty($validate)){
                $serie                  = new PedidoSeriesBasicas();
                $serie->periodo_id      = $request->periodo_id;
                $serie->id_serie        = $item->id_serie;
                $serie->serie_basica    = 0;
                $serie->save();
            }
        }
        return $series;
    }
    //api:get>>/getSeriesBasicas/{periodo}
    public function getSeriesBasicas($periodo){
        $series = DB::SELECT("SELECT sb.*, s.nombre_serie
            FROM pedidos_series_basicas sb
            LEFT JOIN series s ON sb.id_serie = s.id_serie
            WHERE sb.periodo_id = '$periodo'        
        ");
        return $series;
    }
    public function cambiarSerieBasica(Request $request){
        if($request->id > 0){
            $serie = PedidoSeriesBasicas::findOrFail($request->id);
        }
        else{
            $serie = new PedidoSeriesBasicas();
        }
        $serie->serie_basica = $request->estado;
        $serie->save();
        if($serie){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "1", "message" => "No se pudo guardar"];
        }
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
        Series::findOrFail($id)->delete();
    }
}
