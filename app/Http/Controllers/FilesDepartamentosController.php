<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FilesDepartamentos;
use Illuminate\Provider\Image;
use Illuminate\Support\Facades\File;
use DB;

class FilesDepartamentosController extends Controller
{
    public function index()
    {
        $departamentos = DB::SELECT("SELECT * FROM files_departamentos");
        return $departamentos;
    }

    public function ver_archivos_departamento($id_categoria)
    {
        $archivos = DB::SELECT("SELECT * FROM files_archivos fa, usuario u WHERE fa.id_usuario = u.idusuario AND fa.id_departamento = $id_categoria AND estado = 1 ORDER BY fa.created_at");
        return $archivos;
    }

    public function archivos_departamento_filtro($id_categoria, $fecha, $tipo)
    {
        if( $tipo != 'null'){
            $consulta_tipo = '';
            switch ($tipo) {
                case '1':
                    $consulta_tipo = " AND fa.nombre_archivo LIKE '%.xls%'";
                break;
                case '0':
                    $consulta_tipo = "";
                break;
                case '2':
                    $consulta_tipo = " AND fa.nombre_archivo LIKE '%.doc%'";
                break;
                case '3':
                    $consulta_tipo = " AND (fa.nombre_archivo LIKE '%.png%' OR fa.nombre_archivo LIKE '%.jpg%' OR fa.nombre_archivo LIKE '%.gif%')";
                break;
                case '4':
                    $consulta_tipo = " AND (fa.nombre_archivo LIKE '%.zip%' OR fa.nombre_archivo LIKE '%.rar%')";
                break;
                case '5':
                    $consulta_tipo = " AND (fa.nombre_archivo LIKE '%.mpeg%' OR fa.nombre_archivo LIKE '%.wav%' OR fa.nombre_archivo LIKE '%.mp4%' OR fa.nombre_archivo LIKE '%.wmv%')";
                break;
                case '6':
                    $consulta_tipo = " AND fa.nombre_archivo LIKE '%.pdf%'";
                break;
                default:
                    $consulta_tipo = " AND fa.nombre_archivo NOT LIKE '%.xls%' AND fa.nombre_archivo NOT LIKE '%.doc%' AND fa.nombre_archivo NOT LIKE '%.png%' AND fa.nombre_archivo NOT LIKE '%.jpg%' AND fa.nombre_archivo NOT LIKE '%.gif%' AND fa.nombre_archivo NOT LIKE '%.zip%' AND fa.nombre_archivo NOT LIKE '%.rar%' AND fa.nombre_archivo NOT LIKE '%.mpeg%' AND fa.nombre_archivo NOT LIKE '%.wav%' AND fa.nombre_archivo NOT LIKE '%.mp4%' AND fa.nombre_archivo NOT LIKE '%.wmv%' AND fa.nombre_archivo NOT LIKE '%.pdf%'";
                break;
            }

            if( $fecha != 'null' ){
                $archivos = DB::SELECT("SELECT * FROM files_archivos fa, usuario u WHERE fa.id_usuario = u.idusuario AND fa.id_departamento = $id_categoria AND fa.created_at LIKE '$fecha%' $consulta_tipo AND estado = 1 ORDER BY fa.created_at");
            }else{
                $archivos = DB::SELECT("SELECT * FROM files_archivos fa, usuario u WHERE fa.id_usuario = u.idusuario AND fa.id_departamento = $id_categoria $consulta_tipo AND estado = 1 ORDER BY fa.created_at");
            }
        }else{
            if( $fecha != 'null' ){
                $archivos = DB::SELECT("SELECT * FROM files_archivos fa, usuario u WHERE fa.id_usuario = u.idusuario AND fa.id_departamento = $id_categoria AND fa.created_at LIKE '$fecha%' AND estado = 1 ORDER BY fa.created_at");
            }else{
                $archivos = DB::SELECT("SELECT * FROM files_archivos fa, usuario u WHERE fa.id_usuario = u.idusuario AND fa.id_departamento = $id_categoria AND estado = 1 ORDER BY fa.created_at");
            }
        }

        return $archivos;

    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $archivo = new FilesDepartamentos();

        $file = $request->file('nombre_archivo');
        $ruta = public_path('departamentos');
        $fileName = uniqid().$file->getClientOriginalName();
        $file->move($ruta, $fileName);

        $archivo->nombre_archivo = $fileName;
        $archivo->id_departamento = $request->id_departamento;
        $archivo->id_usuario = $request->id_usuario;

        $archivo->save();
        return $archivo;
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function remover_archivo(Request $request)
    {
        $archivo = FilesDepartamentos::find($request->id_archivo);
        $archivo->estado = 0;
        $archivo->id_usuario = $request->id_usuario;
        $archivo->save();

        $ruta = public_path('departamentos/');
        if( file_exists($ruta.$request->nombre_archivo) ){
            unlink($ruta.$request->nombre_archivo);
        }
        return $archivo;
    }
}
