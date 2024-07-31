<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;

class L_libreriasController extends Controller
{           
    public function GetAsignacion(Request $request){
        $query = DB::SELECT("SELECT i.nombreInstitucion,  u.iniciales, u.nombres, u.apellidos, ai.*
        FROM f_asesor_institucion ai
        LEFT JOIN institucion i ON ai.asin_idInstitucion = i.idInstitucion
        LEFT JOIN usuario u ON ai.asin_idusuario = u.idusuario
        WHERE u.idusuario = '$request->datoUsuario'");
        return $query;
    }  
    public function GetInstituciones(){
        $query = DB::SELECT("SELECT i.nombreInstitucion
        FROM f_asesor_institucion ai
        LEFT JOIN institucion i ON ai.asin_idInstitucion = i.idInstitucion
        LEFT JOIN usuario u ON ai.asin_idusuario = u.idusuario
        WHERE u.idusuario = '$request->datoUsuario'");
        return $query;
    }
    public function GetInstitucionesAsignadasParametro(Request $request ){ 
        $query = DB::SELECT("SELECT i.nombreInstitucion,  u.iniciales, u.nombres, u.apellidos, ai.*
        FROM f_asesor_institucion ai
        LEFT JOIN institucion i ON ai.asin_idInstitucion = i.idInstitucion
        LEFT JOIN usuario u ON ai.asin_idusuario = u.idusuario
        WHERE i.nombreInstitucion LIKE '%$request->razonbusqueda%'");
        return $query;
    }  
    
}
