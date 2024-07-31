<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
class GestionEstudianteController extends Controller
{
    public function cursosInstitucion(Request $request)
    {
        $docentes = DB::SELECT("SELECT * FROM usuario WHERE institucion_idinstitucion = ? AND id_group = ?",[66,6]);
        foreach ($docentes as $key => $value) {
            $data['items'][$key] = [
                'usuario' => $value,
                'cursos' => $this->cursosDocente($value->idusuario),
            ];
        }
        return $data;
    }
    public function cursosDocente($idusuario)
    {
        $cursos = DB::SELECT("SELECT * FROM curso WHERE idusuario = ?",[$idusuario]);
        foreach ($variable as $key => $value) {
            $data['items'][$key] = [
                'curso' => $value,
                'estudiantes' => $this->estudiantes($value->idusuario),
            ];
        }
        return $data;
    }
    public function estudiantes($codgio)
    {
        $estudiantes = DB::SELECT("SELECT * FROM curso WHERE codigo = ?",[$codgio]);
        return $estudiantes;
    }
    
}
