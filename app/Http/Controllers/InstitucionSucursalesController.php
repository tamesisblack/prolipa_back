<?php

namespace App\Http\Controllers;

use App\Models\InstitucionSucursales;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InstitucionSucursalesController extends Controller
{
    public function GetSucursalxPuntoVentaparametro(Request $request){
        $query = DB::SELECT("SELECT insu.*, i.nombreInstitucion FROM institucion_sucursales insu
        INNER JOIN institucion i ON insu.isuc_idInstitucion = i.idInstitucion
        WHERE insu.isuc_idInstitucion = $request->ID_INSTITUCION 
        ORDER BY insu.isuc_id ASC");
        return $query;
    }

    public function PostSucursalesInstitucion_Registrar_modificar(Request $request)
    {
       if($request->isuc_id){
       
        $sucursalinstitucion = InstitucionSucursales::findOrFail($request->isuc_id);
        // $sucursalinstitucion->isuc_idInstitucion = $request->isuc_idInstitucion;
        $sucursalinstitucion->isuc_nombre = $request->isuc_nombre;
        $sucursalinstitucion->isuc_correo = $request->isuc_correo;
        $sucursalinstitucion->isuc_telefono = $request->isuc_telefono;
        $sucursalinstitucion->isuc_ruc = $request->isuc_ruc;
        $sucursalinstitucion->isuc_direccion = $request->isuc_direccion;
        $sucursalinstitucion->updated_at = now();

       }else{
           $sucursalinstitucion = new InstitucionSucursales;
           $sucursalinstitucion->isuc_idInstitucion = $request->isuc_idInstitucion;
           $sucursalinstitucion->isuc_nombre = $request->isuc_nombre;
           $sucursalinstitucion->isuc_correo = $request->isuc_correo;
           $sucursalinstitucion->isuc_telefono = $request->isuc_telefono;
           $sucursalinstitucion->isuc_ruc = $request->isuc_ruc;
           $sucursalinstitucion->isuc_direccion = $request->isuc_direccion;
           $sucursalinstitucion->updated_at = now();
           $sucursalinstitucion->user_created = $request->user_created;
           //$area->tipoareas_idtipoarea = $request->idtipoarea;
       }
       $sucursalinstitucion->save();
       if($sucursalinstitucion){
           return $sucursalinstitucion;
       }else{
           return "No se pudo guardar/actualizar";
       }
    }

    public function Desactivar_SucursalesInstitucion(Request $request)
    {
        if ($request->isuc_id) {
            $sucursalinstitucion = InstitucionSucursales::find($request->isuc_id);

            if (!$sucursalinstitucion) {
                return "El isuc_id no existe en la base de datos";
            }

            $sucursalinstitucion->isuc_estado = $request->isuc_estado;
            $sucursalinstitucion->save();

            return $sucursalinstitucion;
        } else {
            return "No está ingresando ningún isuc_id";
        }
    }

    public function Post_Eliminar_SucursalesInstitucion(Request $request)
    {
        //return $request;
        //$Rol = Rol::destroy($request->id);
        $sucursalinstitucion = InstitucionSucursales::findOrFail($request->isuc_id);
        $sucursalinstitucion->delete();
        $ultimoId  = InstitucionSucursales::max('isuc_id') + 1;
        DB::statement('ALTER TABLE institucion_sucursales AUTO_INCREMENT = ' . $ultimoId);
        return $sucursalinstitucion;
        //Esta función obtendra el id de la tarea que hayamos seleccionado y la borrará de nuestra BD
    }
}
