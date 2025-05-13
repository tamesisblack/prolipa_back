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
        $cambio->email                          = $request->email == null || $request->email == "null" ? null : $request->email;
        $cambio->representante_legal            = $request->representante_legal == null || $request->representante_legal == "null" ? null : $request->representante_legal;
        $cambio->solicitudInstitucion           = $request->solicitudInstitucion;
        $cambio->codigo_institucion_milton      = $request->codigo_institucion_milton;
        $cambio->vendedorInstitucion            = $request->vendedorInstitucion;
        $cambio->tipo_institucion               = $request->tipo_institucion;
        $cambio->region_idregion                = $request->region_idregion;
        $cambio->ciudad_id                      = $request->ciudad_id;
        $cambio->estado_idEstado                = $request->estado;
        $cambio->aplica_matricula               = $request->aplica_matricula;
        $cambio->punto_venta                    = $request->punto_venta;
        // Validación para enviar NULL si está vacío o es "null"
        $cambio->zona_id = $request->zona_id === '' || $request->zona_id === 'null' ? null : $request->zona_id;
        $cambio->asesor_id                      = $request->asesor_id;
        $cambio->maximo_porcentaje_autorizado   = $request->maximo_porcentaje_autorizado;
        $cambio->evaluacion_personalizada       = $request->evaluacion_personalizada;
        $cambio->cantidad_cambio_ventana_evaluacion     = $request->cantidad_cambio_ventana_evaluacion;
        $cambio->ifcodigoEvaluacion             = $request->ifcodigoEvaluacion;
        $cambio->tipo_evaluacion                = $request->tipo_evaluacion;
        $cambio->mensaje_tipo_evaluacion       = $request->mensaje_tipo_evaluacion;
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
        $todas = $request->query('todas');
        $asesor = $request->query('asesor');

        $query = DB::table('institucion as i')
            ->leftJoin('ciudad as c', 'i.ciudad_id', '=', 'c.idciudad')
            ->leftJoin('region as r', 'i.region_idregion', '=', 'r.idregion')
            ->leftJoin('usuario as u', 'i.vendedorInstitucion', '=', 'u.cedula')
            
            // Último periodo configurado por región
            ->leftJoin('institucion_configuracion_periodo as ic', function ($join) {
                $join->on('i.region_idregion', '=', 'ic.region')
                    ->whereRaw('ic.id = (SELECT id FROM institucion_configuracion_periodo 
                                    WHERE region = i.region_idregion 
                                    AND estado = 1 
                                    ORDER BY id DESC LIMIT 1)');
            })
            ->leftJoin('periodoescolar as pec', 'ic.periodo_configurado', '=', 'pec.idperiodoescolar')

            // Último periodo escolar registrado para la institución
            ->leftJoinSub(
                DB::table('periodoescolar_has_institucion as phi')
                    ->selectRaw('phi.institucion_idInstitucion, phi.periodoescolar_idperiodoescolar as periodo_id')
                    ->whereRaw('phi.id = (SELECT MAX(id) FROM periodoescolar_has_institucion 
                                        WHERE institucion_idInstitucion = phi.institucion_idInstitucion)')
                , 'last_periodo', 'i.idInstitucion', '=', 'last_periodo.institucion_idInstitucion'
            )
            ->leftJoin('periodoescolar as pe', 'last_periodo.periodo_id', '=', 'pe.idperiodoescolar')

            ->select(
                'i.idInstitucion', 'i.region_idregion', 'i.nombreInstitucion', 'i.aplica_matricula',
                DB::raw("IF(i.estado_idEstado = '1','activado','desactivado') AS estado"),
                'i.estado_idEstado as estadoInstitucion', 'c.nombre AS ciudad', 'u.idusuario AS asesor_id',
                'u.nombres AS nombre_asesor', 'u.apellidos AS apellido_asesor', 'i.fecha_registro',
                'r.nombreregion', 'i.codigo_institucion_milton', 'i.vendedorInstitucion', 'u.iniciales',
                'i.cantidad_cambio_ventana_evaluacion', 'i.punto_venta', 'i.maximo_porcentaje_autorizado',
                'i.ruc', 'i.ifcodigoEvaluacion',

                // Último periodo activo por región
                'ic.periodo_configurado',
                'pec.periodoescolar as periodoNombreConfigurado',

                // EstadoConfiguracion: Si hay un periodo con estado = 1, será 1; si no, será 0
                DB::raw("COALESCE(ic.estado, 0) AS EstadoConfiguracion"),

                // Último periodo escolar por institución
                DB::raw("COALESCE(last_periodo.periodo_id, 0) AS periodo_id"),
                DB::raw("COALESCE(pe.periodoescolar, 'Sin periodo') AS periodo"),
                DB::raw("IF(pe.estado = '1', 'Activo', 'Desactivado') AS estadoPeriodo"),
                DB::raw("COALESCE(pe.estado, 0) AS statusPeriodo")
            )
            ->orderBy('i.idInstitucion') // Ordena por ID de institución
            ->orderBy('i.fecha_registro', 'DESC'); // Luego ordena por fecha de registro DESC

        if ($request->has('asesor')) {
            $query->where('i.vendedorInstitucion', '=', $request->cedula);
        }

        if ($request->has('busqueda')) {
            $query->where('i.nombreInstitucion', 'like', '%' . $request->busqueda . '%');
        }
        $lista = $query->get();
        if ($lista->isEmpty()) {
            return ["status" => "0", "message" => "No se encontró instituciones con ese nombre"];
        }

        $datos = $lista->map(function ($item) use ($asesor, $todas) {
            return [
                "idInstitucion" => $item->idInstitucion,
                "nombreInstitucion" => $item->nombreInstitucion,
                "aplica_matricula" => $item->aplica_matricula,
                "estado" => $item->estado,
                "estadoInstitucion" => $item->estadoInstitucion,
                "ciudad" => $item->ciudad,
                "asesor_id" => $item->asesor_id,
                "nombre_asesor" => $item->nombre_asesor,
                "apellido_asesor" => $item->apellido_asesor,
                "asesor" => $item->nombre_asesor . " " . $item->apellido_asesor,
                "fecha_registro" => $item->fecha_registro,
                "nombreregion" => $item->nombreregion,
                "vendedorInstitucion" => $item->vendedorInstitucion,
                "iniciales" => $item->iniciales,
                "region" => $item->region_idregion,
                "cantidad_cambio_ventana_evaluacion" => $item->cantidad_cambio_ventana_evaluacion,
                "punto_venta" => $item->punto_venta,
                "maximo_porcentaje_autorizado" => $item->maximo_porcentaje_autorizado,
                "ruc" => $item->ruc,
                "ifcodigoEvaluacion" => $item->ifcodigoEvaluacion,
                // Último periodo escolar por institución
                "periodo_id" => $item->periodo_id,
                "periodo" => $item->periodo,
                "estadoPeriodo" => $item->estadoPeriodo,
                "statusPeriodo" => $item->statusPeriodo,

                // Último periodo activo por región
                "periodo_configurado" => $item->periodo_configurado,
                "periodoNombreConfigurado" => $item->periodoNombreConfigurado,
                "EstadoConfiguracion" => $item->EstadoConfiguracion,
            ];
        });
        // Si el parámetro "todas" está presente, retornar todos los datos
        if ($request->has('todas') && $request->todas == true) {
            return $datos->values();
        }
        return $datos->where('estadoInstitucion', '1')->values();
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
            return $todoAgenda;
            if(count($todoAgenda) == 0){
               return [];
            }else{
                $query = collect($todoAgenda);
                $data  = $query->each(function($item,$key){
                    if($item->estado_institucion_temporal == '1'){
                        $item->userAsesor = $item->asesor;
                    }else{
                        $getInstitucion = DB::SELECT("SELECT u.nombres,u.apellidos  FROM institucion i
                        LEFT JOIN usuario u ON i.asesor_id = u.idusuario
                        WHERE i.idInstitucion = '$item->institucion_id'
                        ");
                        if(count($getInstitucion) > 0){
                            $item->userAsesor = $getInstitucion[0]->nombres." ".$getInstitucion[0]->apellidos;
                        }else{
                            $item->userAsesor = "No se encontro asesor";
                        }
                        // $getInstitucion = Institucion::Where('idInstitucion',$item->institucion_id)->with('asesor')->get();
                        // if(count($getInstitucion) > 0){
                        //     $item->userAsesor = $getInstitucion[0]->asesor->nombres." ".$getInstitucion[0]->asesor->apellidos;
                        // }else{
                        //     $item->userAsesor = "";
                        // }
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

    public function getinstitucion_libros(Request $request)
    {
        $lista = DB::SELECT("SELECT l.*, ls.nombre FROM librosinstituciones_detalle l
        INNER JOIN librosinstituciones li ON li.li_id = l.li_id
        LEFT JOIN libros_series ls ON l.lid_idLibro= ls.idLibro
        WHERE li.li_idInstitucion = '$request->institucion'
        AND li.li_periodo = '$request->periodo' ");
        return $lista;
    }

    public function getInfoinstitucion_libros(Request $request)
    {
        $lista = DB::SELECT("SELECT li.* FROM librosinstituciones li
        WHERE li.li_idInstitucion = '$request->institucion'
        AND li.li_periodo = '$request->periodo' ");
        return $lista;
    }

    public function guardarLibrosInstitucion(Request $request)
    {
        // Validación de los datos recibidos
        $request->validate([
            'codigo_institucion' => 'required|integer',
            'periodo_id' => 'required|integer',
            'libros' => 'required|array',
            'libros.*.idlibro' => 'required|integer',
            'libros.*.codigo' => 'required|string',
            'libros.*.nombrelibro' => 'required|string',
        ]);

        // Iniciar la transacción
        DB::beginTransaction();

        try {
            // Verificar si ya existe un registro en librosinstituciones con los mismos valores
            $existingRecord = DB::table('librosinstituciones')
                ->where('li_idInstitucion', $request->codigo_institucion)
                ->where('li_periodo', $request->periodo_id)
                ->first();

            if ($existingRecord) {
                // Si ya existe, obtenemos el ID de la institución
                $librosInstitucionId = $existingRecord->li_id;

                  // Actualizamos los campos 'li_codigo' y 'expires_at' en caso de que ya exista el registro
                DB::table('librosinstituciones')
                ->where('li_id', $librosInstitucionId)
                ->update([
                    'li_codigo' => $request->codigo,
                    'li_url' => $request->url,
                    'expires_at' => $request->expires,
                ]);

                // Verificar los libros que ya están asociados a esta institución
                $existingBooks = DB::table('librosinstituciones_detalle')
                    ->where('li_id', $librosInstitucionId)
                    ->pluck('lid_producto'); // Traemos solo los códigos de productos existentes

                // Primero, eliminar los libros que ya no están en el registro
                $booksToDelete = $existingBooks->diff(collect(array_column($request->libros, 'codigo')));

                if ($booksToDelete->isNotEmpty()) {
                    DB::table('librosinstituciones_detalle')
                        ->where('li_id', $librosInstitucionId)
                        ->whereIn('lid_producto', $booksToDelete->toArray())
                        ->delete();
                }

                // Insertar los nuevos libros que no están asociados
                foreach ($request->libros as $libro) {
                    // Verificar si este libro ya existe en los detalles
                    if (!in_array($libro['codigo'], $existingBooks->toArray())) {
                        // Insertamos solo los nuevos detalles
                        DB::table('librosinstituciones_detalle')->insert([
                            'li_id' => $librosInstitucionId, // Usar el ID de la institución existente
                            'lid_idLibro' => $libro['idlibro'],
                            'lid_producto' => $libro['codigo'],
                        ]);
                    }
                }

                // Confirmar transacción si todo es correcto
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Detalles actualizados correctamente.',
                ]);

            } else {
                // Si no existe, insertamos el registro en la tabla librosinstituciones
                $librosInstitucionId = DB::table('librosinstituciones')->insertGetId([
                    'li_idInstitucion' => $request->codigo_institucion,
                    'li_periodo' => $request->periodo_id,
                    'li_codigo' => $request->codigo,
                    'li_url' => $request->url,
                    'expires_at' => $request->expires,
                ]);

                if ($librosInstitucionId <= 0) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo generar un ID para la institución.',
                    ], 500);
                }

                // Insertar los detalles para los nuevos registros
                foreach ($request->libros as $libro) {
                    DB::table('librosinstituciones_detalle')->insert([
                        'li_id' => $librosInstitucionId,
                        'lid_idLibro' => $libro['idlibro'],
                        'lid_producto' => $libro['codigo'],
                    ]);
                }

                // Confirmar la transacción
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Información guardada con éxito.',
                ]);
            }

        } catch (Exception $e) {
            // Si ocurre un error, revertir la transacción
            DB::rollBack();

            // Devolver una respuesta de error
            return response()->json([
                'success' => false,
                'message' => 'Hubo un problema al guardar la información.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function InstitucionLibrosInformacion($institucion)
    {
        $lista = DB::SELECT("SELECT li.idInstitucion, li.region_idregion,li.nombreInstitucion FROM institucion li
        WHERE li.idInstitucion = '$institucion'");
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

    //METODOS JEYSON INICIO
    public function MoverInstitucionxAsesor(Request $request)
    {
        // return $request;
        DB::beginTransaction();
        $Id_Asesor_Seleccionado = $request->input('Id_Asesor_Seleccionado');
        $Cedula_Asesor_Seleccionado = $request->input('Cedula_Asesor_Seleccionado');
        $InstitucionesAMover = $request->input('InstitucionesAMover', []);
        try {
            // Si hay instituciones, moverlas
            foreach ($InstitucionesAMover as $institucionmover) {
                //Producto
                $actualizarasesorde_institucion = Institucion::find($institucionmover['id_institucion']);
                $actualizarasesorde_institucion->asesor_id = $Id_Asesor_Seleccionado;
                $actualizarasesorde_institucion->vendedorInstitucion = $Cedula_Asesor_Seleccionado;
                $actualizarasesorde_institucion->save();
            }
            DB::commit();
            return response()->json(["status" => "1", 'message' => 'Compra Finalizada correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["status" => "0", 'message' => 'Error al finalizar la compra: ' . $e->getMessage()], 500);
        }
    }
    //METODOS JEYSON FIN
    //novedades institucion
    public function get_novedades_institucion($id){
        $dato = DB::table('novedades_institucion as nit')
        ->leftjoin('periodoescolar as per','per.idperiodoescolar','=','nit.id_periodo')
        ->leftjoin('usuario as usu','nit.id_editor','=','usu.idusuario')
        ->where('nit.idInstitucion','=',$id)
        ->select([
            'nit.*',
            'per.descripcion','per.idperiodoescolar',
            DB::raw("CONCAT(usu.nombres, ' ', usu.apellidos) as usuario")
        ])
        ->orderBy('nit.created_at', 'DESC') // Ordenar por el más reciente
        ->get();
        return $dato;
    }

    public function new_novedades_add(Request $request){
        $dato = DB::table('novedades_institucion')->insertGetId([
            'idInstitucion' => $request->idInstitucion,
            'id_periodo'    => $request->id_periodo,
            'id_editor'     => $request->id_editor,
            'novedades'     => $request->novedades,
            'estado'        => '0',
        ]);
        return response()->json([
            'message' => 'Novedad creada exitosamente'
        ], 201);
    }
    public function cod_evaluacion_institucion($id){ 
        $dato = DB::table('institucion')
        ->where('idInstitucion','=',$id)
        ->select([
            'ifcodigoEvaluacion',
            'idInstitucion',
            'evaluacion_personalizada',
        ])
        ->get();
        return $dato;
    }
}
