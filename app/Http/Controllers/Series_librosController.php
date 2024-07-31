<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Libros_series;
use App\Traits\Codigos\TraitCodigosGeneral;

class Series_librosController extends Controller
{
    use TraitCodigosGeneral;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $libros_series = DB::SELECT("SELECT ls.*,
        l.nombrelibro,
        s.nombre_serie,
        s.longitud_numeros,
        s.longitud_letras,
        s.longitud_codigo
        from libros_series ls
        LEFT JOIN libro l ON ls.idLibro = l.idlibro
        LEFT JOIN series s ON ls.id_serie = s.id_serie
        WHERE l.Estado_idEstado = '1'
        ORDER BY ls.nombre ASC
        ");
        return $libros_series;
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $libros_series = DB::SELECT("SELECT ls.*, l.nombre_imprimir,
        l.nombre_imprimir as  nombrelibro
        from libros_series ls
        LEFT JOIN libro l ON ls.idLibro = l.idlibro
        WHERE id_serie = $id
        ");
        return $libros_series;
    }
    public function traerSeries(Request $request){
        if($request->docentes){
            //validar que libros a puesto el asesor para el docente
            $validate = DB::SELECT("SELECT * FROM docente_formulario_asignaturas a
            WHERE a.formulario_link_id = '$request->formulario_id'
            ");
            $data = [];
            foreach($validate as $key => $item){
                $libros_series = DB::SELECT("SELECT l.id_serie,l.idLibro,l.nombre,l.version, s.nombre_serie,a.idasignatura
                from libros_series l
                LEFT JOIN series s ON l.id_serie = s.id_serie
                LEFT JOIN libro li ON li.idlibro = l.idLibro
                LEFT JOIN asignatura a ON li.asignatura_idasignatura = a.idasignatura
                WHERE (l.version <> 'PLUS' OR l.version IS NULL)
                AND l.idLibro = '$item->libro_id'
                ");
                $data[$key] = [
                    "id_serie"      => $libros_series[0]->id_serie,
                    "idLibro"       => $libros_series[0]->idLibro,
                    "nombre"        => $libros_series[0]->nombre,
                    "nombre_serie" => $libros_series[0]->nombre_serie,
                    "idasignatura" => $libros_series[0]->idasignatura,
                ];
            }
            return $data;
        }else{
            $libros_series = DB::SELECT("SELECT l.id_serie,l.idLibro,l.year,a.nombreasignatura,l.nombre,l.version, s.nombre_serie,a.idasignatura
            from libros_series l
            LEFT JOIN series s ON l.id_serie = s.id_serie
            LEFT JOIN libro li ON li.idlibro = l.idLibro
            LEFT JOIN asignatura a ON li.asignatura_idasignatura = a.idasignatura
            WHERE (l.version <> 'PLUS' OR l.version IS NULL)
            ");
            return $libros_series;
        }

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
}
