<?php
namespace App\Repositories\pedidos;

use App\Models\NotificacionGeneral;
use App\Repositories\BaseRepository;
use App\Services\PusherService;
use DB;
use Pusher\Pusher;

class  NotificacionRepository extends BaseRepository
{
    protected $pusherService;

    public function __construct(NotificacionGeneral $NotificacionRepository, PusherService $pusherService)
    {
        parent::__construct($NotificacionRepository);
        $this->pusherService = $pusherService;
    }
   
    /**
     * Envia una notificación de verificación al canal especificado.
     *
     * @param string $channel El canal donde se enviará la notificación. Debe ser especificado en el archivo channels.php.
     * @param string $event El evento de la notificación. Este será el nombre del evento a disparar en Pusher.
     * @param array $data Los datos de la notificación, generalmente un array asociativo con la información a enviar.
     *
     * @throws \Exception Si no se puede enviar la notificación en Pusher.
     */
    public function notificacionVerificaciones($channel, $event, $data){
        try{
            if (empty($channel)) {
                throw new \InvalidArgumentException("El canal no puede estar vacío.");
            }
            // **$channel** tiene que especificar el canal en channels.php

            /* EJEMPLO DE PARÁMETROS */
            // $channel = 'admin.notifications_verificaciones';
            // $event = 'NewNotification';
            // $data = [
            //     'message' => 'Nueva notificación',
            // ];

            // Usar el servicio para enviar la notificación
            $this->pusherService->triggerNotification($channel, $event, $data);
        } catch(\Exception $e) {
            throw new \Exception("No se pudo enviar notificación en Pusher " . $e->getMessage());
        }
    }

}
