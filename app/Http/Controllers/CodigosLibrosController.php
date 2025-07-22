<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\CodigosLibros;
use App\Models\HistoricoCodigos;
use App\Models\Libro;
use App\Models\LibroSerie;
use DataTables;
Use Exception;
use Carbon\Carbon;

class CodigosLibrosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $auxlibros = [];
        $auxlibrosf = [];
        $auxlibros = $codigos_libros = DB::SELECT("SELECT libro.*,asignatura.* from codigoslibros join libro on libro.idlibro = codigoslibros.libro_idlibro join asignatura on asignatura.idasignatura = libro.asignatura_idasignatura  WHERE idusuario = ?",[$request->idusuario]);
        $usuario = DB::SELECT("SELECT * FROM usuario WHERE idusuario = ?",[$request->idusuario]);
        $idinstitucion = '';
        foreach ($usuario as $key => $value) {
            $idinstitucion = $value->institucion_idInstitucion;
        }

        if(!empty($codigos_libros)){
            foreach ($codigos_libros as $key => $value) {
                $free = DB::SELECT("SELECT libro.*,asignatura.* FROM institucion_libro join libro on libro.idlibro = institucion_libro.idlibro join asignatura on asignatura.idasignatura = libro.asignatura_idasignatura  WHERE institucion_libro.idinstitucion = ? AND asignatura.nivel_idnivel = ? AND institucion_libro.estado = '1'",[$idinstitucion,$value->nivel_idnivel]);
                foreach ($free as $keyl => $valuel) {
                    array_push($auxlibros, $valuel);
                }
            }
        }
        $auxlibrosf = array_unique($auxlibros, SORT_REGULAR);


        return $auxlibrosf;
    }

    //api get>>/codigoslibrosEstudiante
    public function codigoslibrosEstudiante(Request $request){
        $auxlibros = [];
        $auxlibrosf = [];
        $auxlibros = $codigos_libros = DB::SELECT("SELECT libro.*,asignatura.* from codigoslibros join libro on libro.idlibro = codigoslibros.libro_idlibro join asignatura on asignatura.idasignatura = libro.asignatura_idasignatura  WHERE idusuario = ?",[$request->idusuario]);
        $usuario = DB::SELECT("SELECT * FROM usuario WHERE idusuario = ?",[$request->idusuario]);
        $idinstitucion = '';
        foreach ($usuario as $key => $value) {
            $idinstitucion = $value->institucion_idInstitucion;
        }

        if(!empty($codigos_libros)){
            foreach ($codigos_libros as $key => $value) {
                $free = DB::SELECT("SELECT libro.*,asignatura.* FROM institucion_libro join libro on libro.idlibro = institucion_libro.idlibro join asignatura on asignatura.idasignatura = libro.asignatura_idasignatura  WHERE institucion_libro.idinstitucion = ? AND asignatura.nivel_idnivel = ? AND institucion_libro.estado = '1'",[$idinstitucion,$value->nivel_idnivel]);
                foreach ($free as $keyl => $valuel) {
                    array_push($auxlibros, $valuel);
                }
            }
        }
        $auxlibrosf = array_unique($auxlibros, SORT_REGULAR);
        foreach($auxlibrosf as $k => $item){
            $data[] = DB::SELECT("SELECT j.*, a.nombreasignatura FROM juegos j
             LEFT JOIN asignatura a ON a.idasignatura = j.asignatura_idasignatura
             WHERE j.asignatura_idasignatura = $item->idasignatura

            ");
        }

        $contador1 =0;
        while ($contador1 < count($data)) {

            if(count($data) == 1){
                $array_resultante= $data[0];
            }

            if(count($data) == 2){
                $array_resultante= array_merge($data[0],$data[1]);
            }

            if(count($data) == 3){
                $array_resultante= array_merge($data[0],$data[1],$data[2]);
            }

            if(count($data) == 4){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3]);
            }

            if(count($data) == 5){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4]);
            }

            if(count($data) == 6){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5]);
            }

            if(count($data) == 7){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6]);
            }

            if(count($data) == 8){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7]);
            }

            if(count($data) == 9){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8]);
            }

            if(count($data) == 10){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9]);
            }

            if(count($data) == 11){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10]);
            }


            if(count($data) == 12){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10],$data[11]);
            }

            if(count($data) == 13){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10],$data[11],$data[12]);
            }

            if(count($data) == 14){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10],$data[11],$data[12],$data[13]);
            }

            if(count($data) == 15){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10],$data[11],$data[12],$data[13],$data[14]);
            }

            if(count($data) == 16){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10],$data[11],$data[12],$data[13],$data[14],$data[15]);
            }

            $contador1=$contador1+1;
        }
        return $array_resultante;

    }

    public function codigosCuaderno(Request $request){
        $codigos_libros = DB::SELECT("SELECT cuaderno.* from codigoslibros join libro on libro.idlibro = codigoslibros.libro_idlibro join cuaderno on cuaderno.asignatura_idasignatura = libro.asignatura_idasignatura  WHERE idusuario = ? AND codigoslibros.codigo LIKE '%PLUS%'",[$request->idusuario]);
        return $codigos_libros;
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

    public function codigos_libros_estudiante($id,$institucion,$periodo,$region,$grupo){
        $auxlibros = [];
        $nivel = 0;
        // $auxlibros = $codigos_libros = DB::SELECT("SELECT libro.*,asignatura.*,
        // codigoslibros.serie as serieCodigo, codigoslibros.anio,codigoslibros.updated_at as fechaUpdate, codigoslibros.plus,
        // codigoslibros.codigo
        //  from codigoslibros
        //  join libro on libro.idlibro = codigoslibros.libro_idlibro
        //  join asignatura on asignatura.idasignatura = libro.asignatura_idasignatura
        //  WHERE codigoslibros.id_periodo = '$periodo'
        //  AND codigoslibros.idusuario = ?
        // AND (
		// 	codigoslibros.estado_liquidacion <> 3
		// 	OR codigoslibros.quitar_de_reporte = '1'
		// 	)
        //  AND codigoslibros.estado <> 2
        //  ",[$id]);
        //  return $auxlibros;

        $auxlibros = DB::SELECT("
            -- Casos donde plus = 1
            SELECT
                lib_plus.*,
                a_plus.*,
                codigoslibros.serie AS serieCodigo,
                codigoslibros.anio,
                codigoslibros.updated_at AS fechaUpdate,
                codigoslibros.plus,
                codigoslibros.codigo
            FROM codigoslibros
            JOIN libro ON libro.idlibro = codigoslibros.libro_idlibro
            JOIN asignatura ON asignatura.idasignatura = libro.asignatura_idasignatura
            LEFT JOIN libros_series ls ON ls.idLibro = libro.idlibro
            LEFT JOIN libros_series l_plus ON l_plus.idLibro = ls.id_libro_plus
            LEFT JOIN libro lib_plus ON ls.id_libro_plus = lib_plus.idlibro
            LEFT JOIN asignatura a_plus ON lib_plus.asignatura_idasignatura = a_plus.idasignatura
            WHERE codigoslibros.id_periodo = '$periodo'
            AND codigoslibros.idusuario = '$id'
            AND (
                    codigoslibros.estado_liquidacion <> 3
                    OR codigoslibros.quitar_de_reporte = '1'
                )
            AND codigoslibros.estado <> 2
            AND codigoslibros.plus = 1

            UNION ALL

            -- Casos donde plus ≠ 1
            SELECT
                libro.*,
                asignatura.*,
                codigoslibros.serie AS serieCodigo,
                codigoslibros.anio,
                codigoslibros.updated_at AS fechaUpdate,
                codigoslibros.plus,
                codigoslibros.codigo
            FROM codigoslibros
            JOIN libro ON libro.idlibro = codigoslibros.libro_idlibro
            JOIN asignatura ON asignatura.idasignatura = libro.asignatura_idasignatura
            LEFT JOIN libros_series ls ON ls.idLibro = libro.idlibro
            LEFT JOIN libros_series l_plus ON l_plus.idLibro = ls.id_libro_plus
            LEFT JOIN libro lib_plus ON ls.id_libro_plus = lib_plus.idlibro
            LEFT JOIN asignatura a_plus ON lib_plus.asignatura_idasignatura = a_plus.idasignatura
            WHERE codigoslibros.id_periodo = '$periodo'
            AND codigoslibros.idusuario = '$id'
            AND (
                    codigoslibros.estado_liquidacion <> 3
                    OR codigoslibros.quitar_de_reporte = '1'
                )
            AND codigoslibros.estado <> 2
            AND (codigoslibros.plus IS NULL OR codigoslibros.plus <> 1);
        ");
        if(!empty($auxlibros)){
            foreach ($auxlibros as $key => $value) {
                $nivel = $value->nivel_idnivel;
                $free = DB::SELECT("SELECT l.*, a.*,s.nombre_serie as serieCodigo,
                ls.year as anio, ls.updated_at  as fechaUpdate
                 FROM free_estudiante_libro f
                JOIN libro l ON f.libro_id  = l.idlibro
                JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
                LEFT JOIN libros_series ls ON ls.idLibro = l.idlibro
                LEFT JOIN series s ON ls.id_serie = s.id_serie
                WHERE f.institucion_id = '$institucion'
                AND f.nivel_id = '$nivel'
                ");
                 foreach ($free as $keyl => $valuel) {
                    array_push($auxlibros, $valuel);
                }
            }
        }
        $datos = [];
        foreach ($auxlibros as $key => $item) {
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
            $mostratCode = "";
            if(isset($item->codigo)){
                $mostratCode  = $item->codigo;
            }
            if($grupo == 10){
                $datos[$key] = (Object)[
                    "idlibro"                   => $item->idlibro,
                    "nombrelibro"               => $item->nombrelibro,
                    "descripcionlibro"          => $item->descripcionlibro,
                    "serie"                     => $item->serieCodigo,
                    "anio"                      => $item->anio,
                    "fechaUpdate"               => $item->fechaUpdate,
                    "codigo"                    => $mostratCode,
                    "titulo"                    => $item->titulo,
                    "portada"                   => $portada,
                    "weblibro"                  => $weblibro,
                    "pdfsinguia"                => $pdfsinguia,
                    "pdfconguia"                => $pdfconguia,
                    "guiadidactica"             => $guiadidactica,
                    "Estado_idEstado"           => $item->Estado_idEstado,
                    "asignatura_idasignatura"   => $item->asignatura_idasignatura,
                    "ziplibro"                  => $item->ziplibro,
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
                    "created_at"                => $item->created_at,
                    "updated_at"                => $item->updated_at,
                    "plus"                      => $item->plus ?? 0,
                    "id_folleto"                => $item->id_folleto ?? null,
                ];
            }else{
                $datos[$key] =(Object)[
                    "idlibro"                   => $item->idlibro,
                    "nombrelibro"               => $item->nombrelibro,
                    "descripcionlibro"          => $item->descripcionlibro,
                    "serie"                     => $item->serieCodigo,
                    "anio"                      => $item->anio,
                    // "fechaUpdate"               => $item->fechaUpdate,
                    // "codigo"                    => $mostratCode,
                    "titulo"                    => $item->titulo,
                    "portada"                   => $portada,
                    "weblibro"                  => $weblibro,
                    "pdfsinguia"                => $pdfsinguia,
                    "pdfconguia"                => $pdfconguia,
                    "guiadidactica"             => $guiadidactica,
                    "Estado_idEstado"           => $item->Estado_idEstado,
                    "asignatura_idasignatura"   => $item->asignatura_idasignatura,
                    "ziplibro"                  => $item->ziplibro,
                    "libroFechaModificacion"    => $item->libroFechaModificacion,
                    "grupo"                     => $item->grupo,
                    "puerto"                    => $item->puerto,
                    // "creado_at"                 => $item->creado_at,
                    // "actualizado_at"            => $item->actualizado_at,
                    "idasignatura"              => $item->idasignatura,
                    "nombreasignatura"          => $item->nombreasignatura,
                    "area_idarea"               => $item->area_idarea,
                    "nivel_idnivel"             => $item->nivel_idnivel,
                    "tipo_asignatura"           => $item->tipo_asignatura,
                    "estado"                    => $item->estado,
                    "plus"                      => $item->plus ?? 0,
                    "id_folleto"                => $item->id_folleto ?? null,
                    // "created_at"                => $item->created_at,
                    // "updated_at"                => $item->updated_at,
                ];
            }
        }
        $data = [];
        $auxlibrosf = $datos; // libros iniciales

        foreach ($auxlibrosf as $item) {
            $idFolleto = $item->id_folleto ?? null;

            if ($idFolleto) {
                $folletoResult = DB::SELECT("
                    SELECT l.*, a.*, s.nombre_serie AS serie
                    FROM libro l
                    LEFT JOIN libros_series ls ON ls.idLibro = l.idlibro
                    LEFT JOIN asignatura a ON a.idasignatura = l.asignatura_idasignatura
                    LEFT JOIN series s ON s.id_serie = ls.id_serie
                    WHERE l.idlibro = ?
                ", [$idFolleto]);

                if (!empty($folletoResult)) {
                    $auxlibrosf[] = $folletoResult[0];
                }
            }
        }

        // ✅ Eliminar duplicados por idlibro
        $librosUnicos = [];
        foreach ($auxlibrosf as $libro) {
            $librosUnicos[$libro->idlibro] = $libro; // usa el idlibro como clave
        }
        $auxlibrosf = array_values($librosUnicos); // reindexar

        // ✅ Ordenar alfabéticamente por nombrelibro
        usort($auxlibrosf, function($a, $b) {
            return strcmp($a->nombrelibro, $b->nombrelibro);
        });

        $data = [
            'libros' => $auxlibrosf,
            'nivel' => $nivel,
            'institucion' => $institucion,
        ];


        return $data;

    }

    public function store(Request $request)
    {
        $validacion = DB::SELECT("SELECT *
        FROM  codigoslibros WHERE
        codigo = ?",["$request->codigo"]);
        $iduser = '';
        foreach ($validacion as $key => $value) {
            $iduser             = $value->idusuario;
            $estadoCodigo       = $value->estado;
            $estado_liquidacion = $value->estado_liquidacion;
            $prueba_diagnostica = $value->prueba_diagnostica;
            $quitar_de_reporte  = $value->quitar_de_reporte;
        }
        //para obtener los datos del estudiante para abrir el ticket
        $datosEstudiante = DB::SELECT("SELECT CONCAT(e.nombres,' ', e.apellidos)
            as estudiante ,e.name_usuario,e.cedula,e.idusuario, i.nombreInstitucion
            FROM usuario e, institucion i
            WHERE e.id_group = '4'
            AND e.idusuario = $request->idusuario
            AND e.institucion_idInstitucion = i.idInstitucion
        ");
        //para ver cuantos tickets abiertos tiene el usuario
        $cantidadTicketOpen = DB::SELECT("SELECT t.* FROM tickets t
         WHERE t.usuario_id = $request->idusuario
         AND t.estado = '1'
         AND t.ticket_asesor = '0'
        ");
        $realizarTicket = "no";
        if(empty($cantidadTicketOpen)){
            $realizarTicket = "ok";
        }
        //EL CODIGO NO EXISTE
        if(empty($validacion)){
            $data = [
                'status'            => '2',
                'codigo'            => $request->codigo,
                'institucion'       => $request->id_institucion,
                'usuario'           => $request->idusuario,
                'datosEstudiante'   => $datosEstudiante,
                'realizarTicket'    => $realizarTicket,
            ];
            return $data;
        }
        //validar que el codigo no sea de diagnostico
        if($prueba_diagnostica == 1){
            return ["status" => '5' ,"message" => "El código que esta ingresando, es una Prueba Diagnóstica, no tiene libro digital."];
        }
        //para mandar los codigos que esten bloqueados
        else if($estadoCodigo == '2'){
            $data = [
                'status'            => '3',
                'codigo'            => $request->codigo,
                'institucion'       => $request->id_institucion,
                'usuario'           => $request->idusuario,
                'datosEstudiante'   => $datosEstudiante,
                'realizarTicket'    => $realizarTicket,
            ];
            return $data;
        }
        //para mandar los codigos que esten devueltos
        else if(($estado_liquidacion == '3' || $estado_liquidacion == '4') && $quitar_de_reporte == '0'){
            //si esta devuelto por yaneth  no mandar ticket
            $data = [
                'status'            => '4',
                'codigo'            => $request->codigo,
                'institucion'       => $request->id_institucion,
                'usuario'           => $request->idusuario,
                'datosEstudiante'   => $datosEstudiante,
                'realizarTicket'    => $realizarTicket,
            ];
            return $data;
        }
        else{
            $institucion = $request->id_institucion;
            if(empty($iduser) || $iduser == 0 || $iduser == NULL ){
                ///Para buscar el periodo
                $verificarperiodoinstitucion = DB::table('periodoescolar_has_institucion')
                ->select('periodoescolar_has_institucion.periodoescolar_idperiodoescolar')
                ->where('periodoescolar_has_institucion.institucion_idInstitucion','=',$request->id_institucion)
                ->get();
                foreach($verificarperiodoinstitucion  as $clave=>$item){
                    $verificarperiodos =DB::SELECT("SELECT p.idperiodoescolar
                    FROM periodoescolar p
                    WHERE p.estado = '1'
                    and p.idperiodoescolar = $item->periodoescolar_idperiodoescolar
                    ");
                }
                if(count($verificarperiodoinstitucion) <=0){
                    return ["status"=>"0", "message" => "No existe el periodo lectivo por favor, asigne un periodo a esta institucion"];
                }
                    //verificar que el periodo exista
                if(count($verificarperiodos) <= 0){
                    return ["status"=>"0", "message" => "No existe el periodo lectivo por favor, asigne un periodo a esta institucion"];
                }
                //fin de busqueda del periodo
                //almancenar el periodo
                $periodo =  $verificarperiodos[0]->idperiodoescolar;
                DB::INSERT("INSERT INTO hist_codlibros(id_usuario, codigo_libro, idInstitucion, usuario_editor, observacion,id_periodo) VALUES ($request->idusuario, '$request->codigo', $request->idusuario, $request->id_institucion, 'registrado',$periodo)");
                $contenido = CodigosLibros::find($request->codigo)->update(
                    [
                        'idusuario' => $request->idusuario,
                        'id_periodo' => $periodo
                    ]
                );
                $data = [
                    'status' => '1',
                ];
                return $data;
            }else{
                $data = [
                    'status' => '0',
                    'codigo' => $request->codigo,
                    'institucion' => $request->id_institucion,
                    'usuario' => $request->idusuario,
                    'datosEstudiante' => $datosEstudiante,
                    'realizarTicket' => $realizarTicket,
                ];
                return $data;
            }
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
        $codigos_libros = DB::SELECT("SELECT * from codigoslibros WHERE libro = '$id'");
        return $codigos_libros;
    }



    public function codigosLibrosFecha($datos)
    {
        $data = explode("*", $datos);

        if( $data[0] != "" ){
            $libro = $data[0];
            $fecha = $data[1];

            $codigos_libros = DB::SELECT("SELECT * from codigoslibros WHERE libro = '$libro' AND created_at like '$fecha%' ORDER BY `codigoslibros`.`fecha_create` ASC");

            return $codigos_libros;

        }else{
            return 0;
        }

    }


    public function librosBuscar(){
        $codigos_libros = DB::SELECT("SELECT id_libro_serie as id, nombre as label from libros_series");
        return $codigos_libros;
    }




    public function codigosLibrosExportados($data){
        $datos = explode("*", $data);
        $usuario = $datos[0];
        $cantidad = $datos[1];

        $codigos_libros = DB::SELECT("SELECT * from codigoslibros WHERE idusuario = '$usuario' ORDER BY fecha_create DESC LIMIT $cantidad");

        return $codigos_libros;
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
        //
    }
    //api:get/getEstudianteCodigos
    public function getEstudianteCodigos($data){
        $datos = explode("*", $data);
        $periodo     = $datos[0];
        $institucion = $datos[1];
        $query = DB::SELECT("SELECT c.idusuario,
        CONCAT(u.nombres,' ',u.apellidos) AS estudiante,
          c.codigo,l.nombrelibro
         FROM codigoslibros c
        LEFT JOIN usuario u ON c.idusuario = u.idusuario
        LEFT JOIN libro l ON l.idlibro = c.libro_idlibro
        WHERE u.institucion_idInstitucion ='$institucion'
        AND c.id_periodo = '$periodo'
        AND c.estado <> '2'
        ");
        return $query;
    }

    public function codigos_from_historico($codigo)  {
        $dato = DB::table('hist_codlibros as hc')
        ->leftjoin('usuario as u','hc.idInstitucion','=','u.idusuario')
        ->leftjoin('institucion as i','hc.usuario_editor','=','i.idInstitucion')
        ->leftJoin('periodoescolar as p','hc.id_periodo','=','p.idperiodoescolar')
        ->where('hc.codigo_libro','like','%'.$codigo.'%')
        ->select('hc.*','u.idusuario','u.nombres','u.apellidos','i.nombreInstitucion','p.periodoescolar')
        ->get();
        return $dato;
    }
}
