<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NotificacionGeneral;
class NotificacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //api:get/notificaciones
    public function index()
    {
        return "Hola mundo";
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
    //api:post/notificaciones
    public function store(Request $request)
    {
        //save notificacion
        if($request->saveNotification){ return $this->saveNotification($request); }
        if($request->marcarComoLeida) { return $this->marcarComoLeida($request); }

    }
    //api:post/notificaciones?save_notification=1
    public function saveNotification(Request $request)
    {
        // Validación de datos
        $validatedData = $request->validate([
            'id_padre' => 'required',
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'user_created' => 'required',
            'tipo' => 'nullable|string',
            'id_periodo' => 'nullable',
            'estado' => 'nullable|in:0,1',
        ]);

        // Verificar si ya existe una notificación con id_padre y estado 0
        $notificacion = NotificacionGeneral::where('id_padre', $validatedData['id_padre'])
            ->first();

        if ($notificacion) {
            //si es estado 0  mostrar un mensaje con status 2 ya fue notificado a facturacion
            if($notificacion->estado == 0){
                return ["status" => "2", "message" => "Ya fue notificado a facturación"];
            }
            // Actualizar la notificación existente
            $notificacion->fill([
                'nombre'        => $validatedData['nombre'],
                'descripcion'   => $validatedData['descripcion'],
                'user_created'  => $validatedData['user_created'],
                'created_at'    => now(),
                'estado'        => $validatedData['estado'],
            ]);

            $success = $notificacion->save();
        } else {
            // Crear una nueva notificación
            $notificacion = new NotificacionGeneral($validatedData);
            $success      = $notificacion->save();
        }

        // Retornar la respuesta
        return $success
            ? ["status" => "1", "message" => "Se guardó correctamente la notificación"]
            : ["status" => "0", "message" => "No se pudo guardar la notificación"];
    }

    //api:post/notificaciones?marcarComoLeida=1
    public function marcarComoLeida(Request $request)
    {
        // Validación de datos
        $validatedData = $request->validate([
            'id' => 'required',
            'id_usuario' => 'required',
        ]);

        // Obtener la notificación
        $notificacion = NotificacionGeneral::find($validatedData['id']);

        if ($notificacion) {
            // Marcar la notificación como leída
            $notificacion->estado           = 1;
            $notificacion->user_finaliza    = $validatedData['id_usuario'];
            $notificacion->fecha_finaliza   = now();
            $success                        = $notificacion->save();
        } else {
            $success = false;
        }

        // Retornar la respuesta
        return $success
            ? ["status" => "1", "message" => "Se marcó correctamente la notificación como leída"]
            : ["status" => "0", "message" => "No se pudo marcar la notificación como leída"];
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
    }
}
