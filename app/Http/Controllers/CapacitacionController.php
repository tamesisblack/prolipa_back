<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Capacitacion;
use App\Models\Seminarios;
use DB;
use Illuminate\Support\Facades\Log;

class CapacitacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $capacitacion = DB::SELECT("SELECT c.id_seminario as id,
        c.tema_id,
        c.label,
        c.nombre as title,
        c.classes,
        date_format(c.fecha_inicio, '%Y-%m-%d %H:%i:%s') as endDate ,
        c.fecha_inicio as startDate,
        c.hora_inicio,
        c.hora_fin,
        c.id_institucion as institucion_id,
        c.periodo_id,
        c.id_usuario,
        c.estado_capacitacion as estado,
        c.observacion_admin as observacion,
        c.institucion_id_temporal,
        c.nombre_institucion_temporal,
        c.estado_institucion_temporal,
        c.tipo,
        c.cant_asistentes as personas,
        p.idperiodoescolar, p.periodoescolar AS periodo, i.nombreInstitucion,
        c.capacitador as capacitadores,
        c.link_reunion
        FROM seminarios c
        LEFT JOIN periodoescolar p ON c.periodo_id = p.idperiodoescolar
        LEFT JOIN institucion i ON  c.id_institucion = i.idInstitucion
        LEFT JOIN capacitacion_temas t ON c.tema_id = t.id
        WHERE c.id_usuario = '$request->id_usuario'
        AND c.tipo_webinar = '2'
        AND c.estado = '1'
        ");
        return $capacitacion;
    }
    public function temasCapacitacion(Request $request)
    {
        if ($request->validarPorFecha) {
            $validar = DB::SELECT("SELECT * FROM seminarios a
            WHERE a.fecha_inicio_temp = '$request->fecha'
            AND a.tema_id  ='$request->tema_id'
            AND a.estado = '1'
            AND a.tipo_webinar = '2'
            ");
            return $validar;
        }
        if ($request->encontrarCapacitaciones) {
            $capacitaciones = DB::SELECT("SELECT CONCAT(u.nombres,' ',u.apellidos) AS vendedor,
            i.nombreInstitucion,  ciu.nombre AS ciudad, it.ciudad AS ciudad_temporal, t.capacitador, t.tema,
             c.*
             FROM seminarios c
            LEFT JOIN institucion i ON c.id_institucion = i.idInstitucion
            LEFT JOIN usuario u ON c.id_usuario = u.idusuario
            LEFT JOIN ciudad ciu ON ciu.idciudad = i.ciudad_id
            LEFT JOIN seguimiento_institucion_temporal it ON c.institucion_id_temporal = it.institucion_temporal_id
            LEFT JOIN capacitacion_temas t ON  c.tema_id = t.id
            WHERE c.fecha_inicio_temp = '$request->fecha'
            AND c.estado = '1'
            AND c.tipo_webinar = '2'
            ");
            return $capacitaciones;

        } else {
            $temas = DB::SELECT("SELECT c.*   FROM capacitacion_temas c
            WHERE c.estado = '1'
            ");
            return $temas;
        }

    }
    public function solicitarTema(Request $request)
    {
        //para eliminar la solicitud
        if ($request->eliminar) {
            $deleted = DB::table('capacitacion_solicitudes')->where('id', '=', $request->id)->delete();
            return "se elimino";
        }
        //para traer todas las solicitudes de los asesores
        if ($request->todo) {
            $todo = $this->todasSolicitudes();
            return $todo;
        }
        if ($request->listado) {
            $listadoAsesor = $this->solicitudTemasAsesor($request->asesor_id);
            return $listadoAsesor;
        }
        $observacion = "";
        if($request->observacion == "null" || $request->observacion == null){
            $observacion = null;
        }
        else{
            $observacion = $request->observacion;
        }
        $fecha_solicitud = now();
        $ingreso = DB::insert('insert into capacitacion_solicitudes (tema, asesor_id,observacion,fecha_solicitud) values (?, ?,?, ?)', [$request->tema, $request->asesor_id, $observacion, $fecha_solicitud]);
        if ($ingreso) {
            return ["status" => "1", "message" => "Se solicito correctamente"];
        } else {
            return ["status" => "0", "message" => "No se pudo solicitar"];
        }
    }
    public function editarSolicitudTema(Request $request)
    {
        DB::table('capacitacion_solicitudes')
            ->where('id', $request->id)
            ->where('asesor_id', $request->asesor)
            ->update([
                'comentario_admin' => $request->comentario,
                'estado' => $request->estado,
                'fecha_aprobacion_anulacion' => now()
            ]);
        return "se editor correctamente";
    }
    public function todasSolicitudes()
    {
        $todo = DB::SELECT("SELECT  c.* , CONCAT(u.nombres, ' ', u.apellidos) AS asesor
        FROM  capacitacion_solicitudes c
       LEFT JOIN usuario u ON u.idusuario = c.asesor_id
       ORDER BY c.id DESC
       LIMIT 50
       ");
        return $todo;
    }
    public function solicitudTemasAsesor($asesor)
    {
        $listado = DB::SELECT("SELECT  * FROM  capacitacion_solicitudes
        WHERE asesor_id = '$asesor'
        ORDER BY id DESC
        LIMIT 200
        ");
        return $listado;
    }
    public function store(Request $request)
    {
        try {
            //para editar la capacitacion agenda
            if ($request->id != 0) {
                $agenda = Seminarios::find($request->id);
                //para guardar la capacitacion agenda
            } else {
                $agenda = new Seminarios();
                $agenda->fecha_fin = $request->fecha_fin;
            }

            //si crean una insitucion temporal
            if ($request->estado_institucion_temporal == 1) {
                $agenda->periodo_id = $request->periodo_id_temporal;
                $agenda->institucion_id_temporal = $request->institucion_id_temporal;
                $agenda->nombre_institucion_temporal = $request->nombreInstitucion;
                $agenda->id_institucion = "";
            }

            if ($request->estado_institucion_temporal == 0) {
                $agenda->id_institucion = $request->id_institucion;
                $agenda->institucion_id_temporal = "";
                $agenda->nombre_institucion_temporal = "";
                //para traer el periodo
                $buscarPeriodo = $this->traerPeriodo($request->id_institucion);

                if ($buscarPeriodo["status"] == "1") {
                    $obtenerPeriodo = $buscarPeriodo["periodo"][0]->periodo;
                    $agenda->periodo_id = $obtenerPeriodo;
                }
            }
            $agenda->descripcion = $request->fecha_inicio;
            $agenda->tipo_webinar = "2";
            $agenda->id_usuario = $request->idusuario;
            $agenda->nombre = $request->nombre;
            $agenda->label = $request->label;
            $agenda->classes = $request->classes;
            $agenda->fecha_inicio = $request->fecha_inicio;
            $agenda->fecha_inicio_temp = $request->fecha_inicio;
            $agenda->tipo = $request->tipo;
            if ($request->desdeAdmin) {
                $agenda->capacitador_id = $request->capacitador_id;
                $agenda->capacitador = $request->capacitador;
            }
            if ($request->observacion_admin == "null") {
                $agenda->observacion_admin = "";
            } else {
                $agenda->observacion_admin = $request->observacion_admin;
            }
            $agenda->hora_inicio = $request->hora_inicio;
            $agenda->hora_fin = $request->hora_fin;
            $agenda->tema_id = $request->tema_id;
            // $agenda->capacitador = $request->capacitador;
            $agenda->estado_institucion_temporal = $request->estado_institucion_temporal;
            $agenda->cant_asistentes = $request->asistentes;
            $agenda->link_reunion   = $request->link_reunion ?? null;
            $agenda->editor_id = $request->editor_id ?? null ;
            $agenda->save();
            return $agenda;
        } catch (\Throwable $th) {
            return ["status" => "0", "message" => "Error inesperado: " . $th->getMessage()];

        }
    }
    public function traerPeriodo($institucion_id)
    {
        $periodoInstitucion = DB::SELECT("SELECT idperiodoescolar AS periodo , periodoescolar AS descripcion FROM periodoescolar WHERE idperiodoescolar = (
            SELECT  pir.periodoescolar_idperiodoescolar as id_periodo
            from institucion i,  periodoescolar_has_institucion pir
            WHERE i.idInstitucion = pir.institucion_idInstitucion
            AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
            WHERE phi.institucion_idInstitucion = i.idInstitucion
            AND i.idInstitucion = '$institucion_id'))
        ");
        if (count($periodoInstitucion) > 0) {
            return ["status" => "1", "message" => "correcto", "periodo" => $periodoInstitucion];
        } else {
            return ["status" => "0", "message" => "no hay periodo"];
        }
    }
    public function delete_agenda_asesor($id_agenda)
    {
        DB::DELETE("DELETE FROM `seminarios` WHERE `id_seminario` = $id_agenda");
    }
    public function edit_agenda_admin(Request $request)
    {
        $agenda = Capacitacion::find($request->id);
        $agenda->personas = $request->personas;
        $agenda->observacion = $request->observacion;
        $agenda->estado = $request->estado;
        $agenda->startDate = $request->endDate;
        $agenda->endDate = $request->endDate;
        $agenda->save();
    }

    public function filtroCapacitacionInstitucion(Request $request)
    {
        // $periodo = $this->periodosActivos();
        // if(count($periodo) < 0){
        //     return ["status" => "0","No existe periodos activos"];
        // }
        //almacenar los periodos
        // $periodo1 = $periodo[0]->idperiodoescolar;
        // $periodo2 = $periodo[1]->idperiodoescolar;
        $filtro = DB::SELECT("SELECT p.periodoescolar, i.nombreInstitucion, a.*
        FROM seminarios a
        LEFT JOIN institucion i ON a.id_institucion = i.idInstitucion
        LEFT JOIN periodoescolar p ON a.periodo_id = p.idperiodoescolar
        WHERE a.id_usuario = '$request->asesor_id'
        AND a.tipo_webinar = '2'
        AND a.estado = '1'
        ORDER BY a.id_seminario DESC
        LIMIT 100
        ");
        return $filtro;
    }
    public function periodosActivos()
    {
        $periodo = DB::SELECT("SELECT DISTINCT  p.* FROM periodoescolar p
        LEFT JOIN  codigoslibros c ON p.idperiodoescolar  = c.id_periodo
        WHERE  p.estado = '1'
        ");
        return $periodo;
    }
    public function getCapacitadores()
    {
        $query = DB::SELECT("SELECT u.idusuario,
        CONCAT(u.nombres, ' ',u.apellidos) AS capacitador
        FROM usuario u
       WHERE u.capacitador = '1'
       AND u.estado_idEstado = '1'
       AND u.idusuario <> '70102'");
        return $query;
    }
    //api:get/reporteCapacitacionesGrupal/datos
    public function reporteCapacitacionesGrupal($periodo)
    {
        $capacitadores = $this->getCapacitadores();
        if (!empty($capacitadores)) {
            $datos = [];
            $contador = 0;
            foreach ($capacitadores as $key => $item) {
                //capacitaciones
                // $query = DB::SELECT("SELECT *
                // from seminarios s
                // WHERE s.tipo_webinar = '2'
                // AND s.capacitador_id = '$item->idusuario'
                // AND s.periodo_id = '$periodo'
                // AND s.estado = '1'
                // ");
                $query = DB::SELECT("SELECT s.*
                FROM seminarios_capacitador sc
                LEFT JOIN seminarios s ON s.id_seminario = sc.seminario_id
                WHERE sc.idusuario = '$item->idusuario'
                AND s.estado = '1'
                AND s.periodo_id = '$periodo'
                ");
                $datos[$contador] = [
                    "idusuario" => $item->idusuario,
                    "capacitador" => $item->capacitador,
                    "contador" => sizeof($query)
                ];
                $contador++;
            }
            return $datos;
        }
    }
    //api:get/reporteCapacitaciones
    public function reporteCapacitaciones($data)
    {
        //Variables
        $valores = explode("*", $data);
        $idusuario = $valores[0];
        //tipoFiltro => 0 = por periodo; 1 = por meses;
        $tipoFiltro = $valores[1];
        //value => periodo_id,anio
        $value = $valores[2];
        $datos = [];
        $contador = 0;
        //obtener las instituciones
        $query = $this->getInstitucionesCapacitaciones($idusuario);
        if (empty($query)) {
            return $datos;
        }
        foreach ($query as $key => $item) {
            $reporte = [];
            //Institucion Prolipa
            if ($item->estado_institucion_temporal == 0) {
                //filtro por periodo
                if ($tipoFiltro == 0)
                    $reporte = $this->getReportePeriodoInstitucion($idusuario, $value, $item->id_institucion);
                //filtro por meses
                if ($tipoFiltro == 1)
                    $reporte = $this->getReporteInstitucion($idusuario, $value, $item->id_institucion);
            }
            //Institucion Temporal
            else {
                //filtro por periodo
                if ($tipoFiltro == 0)
                    $reporte = $this->getReportePeriodoInstitucionTemporal($idusuario, $value, $item->nombre_institucion_temporal);
                //filtro por meses
                if ($tipoFiltro == 1)
                    $reporte = $this->getReporteInstitucionTemporal($idusuario, $value, $item->nombre_institucion_temporal);
            }
            $data = [];
            if ($tipoFiltro == 1) {
                $data[0] = [
                    "Ene" => $reporte[0]->Ene == NULL ? '0' : $reporte[0]->Ene,
                    "Feb" => $reporte[0]->Feb == NULL ? '0' : $reporte[0]->Feb,
                    "Mar" => $reporte[0]->Mar == NULL ? '0' : $reporte[0]->Mar,
                    "Abr" => $reporte[0]->Abr == NULL ? '0' : $reporte[0]->Abr,
                    "May" => $reporte[0]->May == NULL ? '0' : $reporte[0]->May,
                    "Jun" => $reporte[0]->Jun == NULL ? '0' : $reporte[0]->Jun,
                    "Jul" => $reporte[0]->Jul == NULL ? '0' : $reporte[0]->Jul,
                    "Ago" => $reporte[0]->Ago == NULL ? '0' : $reporte[0]->Ago,
                    "Sep" => $reporte[0]->Sep == NULL ? '0' : $reporte[0]->Sep,
                    "Oct" => $reporte[0]->Oct == NULL ? '0' : $reporte[0]->Oct,
                    "Nov" => $reporte[0]->Nov == NULL ? '0' : $reporte[0]->Nov,
                    "Dic" => $reporte[0]->Feb == NULL ? '0' : $reporte[0]->Dic
                ];
            } else {
                $data = $reporte;
            }
            $datos[$contador] = [
                "id_usuario" => $item->id_usuario,
                "asesor" => $item->asesor,
                "id_institucion" => $item->id_institucion,
                "estado_institucion_temporal" => $item->estado_institucion_temporal,
                "nombreInstitucion" => $item->nombreInstitucion,
                "nombre_institucion_temporal" => $item->nombre_institucion_temporal,
                "periodo_id" => $item->periodo_id,
                "ciudad_temporal" => $item->ciudad_temporal,
                "ciudad" => $item->ciudad,
                "periodo" => $item->periodo,
                "reporte" => $data
            ];
            $contador++;
        }
        return $datos;
    }
    public function getInstitucionesCapacitaciones($idusuario)
    {
        $query = DB::SELECT("SELECT DISTINCT s.id_usuario,s.id_institucion,
            s.estado_institucion_temporal,i.nombreInstitucion,s.nombre_institucion_temporal,
            s.periodo_id,temp.ciudad AS ciudad_temporal,
            c.nombre AS ciudad, p.periodoescolar AS periodo,
            CONCAT(u.nombres,' ',u.apellidos) AS asesor
            FROM seminarios_capacitador sa
            LEFT JOIN seminarios s ON sa.seminario_id = s.id_seminario
            LEFT JOIN institucion i ON s.id_institucion = i.idInstitucion
            LEFT JOIN ciudad c ON c.idciudad = i.ciudad_id
            LEFT JOIN seguimiento_institucion_temporal temp ON s.institucion_id_temporal = temp.institucion_temporal_id
            LEFT JOIN periodoescolar p ON s.periodo_id = p.idperiodoescolar
            LEFT JOIN usuario u ON s.id_usuario = u.idusuario
            WHERE sa.idusuario = '$idusuario'
            AND s.estado = '1'
            AND s.tipo_webinar = '2'
        ");
        return $query;
        // $query = DB::SELECT("SELECT DISTINCT s.id_usuario,s.id_institucion,
        //     s.estado_institucion_temporal,i.nombreInstitucion,s.nombre_institucion_temporal,
        //     s.periodo_id,temp.ciudad AS ciudad_temporal,
        //     c.nombre AS ciudad, p.periodoescolar AS periodo,
        //     CONCAT(u.nombres,' ',u.apellidos) AS asesor
        //     FROM seminarios s
        //     LEFT JOIN institucion i ON s.id_institucion = i.idInstitucion
        //     LEFT JOIN ciudad c ON c.idciudad = i.ciudad_id
        //     LEFT JOIN seguimiento_institucion_temporal temp ON s.institucion_id_temporal = temp.institucion_temporal_id
        //     LEFT JOIN periodoescolar p ON s.periodo_id = p.idperiodoescolar
        //     LEFT JOIN usuario u ON s.id_usuario = u.idusuario
        //     WHERE s.tipo_webinar = '2'
        //     AND s.capacitador_id = '$idusuario'
        //     AND s.estado = '1'
        // ");
    }
    //========FILTRO POR PERIODO=========
    public function getReportePeriodoInstitucion($idusuario, $periodo, $institucion)
    {
        // $query = DB::SELECT("SELECT s.nombre as tema,  SUBSTRING(s.fecha_inicio, 1, 10) AS fecha_inicio,s.capacitador
        //     from seminarios s
        //     WHERE s.tipo_webinar = '2'
        //     AND s.capacitador_id = '$idusuario'
        //     AND s.id_institucion = '$institucion'
        //     AND s.periodo_id = '$periodo'
        //     AND s.estado = '1'
        // ");
        $query = DB::SELECT("SELECT s.nombre as tema,
        SUBSTRING(s.fecha_inicio, 1, 10) AS fecha_inicio,s.capacitador
        FROM seminarios_capacitador sa
        LEFT JOIN seminarios s ON sa.seminario_id = s.id_seminario
        WHERE s.tipo_webinar = '2'
        AND sa.idusuario = '$idusuario'
        AND s.id_institucion = '$institucion'
        AND s.periodo_id = '$periodo'
        AND s.estado = '1'
        ");
        return $query;
    }
    public function getReportePeriodoInstitucionTemporal($idusuario, $periodo, $institucion)
    {
        // $query = DB::SELECT("SELECT
        //     s.nombre as tema, s.fecha_inicio_temp,s.capacitador
        //     from seminarios s
        //     WHERE s.tipo_webinar = '2'
        //     AND s.capacitador_id = '$idusuario'
        //     AND s.nombre_institucion_temporal = '$institucion'
        //     AND s.periodo_id = '$periodo'
        //     AND s.estado = '1'
        // ");
        // return $query;
        $query = DB::SELECT("SELECT s.nombre as tema,
        SUBSTRING(s.fecha_inicio, 1, 10) AS fecha_inicio,s.capacitador
        FROM seminarios_capacitador sa
        LEFT JOIN seminarios s ON sa.seminario_id = s.id_seminario
        WHERE s.tipo_webinar = '2'
        AND sa.idusuario = '$idusuario'
        AND s.nombre_institucion_temporal = '$institucion'
        AND s.periodo_id = '$periodo'
        AND s.estado = '1'
        ");
        return $query;
    }
    //========FILTRO POR MESES===========
    public function getReporteInstitucion($idusuario, $anio, $institucion)
    {
        // $reporteMes = DB::SELECT("SELECT
        // sum(case when month(fecha_inicio) = 1 then 1 else 0 end) Ene
        // , sum(case when month(fecha_inicio) = 2 then 1 else 0 end) Feb
        // , sum(case when month(fecha_inicio) = 3 then 1 else 0 end) Mar
        // , sum(case when month(fecha_inicio) = 4 then 1 else 0 end) Abr
        // , sum(case when month(fecha_inicio) = 5 then 1 else 0 end) May
        // , sum(case when month(fecha_inicio) = 6 then 1 else 0 end) Jun
        // , sum(case when month(fecha_inicio) = 7 then 1 else 0 end) Jul
        // , sum(case when month(fecha_inicio) = 8 then 1 else 0 end) Ago
        // , sum(case when month(fecha_inicio) = 9 then 1 else 0 end) Sep
        // , sum(case when month(fecha_inicio) = 10 then 1 else 0 end) Oct
        // , sum(case when month(fecha_inicio) = 11 then 1 else 0 end) Nov
        // , sum(case when month(fecha_inicio) = 12 then 1 else 0 end) Dic
        // from seminarios s
        // WHERE s.tipo_webinar = '2'
        //     AND s.capacitador_id = '$idusuario'
        //     and YEAR(s.fecha_inicio) = '$anio'
        //     AND s.id_institucion = '$institucion'
        //     AND s.estado = '1'
        // ");
        $reporteMes = DB::SELECT("SELECT
            sum(case when MONTH(s.fecha_inicio) = 1 then 1 else 0 end) Ene
            , sum(case when month(s.fecha_inicio) = 2 then 1 else 0 end) Feb
            , sum(case when month(s.fecha_inicio) = 3 then 1 else 0 end) Mar
            , sum(case when month(s.fecha_inicio) = 4 then 1 else 0 end) Abr
            , sum(case when month(s.fecha_inicio) = 5 then 1 else 0 end) May
            , sum(case when month(s.fecha_inicio) = 6 then 1 else 0 end) Jun
            , sum(case when month(s.fecha_inicio) = 7 then 1 else 0 end) Jul
            , sum(case when month(s.fecha_inicio) = 8 then 1 else 0 end) Ago
            , sum(case when month(s.fecha_inicio) = 9 then 1 else 0 end) Sep
            , sum(case when month(s.fecha_inicio) = 10 then 1 else 0 end) Oct
            , sum(case when month(s.fecha_inicio) = 11 then 1 else 0 end) Nov
            , sum(case when month(s.fecha_inicio) = 12 then 1 else 0 end) Dic
            FROM seminarios_capacitador sa
            LEFT JOIN seminarios s ON sa.seminario_id = s.id_seminario
            WHERE s.tipo_webinar = '2'
            AND sa.idusuario = '$idusuario'
            and YEAR(s.fecha_inicio) = '$anio'
            AND s.id_institucion = '$institucion'
            AND s.estado = '1'
         ");
        return $reporteMes;
    }
    public function getReporteInstitucionTemporal($idusuario, $anio, $institucion)
    {
        // $reporteMes = DB::SELECT("SELECT
        // sum(case when month(fecha_inicio) = 1 then 1 else 0 end) Ene
        // , sum(case when month(fecha_inicio) = 2 then 1 else 0 end) Feb
        // , sum(case when month(fecha_inicio) = 3 then 1 else 0 end) Mar
        // , sum(case when month(fecha_inicio) = 4 then 1 else 0 end) Abr
        // , sum(case when month(fecha_inicio) = 5 then 1 else 0 end) May
        // , sum(case when month(fecha_inicio) = 6 then 1 else 0 end) Jun
        // , sum(case when month(fecha_inicio) = 7 then 1 else 0 end) Jul
        // , sum(case when month(fecha_inicio) = 8 then 1 else 0 end) Ago
        // , sum(case when month(fecha_inicio) = 9 then 1 else 0 end) Sep
        // , sum(case when month(fecha_inicio) = 10 then 1 else 0 end) Oct
        // , sum(case when month(fecha_inicio) = 11 then 1 else 0 end) Nov
        // , sum(case when month(fecha_inicio) = 12 then 1 else 0 end) Dic
        // from seminarios s
        // WHERE s.tipo_webinar = '2'
        //     AND s.capacitador_id = '$idusuario'
        //     and YEAR(s.fecha_inicio) = '$anio'
        //     AND s.nombre_institucion_temporal = '$institucion'
        //     AND s.estado = '1'
        // ");
        $reporteMes = DB::SELECT("SELECT
            sum(case when MONTH(s.fecha_inicio) = 1 then 1 else 0 end) Ene
            , sum(case when month(s.fecha_inicio) = 2 then 1 else 0 end) Feb
            , sum(case when month(s.fecha_inicio) = 3 then 1 else 0 end) Mar
            , sum(case when month(s.fecha_inicio) = 4 then 1 else 0 end) Abr
            , sum(case when month(s.fecha_inicio) = 5 then 1 else 0 end) May
            , sum(case when month(s.fecha_inicio) = 6 then 1 else 0 end) Jun
            , sum(case when month(s.fecha_inicio) = 7 then 1 else 0 end) Jul
            , sum(case when month(s.fecha_inicio) = 8 then 1 else 0 end) Ago
            , sum(case when month(s.fecha_inicio) = 9 then 1 else 0 end) Sep
            , sum(case when month(s.fecha_inicio) = 10 then 1 else 0 end) Oct
            , sum(case when month(s.fecha_inicio) = 11 then 1 else 0 end) Nov
            , sum(case when month(s.fecha_inicio) = 12 then 1 else 0 end) Dic
            FROM seminarios_capacitador sa
            LEFT JOIN seminarios s ON sa.seminario_id = s.id_seminario
            WHERE s.tipo_webinar = '2'
            AND sa.idusuario = '$idusuario'
            and YEAR(s.fecha_inicio) = '$anio'
            AND s.nombre_institucion_temporal = '$institucion'
            AND s.estado = '1'
        ");
        return $reporteMes;
    }
    //=====FIN DE FILTRO POR MESES=====
}
