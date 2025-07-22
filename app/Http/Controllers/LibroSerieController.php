<?php

namespace App\Http\Controllers;

use App\Models\LibroSerie;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Libro;
use App\Models\Series;
use Illuminate\Support\Facades\DB;


class LibroSerieController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       $librosSerie = LibroSerie::orderBy('id_libro_serie','desc')->get();

          $libro = Libro::all();
          $series = Series::all();

        $librosSerie = DB::table('libros_series')
      ->join('series', 'libros_series.id_serie', '=', 'series.id_serie')
      ->join('libro','libro.idlibro','=','libros_series.idLibro')
      ->select('libro.nombrelibro','libro.idlibro','series.nombre_serie','series.id_serie','libros_series.id_libro_serie','libros_series.iniciales', 'libros_series.codigo_liquidacion','libros_series.nombre','libros_series.version','libros_series.boton','libros_series.year','libros_series.estado')
     ->orderBy('id_libro_serie','desc')
      ->get();

       return  ['librosSerie' => $librosSerie, 'libroslista' => $libro, 'serieslista' => $series];

    }

    public function todoLibroSerie(){
        $librosSerie = LibroSerie::orderBy('id_libro_serie','desc')->get();
        return $librosSerie;
    }
    //para ver el libro serie especifico de un libro api:/verLibroSerie
    public function verLibroSerie(Request $request){
        $libro  = DB::select("SELECT ls.*, s.nombre_serie
        FROM libros_series ls , series s
        WHERE ls.idlibro = $request->idlibro
        and ls.id_serie = s.id_serie
        ");
        return $libro;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    $libro = Libro::all();
    $series = Series::all();
    return [
        'series' => $series,
       'libro' => $libro,

    ];


    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {



        if( $request->id ){
            $librosSerie = LibroSerie::find($request->id);
        }else{
            $librosSerie = new LibroSerie();
        }

        $librosSerie->idLibro = $request->idLibro;
        $librosSerie->id_serie = $request->id_serie;
        $librosSerie->iniciales = $request->iniciales;
        $librosSerie->codigo_liquidacion = $request->codigo_liquidacion;
        $librosSerie->nombre = $request->nombre;
        $librosSerie->year = $request->year;
        $librosSerie->version = $request->version2;
        $librosSerie->boton = "success";


        $librosSerie->save();

        return $librosSerie;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\LibroSerie  $libroSerie
     * @return \Illuminate\Http\Response
     */


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\LibroSerie  $libroSerie
     * @return \Illuminate\Http\Response
     */
    public function edit(LibroSerie $libroSerie)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\LibroSerie  $libroSerie
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LibroSerie $libroSerie)
    {
        $libroSerie->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\LibroSerie  $libroSerie
     * @return \Illuminate\Http\Response
     */
  public function destroy($id)
    {
        $libroSerie = LibroSerie::findOrFail($id);
        $libroSerie->delete();
        return response()->json($libroSerie);

    }



     public function desactivar(Request $request)
    {

        $libroSerie =  LibroSerie::findOrFail($request->get('id_libro_serie'));

        $libroSerie->estado = 0;
        $libroSerie->save();
        return response()->json($libroSerie);
    }

     public function activar(Request $request)
    {

        $libroSerie =  LibroSerie::findOrFail($request->get('id_libro_serie'));

        $libroSerie->estado = 1;
        $libroSerie->save();
        return response()->json($libroSerie);
    }
   // para la liquidacion por periodos
   public function liquidacionperiodo(Request $request){
    set_time_limit(6000000);
    ini_set('max_execution_time', 6000000);
    // return csrf_token();
    $id_periodo  = $request->id_periodo;
    $idinstitucion = $request->idinstitucion;
    $codigos_libros = DB::SELECT("SELECT ls.codigo_liquidacion AS codigo,
     COUNT(ls.codigo_liquidacion) AS cantidad, c.serie,
    c.libro_idlibro,ls.nombre as nombrelibro
        FROM codigoslibros c
        LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
        WHERE (c.bc_estado = '2' OR c.estado_liquidacion = '0' OR c.estado_liquidacion ='2')
        -- AND c.estado <> 2
        AND c.bc_periodo  = '$id_periodo'
        AND (c.bc_institucion       = '$idinstitucion' OR c.venta_lista_institucion = '$idinstitucion')
        AND ls.idLibro = c.libro_idlibro
        AND c.prueba_diagnostica = '0'
        GROUP BY ls.codigo_liquidacion,ls.nombre, c.serie,c.libro_idlibro
    ");
        return  $codigos_libros;
    }
    // $codigos_libros = DB::SELECT("SELECT ls.codigo_liquidacion AS codigo,  COUNT(ls.codigo_liquidacion) AS cantidad, c.serie,
    // c.libro_idlibro,ls.nombre as nombrelibro,i.nombreInstitucion,
    // CONCAT(v.nombres, ' ', v.apellidos) as asesor
    //     FROM codigoslibros c
    //     LEFT JOIN usuario u ON c.idusuario = u.idusuario
    //     LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
    //     LEFT JOIN institucion i ON i.idInstitucion = u.institucion_idInstitucion
    //     LEFT JOIN usuario v ON i.vendedorInstitucion = v.cedula
    //     WHERE (c.bc_estado = '2' OR c.estado_liquidacion = '0')
    //     AND c.estado <> 2
    //     AND c.bc_periodo  = '$id_periodo'
    //     AND c.bc_institucion = '$idinstitucion'
    //     AND ls.idLibro = c.libro_idlibro
    //    GROUP BY ls.codigo_liquidacion,ls.nombre, c.serie,c.libro_idlibro");
    //     return  $codigos_libros;
    // }

    //para el listado de bloqueo de codigos
    public function codigosBloqueados(Request $request){
        $codigosBloqueados = DB::select("SELECT h.id_codlibros, h.id_usuario, h.codigo_libro,
        h.idInstitucion,h.usuario_editor,h.observacion,h.id_periodo,
        u.nombres,apellidos, i.nombreInstitucion, p.descripcion
        FROM  hist_codlibros h
        LEFT JOIN usuario u ON u.idusuario = h.id_usuario
        LEFT JOIN institucion i ON i.idInstitucion = h.usuario_editor
        LEFT JOIN periodoescolar p ON p.idperiodoescolar = h.id_periodo
        WHERE h.id_usuario = '45017'
        ORDER BY id_codlibros DESC
        ");
        return $codigosBloqueados;

    }
}
