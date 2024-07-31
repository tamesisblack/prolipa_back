<?php

namespace App\Http\Controllers;

use App\Models\_14GrupoProducto;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class _14GrupoProductoController extends Controller
{
    public function GetGrupoProducto_todo(){
        $query = DB::SELECT("SELECT * FROM 1_4_grupo_productos ORDER BY gru_pro_codigo DESC");
        return $query;
    }
    public function GetGrupoProducto_limitado(){
        $query = DB::SELECT("SELECT * FROM 1_4_grupo_productos 
        WHERE gru_pro_codigo = 1 OR gru_pro_codigo = 3 OR gru_pro_codigo = 6 
        ORDER BY gru_pro_codigo DESC");
        return $query;
    }

    public function PostGrupoProducto_Registrar_modificar(Request $request)
    {
       if($request->gru_pro_codigo){
       
        $grupoproducto = _14GrupoProducto::findOrFail($request->gru_pro_codigo);
        $grupoproducto->gru_pro_nombre = $request->gru_pro_nombre;
        $grupoproducto->updated_at = now();

       }else{
           $grupoproducto = new _14GrupoProducto;
           $grupoproducto->gru_pro_nombre = $request->gru_pro_nombre;
           $grupoproducto->updated_at = now();
           $grupoproducto->user_created = $request->user_created;
           //$area->tipoareas_idtipoarea = $request->idtipoarea;
       }
       $grupoproducto->save();
       if($grupoproducto){
           return $grupoproducto;
       }else{
           return "No se pudo guardar/actualizar";
       }
    }

    public function Desactivar_GrupoProducto(Request $request)
    {
        if ($request->gru_pro_codigo) {
            $grupoproducto = _14GrupoProducto::find($request->gru_pro_codigo);

            if (!$grupoproducto) {
                return "El gru_pro_codigo no existe en la base de datos";
            }

            $grupoproducto->gru_pro_estado = $request->gru_pro_estado;
            $grupoproducto->save();

            return $grupoproducto;
        } else {
            return "No está ingresando ningún gru_pro_codigo";
        }
    }


}
