<?php

namespace App\Http\Controllers;

use App\Models\_14TipoEmpaque;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class _14TipoEmpaqueController extends Controller
{
    public function Get_TipoEmpaqueContador(Request $request){
        $valormasuno = $request->conteo+1;
        $query = DB::SELECT("SELECT tip_empa_codigo FROM 1_4_tipo_empaque LIMIT $valormasuno");
        $conteogeneral = count($query);
        if($conteogeneral<$valormasuno){
            return response()->json(['mensaje' => 'data_menor', 'conteo' => $conteogeneral]);
        }else if($conteogeneral==$valormasuno){
            return response()->json(['mensaje' => 'data_igual', 'conteo' => $conteogeneral]);
        }
    }
    
    public function Get_TipoEmpaque(){
        $query = DB::SELECT("SELECT t.*, t.tip_empa_codigo as codigoanterior FROM 1_4_tipo_empaque t ORDER BY created_at ASC");
        return $query;
    }

    public function GetTipoEmpaque_xfiltro(Request $request){
        if ($request->busqueda == 'undefined' || $request->busqueda == 'codigoempa' || $request->busqueda == '' || $request->busqueda == null) {
            $query = DB::SELECT("SELECT t.*, t.tip_empa_codigo as codigoanterior FROM 1_4_tipo_empaque t
            WHERE t.tip_empa_codigo LIKE '%$request->razonbusqueda%'
            ORDER BY created_at ASC
            ");
            return $query;
        }
        if ($request->busqueda == 'nombreempa') {
            $query = DB::SELECT("SELECT t.*, t.tip_empa_codigo as codigoanterior FROM 1_4_tipo_empaque t
            WHERE t.tip_empa_nombre LIKE '%$request->razonbusqueda%'
            ORDER BY created_at ASC
            ");
            return $query;
        }
    }

    //PARA EDITAR EL CODIGO DE TIPO EMPAQUE
    // public function Post_Registrar_modificar_tipo_empaque(Request $request)
    // {
    //     // Buscar el tipo_empaque por su tip_empa_codigo o crear uno nuevo
    //     $tipo_empaque = _14TipoEmpaque::firstOrNew(['tip_empa_codigo' => $request->codigoanterior]);
    //     // Asignar los demás datos del tipo_empaque
    //     $tipo_empaque->tip_empa_peso = $request->tip_empa_peso;
        
    //     // Verificar si es un nuevo registro o una actualización
    //     if ($tipo_empaque->exists){
    //         // Si ya existe, omitir el campo user_created para evitar que se establezca en null
    //         $tipo_empaque = _14TipoEmpaque:: findOrFail($request -> codigoanterior);
    //         $tipo_empaque -> delete ();
    //         $tipo_empaque->tip_empa_codigo = $request->tip_empa_codigo;
    //         $tipo_empaque->tip_empa_peso = $request->tip_empa_peso;
    //         $tipo_empaque->user_created = $request->user_created;
    //         $tipo_empaque->updated_at = now();
    //         // Guardar el tipo_empaque sin modificar user_created
    //         $tipo_empaque->save();
    //     } else {
    //         // Si es un nuevo registro, establecer user_created y updated_at
    //         $tipo_empaque->updated_at = now();
    //         $tipo_empaque->tip_empa_codigo = $request->tip_empa_codigo;
    //         $tipo_empaque->user_created = $request->user_created;
    //         $tipo_empaque->save();
    //     }

    //     // Verificar si el producto se guardó correctamente
    //     if ($tipo_empaque->wasRecentlyCreated || $tipo_empaque->wasChanged()) {
    //         return "Se guardó correctamente";
    //     } else {
    //         return "No se pudo guardar/actualizar";
    //     }
    // }

    public function Post_Registrar_modificar_tipo_empaque(Request $request)
    {
        // Buscar el tipo_empaque por su tip_empa_codigo o crear uno nuevo
        $tipo_empaque = _14TipoEmpaque::firstOrNew(['tip_empa_codigo' => $request->tip_empa_codigo]);
        // Asignar los demás datos del tipo_empaque
        $tipo_empaque->tip_empa_peso = $request->tip_empa_peso;
        $tipo_empaque->tip_empa_nombre = $request->tip_empa_nombre;
        // Verificar si es un nuevo registro o una actualización
        if ($tipo_empaque->exists){
            // Si ya existe, omitir el campo user_created para evitar que se establezca en null
            $tipo_empaque->updated_at = now();
            // Guardar el tipo_empaque sin modificar user_created
            $tipo_empaque->save();
        } else {
            // Si es un nuevo registro, establecer user_created y updated_at
            $tipo_empaque->updated_at = now();
            $tipo_empaque->user_created = $request->user_created;
            $tipo_empaque->save();
        }

        // Verificar si el producto se guardó correctamente
        if ($tipo_empaque->wasRecentlyCreated || $tipo_empaque->wasChanged()) {
            return "Se guardó correctamente";
        } else {
            return "No se pudo guardar/actualizar";
        }
    }

    public function Desactivar_Tipo_Empaque(Request $request)
    {
        if ($request->tip_empa_codigo) {
            $tipo_empaque = _14TipoEmpaque::find($request->tip_empa_codigo);

            if (!$tipo_empaque) {
                return "El tip_empa_codigo no existe en la base de datos";
            }

            $tipo_empaque->tip_empa_estado = $request->tip_empa_estado;
            $tipo_empaque->save();

            return $tipo_empaque;
        } else {
            return "No está ingresando ningún tip_empa_codigo";
        }
    }

    public function Post_Eliminar_tipo_empaque(Request $request)
    {
        //return $request;
        //$Rol = Rol::destroy($request->id);
        $tipo_empaque = _14TipoEmpaque::findOrFail($request->tip_empa_codigo);
        $tipo_empaque->delete();
        return $tipo_empaque;
        //Esta función obtendra el id de la tarea que hayamos seleccionado y la borrará de nuestra BD
    }
}
