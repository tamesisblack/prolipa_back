<?php

namespace App\Http\Controllers;
use DB;
use App\Models\unidad;
use App\Models\Temas;
use Illuminate\Http\Request;

class UnidadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }
    public function libro_enUnidad()
    {
        $libros = DB::SELECT("SELECT l.idlibro, l.nombrelibro
        FROM libro l
        WHERE l.Estado_idEstado = '1'
        ORDER BY l.nombrelibro ASC");
        return $libros;
        // -- and l.asignatura_idasignatura = a.idasignatura
        // -- and a.area_idarea = ar.idarea
        // -- and ar.tipoareas_idtipoarea =  t.idtipoarea
        // -- and t.idtipoarea = '1'
    }
    public function unidadesX_Libro($id)
    {
        $unidades = DB::SELECT("SELECT ul.*
        FROM unidades_libros ul
        WHERE ul.id_libro = $id
        AND estado = '1'
        ");
        return $unidades;
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
    public function updateUnidades(Request $request){
        if( $request->id_unidad_libro ){
            $dato = unidad::find($request->id_unidad_libro);
        }else{
            $dato = new unidad();
        }
        $dato->id_libro = $request->id_libro;
        $dato->unidad = $request->unidad;
        $dato->nombre_unidad = $request->nombre_unidad;
        $dato->pag_inicio = $request->pag_inicio;
        $dato->pag_fin = $request->pag_fin;
        $dato->txt_nombre_unidad = $request->txt_nombre_unidad;
        $dato->estado = '1';
        $dato->save();
        // $datos = DB::UPDATE("UPDATE unidades_libros SET id_libro = $request->id_libro, unidad = $request->unidad, nombre_unidad = '$request->nombre_unidad', pag_inicio = $request->pag_inicio, pag_fin = $request->pag_fin, estado = $request->estado WHERE id_unidad_libro =  $request->id_unidad_libro");

        return $dato;

    }
    public function store(Request $request)
    {
        $datos = unidades_libros::where('id_unidad_libro', '=', $request->$id_unidad_libro)->first();

        $datos->update($request->all());
        return $datos;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\unidad  $unidad
     * @return \Illuminate\Http\Response
     */
    public function show(unidad $unidad)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\unidad  $unidad
     * @return \Illuminate\Http\Response
     */
    public function edit(unidad $unidad)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\unidad  $unidad
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, unidad $unidad)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\unidad  $unidad
     * @return \Illuminate\Http\Response
     */
    public function destroy(unidad $unidad)
    {
        //
    }
    public function buscar_id_unidad($id)
    {
        //buscar si existen temas registrados
        $dato = DB::SELECT("SELECT *
        FROM temas
        WHERE id_unidad = $id
        LIMIT 1");
        return $dato;
    }
    public function eliminar_id_unidad($unidad)
    {
        $dato = unidad::find( $unidad );
        $dato->delete();
        return $dato;
    }
    public function transferenciaUnidades(Request $request) {
        // Validar los datos recibidos
        $request->validate([
            'libro_a_transferir' => 'required|integer',
            'libro_recibir_transferencia' => 'required|integer',
        ]);
    
        // Eliminar unidades existentes asociadas al libro receptor
        unidad::where('id_libro', $request->libro_recibir_transferencia)->delete();
    
        // Buscar las unidades asociadas al libro que se va a transferir
        $unidades = unidad::where('id_libro', $request->libro_a_transferir)->get();
    
        // Recorrer las unidades y replicar la información en el libro receptor
        foreach ($unidades as $unidad) {
            $nuevaUnidad = new unidad();
            $nuevaUnidad->id_libro = $request->libro_recibir_transferencia;
            $nuevaUnidad->unidad = $unidad->unidad;
            $nuevaUnidad->nombre_unidad = $unidad->nombre_unidad;
            $nuevaUnidad->txt_nombre_unidad = $unidad->txt_nombre_unidad;
            $nuevaUnidad->pag_inicio = $unidad->pag_inicio;
            $nuevaUnidad->pag_fin = $unidad->pag_fin;
            $nuevaUnidad->estado = $unidad->estado;
            $nuevaUnidad->created_at = now(); // Establecer la fecha de creación
            $nuevaUnidad->updated_at = now(); // Establecer la fecha de actualización
            $nuevaUnidad->save(); // Guardar la nueva unidad
        }
    
        return response()->json(['message' => 'Transferencia de unidades completada exitosamente.'], 200);
    }
}
