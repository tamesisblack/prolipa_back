<?php

namespace App\Http\Controllers;

use App\Models\ProveedorOrdentrabajo;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProveedorOrdentrabajoController extends Controller
{
    //
    public function GetCiudades( Request $request){
        $query = DB::SELECT("SELECT idciudad, nombre FROM ciudad where provincia_idprovincia ='$request->nombre'");
        return $query;
    }
    public function Getprovincias(){
        $query = DB::SELECT("SELECT idprovincia, nombreprovincia FROM provincia");
        return $query;
    }
    public function Get_provedor(){
        $query = DB::SELECT("SELECT prov_codigo, prov_nombre FROM 1_4_proveedor");

             return $query;
    }
    public function Get_proveo(){
        $query = DB::SELECT("SELECT * FROM 1_4_proveedor as prov
        INNER JOIN ciudad as ciu on prov.ciu_codigo=ciu.idciudad
        inner join provincia as p on p.idprovincia=provincia_idprovincia");

             return $query;
    }
    public function PostProver_Registrar_modificar(Request $request)
    {
    if($request->prov_codigo){
        $provee = ProveedorOrdentrabajo::findOrFail($request->prov_codigo);
        $provee->ciu_codigo = $request->ciu_codigo;
        $provee->prov_nombre = $request->prov_nombre;
        $provee->prov_descripcion = $request->prov_descripcion;
        $provee->prov_direccion = $request->prov_direccion;
        $provee->prov_ruc = $request->prov_ruc;
        $provee->prov_telefono = $request->prov_telefono;

       }else{
           $provee = new ProveedorOrdentrabajo;
        $provee->ciu_codigo = $request->ciu_codigo;
        $provee->prov_nombre = $request->prov_nombre;
        $provee->prov_descripcion = $request->prov_descripcion;
        $provee->prov_direccion = $request->prov_direccion;
        $provee->prov_ruc = $request->prov_ruc;
        $provee->prov_telefono = $request->prov_telefono;

       }
       $provee->save();
       if($provee){
           return $provee;
       }else{
           return "No se pudo guardar/actualizar";
       }
    }
    public function Eliminar_Proveo(Request $request)
    {
        if ($request->prov_codigo) {
            $provee = ProveedorOrdentrabajo::find($request->prov_codigo);

            if (!$provee) {
                return "El prov_codigo no existe en la base de datos";
            }


            $provee->delete();

            return $provee;
        } else {
            return "No está ingresando ningún prov_codigo";
        }


    }


}
