<?php

namespace App\Http\Controllers;

use App\Models\Bodegas;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BodegasController extends Controller
{
    public function GetBodega(){
        $query = DB::SELECT("SELECT b.*, CONCAT(u.nombres,' ', u.apellidos) AS nombreResponsable 
        FROM bodegas b LEFT JOIN usuario u ON b.bod_responsable = u.idusuario 
        WHERE b.bod_estado = 1
        ORDER BY b.bod_id DESC limit 10");
        return $query;
    }

    public function GetUserBodega(){
        $query = DB::SELECT("SELECT *,CONCAT(u.nombres,' ', u.apellidos) AS nombreResponsable FROM usuario u WHERE u.id_group = 27");
        return $query;
    }

    public function GetBodega_inactiva(){
        $query = DB::SELECT("SELECT b.*, CONCAT(u.nombres,' ', u.apellidos) AS nombreResponsable 
        FROM bodegas b LEFT JOIN usuario u ON b.bod_responsable = u.idusuario
        WHERE b.bod_estado = 0
        ORDER BY b.bod_nombre ASC");
        return $query;
    }

    public function GetBodega_xfiltro(Request $request){
        if ($request->busqueda == 'nombre') {
            $query = DB::SELECT("SELECT b.*, CONCAT(u.nombres,' ', u.apellidos) AS nombreResponsable
            FROM bodegas b LEFT JOIN usuario u ON b.bod_responsable = u.idusuario
            WHERE b.bod_nombre LIKE '%$request->razonbusqueda%'
            AND b.bod_estado = 1
            ");
            return $query;
        }
        if ($request->busqueda == 'undefined' || $request->busqueda == '' || $request->busqueda == null) {
            $query = DB::SELECT("SELECT b.*, CONCAT(u.nombres,' ', u.apellidos) AS nombreResponsable
            FROM bodegas b LEFT JOIN usuario u ON b.bod_responsable = u.idusuario
            WHERE b.bod_nombre LIKE '%$request->razonbusqueda%'
            AND b.bod_estado = 1
            ");
            return $query;
        }
        
        // if ($request->busqueda == 'inactivo') {
        //     $query = DB::SELECT("SELECT * FROM bodegas 
        //     WHERE bod_estado = 0");
        //     return $query;
        // }
    }

    public function PostBodega_Registrar_modificar(Request $request)
    {
        if($request->bod_id){
       
        $bodega = Bodegas::findOrFail($request->bod_id);
        $bodega->bod_responsable = $request->bod_responsable;
        $bodega->bod_nombre = $request->bod_nombre;
        $bodega->bod_ubicacion = $request->bod_ubicacion;
        $bodega->updated_at = now();

       }else{
           $bodega = new Bodegas;
           $bodega->user_created = $request->user_created;
           $bodega->bod_responsable = $request->bod_responsable;
           $bodega->bod_nombre = $request->bod_nombre;
           $bodega->bod_ubicacion = $request->bod_ubicacion;
           $bodega->created_at = now();
           $bodega->updated_at = now();
           //$area->tipoareas_idtipoarea = $request->idtipoarea;
       }
       $bodega->save();
       if($bodega){
           return $bodega;
       }else{
           return "No se pudo guardar/actualizar";
       }
    }

    
    public function Desactivar_Bodega(Request $request)
    {
        if ($request->bod_id) {
            $bodega = Bodegas::find($request->bod_id);

            if (!$bodega) {
                return "El bod_id no existe en la base de datos";
            }

            $bodega->bod_estado = $request->bod_estado;
            $bodega->save();

            return $bodega;
        } else {
            return "No está ingresando ningún bod_id";
        }
    }

    public function Eliminar_Bodega(Request $request)
    {
        if ($request->bod_id) {
            $bodega = Bodegas::find($request->bod_id);

            if (!$bodega) {
                return "El bod_id no existe en la base de datos";
            }

           
            $bodega->delete();
            // Reajustar el autoincremento - estas 2 lineas permite reajustar el autoincrementable por defecto 
            $ultimoId  = Bodegas::max('bod_id') + 1;
            DB::statement('ALTER TABLE bodegas AUTO_INCREMENT = ' . $ultimoId);

            return $bodega;
        } else {
            return "No está ingresando ningún bod_id";
        }
        

    }

}
