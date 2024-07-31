<?php
namespace App\Repositories\pedidos;

use App\Models\Institucion;
use App\Models\Models\Pedidos\PedidosDocumentosLiq;
use App\Models\PedidoConvenio;
use App\Models\Pedidos;
use App\Models\PedidosSolicitudesGerencia;
use App\Repositories\BaseRepository;
use DB;
use League\CommonMark\Block\Element\Document;

use function Safe\json_encode;

class  ConvenioRepository extends BaseRepository
{
    public function __construct(PedidoConvenio $convenioRepository)
    {
        parent::__construct($convenioRepository);
    }
    public function getConvenioInstitucion($institucion){
        $query = DB::SELECT("SELECT c.*, i.nombreInstitucion, p.periodoescolar as periodo
        FROM pedidos_convenios c
        LEFT JOIN institucion i ON i.idInstitucion = c.institucion_id
        LEFT JOIN periodoescolar p ON p.idperiodoescolar = c.periodo_id
        WHERE c.institucion_id = ?
        AND c.estado = '1'
        ",[$institucion]);
        return $query;
    }
    public function hijosConvenio($idConvenio){
        $query = DB::SELECT("SELECT * FROM 1_4_documento_liq l
        WHERE l.pedidos_convenios_id = ?
        AND l.estado = '1'
        AND l.tipo_pago_id = '4'
        ",[$idConvenio]);
        return $query;
    }
    public function registrarConvenioHijo($id_pedido,$idConvenio,$contrato,$institucion,$periodo){
        $GetConvenio        = PedidoConvenio::where('id','=',$idConvenio)->get();
        $global             = $GetConvenio[0]->anticipo_global;
        $convenio_anios     = $GetConvenio[0]->convenio_anios;
        $valorConvenioHijo  = 0;
        //update a pedido
        $datosUpdate = [
            "convenio_anios"        => $convenio_anios,
            "pedidos_convenios_id"  => $idConvenio
        ];
        Pedidos::Where('id_pedido','=',$id_pedido)->update($datosUpdate);
        //=========guardar convenio hijo=============
        //validar si la cantidad de valores hijos supera la cantidad del valor global
        $arraryHijos = $this->hijosConvenio($idConvenio);
        $cantidadHijos = 0;
        foreach($arraryHijos as $key => $item){
            $cantidadHijos = $cantidadHijos + $item->doc_valor;
        }
        //fin validacion
        //si supera la cantidad global se registra en cero
        if($global <= $cantidadHijos){
           $valorConvenioHijo = 0;
        }
        else{
            $valorConvenioHijo = $global / $convenio_anios;
        }
        $query = PedidosDocumentosLiq::Where('id_pedido','=',$id_pedido)->where('tipo_pago_id','4')->get();
        if(count($query) == 0){
            $hijoConvenio                               = new PedidosDocumentosLiq();
            $hijoConvenio->doc_fecha                    = date("Y-m-d H:i:s");
            $hijoConvenio->pedidos_convenios_id         = $idConvenio;
            $hijoConvenio->id_pedido                    = $id_pedido;
            $hijoConvenio->ven_codigo                   = $contrato;
            $hijoConvenio->institucion_id               = $institucion;
            $hijoConvenio->periodo_id                   = $periodo;
            $hijoConvenio->doc_valor                    = $valorConvenioHijo;
            $hijoConvenio->tipo_pago_id                 = 4;
            $hijoConvenio->doc_ci                       = 4;
            $hijoConvenio->forma_pago_id                = 1;
            $hijoConvenio->estado                       = 0;
            $hijoConvenio->save();
        }
    }
    public function updatePedido($contrato,$anio,$idConvenio){
        $datosUpdate = [
            "convenio_anios"        => $anio,
            "pedidos_convenios_id"  => $idConvenio
        ];
        Pedidos::Where('contrato_generado','=',$contrato)->update($datosUpdate);
    }
    public function saveMovimientosConvenio($idConvenio,$id_pedido=null){
        //en la tabla institucion se coloca el valor global , el valor convenio pagado y la deuda convenio
        //===VALOR PAGADO CONVENIO===
        $hijosConvenio = $this->hijosConvenio($idConvenio);
        $valorConvenio = 0;
        $deudaConvenio = 0;
        if(count($hijosConvenio) > 0){
            foreach($hijosConvenio as $key => $item){
                $valorConvenio = $valorConvenio + $item->doc_valor;
            }
        }
        //obtener el estado del convenio
        $convenio                   = PedidoConvenio::where('id', $idConvenio)->first();
        $estadoConvenio             = $convenio->estado;
        $valorGlobal                = $convenio->anticipo_global;
        $convenioPeriodo            = $convenio->periodo_id;
        $DatoInstitucion            = Institucion::where('idInstitucion','=',$convenio->institucion_id)->first();
        $deuda_convenioI            = $DatoInstitucion->deuda_convenio;
        $id_convenio_autorizacionI  = $DatoInstitucion->id_convenio_autorizacion;
        //===DEUDA CONVENIO===
        $deudaConvenio              = $valorGlobal - $valorConvenio;
        //0 => finalizado, 1 => en curso , 2 => cerrado
        //estados de convenio en movimientos 0 => finalizado; 1 => en curso; 2 => cerrado ;
        // ==== PROCESO EN CURSO ====
        /*
            En el caso que convenio este en estado 1 en curso creo el movimiento pregunto si existe lo creo si no lo actualizo
        */
        if ($estadoConvenio == 1) {
            //si hay deuda de convenio y si hay una autorizacion pendiente (cuando hay una deuda de convenio) creo una solicitud para gerencia autorize
            if($DatoInstitucion->ifautorizacionConvenio == 1){
                return $this->procesoCuandoExistsAutorizacion($idConvenio,$id_convenio_autorizacionI,$valorGlobal,$convenioPeriodo,$id_pedido);
            }
            $movimiento = PedidoConvenio::where('id', $idConvenio)->where('estado', '1')->first();
            if (!$movimiento) {
                // El registro no existe, así que crea uno nuevo
                $movimiento = new PedidoConvenio();
            }else{
                // El registro ya existe, así que lo actualiza
                $movimiento = PedidoConvenio::where('id', $idConvenio)->where('estado', '1')->first();
            }
            // Asigna los valores al objeto $movimiento
            $movimiento->institucion_id         = $convenio->institucion_id;
            $movimiento->valor_convenio_pagado  = $valorConvenio;
            $movimiento->deuda_convenio         = $deudaConvenio;
            $movimiento->estado                 = $estadoConvenio;
            // Guarda los cambios (tanto si fue una actualización o una creación)
            $movimiento->save();
            $datosUpdate = [
                "valor_global"                  => $valorGlobal,
                "valor_convenio_pagado"         => $valorConvenio,
                "deuda_convenio"                => $deudaConvenio,
            ];
            Institucion::Where('idInstitucion','=',$convenio->institucion_id)->update($datosUpdate);
        }
        // ==== PROCESO FINALIZADO ====
        /*
            En el caso que convenio este en estado 0 finalizado dejo el movimiento que esta en curso y lo finalizo
        */
        if ($estadoConvenio == 0) {
            // Guarda los cambios (tanto si fue una actualización o una creación)
            /*
              si no hay deudas reseteo los valores de la institucion de lo contrario dejo los valores
            */
            if($deuda_convenioI < 1){
                $datosUpdate = [
                    "valor_global"                  => 0,
                    "valor_convenio_pagado"         => 0,
                    "deuda_convenio"                => 0,
                    "ifautorizacionConvenio"        => 0,
                    "id_convenio_autorizacion"      => 0,
                ];
                Institucion::Where('idInstitucion','=',$convenio->institucion_id)->update($datosUpdate);
            }
            /*
            */
            else{

            }

        }
        // ==== PROCESO CERRADO ====
        /*
            En el caso que convenio este en estado 2 cerrado si hay deuda convenio le coloco el campo de ifautorizacion en 1
        */
        if ($estadoConvenio == 2) {
            /*
                Si esta cerrado y existe una autorizacion pendiente (cuando hay una deuda de convenio) creo una solicitud para gerencia autorize
            */
            if($DatoInstitucion->ifautorizacionConvenio == 1){
                //si la solicitud existe la edito si no la creo
                return $this->procesoCuandoExistsAutorizacion($idConvenio,$id_convenio_autorizacionI,$valorGlobal,$convenioPeriodo,$id_pedido);
            }else{
                //mando a pedir a gerencia para que autorize
                $datosUpdate = [
                    "ifautorizacionConvenio"        => 1,
                    "id_convenio_autorizacion"      => $idConvenio
                ];
                Institucion::Where('idInstitucion','=',$convenio->institucion_id)->update($datosUpdate);
            }
        }
    }
    public function procesoCuandoExistsAutorizacion($idConvenio,$id_convenio_autorizacionI,$valorGlobal,$convenioPeriodo,$id_pedido){
        //si la solicitud existe la edito si no la creo
        $query = PedidosSolicitudesGerencia::Where('id_pedido',$idConvenio)
        ->where('tipo',1)
        ->get();
        //obtener otros valores del convenio que se cerro
        $otrosValores                           = PedidoConvenio::where('id',$id_convenio_autorizacionI)->get();
        if(count($query) > 0){
            $solicitud                          = PedidosSolicitudesGerencia::findOrFail($query[0]->id);
            //si el estado es 1 esta aprobado y no se puede editar
            if($solicitud->estado == 1){
                return;
            }
        }
        else{
            $solicitud                          = new PedidosSolicitudesGerencia();
            $solicitud->estado                  = 0;
        }
        $solicitud->id_pedido                   = $idConvenio;
        $solicitud->tipo                        = 1;
        $solicitud->cantidad_solicitada         = $valorGlobal;
        $solicitud->periodo_id                  = $convenioPeriodo;
        $solicitud->user_created                = 0;
        $solicitud->observacion                 = "Autorización de para iniciar un convenio que fue cerrado";
        $solicitud->otrosValores                = $otrosValores;
        $solicitud->save();
        //colocar el id_solicitud_gerencia_convenio en pedidos convenios y el campo en estado_solicitud_convenio_cerrado en 1 como pendiente por autorizar
        PedidoConvenio::Where('id',$idConvenio)
        ->update([
            "id_solicitud_gerencia_convenio"    => $solicitud->id,
            "estado_solicitud_convenio_cerrado" => 1,
        ]);
    }
    public function autorizarConvenio($idConvenio,$idAutorizacion,$valorGlobalAutorizado){
        $GetConvenio        =  PedidoConvenio::findOrfail($idConvenio);
        $institucion_id     = $GetConvenio->institucion_id;
        $id_pedido          = $GetConvenio->id_pedido;
        $anticipo_global    = $GetConvenio->anticipo_global;
        $valorXConvenio = $valorGlobalAutorizado / $GetConvenio->convenio_anios;
        //actualizar en el pedido para que apruebe el convenio cierre el admin facturador $idConvenio nuevo
        PedidosDocumentosLiq::Where('pedidos_convenios_id',$idConvenio)->where('tipo_pago_id','4')->update(['doc_valor' => $valorXConvenio]);
        $pedido = Pedidos::Where('id_pedido',$id_pedido)
         ->update([
             "estado_aprobado_convenio_cerrado"  => 1,
         ]);
        //===VALOR PAGADO CONVENIO===
        $hijosConvenio = $this->hijosConvenio($idConvenio);
        $valorConvenio = 0;
        if(count($hijosConvenio) > 0){
            foreach($hijosConvenio as $key => $item){
                $valorConvenio = $valorConvenio + $item->doc_valor;
            }
        }
        //===DEUDA CONVENIO===
        $deudaConvenio                          = $valorGlobalAutorizado - $valorConvenio;
        $datosUpdate = [
            "ifautorizacionConvenio"            => 0,
            "id_convenio_autorizacion"          => 0,
            "valor_global"                      => $valorGlobalAutorizado,
            "valor_convenio_pagado"             => $valorConvenio,
            "deuda_convenio"                    => $deudaConvenio,
        ];
        //actualizar en la institucion el valor global y el campo de autorizacion de convenio en 0
        Institucion::Where('idInstitucion','=',$institucion_id)->update($datosUpdate);
        //colocar en pedidos convenio el campo estado_solicitud_convenio_cerrado en 2 como autorizado
        PedidoConvenio::Where('id',$idConvenio)->update([
            "estado_solicitud_convenio_cerrado" => 2,
            "valor_global_inicial"              => $anticipo_global,
            "anticipo_global"                   => $valorGlobalAutorizado,
            "valor_convenio_pagado"             => $valorConvenio,
            "deuda_convenio"                    => $deudaConvenio,
            "estado_aprobado_convenio_cerrado"  => 1
        ]);
        //de los pedidos convenio con idAutorizacion cambiar el pedidos_convenios_id a
        return ["status" => "1", "message" => "Se autorizo correctamente"];
    }
    public function aprobarConvenioCerrado($idConvenio,$user_edit){
        //dejar el pedido el campo estado_aprobado_convenio_cerrado en estado 2 como aprobado el convenio cierre
        PedidoConvenio::Where('id',$idConvenio)->update(["estado_aprobado_convenio_cerrado" => 2,"user_aprueba_convenio_cerrado" => $user_edit]);
        $GetConvenio       =  PedidoConvenio::findOrfail($idConvenio);
        $id_pedido         = $GetConvenio->id_pedido;
        //dejar el pedido el campo estado_aprobado_convenio_cerrado en estado 2 como aprobado el convenio cierre
        Pedidos::Where('id_pedido',$id_pedido)->update(["estado_aprobado_convenio_cerrado" => 2]);
        return ["status" => "1", "message" => "Se aprobo correctamente"];
    }
}
