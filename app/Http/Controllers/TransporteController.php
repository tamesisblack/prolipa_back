<?php

namespace App\Http\Controllers;

use App\Models\Transporte;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransporteController extends Controller
{
    //
    public function Get_Transporte(){
        $query = DB::SELECT("SELECT * FROM 1_4_transporte  ORDER BY trans_codigo asc");
        return $query;
    }
    public function SearchTransporte(Request $request){
        if($request->trans_nombre){
            $query = DB::SELECT("SELECT trans_codigo, trans_nombre FROM 1_4_transporte  where trans_nombre like'%$request->trans_nombre%' limit 1");
            return $query;
        }else{
            return "No existe registro";
       }

    }
    public function GetTransporte_xfiltro(Request $request){
        if ($request->busqueda == 'codigo') {
            $query = DB::SELECT("SELECT * FROM 1_4_transporte
            WHERE trans_ruc LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
        if ($request->busqueda == 'undefined') {
            $query = DB::SELECT("SELECT * FROM 1_4_transporte 
            WHERE trans_ruc LIKE '%$request->razonbusqueda%'
           ");
            return $query;
        }
        
        if ($request->busqueda == 'nombre') {
            $query = DB::SELECT("SELECT * FROM 1_4_transporte
            WHERE trans_nombre LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
    }
    public function PostTransporte_Registrar_modificar(Request $request)
    {
        if($request->trans_codigo){
       
        $transporte = Transporte::findOrFail($request->trans_codigo);
        
        $transporte->trans_nombre = $request->trans_nombre;
        $transporte->trans_ruc = $request->trans_ruc;
        $transporte->trans_direccion = $request->trans_direccion;
        $transporte->trans_guia_remision = $request->trans_guia_remision;
        $transporte->updated_at = now();

       }else{
           $transporte = new Transporte;

            $transporte->trans_nombre = $request->trans_nombre;
            $transporte->trans_ruc = $request->trans_ruc;
            $transporte->trans_direccion = $request->trans_direccion;
            $transporte->trans_guia_remision = $request->trans_guia_remision;
            $transporte->updated_at = now();
            $transporte->user_created = $request->user_created;
           //$area->tipoareas_idtipoarea = $request->idtipoarea;
       }
       $transporte->save();
       if($transporte){
           return $transporte;
       }else{
           return "No se pudo guardar/actualizar";
       }
    }


    public function Eliminar_Transporte(Request $request)
    {
        if ($request->trans_codigo) {
            $transporte = Transporte::find($request->trans_codigo);

            if (!$transporte) {
                return "El trans_codigo no existe en la base de datos";
            }

           
            $transporte->delete();

            // Reajustar el autoincremento - estas 2 lineas permite reajustar el autoincrementable por defecto 
            $ultimoId  = Transporte::max('trans_codigo') + 1;
            DB::statement('ALTER TABLE 1_4_transporte AUTO_INCREMENT = ' . $ultimoId);

            return $transporte;
        } else {
            return "No está ingresando ningún trans_codigo";
        }
        

    }
}
