<?php

namespace App\Http\Controllers;

use App\Models\_14MaterialCubierta;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class _14MaterialCubiertaController extends Controller
{
    public function Get_Materialcubierta(){
        $query = DB::SELECT("SELECT * FROM 1_4_cal_material_cubierta ORDER BY mat_cub_nombre ASC");
        return $query;
    }

    public function Get_Material_cubiertaxFiltro(Request $request){
        if ($request->busqueda == 'codigopro') {
            $query = DB::SELECT("SELECT * 
            FROM 1_4_cal_material_cubierta 
            WHERE mat_cub_codigo 
            LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
        if ($request->busqueda == 'undefined') {
            $query = DB::SELECT("SELECT * 
            FROM 1_4_cal_material_cubierta 
            WHERE mat_cub_codigo 
            LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
        if ($request->busqueda == 'nombres') {
            $query = DB::SELECT("SELECT * 
            FROM 1_4_cal_material_cubierta 
            WHERE mat_cub_nombre 
            LIKE '%$request->razonbusqueda%'");
            return $query;
        }
    }

    public function Post_Registrar_modificar_material_cubierta(Request $request)
    {
        // Buscar el material_cubierta por su mat_cub_codigo o crear uno nuevo
        $material_cubierta = _14MaterialCubierta::firstOrNew(['mat_cub_codigo' => $request->mat_cub_codigo]);
        // Asignar los demás datos del material_cubierta
        $material_cubierta->mat_cub_nombre = $request->mat_cub_nombre;
        $material_cubierta->mat_cub_gramaje = $request->mat_cub_gramaje;
        
        // Verificar si es un nuevo registro o una actualización
        if ($material_cubierta->exists){
            // Si ya existe, omitir el campo user_created para evitar que se establezca en null
            $material_cubierta->updated_at = now();
            // Guardar el material_cubierta sin modificar user_created
            $material_cubierta->save();
        } else {
            // Si es un nuevo registro, establecer user_created y updated_at
            $material_cubierta->updated_at = now();
            $material_cubierta->user_created = $request->user_created;
            $material_cubierta->save();
        }

        // Verificar si el producto se guardó correctamente
        if ($material_cubierta->wasRecentlyCreated || $material_cubierta->wasChanged()) {
            return "Se guardó correctamente";
        } else {
            return "No se pudo guardar/actualizar";
        }
    }

    public function Desactivar_Material_cubierta(Request $request)
    {
        if ($request->mat_cub_codigo) {
            $material_cubierta = _14MaterialCubierta::find($request->mat_cub_codigo);

            if (!$material_cubierta) {
                return "El mat_cub_codigo no existe en la base de datos";
            }

            $material_cubierta->mat_cub_estado = $request->mat_cub_estado;
            $material_cubierta->save();

            return $material_cubierta;
        } else {
            return "No está ingresando ningún mat_cub_codigo";
        }
    }

    public function Post_Eliminar_material_cubierta(Request $request)
    {
        //return $request;
        //$Rol = Rol::destroy($request->id);
        $material_cubierta = _14MaterialCubierta::findOrFail($request->mat_cub_codigo);
        $material_cubierta->delete();
        return $material_cubierta;
        //Esta función obtendra el id de la tarea que hayamos seleccionado y la borrará de nuestra BD
    }
}
