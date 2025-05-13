<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NotificacionGeneral;
use App\Repositories\pedidos\NotificacionRepository;
use Pusher\Pusher;
class NotificacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $NotificacionRepository;
    public function __construct(NotificacionRepository $NotificacionRepository)
    {
        $this->NotificacionRepository   = $NotificacionRepository;
    }
    //api:get/notificaciones
    public function index()
    {
        return "Hola mundo";
    }
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
            ->where('tipo', $validatedData['tipo']) // Agregamos la condición para tipo
            ->where('estado', 0)
            ->first();

        if ($notificacion) {
            //si es estado 0  mostrar un mensaje con status 2 ya fue notificado a facturacion
            if($notificacion->estado == 0 && ($validatedData['tipo'] == 0 || $validatedData['tipo'] == 3)){
                return ["status" => "2", "message" => "Ya fue notificado a facturación"];
            }else if($notificacion->estado == 0){
                return ["status" => "2", "message" => "Existe una notificación de ".$request->nombre." para este pedido"];
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

        if($success){
            if($validatedData['tipo'] == 0 || $validatedData['tipo'] == 3){
                $channel = 'admin.notifications_verificaciones';
                $event = 'NewNotification';
                $data = [
                    'message' => 'Nueva notificación',
                ];
                $this->NotificacionRepository->notificacionVerificaciones($channel, $event, $data);
            }
            if($validatedData['tipo'] == 2){
                $channel = 'asesor.notificacionVerificacion';
                $event = 'NewNotification';
                $data = [
                    'message' => 'Nueva notificación',
                ];
                $this->NotificacionRepository->notificacionVerificaciones($channel, $event, $data);
            }
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

    public function pusherNot(Request $request){
        $options = array(
            'cluster' => 'us2',
            'useTLS' => true
        );
        $pusher = new Pusher(
            '67ad293a1345b6955bf0',
            '715db7e905f8d5879967',
            '1960752',
            $options
        );
        $data['message'] = 'hello world';
        $pusher->trigger('notification', $request->email, $data);

    }
    public function pruebaPush(Request $request)
    {
        // Validación de datos (opcional pero recomendable)
        $request->validate([
            'email' => 'required|email',  // Verificar que el email sea válido
            'mensaje' => 'required|string',  // Verificar que el mensaje sea válido
        ]);

        // Configuración de Pusher
        $options = array(
            'cluster' => 'us2',
            'useTLS' => true,
        );

        // Crear la instancia de Pusher
        $pusher = new Pusher(
            '67ad293a1345b6955bf0',
            '715db7e905f8d5879967',
            '1960752',
            $options
        );

        // Definir el canal (basado en el email)
        $channel = 'notification.' . $request->email;  // El canal debería ser "notification.{email}"

        // Obtener el mensaje desde la solicitud
        $mensaje = $request->mensaje;  // Mensaje proporcionado en la solicitud

        // Definir el evento y los datos a enviar
        $event = 'NewNotification';  // El nombre del evento debe coincidir con el nombre en Vue.js
        $data = [
            'message' => 'hello world',  // Mensaje estático por defecto
            'mensaje' => $mensaje,       // Mensaje dinámico recibido desde la solicitud
        ];

        // Emitir el evento a través de Pusher
        try {
            $pusher->trigger($channel, $event, $data);
            return response()->json(['status' => 'success', 'message' => 'Notification sent']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }


}
