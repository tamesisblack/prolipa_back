<?php

namespace App\Http\Controllers;

use App\Models\f_asignacion_asesor_institucion;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class f_asignacion_asesor_institucionController extends Controller
{
    // public function GetAserorInstitucion_todo(){
    //     $query = DB::SELECT("SELECT i.nombreInstitucion,  u.iniciales, u.nombres, u.apellidos, ai.*
    //     FROM f_asesor_institucion ai
    //     LEFT JOIN institucion i ON ai.asin_idInstitucion = i.idInstitucion
    //     LEFT JOIN usuario u ON ai.asin_idusuario = u.idusuario
    //     ORDER BY asin_id DESC");
    //     return $query;
    // }
    public function GetAserorInstitucion(Request $request){
        $query = DB::SELECT("SELECT i.nombreInstitucion,  u.iniciales, u.nombres, u.apellidos, ai.*
        FROM f_asesor_institucion ai
        LEFT JOIN institucion i ON ai.asin_idInstitucion = i.idInstitucion
        LEFT JOIN usuario u ON ai.asin_idusuario = u.idusuario
        WHERE ai.asin_idInstitucion = '$request->institucion_id'
        ORDER BY asin_id DESC");
        return $query;
    }
    public function GetAsesoresParametro(Request $request ){ 
        $query = DB::SELECT("SELECT i.nombreInstitucion,  u.iniciales, u.nombres, u.apellidos, ai.*
        FROM f_asesor_institucion ai
        LEFT JOIN institucion i ON ai.asin_idInstitucion = i.idInstitucion
        LEFT JOIN usuario u ON ai.asin_idusuario = u.idusuario
        WHERE i.nombreInstitucion LIKE '%$request->razonbusqueda%'");
        return $query;
    }
    public function GetInstitucionesParametro(Request $request ){ 
           
        if ($request->busqueda == 'institucion') {
            $query = DB::SELECT("SELECT i.nombreInstitucion,  u.iniciales, u.nombres, u.apellidos, ai.*
            FROM f_asesor_institucion ai
            LEFT JOIN institucion i ON ai.asin_idInstitucion = i.idInstitucion
            LEFT JOIN usuario u ON ai.asin_idusuario = u.idusuario
            WHERE i.nombreInstitucion LIKE '%$request->razonbusqueda%'");
            return $query;
        }
        if ($request->busqueda == 'asesor') {
            $query = DB::SELECT("SELECT i.nombreInstitucion,  u.iniciales, u.nombres, u.apellidos, ai.*
            FROM f_asesor_institucion ai
            LEFT JOIN institucion i ON ai.asin_idInstitucion = i.idInstitucion
            LEFT JOIN usuario u ON ai.asin_idusuario = u.idusuario
            WHERE CONCAT(u.nombres,' ', u.apellidos) LIKE '%$request->razonbusqueda%'");
            return $query;
        }        
        if ($request->busqueda == 'undefined') {
            $query = DB::SELECT("SELECT i.nombreInstitucion,  u.iniciales, u.nombres, u.apellidos, ai.*
            FROM f_asesor_institucion ai
            LEFT JOIN institucion i ON ai.asin_idInstitucion = i.idInstitucion
            LEFT JOIN usuario u ON ai.asin_idusuario = u.idusuario");
            return $query;
        }
    }

    public function Desactivar_asignacion(Request $request)
    {
        if ($request->asin_id) {
            $asignacion = f_asignacion_asesor_institucion::find($request->asin_id);

            if (!$asignacion) {
                return "El asin_id no existe en la base de datos";
            }

            $asignacion->asin_estado = $request->asin_estado;
            $asignacion->save();

            return $asignacion;
        } else {
            return "No está ingresando ningún asin_id";
        }
    }

    public function listaInsitucion_asignacionAsesor(){
        $query = DB::SELECT("SELECT i.idInstitucion,i.nombreInstitucion,
        i.estado_idEstado as estadoInstitucion,u.idusuario AS asesor_id,u.nombres AS nombre_asesor,
        u.apellidos AS apellido_asesor
        FROM institucion i
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        LEFT JOIN region r ON i.region_idregion = r.idregion
        LEFT JOIN usuario u ON i.vendedorInstitucion = u.cedula
        LEFT JOIN institucion_configuracion_periodo ic ON i.region_idregion = ic.region
        LEFT JOIN periodoescolar pec ON ic.periodo_configurado = pec.idperiodoescolar
        WHERE i.estado_idEstado = '1'
        ORDER BY i.fecha_registro DESC");
        return $query;
    }

    public function GetAsesor(){
        $query = DB::SELECT("SELECT u.idusuario, u.id_group, sgu.level,
        CONCAT(u.nombres,' ', u.apellidos) AS NombreApellidoAsesor
        FROM usuario u 
        LEFT JOIN sys_group_users sgu ON u.id_group = sgu.id
        WHERE u.id_group = 33");
        return $query;
    }

    public function PostRegistrar_modificar_asesor_institucion(Request $request)
    {
       if($request->asin_id){       
        $asignacion = f_asignacion_asesor_institucion::findOrFail($request->asin_id);
        $asignacion->asin_idInstitucion = $request->asin_idInstitucion;
        $asignacion->asin_idusuario = $request->asin_idusuario;
        //$asignacion->user_created = $request->user_created;
        //$asignacion->updated_at = $request->updated_at;
       }else{
           $asignacion = new f_asignacion_asesor_institucion;
           $asignacion->asin_idInstitucion = $request->asin_idInstitucion;
           $asignacion->asin_idusuario = $request->asin_idusuario;
           $asignacion->user_created = $request->user_created;
        //$asignacion->updated_at = $request->updated_at;
       }
        $asignacion->save();
        if($asignacion){
           return $asignacion;
       }else{
           return "No se pudo guardar/actualizar";
       }
    }

    public function Eliminar_asignacion(Request $request)
    {
        if ($request->asin_id) {
            $asignacion = f_asignacion_asesor_institucion::findOrFail($request->asin_id);

            if (!$asignacion) {
                return "El asin_id no existe en la base de datos";
            }
            $asignacion->delete();
            
            // Reajustar el autoincremento - estas 2 lineas permite reajustar el autoincrementable por defecto 
            $ultimoId  = f_asignacion_asesor_institucion::max('asin_id') + 1;
            DB::statement('ALTER TABLE f_asesor_institucion AUTO_INCREMENT = ' . $ultimoId);

            return $asignacion;
        } else {
            return "No está ingresando ningún asin_id";
        }
    }
    
    
}
