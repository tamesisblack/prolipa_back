<?php

namespace App\Http\Controllers;

use App\Models\Institucion;
use App\Models\Ciudad;
use App\Models\PeriodoInstitucion;
use Illuminate\Http\Request;
use DB;
use App\Quotation;
use App\Models\Configuracion_salle;
use Illuminate\Support\Facades\Cache;

class InstitucionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    //API:GET/institucion
    public function index(Request $request)
    {
        if($request->listaInstitucionesActivaXRegion){
            return $this->listaInstitucionesActivaXRegion($request->region);
        }
        $institucion = DB::select("CALL `listar_instituciones_periodo_activo` ();");
        return $institucion;

    }
    //API:GET/institucion?listaInstitucionesActivaXRegion=yes&region=2
    public function listaInstitucionesActivaXRegion($region){
        $key = "listaInstitucionesActivaXRegion".$region;
        if (Cache::has($key)) {
           $institucion = Cache::get($key);
        } else {
            $institucion = DB::SELECT("SELECT inst.idInstitucion,
            UPPER(inst.nombreInstitucion) as nombreInstitucion,
            UPPER(ciu.nombre) as ciudad,
            UPPER(reg.nombreregion) as nombreregion,
            inst.solicitudInstitucion,
            -- inst.vendedorInstitucion as asesor
            concat_ws(' ', usu.nombres, usu.apellidos) as asesor,
            inst.region_idregion
            FROM institucion inst, ciudad ciu, region reg, usuario usu
            where inst.ciudad_id = ciu.idciudad
            AND inst.region_idregion = reg.idregion
            AND inst.vendedorInstitucion = usu.cedula
            AND inst.estado_idEstado = 1
            AND inst.region_idregion = ?
            ",[$region]);
            Cache::put($key,$institucion);
        }
        return $institucion;
    }
    public function traerInstitucion(Request $request){
        $institucion = DB::select("SELECT * FROM institucion WHERE  idInstitucion = $request->institucion_idInstitucion
        ");
        return $institucion;
    }

    public function selectInstitucion(Request $request){
        if(empty($request->idregion) && empty($request->idciudad)){
            $institucion = DB::SELECT("SELECT i.idInstitucion,UPPER(i.nombreInstitucion) as nombreInstitucion
             FROM institucion i, periodoescolar_has_institucion pi
              WHERE i.idInstitucion != 66
              AND i.idInstitucion != 1170
              AND i.idInstitucion != 981
              AND i.idInstitucion != 914
              AND i.idInstitucion != 871
              AND i.idInstitucion != 1281
              AND i.estado_idEstado = 1
              AND i.punto_venta = '0'
              AND pi.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi WHERE phi.institucion_idInstitucion = i.idInstitucion)
            ");
        }
        if(!empty($request->idregion) && empty($request->idciudad)){
            $institucion = DB::SELECT("SELECT i.idInstitucion,UPPER(i.nombreInstitucion) as nombreInstitucion
             FROM institucion i, periodoescolar_has_institucion pi
             WHERE i.region_idregion = ? AND i.idInstitucion != 66
             AND i.idInstitucion != 1170
              AND i.idInstitucion != 981
              AND i.idInstitucion != 914
              AND i.idInstitucion != 871
              AND i.idInstitucion != 1281
             AND i.estado_idEstado = 1
             AND i.punto_venta = '0'
            AND pi.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi WHERE phi.institucion_idInstitucion = i.idInstitucion)
            ",[$request->idregion]);
        }
        if(!empty($request->idciudad) && empty($request->idregion)){
            $institucion = DB::SELECT("SELECT i.idInstitucion,UPPER(i.nombreInstitucion) as nombreInstitucion
             FROM institucion i, periodoescolar_has_institucion pi
             WHERE i.ciudad_id = ? AND i.idInstitucion != 66
              AND i.idInstitucion != 1170
              AND i.idInstitucion != 981
              AND i.idInstitucion != 914
              AND i.idInstitucion != 871
              AND i.idInstitucion != 1281
              AND i.estado_idEstado = 1
              AND i.punto_venta = '0'
             AND pi.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi WHERE phi.institucion_idInstitucion = i.idInstitucion)
             ",[$request->idciudad]);
        }
        if(!empty($request->idciudad) && !empty($request->idregion)){
            $institucion = DB::SELECT("SELECT idInstitucion,UPPER(nombreInstitucion) as nombreInstitucion
            FROM institucion i, periodoescolar_has_institucion pi
             WHERE i.ciudad_id = ? AND i.region_idregion = ? AND i.idInstitucion != 66
              AND i.idInstitucion != 1170
              AND i.idInstitucion != 981
              AND i.idInstitucion != 914
              AND i.idInstitucion != 871
              AND i.idInstitucion != 1281
              AND i.estado_idEstado = 1
              AND i.punto_venta = '0'
             AND pi.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi WHERE phi.institucion_idInstitucion = i.idInstitucion)
            ",[$request->idciudad,$request->idregion]);
        }
        return $institucion;

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    //api:get/institucionActiva
    public function institucionActiva(Request $request){
        $validar = DB::SELECT("SELECT i.estado_idEstado AS estado,u.nombres,u.apellidos FROM institucion i
        LEFT JOIN usuario u ON i.asesor_id = u.idusuario
        WHERE i.idInstitucion = '$request->idInstitucion'
        ");
        return $validar;
    }
    public function store(Request $request)
    {
        $datosValidados=$request->validate([
            'nombreInstitucion' => 'required',
            'telefonoInstitucion' => 'required',
            'direccionInstitucion' => 'required',
            'vendedorInstitucion' => 'required',
            'region_idregion' => 'required',
            'solicitudInstitucion' => 'required',
            'ciudad_id' => 'required',
            // 'zona_id' => 'required', se debe quitar cuando la zona sea obligatoria
            'tipo_institucion' => 'required',
        ]);
        if(!empty($request->idInstitucion)){
            // $institucion = Institucion::find($request->idInstitucion)->update($request->all());
            $cambio = Institucion::find($request->idInstitucion);
            $archivo = $cambio->imgenInstitucion;
            if($request->enviarArchivo){
                //eliminar el archivo anterior si existe
                if($archivo == "" || $archivo == null || $archivo == 0){
                }else{
                    if(file_exists('archivos/instituciones_logos/'.$archivo) ){
                        unlink('archivos/instituciones_logos/'.$archivo);
                    }
                }
                $ruta = public_path('/archivos/instituciones_logos/');
                if(!empty($request->file('imagenInstitucion'))){
                    $file = $request->file('imagenInstitucion');
                    $fileName = uniqid().$file->getClientOriginalName();
                    $file->move($ruta,$fileName);
                }
                $cambio->imgenInstitucion = $fileName;
            }
        }
        else{
            $cambio = new Institucion();
            if($request->enviarArchivo){
                $ruta = public_path('/archivos/instituciones_logos/');
                if(!empty($request->file('imagenInstitucion'))){
                    $file = $request->file('imagenInstitucion');
                    $fileName = uniqid().$file->getClientOriginalName();
                    $file->move($ruta,$fileName);
                }
                $cambio->imgenInstitucion       = $fileName;
            }
        }
        $cambio->idcreadorinstitucion           = $request->idcreadorinstitucion;
        $cambio->nombreInstitucion              = $request->nombreInstitucion;
        $cambio->direccionInstitucion           = $request->direccionInstitucion;
        $cambio->telefonoInstitucion            = $request->telefonoInstitucion;
        $cambio->email                          = $request->email == null || $request->email == "null" ? null : $request->email == null;
        $cambio->solicitudInstitucion           = $request->solicitudInstitucion;
        $cambio->codigo_institucion_milton      = $request->codigo_institucion_milton;
        $cambio->vendedorInstitucion            = $request->vendedorInstitucion;
        $cambio->tipo_institucion               = $request->tipo_institucion;
        $cambio->region_idregion                = $request->region_idregion;
        $cambio->ciudad_id                      = $request->ciudad_id;
        $cambio->estado_idEstado                = $request->estado;
        $cambio->aplica_matricula               = $request->aplica_matricula;
        $cambio->punto_venta                    = $request->punto_venta;
        $cambio->zona_id                        = $request->zona_id;
        $cambio->asesor_id                      = $request->asesor_id;
        $cambio->maximo_porcentaje_autorizado   = $request->maximo_porcentaje_autorizado;
        $cambio->evaluacion_personalizada       = $request->evaluacion_personalizada;
        $cambio->cantidad_cambio_ventana_evaluacion     = $request->cantidad_cambio_ventana_evaluacion;
        $cambio->ifcodigoEvaluacion             = $request->ifcodigoEvaluacion;
        $cambio->ruc                            = $request->ruc;
        $cambio->save();
        return $cambio;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Institucion  $institucion
     * @return \Illuminate\Http\Response
     */
    public function show(Institucion $institucion)
    {
        return $institucion;
    }


    public function verInstitucionCiudad($idciudad)
    {
        $instituciones = DB::SELECT("SELECT idInstitucion as id, nombreInstitucion as label
         FROM institucion
         WHERE idInstitucion != '66'
         AND idInstitucion != '981'
         AND idInstitucion != '795'
         AND idInstitucion != '871'
         AND idInstitucion != '914'
         AND idInstitucion != '1170'
         AND idInstitucion != '1281'
         AND ciudad_id = '$idciudad'
         AND estado_idEstado = '1'
         ");
        return $instituciones;
    }


    public function verificarInstitucion($id)
    {
        $instituciones = DB::SELECT("SELECT u.institucion_idInstitucion, i.* FROM usuario u, institucion i WHERE u.idusuario = $id AND u.institucion_idInstitucion = i.idInstitucion");

        return $instituciones;
    }


    public function asignarInstitucion(Request $request)
    {
        $institucion = DB::UPDATE("UPDATE usuario SET institucion_idInstitucion = $request->institucion WHERE idusuario = $request->usuario");

        return $institucion;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Institucion  $institucion
     * @return \Illuminate\Http\Response
     */
    public function edit(Institucion $institucion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Institucion  $institucion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Institucion $institucion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Institucion  $institucion
     * @return \Illuminate\Http\Response
     */
    public function destroy(Institucion $institucion)
    {
        $institucion = Institucion::find($institucion->idarea)->update(['estado_idEstado' => '0']);
        return $institucion;
    }


    //guardar foto de institucion desde perfil de director
    public function guardarLogoInstitucion(Request $request)
    {
        $cambio = Institucion::find($request->institucion_idInstitucion);

        $ruta = public_path('/instituciones_logos');
        if(!empty($request->file('archivo'))){
        $file = $request->file('archivo');
        $fileName = uniqid().$file->getClientOriginalName();
        $file->move($ruta,$fileName);
        $cambio->imgenInstitucion = $fileName;
        }

        $cambio->ideditor = $request->ideditor;
        $cambio->nombreInstitucion = $request->nombreInstitucion;
        $cambio->direccionInstitucion = $request->direccionInstitucion;
        $cambio->telefonoInstitucion = $request->telefonoInstitucion;
        $cambio->region_idregion = $request->region_idregion;
        $cambio->ciudad_id = $request->ciudad_id;
        $cambio->updated_at = now();

        $cambio->save();
        return $cambio;

    }
    public function institucionesSalle()
    {
        // $institucion = DB::select("SELECT nombreInstitucion, idInstitucion FROM  institucion  WHERE tipo_institucion = 2 and estado_idEstado = 1 ");
        // return $institucion;

        $institucion = DB::select("SELECT i.nombreInstitucion, i.idInstitucion, concat(i.nombreInstitucion,' - ',c.nombre) AS institucion_ciudad
        FROM  institucion i
        INNER JOIN ciudad c ON i.ciudad_id = c.idciudad
        WHERE i.tipo_institucion = 2 and i.estado_idEstado = 1 ");
        return $institucion;
    }

    public function instituciones_salle(){
        $instituciones = DB::SELECT("SELECT i.*, c.nombre as nombre_ciudad,
        concat(i.nombreInstitucion,' - ',c.nombre) AS institucion_ciudad,
         sc.fecha_inicio, sc.fecha_fin, sc.ver_respuestas, sc.observaciones,
         sc.cant_evaluaciones
         FROM institucion i
         INNER JOIN ciudad c ON i.ciudad_id = c.idciudad
         LEFT JOIN salle_configuracion sc ON i.id_configuracion = sc.id_configuracion
         WHERE i.tipo_institucion = 2
         ");
        if(!empty($instituciones)){
            foreach ($instituciones as $key => $value) {
                $periodo = DB::SELECT("SELECT p.idperiodoescolar, p.fecha_inicial, p.fecha_final,
                p.periodoescolar, p.estado FROM periodoescolar_has_institucion pi,
                periodoescolar p
                WHERE pi.institucion_idInstitucion = ?
                AND pi.periodoescolar_idperiodoescolar = p.idperiodoescolar
                ORDER BY p.idperiodoescolar
                DESC LIMIT 1",[$value->idInstitucion]);
                $data['items'][$key] = [
                    'institucion' => $value,
                    'periodo' => $periodo,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }
    //API:GET/instituciones_salle/{n_evaluacion}
    public function instituciones_salleXEvaluacion($n_evaluacion){
        $instituciones = DB::SELECT("SELECT i.*, c.nombre as nombre_ciudad,
        concat(i.nombreInstitucion,' - ',c.nombre) AS institucion_ciudad,
         sc.fecha_inicio, sc.fecha_fin, sc.ver_respuestas, sc.observaciones,
         sc.cant_evaluaciones
         FROM institucion i
         INNER JOIN ciudad c ON i.ciudad_id = c.idciudad
         LEFT JOIN salle_configuracion sc ON i.id_configuracion = sc.id_configuracion
         WHERE i.tipo_institucion = 2
         ");
         //get configuracion x institucion
        $datos = [];
        $contador = 0;
        foreach($instituciones as $key => $item){
            //traer configuracion x evaluacion
            $query = $this->configuracionXInstitucion($item->idInstitucion,$n_evaluacion);
            if(count($query) > 0){
                $datos[$contador] = (Object) [
                    "idInstitucion"     => $item->idInstitucion,
                    "nombreInstitucion" => $item->nombreInstitucion,
                    "region_idregion"   => $item->region_idregion,
                    "tipo_institucion"  => $item->tipo_institucion,
                    "nombre_ciudad"     => $item->nombre_ciudad,
                    "institucion_ciudad"=> $item->institucion_ciudad,
                    "id_configuracion"  => $query[0]->id_configuracion,
                    "fecha_inicio"      => $query[0]->fecha_inicio,
                    "fecha_fin"         => $query[0]->fecha_fin,
                    "cant_evaluaciones" => $query[0]->cant_evaluaciones,
                    "ver_respuestas"    => $query[0]->ver_respuestas,
                    "observaciones"     => $query[0]->observaciones,
                    "n_evaluacion"      => $query[0]->n_evaluacion
                ];
            }else{
                $datos[$contador] = (Object) [
                    "idInstitucion"     => $item->idInstitucion,
                    "nombreInstitucion" => $item->nombreInstitucion,
                    "region_idregion"   => $item->region_idregion,
                    "tipo_institucion"  => $item->tipo_institucion,
                    "nombre_ciudad"     => $item->nombre_ciudad,
                    "institucion_ciudad"=> $item->institucion_ciudad,
                    "id_configuracion"  => 0,
                    "fecha_inicio"      => null,
                    "fecha_fin"         => null,
                    "cant_evaluaciones" => null,
                    "ver_respuestas"    => null,
                    "observaciones"     => null,
                    "n_evaluacion"      => null
                ];
            }
            $contador++;
        }
        if(!empty($datos)){
            foreach ($datos as $key => $value) {
                $periodo = DB::SELECT("SELECT p.idperiodoescolar, p.fecha_inicial, p.fecha_final,
                p.periodoescolar, p.estado FROM periodoescolar_has_institucion pi,
                periodoescolar p
                WHERE pi.institucion_idInstitucion = ?
                AND pi.periodoescolar_idperiodoescolar = p.idperiodoescolar
                ORDER BY p.idperiodoescolar
                DESC LIMIT 1",[$value->idInstitucion]);
                $data['items'][$key] = [
                    'institucion' => $value,
                    'periodo' => $periodo,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }
    public function instituciones_salle_select(){
        $instituciones = DB::SELECT("SELECT i.*, c.nombre as nombre_ciudad, concat(i.nombreInstitucion,' - ',c.nombre) AS institucion_ciudad, sc.fecha_inicio, sc.fecha_fin, sc.ver_respuestas, sc.observaciones, sc.cant_evaluaciones FROM institucion i INNER JOIN ciudad c ON i.ciudad_id = c.idciudad LEFT JOIN salle_configuracion sc ON i.id_configuracion = sc.id_configuracion WHERE i.tipo_institucion = 2");

        return $instituciones;
    }
    public function configuracionXInstitucion($institucion_id,$n_evaluacion){
        $query = DB::SELECT("SELECT * FROM salle_configuracion c
        WHERE c.institucion_id = '$institucion_id'
        AND c.n_evaluacion  = '$n_evaluacion'
        ");
        return $query;
    }
    public function save_instituciones_salle(Request $request){
        if( $request->id_configuracion == 0 ){
            //validate que no exista para crear la configuracion
            $validate = $this->configuracionXInstitucion($request->id_institucion,$request->n_evaluacion);
            if(count($validate) > 0){
                return "Se guardo";
            }
            $configuracion                  = new Configuracion_salle();
            $configuracion->institucion_id  = $request->id_institucion;
            $configuracion->n_evaluacion    = $request->n_evaluacion;
        }else{
            $configuracion                  = Configuracion_salle::find($request->id_configuracion);
        }
        $configuracion->fecha_inicio        = $request->fecha_inicio;
        $configuracion->fecha_fin           = $request->fecha_fin;
        $configuracion->ver_respuestas      = $request->ver_respuestas;
        $configuracion->observaciones       = $request->observaciones;
        $configuracion->cant_evaluaciones   = $request->cant_evaluaciones;
        $configuracion->save();
        if( $request->id_configuracion == 0 ){
            DB::UPDATE("UPDATE `institucion` SET `id_configuracion` = $configuracion->id_configuracion
            WHERE `idInstitucion` = $request->id_institucion
            ");
        }
        return $configuracion;
    }
    public function listaInstitucionesActiva(){

        $institucion = DB::SELECT("SELECT inst.idInstitucion,
        UPPER(inst.nombreInstitucion) as nombreInstitucion,
        UPPER(ciu.nombre) as ciudad,
        UPPER(reg.nombreregion) as nombreregion,
        inst.solicitudInstitucion,
        -- inst.vendedorInstitucion as asesor
        concat_ws(' ', usu.nombres, usu.apellidos) as asesor


        FROM institucion inst, ciudad ciu, region reg, usuario usu
        where inst.ciudad_id = ciu.idciudad
        AND inst.region_idregion = reg.idregion
        AND inst.vendedorInstitucion = usu.cedula
        AND inst.estado_idEstado = 1");
                return $institucion;
    }
    public function institucionConfiguracionSalle($id_institucion,$n_evaluacion)
    {
        $configuracion = $this->configuracionXInstitucion($id_institucion,$n_evaluacion);
        // $configuracion = DB::SELECT("SELECT inst.id_configuracion, sc.*
        // FROM institucion inst, salle_configuracion sc
        // WHERE inst.id_configuracion = sc.id_configuracion
        // AND inst.idInstitucion  = $id");
        // return $configuracion;
        return $configuracion;
    }
    public function listaInsitucion(Request $request)
    {
        if($request->asesor){
            $cedula = $request->cedula;
            $lista = DB::SELECT("SELECT i.idInstitucion,i.region_idregion, i.nombreInstitucion,i.aplica_matricula,
            IF(i.estado_idEstado = '1','activado','desactivado') AS estado,i.estado_idEstado as estadoInstitucion,
            c.nombre AS ciudad, u.idusuario AS asesor_id,u.nombres AS nombre_asesor,
            u.apellidos AS apellido_asesor, i.fecha_registro, r.nombreregion, i.codigo_institucion_milton,
            ic.estado as EstadoConfiguracion, ic.periodo_configurado,i.codigo_mitlon_coincidencias,
            pec.periodoescolar as periodoNombreConfigurado,i.vendedorInstitucion,u.iniciales,i.cantidad_cambio_ventana_evaluacion,
            i.punto_venta
            FROM institucion i
            LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
            LEFT JOIN region r ON i.region_idregion = r.idregion
            LEFT JOIN usuario u ON i.vendedorInstitucion = u.cedula
            LEFT JOIN institucion_configuracion_periodo ic ON i.region_idregion = ic.region
            LEFT JOIN periodoescolar pec ON ic.periodo_configurado = pec.idperiodoescolar
            WHERE i.nombreInstitucion LIKE '%$request->busqueda%'
            AND  i.vendedorInstitucion = '$cedula'
            ORDER BY i.fecha_registro DESC
            ");
        }else{
            $lista = DB::SELECT("SELECT DISTINCT i.idInstitucion,i.region_idregion, i.nombreInstitucion,i.aplica_matricula,
            IF(i.estado_idEstado = '1','activado','desactivado') AS estado,i.estado_idEstado as estadoInstitucion,
            c.nombre AS ciudad, u.idusuario AS asesor_id,u.nombres AS nombre_asesor,
            u.apellidos AS apellido_asesor, i.fecha_registro, r.nombreregion, i.codigo_institucion_milton,
            ic.estado as EstadoConfiguracion, ic.periodo_configurado,i.codigo_mitlon_coincidencias,
            pec.periodoescolar as periodoNombreConfigurado,i.vendedorInstitucion,u.iniciales,i.cantidad_cambio_ventana_evaluacion,
            i.punto_venta
            FROM institucion i
            LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
            LEFT JOIN region r ON i.region_idregion = r.idregion
            LEFT JOIN usuario u ON i.vendedorInstitucion = u.cedula
            LEFT JOIN institucion_configuracion_periodo ic ON i.region_idregion = ic.region
            LEFT JOIN periodoescolar pec ON ic.periodo_configurado = pec.idperiodoescolar
            WHERE i.nombreInstitucion LIKE '%$request->busqueda%'
            ORDER BY i.idInstitucion, i.fecha_registro DESC
            ");
        }
        $datos = [];
        if(count($lista) ==0){
            return ["status" => "0","message"=> "No se encontro instituciones con ese nombre"];
        }else{

            foreach($lista as $key => $item){
                //buscar periodo
                $periodoInstitucion = DB::SELECT("SELECT idperiodoescolar AS periodo_id , periodoescolar AS periodo,
                IF(estado = '1' ,'Activo','Desactivado') as estadoPeriodo,estado
                 FROM periodoescolar
                  WHERE idperiodoescolar = (
                    SELECT  pir.periodoescolar_idperiodoescolar as id_periodo
                    from institucion i,  periodoescolar_has_institucion pir
                    WHERE i.idInstitucion = pir.institucion_idInstitucion
                    AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
                    WHERE phi.institucion_idInstitucion = i.idInstitucion
                    AND i.idInstitucion = '$item->idInstitucion'))
                ");
                if(count($periodoInstitucion) > 0){
                    $datos[$key]=[
                        "idInstitucion" =>     $item->idInstitucion,
                        "nombreInstitucion" => $item->nombreInstitucion,
                        "aplica_matricula" =>  $item->aplica_matricula,
                        "estado" =>            $item->estado,
                        "estadoInstitucion" => $item->estadoInstitucion,
                        "ciudad" =>            $item->ciudad,
                        "asesor_id" =>         $item->asesor_id,
                        "nombre_asesor" =>     $item->nombre_asesor,
                        "apellido_asesor" =>   $item->apellido_asesor,
                        "asesor"           =>  $item->nombre_asesor." ".$item->apellido_asesor,
                        "fecha_registro" =>    $item->fecha_registro,
                        "nombreregion" =>      $item->nombreregion,
                        "periodo_id" =>        $periodoInstitucion[0]->periodo_id,
                        "periodo" =>           $periodoInstitucion[0]->periodo,
                        "estadoPeriodo" =>     $periodoInstitucion[0]->estadoPeriodo,
                        "statusPeriodo" =>     $periodoInstitucion[0]->estado,
                        "EstadoConfiguracion" =>  $item->EstadoConfiguracion,
                        "periodo_configurado" => $item->periodo_configurado,
                        "periodoNombreConfigurado" => $item->periodoNombreConfigurado,
                        "codigo_institucion_milton" => $item->codigo_institucion_milton,
                        "codigo_mitlon_coincidencias" => $item->codigo_mitlon_coincidencias,
                        "vendedorInstitucion"   => $item->vendedorInstitucion,
                        "iniciales"             => $item->iniciales,
                        "region"                => $item->region_idregion,
                        "cantidad_cambio_ventana_evaluacion" => $item->cantidad_cambio_ventana_evaluacion,
                        "punto_venta" => $item->punto_venta
                    ];
                }else{
                    $datos[$key]=[
                        "idInstitucion" =>     $item->idInstitucion,
                        "nombreInstitucion" => $item->nombreInstitucion,
                        "aplica_matricula" =>  $item->aplica_matricula,
                        "estado" =>            $item->estado,
                        "estadoInstitucion" => $item->estadoInstitucion,
                        "ciudad" =>            $item->ciudad,
                        "asesor_id" =>         $item->asesor_id,
                        "nombre_asesor" =>     $item->nombre_asesor,
                        "apellido_asesor" =>   $item->apellido_asesor,
                        "fecha_registro" =>    $item->fecha_registro,
                        "nombreregion" =>      $item->nombreregion,
                        "periodo_id" =>        '0',
                        "periodo" =>           'Sin periodo',
                        "estadoPeriodo" =>     "",
                        "EstadoConfiguracion" =>  $item->EstadoConfiguracion,
                        "periodo_configurado" => $item->periodo_configurado,
                        "periodoNombreConfigurado" => $item->periodoNombreConfigurado,
                        "codigo_institucion_milton" => $item->codigo_institucion_milton,
                        "codigo_mitlon_coincidencias" => $item->codigo_mitlon_coincidencias,
                        "vendedorInstitucion"   => $item->vendedorInstitucion,
                        "iniciales"             => $item->iniciales,
                        "region"                => $item->region_idregion,
                        "cantidad_cambio_ventana_evaluacion" => $item->cantidad_cambio_ventana_evaluacion,
                        "punto_venta" => $item->punto_venta
                    ];
                }
            }
            if($request->todas){
                return $datos;
            }
            else{
                $resultado = collect($datos)->where('estadoInstitucion','1')->values();
                return $resultado;
            }
        }
    }

    public function listaInsitucionAsesor(Request $request)
    {
        if($request->porCedula){
            $lista = Institucion::select('institucion.idInstitucion','institucion.nombreInstitucion','institucion.aplica_matricula','institucion.solicitudInstitucion','estado.nombreestado as estado','ciudad.nombre as ciudad','usuario.idusuario as asesor_id','usuario.nombres as nombre_asesor', 'usuario.apellidos as apellido_asesor', 'institucion.fecha_registro', 'region.nombreregion' )
            ->leftjoin('ciudad','institucion.ciudad_id','=','ciudad.idciudad')
            ->leftjoin('region','institucion.region_idregion','=','region.idregion')
            ->leftjoin('usuario','institucion.vendedorInstitucion','=','usuario.cedula')
            ->join('estado','institucion.estado_idEstado','=','estado.idEstado')
            ->where('institucion.vendedorInstitucion','=',$request->cedula)
            ->orderBy('institucion.fecha_registro','desc')
            ->get();
        }
        //traer las instituciones temporales  creadas por el asesor
        if($request->temporales){
            $instituciones = DB::SELECT("SELECT t.institucion_temporal_id,
            IF(t.region = 2,'Costa','Sierra') AS nombreregion,
            t.nombre_institucion AS nombreInstitucion,
            t.periodo_id,t.asesor_id,t.ciudad,pe.periodoescolar AS periodo
            FROM seguimiento_institucion_temporal t
         	LEFT JOIN periodoescolar pe ON t.periodo_id = pe.idperiodoescolar
            WHERE t.asesor_id = '$request->asesor_id'
            ORDER BY t.institucion_temporal_id DESC

            ");
            return $instituciones;
        }
        //traer la agenda por instituciones de prolipa o temporales
        if($request->todo){
            $todoAgenda = DB::SELECT("SELECT i.nombreInstitucion,
            c.*,
           CONCAT(u.nombres, ' ', u.apellidos) AS asesor,
           CONCAT(f.nombres, ' ', f.apellidos) AS finalizador,
           CONCAT(cr.nombres, ' ', cr.apellidos) AS creador,
           (case when (c.estado_institucion_temporal = 1) then c.nombre_institucion_temporal  else i.nombreInstitucion end) as institucionFinal,
           (case when (c.estado = 1) then 'Finalizada' else 'Generada' end) as status,
           p.idperiodoescolar,p.periodoescolar AS periodo
            FROM agenda_usuario c
            LEFT JOIN usuario u ON c.id_usuario = u.idusuario
            LEFT JOIN usuario f ON c.usuario_editor = f.idusuario
            LEFT JOIN usuario cr ON c.usuario_creador = cr.idusuario
            LEFT JOIN periodoescolar p ON c.periodo_id = p.idperiodoescolar
            LEFT JOIN institucion i ON c.institucion_id = i.idInstitucion
           WHERE c.id_usuario = '$request->asesor_id'
           AND c.estado <> '2'
           ORDER BY c.id DESC
            ");
            if(count($todoAgenda) == 0){
               return [];
            }else{
                $query = collect($todoAgenda);
                $data  = $query->each(function($item,$key){
                    if($item->estado_institucion_temporal == '1'){
                        $item->userAsesor = $item->asesor;
                    }else{
                        $getInstitucion = Institucion::Where('idInstitucion',$item->institucion_id)->with('asesor')->get();
                        if(count($getInstitucion) > 0){
                            $item->userAsesor = $getInstitucion[0]->asesor->nombres." ".$getInstitucion[0]->asesor->apellidos;
                        }else{
                            $item->userAsesor = "";
                        }
                    }
                })->chunk(10)->flatten();
                return $data;
            }
        }
        if($request->todasInstituciones){
            $lista = Institucion::select('institucion.idInstitucion','institucion.nombreInstitucion','institucion.aplica_matricula','institucion.solicitudInstitucion','estado.nombreestado as estado','ciudad.nombre as ciudad','usuario.idusuario as asesor_id','usuario.nombres as nombre_asesor', 'usuario.apellidos as apellido_asesor', 'institucion.fecha_registro', 'region.nombreregion' )
            ->leftjoin('ciudad','institucion.ciudad_id','=','ciudad.idciudad')
            ->leftjoin('region','institucion.region_idregion','=','region.idregion')
            ->leftjoin('usuario','institucion.vendedorInstitucion','=','usuario.cedula')
            ->join('estado','institucion.estado_idEstado','=','estado.idEstado')
            ->where('institucion.nombreInstitucion','like','%'.$request->busqueda.'%')
            ->orderBy('institucion.fecha_registro','desc')
            ->get();
        }
        else{

            $lista = Institucion::select('institucion.idInstitucion','institucion.nombreInstitucion','institucion.aplica_matricula','institucion.solicitudInstitucion','estado.nombreestado as estado','ciudad.nombre as ciudad','usuario.idusuario as asesor_id','usuario.nombres as nombre_asesor', 'usuario.apellidos as apellido_asesor', 'institucion.fecha_registro', 'region.nombreregion' )
            ->leftjoin('ciudad','institucion.ciudad_id','=','ciudad.idciudad')
            ->leftjoin('region','institucion.region_idregion','=','region.idregion')
            ->leftjoin('usuario','institucion.vendedorInstitucion','=','usuario.cedula')
            ->join('estado','institucion.estado_idEstado','=','estado.idEstado')
            ->where('institucion.nombreInstitucion','like','%'.$request->busqueda.'%')
            ->where('institucion.vendedorInstitucion','=',$request->cedula)
            ->orderBy('institucion.fecha_registro','desc')
            ->get();
        }

        if(count($lista) ==0){
            return ["status" => "0","message"=> "Esta institución no esta asignada a su perfil"];
        }else{
            return $lista;
        }

    }
    //api::post//>/institucionEliminar
    public function institucionEliminar(Request $request){
        Institucion::findOrFail($request->id)->delete();
        return "Se elimino correctamente";
    }

    public function instituciones_periodo(Request $request)
    {
        $lista = DB::SELECT("SELECT inst.*, ase.cedula, ase.iniciales, CONCAT(ase.nombres, ase.apellidos)as nombres FROM institucion inst
        INNER JOIN periodoescolar_has_institucion pei ON pei.institucion_idInstitucion=inst.idInstitucion
        INNER JOIN periodoescolar p ON p.idperiodoescolar=pei.periodoescolar_idperiodoescolar
        INNER JOIN usuario ase ON ase.idusuario=inst.asesor_id
        WHERE pei.periodoescolar_idperiodoescolar= '$request->id' ORDER BY inst.fecha_registro DESC");
        return $lista;
    }
    public function instituciones_ciudad(Request $request)
    {
        $lista = DB::SELECT("SELECT i.idInstitucion, i.nombreInstitucion,i.aplica_matricula,
        IF(i.estado_idEstado = '1','activado','desactivado') AS estado,i.estado_idEstado as estadoInstitucion,
        c.nombre AS ciudad, u.idusuario AS asesor_id,u.nombres AS nombre_asesor,
        u.apellidos AS apellido_asesor, i.fecha_registro, r.nombreregion, i.codigo_institucion_milton,
        ic.estado as EstadoConfiguracion, ic.periodo_configurado,i.codigo_mitlon_coincidencias,
        pec.periodoescolar as periodoNombreConfigurado,i.vendedorInstitucion,u.iniciales
        FROM institucion i
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        LEFT JOIN region r ON i.region_idregion = r.idregion
        LEFT JOIN usuario u ON i.vendedorInstitucion = u.cedula
        LEFT JOIN institucion_configuracion_periodo ic ON i.region_idregion = ic.region
        LEFT JOIN periodoescolar pec ON ic.periodo_configurado = pec.idperiodoescolar
        WHERE i.ciudad_id = '$request->ciudad_id'
        ORDER BY i.fecha_registro DESC
        ");
        // return $lista;
        $datos = [];
        foreach($lista as $key => $item){
            $periodoInstitucion = DB::SELECT("SELECT idperiodoescolar AS periodo_id , periodoescolar AS periodo,
            IF(estado = '1' ,'Activo','Desactivado') as estadoPeriodo,estado
             FROM periodoescolar
              WHERE idperiodoescolar = (
                SELECT  pir.periodoescolar_idperiodoescolar as id_periodo
                from institucion i,  periodoescolar_has_institucion pir
                WHERE i.idInstitucion = pir.institucion_idInstitucion
                AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
                WHERE phi.institucion_idInstitucion = i.idInstitucion
                AND i.idInstitucion = '$item->idInstitucion'))
            ");
            $datos[$key] = [
                "institucion" => $item,
                "periodo"     => $periodoInstitucion
            ];
        }
        return $datos;
    }
    public function getInstitucionConfiguracion (){
        $dato = DB::table('institucion_configuracion_periodo as ic')
        ->leftjoin('periodoescolar as p', 'ic.periodo_configurado','p.idperiodoescolar')
        ->leftjoin('region as r', 'r.idregion','=','ic.region')
        ->select('ic.*','r.nombreregion', 'p.idperiodoescolar','p.descripcion','p.periodoescolar','p.codigo_contrato','p.porcentaje_descuento')
        ->get();
        return $dato;
    }
    //api:post/eliminarInstitucionConfiguradaBodega
    public function eliminarInstitucionConfiguradaBodega(Request $request){
        $dato = DB::table('institucion_configuracion_periodo')
        ->where('id',$request->id)
        ->delete();
        return $dato;
    }
    public function institucion_conf_periodo(Request $request)
    {
        $valores = [
            'id' => $request->id,
            'region' => $request->region,
            'periodo_configurado' => $request->periodo_configurado,
            'estado' => $request->estado
        ];
        if ($request->id > 0) {
            $dato = DB::table('institucion_configuracion_periodo')
            ->where('id',$request->id)
            ->update($valores);
            return [ 'dato'=>$dato, 'mensaje'=>'Datos actualizados'];
        }else {
            $dato = DB::table('institucion_configuracion_periodo')->insert($valores);
            return [ 'dato'=>$dato, 'mensaje'=>'Datos registrados'];
        }
    }
    public function InstitucionesXCobranzas(Request $request)
    {
        // $lista = DB::SELECT("SELECT i.idInstitucion, i.nombreInstitucion,i.punto_venta
        // FROM pedidos p
        // INNER JOIN institucion i ON i.idInstitucion = p.id_institucion
        // WHERE p.contrato_generado IS NOT NULL
        // AND p.id_periodo = '$request->periodo'");
        $lista = DB::SELECT("SELECT i.idInstitucion, i.nombreInstitucion
        FROM f_venta fv
        INNER JOIN institucion i ON i.idInstitucion = fv.institucion_id
        WHERE fv.clientesidPerseo = '$request->cliente'
        AND fv.id_empresa = '$request->empresa'
        AND fv.periodo_id = '$request->periodo'
        GROUP BY i.idInstitucion,i.nombreInstitucion");

        return $lista;
    }
    //se debe habilitar cuando la zona sea obligatoria
    // public function institucion_zona(Request $request)
    // {
    //    if($request->idInstitucion){
    //     $zonas = Institucion::findOrFail($request->idInstitucion);
    //     $zonas->zona_id = $request->zona_id;
    //    }else{
    //         return "No se pudo guardar/actualizar";
    //    }
    //     $zonas->save();
    //     if($zonas){
    //        return $zonas;
    //    }else{
    //        return "No se pudo guardar/actualizar";
    //    }
    // }
}
