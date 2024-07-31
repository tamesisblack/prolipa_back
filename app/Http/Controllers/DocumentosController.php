<?php

namespace App\Http\Controllers;

use App\Models\Documentos;
use App\Models\DocumentosArchivo;
use App\Models\DocumentosAsignaturas;
use Illuminate\Http\Request;
use DB;

class DocumentosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $documentos=DB::SELECT('SELECT documentos.*,archivo.* FROM documentos join documentos_archivo on documentos_archivo.documento = documentos.id join archivo on archivo.id = documentos_archivo.archivo Where documentos.id = ?;',[$request->id]);
        return $documentos;
    }

    public function getDocumentosDocente(Request $request)
    {
        $documentos=DB::SELECT('CALL getDocumentos(?)',[$request->idasignatura]);
        return $documentos;
    }

    public function getDocumentos(Request $request)
    {
        $documentos=DB::SELECT('SELECT * FROM documentos');

        foreach ($documentos as $key => $value) {
            $asignaturas = DB::SELECT("SELECT asignatura.idasignatura as id,asignatura.nombreasignatura as label, asignatura.tipo_asignatura FROM documentos_asignatura join asignatura on asignatura.idasignatura = documentos_asignatura.idasignatura WHERE documentos_asignatura.iddocumento = ?",[$value->id]);
            $data['items'][$key] = [
                'id' => $value->id,
                'nombre' => $value->nombre,
                'descripcion'=>$value->descripcion,
                'estado'=>$value->estado,
                'status'=>$value->status,
                'updated_at'=>$value->updated_at,
                'created_at'=>$value->created_at,
                'asignaturas'=>$asignaturas,
            ];
        }
        return $data;
    }
    public function getDocumentosxID($id)
    {
        $documentos=DB::SELECT("SELECT * FROM documentos WHERE id = '$id' ");
        foreach ($documentos as $key => $value) {
            $asignaturas = DB::SELECT("SELECT asignatura.idasignatura as id,asignatura.nombreasignatura as label, asignatura.tipo_asignatura FROM documentos_asignatura join asignatura on asignatura.idasignatura = documentos_asignatura.idasignatura WHERE documentos_asignatura.iddocumento = ?",[$value->id]);
            $data['items'][$key] = [
                'id' => $value->id,
                'nombre' => $value->nombre,
                'descripcion'=>$value->descripcion,
                'estado'=>$value->estado,
                'status'=>$value->status,
                'updated_at'=>$value->updated_at,
                'created_at'=>$value->created_at,
                'asignaturas'=>$asignaturas,
            ];
        }
        return $data;
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
        if(empty($request->id)){
            $data = $request->all();
            $documento = new Documentos();
            $documento->nombre = $request->nombre;
            $documento->descripcion = $request->descripcion;
            $documento->status = $request->status;
            $documento->save();

            foreach ($data['files'] as $key => $value) {
                $dt = new DocumentosArchivo();
                $dt->documento = $documento->id;
                $dt->archivo = $value['id'];
                $dt->save();
            }

            $asignaturas = $request->asignaturas;

            foreach ($asignaturas as $key => $value) {
                $asig = new DocumentosAsignaturas();
                $asig->iddocumento = $documento->id;
                $asig->idasignatura = $value['id'];
                $asig->save();
            }
            return $documento;
            return $data['files'];
        }
        else{
            $documento = Documentos::findOrFail($request->id);
            $documento->nombre = $request->nombre;
            $documento->descripcion = $request->descripcion;
            $documento->status = $request->status;
            $documento->save();

            DB::DELETE("DELETE FROM `documentos_asignatura` WHERE `iddocumento` = ?",[$request->id]);

            $asignaturas = $request->asignaturas;

            foreach ($asignaturas as $key => $value) {
                $asig = new DocumentosAsignaturas();
                $asig->iddocumento = $request->id;
                $asig->idasignatura = $value['id'];
                $asig->save();
            }
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Documentos  $documentos
     * @return \Illuminate\Http\Response
     */
    public function show(Documentos $documentos)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Documentos  $documentos
     * @return \Illuminate\Http\Response
     */
    public function edit(Documentos $documentos)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Documentos  $documentos
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Documentos $documentos)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Documentos  $documentos
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Documentos $documentos)
    {
        DB::SELECT('DELETE FROM `archivo` WHERE id = ?;',[$request->id]);
        return $documentos;
    }

    public function documentDelete(Request $request)
    {
        DB::SELECT('DELETE FROM `documentos` WHERE id = ?;',[$request->id]);
    }
}
