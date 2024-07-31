<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LinkAcortador;
use Illuminate\Http\Request;
use URL;
use DB;

class LinkAcortadorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $url =  URL::to('/');
        $links = DB::SELECT("SELECT l.*,
        CONCAT(u.nombres, ' ',u.apellidos) as editor, li.nombrelibro, ar.nombrearea
        FROM links_acortadores l
        LEFT JOIN usuario u ON l.usuario_editor = u.idusuario
        LEFT JOIN libro li ON li.idlibro = l.libro_id
        LEFT JOIN asignatura a ON li.asignatura_idasignatura = a.idasignatura
        LEFT JOIN area ar ON a.area_idarea = ar.idarea
        ORDER BY id DESC
        ");
       return $links;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $serve = $request->url();
        //$serve = "http://localhost:9000/p";
        $serve = "https://cal.lat/p";
        if(($request->id) > 0){
            //EDITAR LINK
            $link = LinkAcortador::findOrFail($request->id);
            $link->link_original  = $request->link_original;
            $link->usuario_editor = $request->idusuario;
            $link->pagina         = $request->pagina;
            $link->area_id        = $request->area_id;
            $link->libro_id       = $request->libro_id;
            $link->unidad         = $request->unidad;
            $link->save();
            if($link){
                return ["status" => "1","message" => "Se actualizo correctamente"];
            }else{
                return ["status" => "0","message" => "No se pudo actualizar"];
            }
        }else{
            //GUARDAR LINK
            $link = new LinkAcortador();
            $link->link_original  = $request->link_original;
            $link->usuario_editor = $request->idusuario;
            $link->pagina         = $request->pagina;
            $link->area_id        = $request->area_id;
            $link->libro_id       = $request->libro_id;
            $link->unidad         = $request->unidad;
            //generar link
            // $codigo = uniqid();
            $codigo               = LinkAcortador::generateUniqueCode();
            $url                  = $serve.'/'.$codigo;
            $link->codigo         = $codigo;
            $link->link_acortado  = $url;
            $link->save();
            return $link->link_acortado;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //buscar link del acortardor
        $link  = DB::SELECT("SELECT l.*
        FROM links_acortadores l
        WHERE l.codigo = '$id'
        ");
        if(count($link)>0){
            $getLink = $link[0]->link_original;
            return redirect($getLink);
        }else{
            return ["status" => "0", "message" => "El link no existe"];
        }

    }

    public function filtroLibros(Request $request){
        $filtrar = DB::SELECT("SELECT l.* FROM libro l
        LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
        LEFT JOIN area ar ON a.area_idarea = ar.idarea
        WHERE  a.area_idarea = '$request->area'
        AND  a.tipo_asignatura = '1'
        AND a.estado = '1'
        ");
        return $filtrar;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $eliminar  = LinkAcortador::findOrFail($id)->delete();
        return "se elimino correctamente";
    }
}
