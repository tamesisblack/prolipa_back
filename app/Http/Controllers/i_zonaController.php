<?php

namespace App\Http\Controllers;

use App\Models\i_zona;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class i_zonaController extends Controller
{
    public function GetZona_todo(){
        $query = DB::SELECT("SELECT * FROM i_zona");
        return $query;
    }
    public function zonas(){
        $query = DB::SELECT("SELECT * FROM i_zona zn");
        return $query;
    }

    public function PostRegistrar_modificar_i_zona(Request $request)
    {
       if($request->idzona){
        
        $zonas = i_zona::findOrFail($request->idzona);
        $zonas->zn_nombre = $request->zn_nombre;
       }else{
           $zonas = new i_zona;
           $zonas->zn_nombre = $request->zn_nombre;
           $zonas->user_created = $request->user_created;
       }
        $zonas->save();
        if($zonas){
           return $zonas;
       }else{
           return "No se pudo guardar/actualizar";
       }
    }

    public function Desactivar_i_zona(Request $request)
    {
        if ($request->idzona) {
            $zonas = i_zona::find($request->idzona);

            if (!$zonas) {
                return "El idzona no existe en la base de datos";
            }

            $zonas->zn_estado = $request->zn_estado;
            $zonas->save();

            return $zonas;
        } else {
            return "No está ingresando ningún idzona";
        }
    }

    public function Eliminar_i_zona(Request $request)
    {
        if ($request->idzona) {
            $zonas = i_zona::find($request->idzona);

            if (!$zonas) {
                return "El idzona no existe en la base de datos";
            }
           
            $zonas->delete();

            // Reajustar el autoincremento - estas 2 lineas permite reajustar el autoincrementable por defecto 
            $ultimoId  = i_zona::max('idzona') + 1;
            DB::statement('ALTER TABLE i_zona AUTO_INCREMENT = ' . $ultimoId);

            return $zonas;
        } else {
            return "No está ingresando ningún idzona";
        }
    }

    // public function GetTipoDocumentoParametro(Request $request ){ 
           
    //     if ($request->busqueda == 'nombre') {
    //         $query = DB::SELECT("SELECT * FROM i_zona
    //         WHERE zn_nombre LIKE '%$request->razonbusqueda%'");
    //         return $query;
    //     }
    //     if ($request->busqueda == 'undefined') {
    //         $query = DB::SELECT("SELECT * FROM i_zona");
    //         return $query;
    //     }
    // }
}
