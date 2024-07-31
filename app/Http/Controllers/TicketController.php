<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\Ticket;
use App\Models\TicketRespuesta;
use Carbon\Carbon;


class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //para informacion del usuario
        if($request->informacionUsuario){
             //para obtener los datos del estudiante para abrir el ticket
            $datosEstudiante = DB::SELECT("SELECT CONCAT(e.nombres,' ', e.apellidos) as usuario,
            e.name_usuario,e.cedula,e.idusuario, i.nombreInstitucion,i.idInstitucion
            FROM usuario e, institucion i
            WHERE e.idusuario = $request->idusuario
            AND e.institucion_idInstitucion = i.idInstitucion
        ");
        //para ver cuantos tickets abiertos tiene el usuario
        $cantidadTicketOpen = DB::SELECT("SELECT t.* FROM tickets t
         WHERE t.usuario_id = $request->idusuario
         AND t.estado = '1'
        ");
         $realizarTicket = "no";
         if(empty($cantidadTicketOpen)){
             $realizarTicket = "ok";
         }
         return [
             "datosEstudiante" => $datosEstudiante,
             'realizarTicket' => $realizarTicket
         ];
        }
        //para traer los tickets abiertos
        if($request->tabierto){
            return $this->getTicketsAbiertos();
        }
        //para traer las respuesta de los tickets abiertos
        if($request->RespuestaTicketAbierto){
            $respuestTicket = DB::SELECT("SELECT rt.* , CONCAT(u.nombres,' ', u.apellidos) as usuario, g.deskripsi as rol,u.cedula
            FROM ticket_respuesta rt
            LEFT JOIN usuario u ON rt.usuario_id = u.idusuario
            LEFT JOIN sys_group_users g ON rt.group_id = g.id
            WHERE rt.ticket_id = '$request->ticket_id'
            ORDER BY rt.ticket_res_id DESC
            ");
             if(count($respuestTicket) == 0){
                return ["status" => "0", "message" => "No hay respuestas para este ticket"];
            }
            return $respuestTicket;
        }
        //para traer las respuesta de los tickets abiertos para el reporte Individual
        if($request->RespuestaTicketAbiertoReporte){
            $respuestTicket = DB::SELECT("SELECT rt.* , CONCAT(u.nombres,' ', u.apellidos) as usuario, g.deskripsi as rol, u.cedula
            FROM ticket_respuesta rt
            LEFT JOIN usuario u ON rt.usuario_id = u.idusuario
            LEFT JOIN sys_group_users g ON rt.group_id = g.id
            WHERE rt.ticket_id = '$request->ticket_id'
            ORDER BY rt.ticket_res_id ASC
            ");
             if(count($respuestTicket) == 0){
                return ["status" => "0", "message" => "No hay respuestas para este ticket"];
            }
            return $respuestTicket;
        }
        //para cambiar de estado de  un ticket
        if($request->estadoChange){
            $estadoTicket = Ticket::findOrFail($request->ticket_id);
            $estadoTicket->estado = $request->estado;
            $estadoTicket->save();
            //Si se cierrar el ticket actualizo la fecha
            $fechaActual  = date('Y-m-d');
            if($request->estado == '0'){
                DB::table('tickets')
                ->where('ticket_id', $request->ticket_id)
                ->update(['fecha_ticket_final' => $fechaActual]);
            }
            if($estadoTicket){
                return "Se cambio de estado correctamente";
            }else{
                return "No se pudo cambiar de estado ";
            }
        }
        //para la busqueda de ticket por filtro de fechas
        if($request->busqueda == 'fecha'){
            $ticketCerrado = DB::SELECT("SELECT t.*,
            CONCAT(u.nombres,' ', u.apellidos) as usuario , (SELECT TIME(fecha)
            FROM ticket_respuesta  WHERE ticket_id = t.ticket_id
            AND group_id <> '1'
            AND group_id <> '11'
            ORDER BY ticket_res_id DESC  LIMIT 1)  AS ultima_hora ,
            (SELECT COUNT(ticket_id) as c
            FROM ticket_respuesta
            WHERE ticket_id = t.ticket_id
            AND group_id <> '1'
            AND group_id <> '11'
            ) as cantidad ,
            u.email,u.name_usuario,u.cedula,u.idusuario, i.nombreInstitucion,
            g.deskripsi as rol, t.ticket_asesor, i.vendedorInstitucion, t.datos_ticket
            FROM tickets t
            LEFT JOIN institucion i ON t.institucion_id = i.idInstitucion
            LEFT JOIN usuario u ON t.usuario_id = u.idusuario
            LEFT JOIN sys_group_users g ON t.group_id = g.id
            WHERE t.estado = '0'
            AND t.fecha_ticket_final BETWEEN '$request->fromDate' AND '$request->toDate'
            ORDER BY t.ticket_id DESC
            ");
            if(count($ticketCerrado) == 0){
                return ["status" => "0", "message" => "No hay tickets cerrados con esas fechas"];
            }
            return ['ticketCerrado' => $ticketCerrado];
        }
        //para la busqueda de tickets por el numero
        if($request->busqueda == 'numero'){
            $ticketCerrado = DB::SELECT("SELECT t.*,
            CONCAT(u.nombres,' ', u.apellidos) as usuario,u.email ,
            u.name_usuario,u.cedula,u.idusuario, i.nombreInstitucion, g.deskripsi as rol,
            t.ticket_asesor, i.vendedorInstitucion, t.datos_ticket
            FROM tickets t
            LEFT JOIN institucion i ON t.institucion_id = i.idInstitucion
            LEFT JOIN usuario u ON t.usuario_id = u.idusuario
            LEFT JOIN sys_group_users g ON t.group_id = g.id
            WHERE t.estado = '0'
            AND t.ticket_id LIKE '%$request->razonBusqueda%'
            ORDER BY t.ticket_id DESC
            ");
            if(count($ticketCerrado) == 0){
                return ["status" => "0", "message" => "No hay tickets cerrados con ese numero"];
            }
            return ['ticketCerrado' => $ticketCerrado];
        }

        //para la busqueda de tickets cerrados por cedula
        if($request->busqueda == 'cedula'){
            $ticketCerrado = DB::SELECT("SELECT t.*,
            CONCAT(u.nombres,' ', u.apellidos) as usuario,u.email,
            u.name_usuario,u.cedula,u.idusuario, i.nombreInstitucion, g.deskripsi as rol,
            t.ticket_asesor, i.vendedorInstitucion, t.datos_ticket
            FROM tickets t
            LEFT JOIN institucion i ON t.institucion_id = i.idInstitucion
            LEFT JOIN usuario u ON t.usuario_id = u.idusuario
            LEFT JOIN sys_group_users g ON t.group_id = g.id
            WHERE t.estado = '0'
            AND t.cedula LIKE '%$request->razonBusqueda%'
            ORDER BY t.ticket_id DESC
            ");
            if(count($ticketCerrado) == 0){
                return ["status" => "0", "message" => "No hay tickets cerrados con esa cedula"];
            }
            return ['ticketCerrado' => $ticketCerrado];
        }

        //para la busqueda de tickets cerrados por institucion
        if($request->busqueda == 'institucion'){
            $ticketCerrado = DB::SELECT("SELECT t.*,
            DATEDIFF(t.fecha_ticket_final,t.fecha_ticket) as dias ,
            CONCAT(u.nombres,' ', u.apellidos) as usuario,u.email ,
            u.name_usuario,u.cedula,u.idusuario, i.nombreInstitucion, g.deskripsi as rol,
            t.ticket_asesor, i.vendedorInstitucion, t.datos_ticket
            FROM tickets t
            LEFT JOIN institucion i ON t.institucion_id = i.idInstitucion
            LEFT JOIN usuario u ON t.usuario_id = u.idusuario
            LEFT JOIN sys_group_users g ON t.group_id = g.id
            WHERE t.estado = '0'
            AND t.nombreInstitucion LIKE '%$request->razonBusqueda%'
            ORDER BY t.ticket_id DESC
            ");
            if(count($ticketCerrado) == 0){
                return ["status" => "0", "message" => "No hay tickets cerrados con esa institucion"];
            }
            //para traer solo las instituciones para hacer el fitro del reporte
            $filtroInstituciones = DB::SELECT("SELECT DISTINCT i.idInstitucion,
            i.nombreInstitucion,t.ticket_asesor, i.vendedorInstitucion, t.datos_ticket
            FROM tickets t
            LEFT JOIN institucion i ON t.institucion_id = i.idInstitucion
            LEFT JOIN usuario u ON t.usuario_id = u.idusuario
            LEFT JOIN sys_group_users g ON t.group_id = g.id
            WHERE t.estado = '0'
            AND t.nombreInstitucion LIKE '%$request->razonBusqueda%'
            ORDER BY t.ticket_id DESC
            ");
            // return $ticketCerrado;
            return ["ticketCerrado"=>$ticketCerrado,"filtroInstituciones"=>$filtroInstituciones];
        }
        //para la busqueda de ticket cerrados para el reporte general por filtro de fechas
        if($request->reporteGeneral){
            $ticketCerrado = DB::SELECT("SELECT t.*,
            DATEDIFF(t.fecha_ticket_final,t.fecha_ticket) as dias,
            (SELECT COUNT(ticket_id)
            FROM ticket_respuesta WHERE ticket_id = t.ticket_id) as cantidad,
            CONCAT(u.nombres,' ', u.apellidos) as usuario,
            u.email ,u.name_usuario,u.cedula,u.idusuario, i.nombreInstitucion,
            g.deskripsi as rol, t.ticket_asesor, i.vendedorInstitucion, t.datos_ticket
            FROM tickets t
            LEFT JOIN institucion i ON t.institucion_id = i.idInstitucion
            LEFT JOIN usuario u ON t.usuario_id = u.idusuario
            LEFT JOIN sys_group_users g ON t.group_id = g.id
            WHERE t.fecha_ticket BETWEEN '$request->fromDate' AND '$request->toDate'
            AND  t.estado = '0'
            AND t.institucion_id ='$request->institucion_id'
            AND ticket_asesor = '0'
            ORDER BY t.ticket_id DESC
            ");
            if(count($ticketCerrado) == 0){
                return ["status" => "0", "message" => "No hay tickets cerrados para esa institucion"];
            }
            return $ticketCerrado;
        }
        //=================CONTAR TICKETS==========================================
         //para la busqueda de ticket cerrados para el reporte general por filtro de fechas
         if($request->cantAbiertos){
            $abiertos = DB::SELECT("SELECT t.*,
            i.vendedorInstitucion
             FROM tickets t
             LEFT JOIN institucion i ON t.institucion_id = i.idInstitucion
             WHERE estado = '1'

            ");
            return $abiertos;
        }
         //para la busqueda de ticket cerrados para el reporte general por filtro de fechas
         if($request->cantCerrados){
            $cerrados = DB::SELECT("SELECT COUNT(ticket_id) as cantidadC,ticket_asesor FROM tickets WHERE estado = '0'  AND ticket_asesor = '0'
            ");
            return $cerrados;
        }
        //=================FIN CONTAR TICKETS=====================================
        //para ver la cantidad de mensajes por ticket
        if($request->cantidadTicketNotificacion){
            $ticketAbierto = DB::SELECT("SELECT t.*,
            CONCAT(u.nombres,' ', u.apellidos) as usuario ,
            (SELECT COUNT(ticket_id) FROM ticket_respuesta
            WHERE ticket_id = t.ticket_id AND (group_id ='1' OR group_id ='11')) as cantidad,
            u.email,u.name_usuario,u.cedula,u.idusuario,
            i.nombreInstitucion, g.deskripsi as rol
            FROM tickets t
            LEFT JOIN institucion i ON t.institucion_id = i.idInstitucion
            LEFT JOIN usuario u ON t.usuario_id = u.idusuario
            LEFT JOIN sys_group_users g ON t.group_id = g.id
            WHERE t.usuario_id = $request->id
            AND  t.estado = '1'
            ORDER BY t.fecha_update DESC
            ");
            if(count($ticketAbierto) == 0){
                return ["status" => "0", "message" => "No hay tickets abiertos"];
            }
            return $ticketAbierto;
        }
        //Si el usuario ya vio el mensaje Administrador
        if($request->yaviMensajeAdmin){
            if($request->ticket_id){
                $mensajeTicket = Ticket::findOrFail($request->ticket_id);
                $mensajeTicket->cantidad_tickets_admin = $request->cantidad;
                $mensajeTicket->save();
                if($mensajeTicket){
                    return "El mensaje ha sido visto por el usuario";
                }else{
                    return "El menaje no pudo ser visto";
                }
            }
        }
        if($request->yaviMensaje){
            if($request->ticket_id){
                $mensajeTicket = Ticket::findOrFail($request->ticket_id);
                $mensajeTicket->cantidad_tickets_usuario = $request->cantidad;
                $mensajeTicket->save();
                if($mensajeTicket){
                    return "El mensaje ha sido visto por el usuario";
                }else{
                    return "El menaje no pudo ser visto";
                }
            }
        }
    }
    //api:get/>>getInfoTicket/9
    public function getInfoTicket($id_ticket){
        //informacion de la tabla ticket enviando el id del ticket
        $query = DB::SELECT("SELECT * FROM tickets
        where ticket_id = $id_ticket
        ");
        return $query;
    }
    public function getTicketsAbiertos(){
        $ticketAbierto = DB::SELECT("SELECT t.*,
            CONCAT(u.nombres,' ', u.apellidos) as usuario ,
            (
                SELECT TIME(fecha)
                FROM ticket_respuesta
                WHERE ticket_id = t.ticket_id
                AND group_id <> '11'
                AND group_id <> '1'
                ORDER BY ticket_res_id DESC  LIMIT 1)
            AS ultima_hora ,
            (
                SELECT COUNT(ticket_id) as c
                FROM ticket_respuesta
                WHERE ticket_id = t.ticket_id
                AND group_id <> '11'
                AND group_id <> '1'
            )as cantidad,
            u.email,u.name_usuario,u.cedula,u.idusuario,
            i.nombreInstitucion, g.deskripsi as rol, t.ticket_asesor,
            i.vendedorInstitucion, t.datos_ticket
            FROM tickets t
            LEFT JOIN institucion i ON t.institucion_id = i.idInstitucion
            LEFT JOIN usuario u ON t.usuario_id = u.idusuario
            LEFT JOIN sys_group_users g ON t.group_id = g.id
            WHERE t.estado = '1'
            ORDER BY t.ticket_id DESC
        ");
        if(count($ticketAbierto) == 0){
            return ["status" => "0", "message" => "No hay tickets abiertos"];
        }
        return $ticketAbierto;
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
        if($request->ticket_id){
            $ticketRespuesta = new TicketRespuesta;
            $ticketRespuesta->ticket_id =   $request->ticket_id;
            $ticketRespuesta->descripcion = $request->descripcion;
            $ticketRespuesta->group_id =    $request->group_id;
            $ticketRespuesta->usuario_id =  $request->usuario_id;
            if($request->estado){
                $ticketRespuesta->estado =  $request->estado;
            }
            $ticketRespuesta->save();
            if($ticketRespuesta){
                return "Se genero la respuesta correctamente";
            }else{
                return "No se pudo guardar la respuesta";
            }
        }else{
            $fechaActual  = date('Y-m-d');
            $ticket = new Ticket;
            $ticket->fecha_ticket       = $fechaActual;
            $ticket->cedula             = $request->cedula;
            if($request->codigo){
                $ticket->codigo         = $request->codigo;
            }
            if($request->ticket_asesor == 1){
                $ticket->institucion_id     = decrypt($request->institucion_id);
            }else{
                $ticket->institucion_id     = $request->institucion_id;
            }
            $ticket->usuario_id         = $request->usuario_id;
            $ticket->group_id           = $request->group_id;
            $ticket->descripcion        = $request->descripcion;
            $ticket->nombreInstitucion  = $request->nombreInstitucion;
            $ticket->ticket_asesor      = $request->ticket_asesor;
            $ticket->datos_ticket       = $request->datos_ticket;
            $ticket->save();
            if($request->observacion){
                $ticketRespuesta = new TicketRespuesta;
                $ticketRespuesta->ticket_id =   $ticket->ticket_id;
                $ticketRespuesta->descripcion = $request->observacion;
                $ticketRespuesta->group_id =    $request->group_id;
                $ticketRespuesta->usuario_id =  $request->usuario_id;
                $ticketRespuesta->save();
            }
        }
        if($ticket){
            return "Se genero el ticket correctamente";
        }else{
            return "No se pudo generar el ticket";
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
        $ticketAbierto = DB::SELECT("SELECT t.*, CONCAT(u.nombres,' ', u.apellidos) as usuario,
        (
            SELECT TIME(fecha)
            FROM ticket_respuesta
            WHERE ticket_id = t.ticket_id
            AND (group_id = '1' OR group_id = '11')
            ORDER BY ticket_res_id DESC  LIMIT 1
            )  AS ultima_hora,
        (SELECT COUNT(ticket_id) FROM ticket_respuesta WHERE ticket_id = t.ticket_id AND (group_id ='1' OR group_id ='11' )) as cantidad ,u.email,u.name_usuario,u.cedula,u.idusuario, i.nombreInstitucion, g.deskripsi as rol
        FROM tickets t
        LEFT JOIN institucion i ON t.institucion_id = i.idInstitucion
        LEFT JOIN usuario u ON t.usuario_id = u.idusuario
        LEFT JOIN sys_group_users g ON t.group_id = g.id
        WHERE t.usuario_id = $id
        ORDER BY t.ticket_id DESC
        ");
        if(count($ticketAbierto) == 0){
            return ["status" => "0", "message" => "No hay tickets abiertos"];
        }
        return $ticketAbierto;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

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
