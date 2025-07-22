<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PedidoFormato;
use App\Models\FormatoPedidoNew;
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
            t.nombretipoarea, pf.*, pr.codigos_combos
            FROM area ar
            INNER JOIN asignatura a ON ar.idarea = a.area_idarea
            INNER JOIN libro l ON a.idasignatura = l.asignatura_idasignatura
            INNER JOIN libros_series ls ON l.idlibro = ls.idLibro
            INNER JOIN tipoareas t ON ar.tipoareas_idtipoarea = t.idtipoarea
            INNER JOIN pedidos_formato pf ON pf.id_area = ar.idarea
            LEFT JOIN 1_4_cal_producto pr ON pr.pro_codigo = ls.codigo_liquidacion
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

    public function series_formato_periodo_guias($periodo)
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
            INNER JOIN 1_4_cal_producto pr ON pr.pro_codigo = CONCAT('G', ls.codigo_liquidacion)
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
        // $serie->longitud_numeros    = $request->longitud_numeros;
        // $serie->longitud_letras     = $request->longitud_letras;
        $serie->longitud_codigo             = $request->longitud_codigo;
        $serie->longitud_codigo_grafitext   = $request->longitud_codigo_grafitext;
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
            INNER JOIN series s ON sb.id_serie = s.id_serie
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

    //METODOS JEYSON
    public function areasxSerie_FomatoPedido(Request $request)
    {
        // AREAS
        $query = DB::SELECT("SELECT DISTINCT ar.idarea, ar.nombrearea, ls.id_serie
            FROM libros_series ls
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
            LEFT JOIN area ar ON a.area_idarea = ar.idarea
            WHERE ls.id_serie = ?
            AND l.Estado_idEstado = '1'
            AND a.estado = '1'", [$request->id_serie]);
        $datos = [];
        $libros = [];
        $contador = 0;
        foreach ($query as $key => $item) {
            $contador2 = 0;
            for ($i = 1; $i <= 10; $i++) {
                $query2 = DB::SELECT("SELECT l.nombrelibro, l.idlibro, l.asignatura_idasignatura, ls.*, pro.codigos_combos, l.descripcionlibro
                    FROM libros_series ls
                    LEFT JOIN libro l ON ls.idLibro = l.idlibro
                    LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
                    LEFT JOIN area ar ON a.area_idarea = ar.idarea
                    LEFT JOIN 1_4_cal_producto pro ON ls.codigo_liquidacion = pro.pro_codigo
                    WHERE ls.id_serie = ?
                    AND a.area_idarea = ?
                    AND l.Estado_idEstado = '1'
                    AND a.estado = '1'
                    AND ls.year = ?", [$request->id_serie, $item->idarea, $i]);
                if (empty($query2)) {
                    $libros[$contador2] = [
                        "nivel" => 0,
                        "nombrelibro" => "",
                        "formato" => 0,
                        "selected" => false,
                    ];
                } else {
                    $idlibro = $query2[0]->idlibro;
                    $idasignatura = $query2[0]->asignatura_idasignatura;

                    // Validar si el docente tiene el libro
                    $query3 = DB::SELECT("SELECT * FROM pedidos_formato_new pfn
                        WHERE pfn.idperiodoescolar = ?
                        AND pfn.idlibro = ?", [$request->periodo_id, $idlibro]);
                    $pfn_id = 0;
                    $selected = false;
                    $pfn_pvp = 0.00;
                    $pfn_estado = null;

                    if (count($query3) > 0) {
                        $pfn_id = $query3[0]->pfn_id;
                        $selected = true;
                        $pfn_pvp = $query3[0]->pfn_pvp;
                        $pfn_estado = $query3[0]->pfn_estado;
                        $pfn_estado = $pfn_estado == 1 ? true : false;
                    }
                    // Procesar codigos_combos
                    $codigos_combos = $query2[0]->codigos_combos;
                    $pro_codigos_desglose = [];
                    if (!empty($codigos_combos)) {
                        $codigos_array = explode(',', $codigos_combos);

                        foreach ($codigos_array as $codigo) {
                            $desglose = DB::SELECT("SELECT ls.codigo_liquidacion, l.nombrelibro
                                FROM libros_series ls
                                LEFT JOIN libro l ON ls.idLibro = l.idlibro
                                WHERE ls.codigo_liquidacion = ?", [$codigo]);

                            if (empty($desglose)) {
                                // ⚠️ Si no se encuentra el código, lanzar error
                                throw new \Exception("⚠️ Código '$codigo' asociado al combo '{$query2[0]->nombrelibro}' no fue encontrado. 'Verifica los códigos del combo.");
                            }

                            $pro_codigos_desglose[] = [
                                "codigo_liquidacion" => $desglose[0]->codigo_liquidacion,
                                "nombrelibro" => $desglose[0]->nombrelibro,
                            ];
                        }
                    }
                    $libros[$contador2] = [
                        "nivel" => $query2[0]->year,
                        "nombrelibro" => $query2[0]->nombrelibro,
                        "codigos_combos" => $codigos_combos,
                        "idlibro" => $idlibro,
                        "idasignatura" => $idasignatura,
                        "pfn_id" => $pfn_id,
                        "pfn_pvp" => $pfn_pvp,
                        "pfn_estado" => $pfn_estado,
                        "formato" => 0,
                        "descripcionlibro" => $query2[0]->descripcionlibro,
                        "selected" => $selected,
                        "pro_codigos_desglose" => $pro_codigos_desglose
                    ];
                }
                $contador2++;
            }
            $getLibros = $this->setearValores($libros);
            $datos[$contador] = [
                "idarea" => $item->idarea,
                "nombrearea" => $item->nombrearea,
                "id_serie" => $item->id_serie,
                "libros" => $getLibros
            ];
            $contador++;
        }
        return $datos;
    }

    public function areasxSerie_FullPedido(Request $request) {
        // Obtener todas las series disponibles
        $series = DB::table('libros_series')->distinct()->pluck('id_serie');

        $datos = [];
        $contador = 0;

        // Iterar sobre cada serie
        foreach($series as $serie_id) {
            // Obtener las áreas para la serie actual
            $query = DB::SELECT("SELECT DISTINCT ar.idarea, ar.nombrearea, ls.id_serie
                FROM libros_series ls
                LEFT JOIN libro l ON ls.idLibro = l.idlibro
                LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
                LEFT JOIN area ar ON a.area_idarea = ar.idarea
                WHERE ls.id_serie = '$serie_id'
                AND l.Estado_idEstado = '1'
                AND a.estado = '1'
            ");

            foreach($query as $key => $item){
                $libros = [];
                $contador2 = 0;
                for($i = 1; $i <= 10; $i++){
                    $query2 = DB::SELECT("SELECT l.nombrelibro, l.idlibro, l.asignatura_idasignatura, ls.*
                        FROM libros_series ls
                        LEFT JOIN libro l ON ls.idLibro = l.idlibro
                        LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
                        LEFT JOIN area ar ON a.area_idarea = ar.idarea
                        WHERE ls.id_serie = '$serie_id'
                        AND a.area_idarea = '$item->idarea'
                        AND l.Estado_idEstado = '1'
                        AND a.estado = '1'
                        AND ls.year = '$i'
                    ");

                    if(empty($query2)){
                        $libros[$contador2] = [
                            "nivel"             => 0,
                            "nombrelibro"       => "",
                        ];
                    } else {
                        $idlibro = $query2[0]->idlibro;
                        $idasignatura = $query2[0]->asignatura_idasignatura;

                        // Validar si el docente tiene el libro
                        $query3 = DB::SELECT("SELECT * FROM pedidos_formato_new pfn
                            WHERE pfn.idperiodoescolar = '$request->periodo_id'
                            AND pfn.idlibro = '$idlibro'");

                        $pfn_id = 0;
                        $selected = false;
                        $pfn_pvp = 0.00;
                        $pfn_estado = null;
                        if(count($query3) > 0){
                            $pfn_id = $query3[0]->pfn_id;
                            $selected = true;
                            $pfn_pvp = $query3[0]->pfn_pvp;
                            $pfn_estado = $query3[0]->pfn_estado;
                            $pfn_estado = $pfn_estado == 1;
                        }

                        $libros[$contador2] = [
                            "nivel"             => $query2[0]->year,
                            "nombrelibro"       => $query2[0]->nombrelibro,
                            "idlibro"           => $idlibro,
                            "idasignatura"      => $idasignatura,
                            "pfn_id"            => $pfn_id,
                            "pfn_pvp"           => $pfn_pvp,
                            "pfn_estado"        => $pfn_estado,
                            "formato"           => 0,
                            "selected"          => $selected
                        ];
                    }
                    $contador2++;
                }
                $getLibros = $this->setearValores($libros);
                $datos[$contador] = [
                    "idarea"        => $item->idarea,
                    "nombrearea"    => $item->nombrearea,
                    "id_serie"      => $item->id_serie,
                    "libros"        => $getLibros
                ];
                $contador++;
            }
        }
        return $datos;
    }

    public function series_formato_periodo_new($periodo, $id_pedido, $pvn_tipo) {
        // Obtener las series, excluyendo la serie con id 6
        $series = DB::SELECT("SELECT * FROM series s ORDER BY s.nombre_serie DESC");
        $datos = array();
        foreach($series as $key => $value) {
            // Obtener las áreas y libros correspondientes
            $areas = DB::SELECT("SELECT DISTINCT
                    ar.idarea,
                    ar.nombrearea,
                    t.nombretipoarea,
                    l.idlibro,
                    l.descripcionlibro,
                    l.nombrelibro,
                    pf.pfn_pvp,
                    pf.pfn_estado,
                    ls.year,
                    pr.codigos_combos
                FROM area ar
                INNER JOIN asignatura a ON ar.idarea = a.area_idarea
                INNER JOIN libro l ON a.idasignatura = l.asignatura_idasignatura
                INNER JOIN libros_series ls ON l.idlibro = ls.idLibro
                INNER JOIN tipoareas t ON ar.tipoareas_idtipoarea = t.idtipoarea
                INNER JOIN pedidos_formato_new pf ON pf.idlibro = l.idlibro
                LEFT JOIN 1_4_cal_producto pr ON ls.codigo_liquidacion = pr.pro_codigo
                WHERE ls.id_serie = ?
                AND pf.idperiodoescolar = ?
                AND ar.estado = '1'
                AND a.estado = '1'
                AND pf.pfn_estado = 1
                AND l.Estado_idEstado = 1
                ORDER BY ar.idarea, pf.pfn_orden ASC", [$value->id_serie, $periodo]);

            if ($areas) {
                $datos[$key] = [
                    "id_serie" => $value->id_serie,
                    "nombre_serie" => $value->nombre_serie,
                    "areas" => $this->organizeAreas($areas)
                ];

                // Obtener y asignar el valor de pvn_cantidad de la tabla pedidos_val_area_new
                foreach ($datos[$key]['areas'] as &$area) {
                    foreach ($area['libros'] as &$libro) {
                        $pedidoVal = DB::table('pedidos_val_area_new')
                            ->where('idlibro', $libro['idlibro'])
                            ->where('id_pedido', $id_pedido) // Asegúrate de tener el id del pedido correcto
                            ->where('pvn_tipo', $pvn_tipo) // Asegúrate de tener el id del tipo pedido correcto
                            ->first();

                        // Verificar si se encontró un registro en pedidos_val_area_new para este libro
                        if ($pedidoVal) {
                            $libro['pvn_cantidad'] = $pedidoVal->pvn_cantidad;
                        } else {
                            // Si no se encontró un registro, asignar 0 a pvn_cantidad
                            $libro['pvn_cantidad'] = 0;
                        }
                    }
                }
            }
        }

        return $datos;
    }

    public function series_formato_periodo_new_guias($periodo, $id_pedido, $pvn_tipo) {
        // Obtener las series, excluyendo la serie con id 6
        $series = DB::SELECT("SELECT * FROM series s ORDER BY s.nombre_serie DESC");
        $datos = array();

        foreach($series as $key => $value) {
            // Obtener las áreas y libros correspondientes
            $areas = DB::SELECT("SELECT DISTINCT
                ar.idarea,
                ar.nombrearea,
                t.nombretipoarea,
                l.idlibro,
                l.nombrelibro,
                pf.pfn_pvp,
                pf.pfn_estado,
                ls.year,
                l.descripcionlibro,
                pr.codigos_combos
            FROM area ar
            INNER JOIN asignatura a ON ar.idarea = a.area_idarea
            INNER JOIN libro l ON a.idasignatura = l.asignatura_idasignatura
            INNER JOIN libros_series ls ON l.idlibro = ls.idLibro
            INNER JOIN tipoareas t ON ar.tipoareas_idtipoarea = t.idtipoarea
            INNER JOIN pedidos_formato_new pf ON pf.idlibro = l.idlibro
            INNER JOIN 1_4_cal_producto pr ON pr.pro_codigo = CONCAT('G', ls.codigo_liquidacion)
            WHERE ls.id_serie = ?
            AND pf.idperiodoescolar = ?
            AND ar.estado = '1'
            AND a.estado = '1'
            AND pf.pfn_estado = 1
            AND l.Estado_idEstado = 1
            ORDER BY ar.idarea, pf.pfn_orden ASC", [$value->id_serie, $periodo]);



            if ($areas) {
                $datos[$key] = [
                    "id_serie" => $value->id_serie,
                    "nombre_serie" => $value->nombre_serie,
                    "areas" => $this->organizeAreas($areas)
                ];

                // Obtener y asignar el valor de pvn_cantidad de la tabla pedidos_val_area_new
                foreach ($datos[$key]['areas'] as &$area) {
                    foreach ($area['libros'] as &$libro) {
                        $pedidoVal = DB::table('pedidos_val_area_new')
                            ->where('idlibro', $libro['idlibro'])
                            ->where('id_pedido', $id_pedido) // Asegúrate de tener el id del pedido correcto
                            ->where('pvn_tipo', $pvn_tipo) // Asegúrate de tener el id del tipo pedido correcto
                            ->first();

                        // Verificar si se encontró un registro en pedidos_val_area_new para este libro
                        if ($pedidoVal) {
                            $libro['pvn_cantidad'] = $pedidoVal->pvn_cantidad;
                        } else {
                            // Si no se encontró un registro, asignar 0 a pvn_cantidad
                            $libro['pvn_cantidad'] = 0;
                        }
                    }
                }
            }
        }

        return $datos;
    }

    private function organizeAreas($areas){
        $organizedAreas = [];
        foreach ($areas as $area) {
            if (!isset($organizedAreas[$area->idarea])) {
                $organizedAreas[$area->idarea] = [
                    "idarea" => $area->idarea,
                    "nombrearea" => $area->nombrearea,
                    "nombretipoarea" => $area->nombretipoarea,
                    "libros" => []
                ];
            }
            // Desglosar los códigos del combo
            $desglose_codigos_combos = [];
            if (!empty($area->codigos_combos)) {
                $codigos_array = explode(',', $area->codigos_combos);
                foreach ($codigos_array as $codigo) {
                    $desglose = DB::SELECT("
                        SELECT ls.codigo_liquidacion, l.nombrelibro
                        FROM libros_series ls
                        LEFT JOIN libro l ON ls.idLibro = l.idlibro
                        WHERE ls.codigo_liquidacion = ?
                    ", [$codigo]);
                    if (empty($desglose)) {
                        throw new \Exception("⚠️ Código '$codigo' asociado al combo '{$area->nombrelibro}' no fue encontrado. Verifica los códigos del combo. Comunica este error a soporte..");
                    }
                    $desglose_codigos_combos[] = [
                        "codigo_liquidacion" => $desglose[0]->codigo_liquidacion,
                        "nombrelibro" => $desglose[0]->nombrelibro,
                    ];
                }
            }
            $organizedAreas[$area->idarea]['libros'][] = [
                "idlibro" => $area->idlibro,
                "nombrelibro" => $area->nombrelibro,
                "descripcionlibro" => $area->descripcionlibro,
                "pfn_pvp" => $area->pfn_pvp,
                "pfn_estado" => $area->pfn_estado,
                "year" => $area->year,
                "codigos_combos" => $area->codigos_combos,
                "pro_codigos_desglose" => $desglose_codigos_combos,
            ];
        }
        // Ordenar los libros de cada área por 'year'
        foreach ($organizedAreas as &$area) {
            usort($area['libros'], function($a, $b) {
                return $a['year'] <=> $b['year'];
            });
        }
        return array_values($organizedAreas);
    }

    public function areasxSeriePlanLector_FullPedido(Request $request){
        // Obtener todas las series disponibles
        $series = DB::table('libros_series')->distinct()->pluck('id_serie');

        $datos = [];
        $contadorGlobal = 0;

        // Iterar sobre cada serie
        foreach($series as $serie_id) {
            // Obtener los libros para la serie actual
            $query = DB::SELECT("SELECT l.nombrelibro, l.idlibro, l.asignatura_idasignatura, ls.*
                FROM libros_series ls
                LEFT JOIN libro l ON ls.idLibro = l.idlibro
                LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
                LEFT JOIN area ar ON a.area_idarea = ar.idarea
                WHERE ls.id_serie = '$serie_id'
                AND l.Estado_idEstado = '1'
                AND a.estado = '1'
            ");

            foreach($query as $key => $item){
                // Validar si el docente tiene el libro
                $idlibro = $item->idlibro;
                $idasignatura = $item->asignatura_idasignatura;
                $query2 = DB::SELECT("SELECT * FROM pedidos_formato_new pfn
                    WHERE pfn.idperiodoescolar = '$request->periodo_id'
                    AND pfn.idlibro = '$idlibro'");

                $pfn_id = 0;
                $selected = false;
                $pfn_pvp = 0.00;
                $pfn_estado = null;

                if(count($query2) > 0){
                    $pfn_id = $query2[0]->pfn_id;
                    $selected = true;
                    $pfn_pvp = $query2[0]->pfn_pvp;
                    $pfn_estado = $query2[0]->pfn_estado;
                    $pfn_estado = $pfn_estado == 1;
                }

                $datos[$contadorGlobal] = [
                    "nombrelibro"   => $item->nombrelibro,
                    "idlibro"       => $item->idlibro,
                    "idasignatura"  => $idasignatura,
                    "pfn_id"        => $pfn_id,
                    "pfn_pvp"       => $pfn_pvp,
                    "pfn_estado"    => $pfn_estado,
                    "formato"       => 0,
                    "selected"      => $selected
                ];
                $contadorGlobal++;
            }
        }

        return $datos;
    }

    public function areasxSeriePlanLector_FormatoPedido(Request $request){
        $query = DB::SELECT("SELECT l.nombrelibro,  l.idlibro,l.asignatura_idasignatura , ls.*
            FROM libros_series ls
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
            LEFT JOIN area ar ON a.area_idarea = ar.idarea
            WHERE ls.id_serie = '$request->id_serie'
            AND l.Estado_idEstado = '1'
            -- AND ls.estado = '1'
            AND a.estado = '1'
        ");
        $datos = [];
        $contador = 0;
        foreach($query as $key => $item){
            //validar si el docente tiene el libro
            $idlibro        = $item->idlibro;
            $idasignatura   = $item->asignatura_idasignatura;
            $query2 = DB::SELECT("SELECT * FROM pedidos_formato_new pfn
            WHERE pfn.idperiodoescolar = '$request->periodo_id'
            AND pfn.idlibro = '$idlibro'");
            $pfn_id         = 0;
            $selected       = false;
            $pfn_pvp        = 0.00;
            $pfn_estado     = null; // Inicializar la variable
            if(count($query2) > 0){
                $pfn_id         = $query2[0]->pfn_id;
                $selected       = true;
                $pfn_pvp        = $query2[0]->pfn_pvp;
                $pfn_estado     = $query2[0]->pfn_estado; // Asegúrate de que este campo existe en la tabla
                if ($pfn_estado == 0) {
                    $pfn_estado = false;
                }elseif ($pfn_estado == 1) {
                    $pfn_estado = true;
                }
            }
            $datos[$contador] = [
                "nombrelibro"       => $item->nombrelibro,
                "idlibro"           => $item->idlibro,
                "idasignatura"      => $idasignatura,
                "pfn_id"            => $pfn_id,
                "pfn_pvp"           => $pfn_pvp,
                "pfn_estado"        => $pfn_estado,
                //si no ha seleccionado es 0 si ha seleccionado es 1
                "formato"           => 0,
                "selected"          => $selected
            ];
            $contador++;
        }
        return $datos;
    }

    public function setearValores($array){
        $librosFiltrados = array_filter($array, function ($libro) {
            return $libro["nivel"] > 0;
        });
        return array_values($librosFiltrados);
    }


    public function traspasarFormatoPedidos_New(Request $request){
        //Transpaso de series basicas
        // return $request;
        $periodo_formatoactual  = $request->periodoAnterior;
        $periodo_formatonuevo   = $request->periodoTranspaso;
        $user_editor            = $request->user_editor;
        $query = DB::SELECT("SELECT * FROM pedidos_series_basicas sb
        WHERE sb.periodo_id = '$periodo_formatoactual'
        ");
        foreach($query as $key => $item){
            DB::table('pedidos_series_basicas')
            ->where('periodo_id', $periodo_formatonuevo)
            ->where('id_serie', $item->id_serie)
            ->update(['serie_basica' => $item->serie_basica]);
        }
        //transpaso precios formato
        $query2 = DB::SELECT("SELECT * FROM pedidos_formato_new pf
        WHERE pf.idperiodoescolar = '$periodo_formatoactual'
        ");
        // return $query2;
        foreach($query2 as $key => $item2){
            //validate si esta creado lo edito si no creo
            $validate = DB::SELECT("SELECT * FROM pedidos_formato_new pf
                WHERE pf.idperiodoescolar = '$periodo_formatonuevo'
                AND pf.idlibro = '$item2->idlibro'
            ");
            //CREAR
            if(empty($validate)){
                $formato = new FormatoPedidoNew();
            }
            //editar
            else{
                $pfn_id = $validate[0]->pfn_id;
                $formato = FormatoPedidoNew::findOrFail($pfn_id);
            }
            $formato->idlibro               = $item2->idlibro;
            $formato->idperiodoescolar      = $periodo_formatonuevo;
            $formato->pfn_pvp               = $item2->pfn_pvp;
            $formato->pfn_orden             = $item2->pfn_orden;
            $formato->pfn_estado            = $item2->pfn_estado;
            $formato->user_editor           = $user_editor;
            $formato->save();
        }
        return ["status" => "1", "message" => "Se guardo correctamente"];
    }

    public function Gettablaconfiguracionparanuevoformato(){
        $query = DB::SELECT("SELECT * FROM configuracion_general WHERE id = 7");
        return $query;
    }

    public function GetSacarAreasxSerieProducto(Request $request){
        $id_serie = $request->query('id_serie');  // O también puedes usar $request->input('id_serie')
        $query = DB::SELECT("SELECT DISTINCT ar.idarea, ar.nombrearea
        FROM libro li
        LEFT JOIN libros_series ls ON li.idlibro = ls.idLibro
        LEFT JOIN 1_4_cal_producto pro ON ls.codigo_liquidacion = pro.pro_codigo
        LEFT JOIN asignatura asi ON li.asignatura_idasignatura = asi.idasignatura
        LEFT JOIN area ar ON asi.area_idarea = ar.idarea
        LEFT JOIN series se ON ls.id_serie = se.id_serie
        WHERE ar.idarea IS NOT NULL AND se.id_serie = $id_serie
        ORDER BY ar.nombrearea ASC");
        return $query;
    }

    public function GetSacarAreasxSerieComboProducto(Request $request){
        $id_serie = $request->query('id_serie');  // O también puedes usar $request->input('id_serie')
        $query = DB::SELECT("SELECT DISTINCT ar.idarea, ar.nombrearea
        FROM libro li
        LEFT JOIN libros_series ls ON li.idlibro = ls.idLibro
        LEFT JOIN 1_4_cal_producto pro ON ls.codigo_liquidacion = pro.pro_codigo
        LEFT JOIN asignatura asi ON li.asignatura_idasignatura = asi.idasignatura
        LEFT JOIN area ar ON asi.area_idarea = ar.idarea
        LEFT JOIN series se ON ls.id_serie = se.id_serie
        WHERE ar.idarea IS NOT NULL AND se.id_serie = $id_serie AND pro.ifcombo = 1
        ORDER BY ar.nombrearea ASC");
        return $query;
    }

    public function GetObtenerProductosxSerieoArea(Request $request){
        // return $request;
        $id_serie = $request->query('id_serie');
        $idarea = $request->query('idarea');
        $tipo_producto = $request->query('tipo_producto', 1); // Valor predeterminado a 1
        //tipo 1 = grupos; tipo 2 = combos
        $tipo = $request->query('tipo', 1); // Valor predeterminado a 1
        // Condición adicional si $tipo es 2
        $condicionIfCombo = ($tipo == 2) ? "AND pro.ifcombo = 1" : "";

        if($id_serie && $idarea){
            $query = DB::SELECT("SELECT pro.pro_codigo
            FROM libro li
            LEFT JOIN libros_series ls ON li.idlibro = ls.idLibro
            INNER JOIN 1_4_cal_producto pro ON ls.codigo_liquidacion = pro.pro_codigo
            LEFT JOIN asignatura asi ON li.asignatura_idasignatura = asi.idasignatura
            LEFT JOIN area ar ON asi.area_idarea = ar.idarea
            LEFT JOIN series se ON ls.id_serie = se.id_serie
            WHERE pro.pro_codigo IS NOT NULL AND pro.gru_pro_codigo = 1 AND se.id_serie = $id_serie AND ar.idarea = $idarea
            $condicionIfCombo
            ORDER BY pro.pro_nombre");
        }else if($id_serie && !$idarea){
            $query = DB::SELECT("SELECT pro.pro_codigo
            FROM libro li
            LEFT JOIN libros_series ls ON li.idlibro = ls.idLibro
            INNER JOIN 1_4_cal_producto pro ON ls.codigo_liquidacion = pro.pro_codigo
            LEFT JOIN asignatura asi ON li.asignatura_idasignatura = asi.idasignatura
            LEFT JOIN area ar ON asi.area_idarea = ar.idarea
            LEFT JOIN series se ON ls.id_serie = se.id_serie
            WHERE pro.pro_codigo IS NOT NULL AND pro.gru_pro_codigo = 1 AND se.id_serie = $id_serie
            $condicionIfCombo
            ORDER BY pro.pro_nombre");
        }
        if (!$query) {
            return response()->json(['error' => 'No se encontraron productos'], 404);
        }
        // Crear un array con los códigos obtenidos
        $codigos = array_map(fn($item) => $item->pro_codigo, $query);

        if (empty($codigos)) {
            return response()->json(['error' => 'No se encontraron códigos'], 404);
        }
        // Crear un array para almacenar los resultados finales
        $productosFinales = [];
        // Recorrer los códigos y realizar consultas individuales según tipo_producto
        foreach ($codigos as $codigo) {
            if ($tipo_producto == 2) {
                // Buscar código original
                $productoDetalles = DB::select("SELECT pro.pro_codigo, pro.pro_deposito, pro.pro_depositoCalmed, pro.pro_nombre, pro.pro_reservar, pro.pro_stock, pro.pro_stockCalmed,
                    pro.gru_pro_codigo
                    FROM 1_4_cal_producto pro
                    WHERE pro.pro_codigo = ?", [$codigo]);
            } else if ($tipo_producto == 3) {
                // Buscar código con 'G' al inicio
                $codigoConG = "G" . $codigo;
                $productoDetalles = DB::select("SELECT pro.pro_codigo, pro.pro_deposito, pro.pro_depositoCalmed, pro.pro_nombre, pro.pro_reservar, pro.pro_stock, pro.pro_stockCalmed,
                    pro.gru_pro_codigo
                    FROM 1_4_cal_producto pro
                    WHERE pro.pro_codigo = ?", [$codigoConG]);
            } else if ($tipo_producto == 1) {
                // Buscar código original y con 'G' al inicio
                $codigoConG = "G" . $codigo;
                $productoDetallesOriginal = DB::select("SELECT pro.pro_codigo, pro.pro_deposito, pro.pro_depositoCalmed, pro.pro_nombre, pro.pro_reservar, pro.pro_stock, pro.pro_stockCalmed,
                    pro.gru_pro_codigo
                    FROM 1_4_cal_producto pro
                    WHERE pro.pro_codigo = ?", [$codigo]);

                $productoDetallesConG = DB::select("SELECT pro.pro_codigo, pro.pro_deposito, pro.pro_depositoCalmed, pro.pro_nombre, pro.pro_reservar, pro.pro_stock, pro.pro_stockCalmed,
                    pro.gru_pro_codigo
                    FROM 1_4_cal_producto pro
                    WHERE pro.pro_codigo = ?", [$codigoConG]);

                // Inicializar un array para controlar los códigos ya añadidos
                $codigosAgregados = [];

                // Si existen ambos, añadirlos al array final
                if (!empty($productoDetallesOriginal) && !in_array($productoDetallesOriginal[0]->pro_codigo, $codigosAgregados)) {
                    $productosFinales[] = $productoDetallesOriginal[0]; // Añadir código original
                    $codigosAgregados[] = $productoDetallesOriginal[0]->pro_codigo; // Registrar el código como añadido
                }

                if (!empty($productoDetallesConG) && !in_array($productoDetallesConG[0]->pro_codigo, $codigosAgregados)) {
                    $productosFinales[] = $productoDetallesConG[0]; // Añadir código con 'G' al inicio
                    $codigosAgregados[] = $productoDetallesConG[0]->pro_codigo; // Registrar el código como añadido
                }
            } else if ($tipo_producto == 4) {
                // Buscar código original junto con los nombres correspondientes a las iniciales de codigos_combos
                $productoDetalles = DB::select("SELECT pro.pro_codigo, pro.pro_deposito, pro.pro_depositoCalmed, pro.pro_nombre, pro.pro_reservar,
                    pro.pro_stock, pro.pro_stockCalmed, pro.gru_pro_codigo, pro.pro_descripcion as iniciales_con_nombres
                    -- ,(SELECT GROUP_CONCAT(concat(cmb.pro_codigo, '(', cmb.pro_nombre, ')') SEPARATOR ', ') FROM 1_4_cal_producto cmb
                    --     WHERE FIND_IN_SET(cmb.pro_codigo, REPLACE(pro.codigos_combos, ' ', ''))) AS iniciales_con_nombres
                FROM 1_4_cal_producto pro
                WHERE pro.pro_codigo = ?", [$codigo]);
            } else {
                // Si no es un tipo_producto válido, continuar
                continue;
            }

            // Si hay resultados, añadirlos al array final
            if (!empty($productoDetalles)) {
                $productosFinales[] = $productoDetalles[0]; // Tomar el primer resultado
            }
        }

        return response()->json($productosFinales);
    }

    public function getSeries_new(){
        $query = DB::SELECT("SELECT * FROM series s WHERE s.nombre_serie NOT IN ('conexiones')");
        return $query;
    }

    public function getSeries_EdicionStock(){
        $query = DB::SELECT("SELECT * FROM series s WHERE s.nombre_serie NOT IN ('Combos')");
        return $query;
    }

    //FIN METODOS JEYSON
}
