<?php

namespace App\Traits\Usuario;
use DB;
use App\Models\Usuario;
trait TraitUsuarioGeneral
{
    public function userxRolxInstitucion($rol,$institucion){
        $datos = "
        CONCAT(nombres,' ',apellidos) as nombre_completo,
        id_group,cedula,idusuario,institucion_idInstitucion
        ";
        $query = Usuario::where('id_group',$rol)
        ->select(DB::RAW($datos))
        ->where('institucion_idInstitucion', '=', $institucion)
        ->get();
        return $query;
    }
    public function userxRol($rol){
        $datos = "
        CONCAT(nombres,' ',apellidos) as nombre_completo,
        id_group,cedula,idusuario,institucion_idInstitucion
        ";
        $query = Usuario::where('id_group',$rol)
        ->select(DB::RAW($datos))
        ->where('estado_idEstado', '=', '1')
        ->OrderBy('idusuario','DESC')
        ->get();
        return $query;
    }
}
