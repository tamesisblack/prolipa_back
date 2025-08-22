<?php
namespace App\Repositories\pedidos;

use App\Models\NotificacionGeneral;
use App\Models\Verificacion;
use App\Models\VerificacionComboLiquidado;
use App\Models\VerificacionComboLiquidadoDetalle;
use App\Repositories\BaseRepository;
use App\Services\PusherService;
use DB;
use Pusher\Pusher;

class  VerificacionRepository extends BaseRepository
{
    protected $pusherService;

    public function __construct(Verificacion $VerificacionRepository, PusherService $pusherService)
    {
        parent::__construct($VerificacionRepository);
        $this->pusherService = $pusherService;
    }
    public function getAllCodigosIndividualesContrato($contrato,$num_verificacion,$id_verificacion){
        $query = DB::SELECT("SELECT ls.codigo_liquidacion ,c.codigo,
            c.libro_idlibro,l.nombrelibro as nombrelibro,
            IF(c.estado ='2', 'bloqueado','activo') as codigoEstado,
            (case when (c.estado_liquidacion = '0') then 'liquidado'
                when (c.estado_liquidacion = '1') then 'sin liquidar'
                when (c.estado_liquidacion = '2' AND c.liquidado_regalado = '0') then 'Regalado sin liquidar'
                when (c.estado_liquidacion = '2' AND c.liquidado_regalado = '1') then 'Regalado liquidado'
                when (c.estado_liquidacion = '3') then 'codigo devuelto'
                when (c.estado_liquidacion = '4') then 'Código Guia'
            end) as liquidacion,
            (case when (c.bc_estado = '2') then 'codigo leido'
                when (c.bc_estado = '1') then 'codigo sin leer'
            end) as barrasEstado,
            (case when (c.venta_estado = '0') then ''
                when (c.venta_estado = '1') then 'Venta directa'
                when (c.venta_estado = '2') then 'Venta por lista'
             end) as ventaEstado,
             (case when (c.plus = '1') then 'si'
                 when (c.plus = '0') then ''
             end) as plus,
            (case when (c.quitar_de_reporte = '1') then 'si'
                 when (c.plus = '0') then ''
             end) as quitar_de_reporte,
            c.combo,  c.codigo_combo
            FROM codigoslibros c
            LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
            WHERE `$num_verificacion`   = '$id_verificacion'
            AND c.contrato              = '$contrato'
        ");
        return $query;
    }
    public function save_notificacion($request,$color='primary'){
        try{
            //validar si existe la notificacion
            $validar = DB::SELECT("SELECT * FROM notificaciones_general n
            WHERE n.id_padre = '$request->id_padre'
            AND n.estado = '0'
            AND n.tipo = '$request->tipo'");
            if(count($validar) > 0){
                return ["status" => "1", "ya esta guardado"];
            }
            $notificacion               = new NotificacionGeneral();
            $notificacion->nombre       = $request->nombre;
            $notificacion->descripcion  = $request->descripcion;
            $notificacion->tipo         = $request->tipo;
            $notificacion->user_created = $request->user_created;
            $notificacion->id_periodo   = $request->id_periodo;
            $notificacion->id_padre     = $request->id_padre;
            $notificacion->color        = $color;
            $notificacion->save();
            if($notificacion){
                return ["status"=>"1","message"=>"Se guardo correctamente"];
            }else{
                throw new \Exception("No se pudo guardar");
            }
        }catch(\Exception $e){
            throw new \Exception("No se pudo guardar" . $e->getMessage());
        }
    }
    public function cerrarNotificacion($idPadre,$tipo){
        try{
            $notificacion = NotificacionGeneral::where('id_padre', $idPadre)->where('tipo', $tipo)->where('estado', '0')->first();
            if($notificacion){
                $notificacion->estado = 1;
                $notificacion->save();
                return $notificacion;
            }
        }
        catch(\Exception $e){
            throw new \Exception("No se pudo cerrar notificacion" . $e->getMessage());
        }
    }

    public function saveComboLiquidado($periodo,$combo_etiqueta,$contrato_relacion,$datosDetalle)
    {
        $idComboLiquidado = null;
        // validar si existe no crear el combo etiqueta
        $validateComboEtiqueta = VerificacionComboLiquidado::where('contrato', $contrato_relacion)
            ->where('combo_etiqueta', $combo_etiqueta)
            ->first();
        // si existe obtener el id
        if ($validateComboEtiqueta) {
            $idComboLiquidado = $validateComboEtiqueta->id;
        }
        // crear el combo etiqueta
        else{
            $comboEtiqueta                      = new VerificacionComboLiquidado();
            $comboEtiqueta->periodo_id          = $periodo;
            $comboEtiqueta->contrato            = $contrato_relacion;
            $comboEtiqueta->combo_etiqueta      = $combo_etiqueta;
            $comboEtiqueta->save();
            $idComboLiquidado                   = $comboEtiqueta->id;
        }


        // crear los detalles del combo
        foreach ($datosDetalle as $detalle) {
            //validar si ya existe no crear , validar el codigo, contrato_relacion
            $validateDetalle = VerificacionComboLiquidadoDetalle::where('codigo', $detalle->codigo)
                ->where('contrato_relacion', $contrato_relacion)
                ->where('combo_etiqueta', $combo_etiqueta)
                ->first();
            if ($validateDetalle) {
                continue;
            }
            $detalleCombo                           = new VerificacionComboLiquidadoDetalle();
            $detalleCombo->verificaciones_combos_liquidados_id = $idComboLiquidado;
            $detalleCombo->codigo                   = $detalle->codigo;
            $detalleCombo->combo_etiqueta           = $combo_etiqueta;
            $detalleCombo->combo                    = $detalle->combo;
            $detalleCombo->contrato_liquidada       = $detalle->contrato;
            $detalleCombo->institucion_liquidada    = $detalle->bc_institucion;
            $detalleCombo->periodo_liquidada        = $detalle->bc_periodo;
            $detalleCombo->contrato_relacion        = $contrato_relacion;
            $detalleCombo->columna_verificacion     = $detalle->columna_verificacion;
            $detalleCombo->verificacion_id          = $detalle->valor_verificacion;
            $detalleCombo->save();
        }

        //traer todos los codigos del combo para registralos
        $getAllCodigosCombo = DB::SELECT("SELECT * FROM codigoslibros c
        WHERE c.codigo_combo = '$combo_etiqueta'
        AND c.prueba_diagnostica = '0'
        ");
        foreach($getAllCodigosCombo as $codigoCombo){
            //validar si ya existe no crear , validar el codigo, contrato_relacion
            $validateDetalle = VerificacionComboLiquidadoDetalle::where('codigo', $codigoCombo->codigo)
                ->where('contrato_relacion', $contrato_relacion)
                ->where('combo_etiqueta', $combo_etiqueta)
                ->first();
            if ($validateDetalle) {
                continue;
            }
            $detalleCombo                           = new VerificacionComboLiquidadoDetalle();
            $detalleCombo->verificaciones_combos_liquidados_id = $idComboLiquidado;
            $detalleCombo->codigo                   = $codigoCombo->codigo;
            $detalleCombo->combo_etiqueta           = $combo_etiqueta;
            $detalleCombo->combo                    = $codigoCombo->combo;
            $detalleCombo->contrato_liquidada       = $codigoCombo->contrato;
            $detalleCombo->institucion_liquidada    = $codigoCombo->bc_institucion;
            $detalleCombo->periodo_liquidada        = $codigoCombo->bc_periodo;
            $detalleCombo->contrato_relacion        = $contrato_relacion;
            $detalleCombo->venta_lista_institucion   = $codigoCombo->venta_lista_institucion;
            $detalleCombo->columna_verificacion     = null;
            $detalleCombo->verificacion_id          = null; // valor por defecto
            $detalleCombo->save();
        }
    }
}
