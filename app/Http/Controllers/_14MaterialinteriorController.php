<?php

namespace App\Http\Controllers;

use App\Models\_14Materialinterior;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class _14MaterialinteriorController extends Controller
{
    public function Get_Materialinterior(){
        $query = DB::SELECT("SELECT * FROM 1_4_cal_material_interior ORDER BY mat_in_nombre	ASC");
        return $query;
    }

    public function Get_Material_interiorxFiltro(Request $request){
        if ($request->busqueda == 'codigopro') {
            $query = DB::SELECT("SELECT * 
            FROM 1_4_cal_material_interior 
            WHERE mat_in_codigo 
            LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
        if ($request->busqueda == 'undefined') {
            $query = DB::SELECT("SELECT * 
            FROM 1_4_cal_material_interior 
            WHERE mat_in_codigo 
            LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
        if ($request->busqueda == 'nombres') {
            $query = DB::SELECT("SELECT * 
            FROM 1_4_cal_material_interior 
            WHERE mat_in_nombre 
            LIKE '%$request->razonbusqueda%'");
            return $query;
        }
    }

    public function Post_Registrar_modificar_material_interior(Request $request)
    {
        // Buscar el material_interior por su mat_in_codigo o crear uno nuevo
        $material_interior = _14Materialinterior::firstOrNew(['mat_in_codigo' => $request->mat_in_codigo]);
        // Asignar los demás datos del material_interior
        $material_interior->mat_in_nombre = $request->mat_in_nombre;
        $material_interior->mat_in_gramaje = $request->mat_in_gramaje;
        
        // Verificar si es un nuevo registro o una actualización
        if ($material_interior->exists) {
            // Si ya existe, omitir el campo user_created para evitar que se establezca en null
            $material_interior->updated_at = now();
            // Guardar el material_interior sin modificar user_created
            $material_interior->save();
        } else {
            // Si es un nuevo registro, establecer user_created y updated_at
            $material_interior->updated_at = now();
            $material_interior->user_created = $request->user_created;
            $material_interior->save();
        }
    
        // Verificar si el producto se guardó correctamente
        if ($material_interior->wasRecentlyCreated || $material_interior->wasChanged()) {
            return "Se guardó correctamente";
        } else {
            return "No se pudo guardar/actualizar";
        }
    }

    public function Desactivar_Material_interior(Request $request)
    {
        if ($request->mat_in_codigo) {
            $material_interior = _14Materialinterior::find($request->mat_in_codigo);

            if (!$material_interior) {
                return "El mat_in_codigo no existe en la base de datos";
            }

            $material_interior->mat_in_estado = $request->mat_in_estado;
            $material_interior->save();

            return $material_interior;
        } else {
            return "No está ingresando ningún mat_in_codigo";
        }
    }

    public function Post_Eliminar_material_interior(Request $request)
    {
        //return $request;
        //$Rol = Rol::destroy($request->id);
        $material_interior = _14Materialinterior::findOrFail($request->mat_in_codigo);
        $material_interior->delete();
        return $material_interior;
        //Esta función obtendra el id de la tarea que hayamos seleccionado y la borrará de nuestra BD
    }
}
