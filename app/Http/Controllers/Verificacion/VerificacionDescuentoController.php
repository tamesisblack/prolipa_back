<?php

namespace App\Http\Controllers\Verificacion;

use App\Http\Controllers\Controller;
use App\Models\Models\Verificacion\VerificacionDescuento;
use App\Models\Models\Verificacion\VerificacionDescuentoDetalle;
use App\Traits\Verificacion\TraitVerificacionGeneral;
use DB;
use Illuminate\Http\Request;

class VerificacionDescuentoController extends Controller
{
    use TraitVerificacionGeneral;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //API:GET/verificacionesDescuentos
    public function index(Request $request)
    {
        if($request->getListadoDescuentos)      { return $this->getListadoDescuentos($request->contrato,$request->num_verificacion); }
        if($request->getDescuentosVerificacion) { return $this->getDescuentosVerificacion($request); }
        if($request->updateValorVerificacion)   { return $this->updateValorVerificacion($request); }
        if($request->limpiarDescuentos)         { return $this->limpiarDescuentos(); }
    }
    //verificacionesDescuentos?getListadoDescuentos=yes&contrato=C-S23-0000014-DC&num_verificacion=1
    public function getListadoDescuentos($contrato,$num_verificacion){
        $query = VerificacionDescuento::Where('contrato',$contrato)
        ->Where('num_verificacion',$num_verificacion)
        ->OrderBy('id','ASC')
        ->get();
        return $query;
    }
    public function getDescuentosVerificacion($request){
        $verificaciones_descuentos_id   = $request->verificaciones_descuentos_id;
        $contrato                       = $request->contrato;
        $periodo                        = $request->periodo_id;
        $contador                       = 0;
        $detalles = DB::SELECT("SELECT vl.* ,ls.idLibro AS libro_id,
            ls.id_serie,t.id_periodo,a.area_idarea,vd.tipo
            FROM verificaciones_descuentos_detalle vl
            LEFT JOIN libros_series ls ON vl.codigo = ls.codigo_liquidacion
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
            LEFT JOIN temporadas t ON vl.contrato = t.contrato
            LEFT JOIN verificaciones_descuentos vd ON vd.id = vl.verificaciones_descuentos_id
            WHERE vl.verificaciones_descuentos_id = ?
            AND vl.contrato                       = ?
        ",[$verificaciones_descuentos_id,$contrato]);
         $datos = [];
        foreach($detalles as $key => $item){
            //plan lector
            $precio = 0;
            $query = [];
            if($item->id_serie == 6){
                $query = DB::SELECT("SELECT f.pvp AS precio
                FROM pedidos_formato f
                WHERE f.id_serie    = '6'
                AND f.id_area       = '69'
                AND f.id_libro      = '$item->libro_id'
                AND f.id_periodo    = '$periodo'");
            }else{
                $query = DB::SELECT("SELECT f.pvp AS precio
                FROM pedidos_formato f
                WHERE f.id_serie    = '$item->id_serie'
                AND f.id_area       = '$item->area_idarea'
                AND f.id_periodo    = '$periodo'
                ");
            }
            //libro
            if($item->tipo == 1){
                $precio = $item->precio;
            }
            //regalado
            else{
                if(count($query) > 0){
                    $precio = $query[0]->precio;
                }
            }

            $datos[$contador] = [
                "detalle_id"            => $item->id,
                "verificacion_id"       => $item->num_verificacion,
                "contrato"              => $contrato,
                "codigo"                => $item->codigo,
                "codigo_libro"          => $item->codigo_libro,
                "cantidad"              => $item->cantidad,
                "nombre_libro"          => $item->nombre_libro,
                "libro_id"              => $item->libro_id,
                "id_serie"              => $item->id_serie,
                "id_periodo"            => $periodo,
                "precio"                => $precio,
                "valor"                 => $item->cantidad * $precio,
                "descripcion"           => $item->descripcion,
                "cantidad_descontar"    => $item->cantidad_descontar,
                "porcentaje_descuento"  => $item->porcentaje_descuento,
                "total_descontar"       => $item->total_descontar,
                "tipo_calculo"          => $item->tipo_calculo,
                "verificaciones_descuentos_id" => $item->verificaciones_descuentos_id
            ];
            $contador++;
        }
        return $datos;
    }
    public function updateValorVerificacion($request){
        $verificacion_id            = $request->verificacion_id;
        $total_descuentoComision    = 0;
        $totalDescuentoVenta        = 0;
        //update valor en verificacion
        $query = DB::SELECT("SELECT SUM(d.total_descuento) AS total_descuento
         FROM verificaciones_descuentos d
         WHERE d.verificacion_id    = ?
         AND d.estado               = '1'
         AND d.restar               = '0'
        ",[$verificacion_id]);
         $query2 = DB::SELECT("SELECT SUM(d.total_descuento) AS total_descuento
         FROM verificaciones_descuentos d
         WHERE d.verificacion_id    = ?
         AND d.estado               = '1'
         AND d.restar               = '1'
        ",[$verificacion_id]);
        if(!empty($query)){ $total_descuentoComision  = $query[0]->total_descuento; }
        if(!empty($query2)){ $totalDescuentoVenta     = $query2[0]->total_descuento; }
        //update values
        DB::table('verificaciones')
        ->where('id', $verificacion_id)
        ->update([
            "totalDescuento"        => $total_descuentoComision,
            "totalDescuentoVenta"   => $totalDescuentoVenta
        ]);
    }
    //verificacionesDescuentos?limpiarDescuentos=yes
    public function limpiarDescuentos(){
        DB::table('verificaciones_descuentos')
        ->where('nombre_descuento', 'CUPÓN')
        ->where('estado', '0')
        ->where('total_descuento', '0.00')
        ->delete();
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
    //API:POST/saveDescuentosVerificacion
    public function saveDescuentosVerificacion(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $data_detalle   = json_decode($request->data_detalle);
        //actualizar la verificacion con los valores de total de descuento y campo dinamico
        $totalDescuento     = $request->totalDescuento;
        $personalizado      = $request->personalizado;
        $campoPersonalizado = $request->campoPersonalizado == null || $request->campoPersonalizado == "" ? null : $request->campoPersonalizado;
        $user_created       = $request->user_created;
        $tipo               = $request->tipo;
        $restar             = $request->restar;
        $fecha              = date("Y-m-d H:i:s");
        $ingreso = DB::table('verificaciones_descuentos')
        ->where('id', $request->verificaciones_descuentos_id)
        ->update([
            'total_descuento'       => $totalDescuento,
            'nombre_descuento'      => $campoPersonalizado,
            'estado'                => $personalizado,
            'user_created'          => $user_created,
            'tipo'                  => $tipo,
            'restar'                => $restar
        ]);
        foreach($data_detalle as $key => $item){
            $descuento = VerificacionDescuentoDetalle::findOrFail($item->detalle_id);
            $descuento->descripcion             = $item->descripcion;
            $descuento->cantidad_descontar      = $item->cantidad_descontar;
            $descuento->porcentaje_descuento    = $item->porcentaje_descuento;
            $descuento->total_descontar         = $item->total_descontar;
            $descuento->tipo_calculo            = $item->tipo_calculo;
            $descuento->save();
        }
        return ["status" => "1", "message" => "Se guardo correctamente"];
    }
    //API:POST/verificacionesDescuentos
    public function store(Request $request)
    {
        //genero una copia de la tabla verificacion has temporadas para crear un formato de descuentos con libros del contrato y el numero de verificacion
        if($request->generateCopiaValores)      { return $this->generateCopiaValores($request); }
        //crear libro para descuento
        if($request->addBookForDiscount)        { return $this->addBookForDiscount($request); }
        //descuento dinamico
        if($request->saveValorDinamico)         { return $this->saveValorDinamico($request); }
    }
    public function generateCopiaValores($request){
        $contrato               = $request->contrato;
        $num_verificacion       = $request->num_verificacion;
        $verificacion_id        = $request->verificacion_id;
        $user_created           = $request->user_created;
        $institucion            = $request->institucion_id;
        $periodo                = $request->periodo_id;
        //descuento regalado;
        if($request->tipo == 0){
            $query = $this->obtenerAllRegaladosXVerificacion($institucion,$periodo,$num_verificacion,$verificacion_id);
            if(empty($query)) { return ["status" => "0","message" => "No hay codigos regalados configurados para esta verificacion"]; }
        }
        $descuento = new VerificacionDescuento();
        $descuento->nombre_descuento = null;
        $descuento->total_descuento  = 0;
        $descuento->verificacion_id  = $verificacion_id;
        $descuento->num_verificacion = $num_verificacion;
        $descuento->contrato         = $contrato;
        $descuento->user_created     = $user_created;
        $descuento->nombre_descuento = "CUPÓN";
        $descuento->estado           = 0;
        $descuento->tipo             = $request->tipo;
        $descuento->save();
        //descuento libro;
        if($request->tipo == 1){
            return $descuento;
        }
        //DETALLE
        foreach($query as $key2 => $item){
            $detalle = new VerificacionDescuentoDetalle();
            $detalle->verificaciones_descuentos_id  = $descuento->id;
            $detalle->contrato                      = $contrato;
            $detalle->num_verificacion              = $num_verificacion;
            $detalle->codigo_libro                  = $item->codigo;
            $detalle->codigo                        = $item->codigo_liquidacion;
            $detalle->cantidad                      = 0;
            $detalle->nombre_libro                  = $item->nombrelibro;
            $detalle->descripcion                   = null;
            $detalle->cantidad_descontar            = 0;
            $detalle->porcentaje_descuento          = 0;
            $detalle->total_descontar               = 0;
            $detalle->tipo_calculo                  = 0;
            $detalle->save();
        }
        return $descuento;
    }
    public function saveValorDinamico(){

    }
    public function addBookForDiscount($request){
        $descuento_id                           = $request->descuento_id;
        $contrato                               = $request->contrato;
        $num_verificacion                       = $request->num_verificacion;
        $codigo_liquidacion                     = $request->codigo_liquidacion;
        $cantidad                               = $request->cantidad;
        $nombrelibro                            = $request->nombrelibro;
        $descripcion                            = $request->descripcion;
        $precio                                 = $request->precio;
        ///save
        $detalle                                = new VerificacionDescuentoDetalle();
        $detalle->verificaciones_descuentos_id  = $descuento_id;
        $detalle->contrato                      = $contrato;
        $detalle->num_verificacion              = $num_verificacion;
        $detalle->codigo_libro                  = null;
        $detalle->codigo                        = $codigo_liquidacion;
        $detalle->cantidad_descontar            = $cantidad;
        $detalle->precio                        = $precio;
        $detalle->nombre_libro                  = $nombrelibro;
        $detalle->descripcion                   = $descripcion;
        $detalle->porcentaje_descuento          = 0;
        $detalle->total_descontar               = 0;
        $detalle->tipo_calculo                  = 0;
        $detalle->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

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
    //API:POST/descuentosEliminar
    public function descuentosEliminar(Request $request){
        if($request->eliminarDescuentos)    { return $this->eliminarDescuentos($request->id); }
        if($request->eliminarRegistroLibro) { return $this->eliminarRegistroLibro($request->id,$request->verificaciones_descuentos_id); }
    }
    public function eliminarDescuentos($id){
        $descuento = VerificacionDescuento::findOrFail($id)->delete();
        DB::table('verificaciones_descuentos_detalle')->where('verificaciones_descuentos_id', '=', $id)->delete();
        return ["status" => "1" ,"message" => "Se elimino correctamente"];
    }
    public function eliminarRegistroLibro($id,$verificaciones_descuentos_id){
        VerificacionDescuentoDetalle::findOrFail($id)->delete();
        return $this->actualizarDescuentoXId($id,$verificaciones_descuentos_id);
    }
    public function actualizarDescuentoXId($hijo,$padre){
        $total_descuento = 0;
        $query = DB::SELECT("SELECT * FROM verificaciones_descuentos_detalle d
        WHERE d.verificaciones_descuentos_id = '$padre'
        ");
        if(empty($query)) { $total_descuento = 0; }
        else{
            foreach($query as $key => $item){
                $total_descuento = $total_descuento + $item->total_descontar;
            }
        }
        $descuento = VerificacionDescuento::findOrFail($padre);
        $descuento->total_descuento = $total_descuento;
        $descuento->save();
    }
}
