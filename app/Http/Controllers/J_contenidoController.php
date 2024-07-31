<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Provider\Image;
use Illuminate\Support\Facades\File;
use App\Models\J_contenido;
use App\Models\J_opcionesContenidos;

class J_contenidoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $contenidos = DB::SELECT("SELECT * FROM j_contenido_juegos");

        return $contenidos;
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
        if( $request->id_contenido_juego > 0 ){
            $contenido = J_contenido::find($request->id_contenido_juego);
        }else{
            $contenido = new J_contenido();
        }

        $contenido->id_juego = $request->id_juego;
        $contenido->pregunta = $request->pregunta;
        $contenido->respuesta = $request->respuesta;

        $contenido->save();

        return $contenido;
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $contenidos = DB::SELECT("SELECT * FROM j_contenido_juegos WHERE id_juego = $id");

        return $contenidos;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        $pregunta = J_contenido::find($id);
        $pregunta->delete();
    }
    public function preguntas_y_opciones($id_juego)
    {
        $preguntas= DB::SELECT("SELECT c.id_contenido_juego, c.imagen, c.pregunta, j.* FROM j_juegos j, j_contenido_juegos c WHERE j.id_juego = $id_juego AND j.id_juego = c.id_juego");

        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT * FROM j_opciones_contenidos WHERE id_contenido_juegos = ?",[$value->id_contenido_juego]);
                $data['items'][$key] = [
                    'pregunta' => $value,
                    'opciones'=>$opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }


    public function guardaSeleccionSimple(Request $request)
    {

        // guarda pregunta
        if( $request->id_contenido_juego > 0 ){
            $contenido = J_contenido::find($request->id_contenido_juego);
        }else{
            $contenido = new J_contenido();
        }
        $ruta = public_path('/images/imagenes_juegos/seleccionSimple');
        if(!empty($request->file('img_pregunta'))){
            $file = $request->file('img_pregunta');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            $contenido->imagen  = $fileName;
        }

        $contenido->id_juego = $request->id_juego;
        $contenido->pregunta = $request->pregunta;

        $contenido->save();
        // fin guarda pregunta

        // guarda opciones de pregunta
        //OPCION1
        if( $request->id_opcion1 > 0 ){
            $respuestas = J_opcionesContenidos::find($request->id_opcion1);
        }else{
            $respuestas = new J_opcionesContenidos();
        }

        if(!empty($request->file('img_opcion1'))){
            $file = $request->file('img_opcion1');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            $respuestas->imagen_opcion = $fileName;
        }
        $respuestas->id_contenido_juegos  = $contenido->id_contenido_juego;
        $respuestas->nombre_opcion = $request->input1;
        $respuestas->tipo_opcion = $request->check1;
        $respuestas->save();

        //OPCION 2
        if( $request->id_opcion2 > 0 ){
            $respuestas = J_opcionesContenidos::find($request->id_opcion2);
        }else{
            $respuestas = new J_opcionesContenidos();
        }
        if(!empty($request->file('img_opcion2'))){
            $file = $request->file('img_opcion2');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            $respuestas->imagen_opcion = $fileName;
        }
        $respuestas->id_contenido_juegos  = $contenido->id_contenido_juego;
        $respuestas->nombre_opcion = $request->input2;
        $respuestas->tipo_opcion = $request->check2;
        $respuestas->save();

        //OPCION 3 en caso q exista
        if(!empty($request->file('img_opcion3')) || !empty($request->input3) ){
            if( $request->id_opcion3 > 0 ){
                $respuestas = J_opcionesContenidos::find($request->id_opcion3);

            }else{
                $respuestas = new J_opcionesContenidos();
            }
            if(!empty($request->file('img_opcion3'))){
                $file = $request->file('img_opcion3');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
                $respuestas->imagen_opcion = $fileName;
            }
            $respuestas->id_contenido_juegos  = $contenido->id_contenido_juego;
            $respuestas->nombre_opcion = $request->input3;
            $respuestas->tipo_opcion = $request->check3;
            $respuestas->save();
        }
        if( empty($request->file('img_opcion3')) && empty($request->input3) && $request->id_opcion3 > 0){
            $elimina = J_opcionesContenidos::find($request->id_opcion3);
            $elimina->delete();
        }

        //OPCION 4 en caso q exista
        if(!empty($request->file('img_opcion4')) || !empty($request->input4) ){
            if( $request->id_opcion4 > 0 ){
                $respuestas = J_opcionesContenidos::find($request->id_opcion4);
            }else{
                $respuestas = new J_opcionesContenidos();
            }
            if(!empty($request->file('img_opcion4'))){
                $file = $request->file('img_opcion4');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
                $respuestas->imagen_opcion = $fileName;
            }
            $respuestas->id_contenido_juegos  = $contenido->id_contenido_juego;
            $respuestas->nombre_opcion = $request->input4;
            $respuestas->tipo_opcion = $request->check4;
            $respuestas->save();
        }
        if( empty($request->file('img_opcion4')) && empty($request->input4) && $request->id_opcion4 > 0 ){
            $elimina = J_opcionesContenidos::find($request->id_opcion4);
            $elimina->delete();
        }
        //fin guarda opciones de pregunta

        return ['pregunta'=> $contenido, 'opciones'=>$respuestas];
    }
    public function deleteImagenSeleccionSimple(Request $request)
    {
        $msj='';
        if( file_exists('images/imagenes_juegos/seleccionSimple/'.$request->img_eliminar) && $request->img_eliminar != '' ){
            if($request->edita_id_pregunta > 0){
                $contenido = J_contenido::find($request->edita_id_pregunta);
                $contenido->imagen = NULL;
                $contenido->save();
                $msj = 'Pregunta actualizada';
            }
            if($request->edita_id_opcion > 0){
                $contenido = J_opcionesContenidos::find($request->edita_id_opcion);
                $contenido->imagen_opcion = NULL;
                $contenido->save();
                $msj = 'Opcion actualizada';
            }
            // return $request . ' ' . $msj;
            unlink('images/imagenes_juegos/seleccionSimple/'.$request->img_eliminar);
            return $msj;
        }
        return 'nuayy la imagen :(';

    }
}
