<?php

namespace App\Http\Controllers;


use App\Models\MotivoRemision;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MotivoRemisionController extends Controller
{
    //
    public function GetMotivo(){
        $query = DB::SELECT("SELECT * FROM 1_4_remision_motivo  ORDER BY mot_id asc");
        return $query;
    }
    public function SearchMotivo(Request $request){
        if($request->mot_nombre){
            $query = DB::SELECT("SELECT mot_id, mot_nombre FROM 1_4_remision_motivo  where mot_nombre like'%$request->mot_nombre%' ORDER BY mot_id asc LIMIT 1");
            return $query;
        }else{
            return "No existe registro";
       }

    }
    public function PostMotivo_Registrar_modificar(Request $request)
    {
        if($request->mot_id){
       
        $motivo = MotivoRemision::findOrFail($request->mot_id);
        
        $motivo->mot_nombre = $request->mot_nombre;
        $motivo->mot_observacion = $request->mot_observacion;
        $motivo->updated_at = now();

       }else{
           $motivo = new MotivoRemision;
            
            $motivo->mot_nombre = $request->mot_nombre;
            $motivo->mot_observacion = $request->mot_observacion;
            $motivo->created_at = $request->created_at;
            $motivo->user_created = $request->user_created;            
            $motivo->updated_at = now();
        //$area->tipoareas_idtipoarea = $request->idtipoarea;
       }
       $motivo->save();
       if($motivo){
           return $motivo;
       }else{
           return "No se pudo guardar/actualizar";
       }
    }

    
    public function Desactivar_Motivo(Request $request)
    {
        if ($request->mot_id) {
            $motivo = MotivoRemision::find($request->mot_id);

            if (!$motivo) {
                return "El mot_id no existe en la base de datos";
            }

            $motivo->mot_estado = $request->mot_estado;
            $motivo->save();

            return $motivo;
        } else {
            return "No está ingresando ningún mot_id";
        }
    }

    public function Eliminar_Motivo(Request $request)
    {
        if ($request->mot_id) {
            $motivo = MotivoRemision::find($request->mot_id);

            if (!$motivo) {
                return "El mot_id no existe en la base de datos";
            }

            $motivo->delete();
            
            // Reajustar el autoincremento - estas 2 lineas permite reajustar el autoincrementable por defecto 
            $ultimoId  = MotivoRemision::max('mot_id') + 1;
            DB::statement('ALTER TABLE 1_4_remision_motivo AUTO_INCREMENT = ' . $ultimoId);

            return $motivo;
        } else {
            return "No está ingresando ningún mot_id";
        }
        

    }
}
