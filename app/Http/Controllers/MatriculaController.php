<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dirape\Token\Token;
use DB;
use DateTime;
use App\Models\EstudianteMatriculado;
use App\Models\NivelInstitucion;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Mail;

class MatriculaController extends Controller
{

    public function index(Request $request)
    {
        $matriculas = DB::select("");

        return $matriculas;
    }

    public function show($id)
    {
        $matriculas = DB::SELECT("");

        return $matriculas;
    }

    public function combos_matricula($institucion)
    {
        $niveles = DB::SELECT("SELECT nivelInstitucion_id, nivel_id , n.nombrenivel, n.orden
         FROM `mat_niveles_institucion` ma
         LEFT JOIN nivel n ON  ma.nivel_id = n.orden
          WHERE `institucion_id` = $institucion
        ");
        $paralelos = DB::SELECT("SELECT * FROM `mat_paralelos`");

        return response()->json(['niveles' => $niveles, 'paralelos' => $paralelos]);
    }

    public function listado_matriculas($institucion, $periodo, $tipo_listado, $export_excel)
    {

        // ** CONSULTA CON LET JOIN DE CXC TARDA MAS DE 7 SEGS
        // SELECT p.fecha_inicial, p.fecha_final, p.descripcion AS descripcion_periodo, u.cedula, u.institucion_idInstitucion, u.nombres, u.idusuario, u.apellidos, u.email, u.telefono, u.sexo, u.foto_user, u.nacionalidad, u.retirado, em.id_matricula, em.fecha_matricula, em.imagen, em.nivel, em.paralelo, em.estado_matricula, em.observacion, em.url, ni.nivelInstitucion_id, ni.nivel_id, par.paralelo_id, par.descripcion FROM institucion i INNER JOIN periodoescolar_has_institucion pi ON i.idInstitucion = pi.institucion_idInstitucion INNER JOIN usuario u ON u.institucion_idInstitucion = i.idInstitucion INNER JOIN periodoescolar p ON p.idperiodoescolar = pi.periodoescolar_idperiodoescolar LEFT JOIN mat_estudiantes_matriculados em ON em.id_estudiante = u.idusuario LEFT JOIN mat_niveles_institucion ni ON ni.nivelInstitucion_id = em.nivel LEFT JOIN mat_paralelos par ON par.paralelo_id = em.paralelo LEFT JOIN mat_cuotas_por_cobrar cxc ON cxc.id_matricula = em.id_matricula WHERE i.idInstitucion = 1063 AND i.aplica_matricula = 1 AND pi.periodoescolar_idperiodoescolar = 12 AND em.estado_matricula = 1 AND p.estado = '1' GROUP BY cxc.id_matricula


        if( $tipo_listado == 1 || $tipo_listado == 2 ){
            $listado = DB::SELECT("SELECT p.fecha_inicial, p.fecha_final, p.descripcion AS descripcion_periodo, u.cedula, u.institucion_idInstitucion, u.nombres, u.idusuario, u.apellidos, u.email, u.telefono, u.sexo, u.foto_user, u.nacionalidad, u.retirado, em.id_matricula, em.fecha_matricula, em.imagen, em.nivel, em.paralelo, em.estado_matricula, em.observacion, em.url, ni.nivelInstitucion_id, ni.nivel_id, par.paralelo_id, par.descripcion FROM institucion i INNER JOIN periodoescolar_has_institucion pi ON i.idInstitucion = pi.institucion_idInstitucion INNER JOIN usuario u ON u.institucion_idInstitucion = i.idInstitucion INNER JOIN periodoescolar p ON p.idperiodoescolar = pi.periodoescolar_idperiodoescolar LEFT JOIN mat_estudiantes_matriculados em ON em.id_estudiante = u.idusuario LEFT JOIN mat_niveles_institucion ni ON ni.nivelInstitucion_id = em.nivel LEFT JOIN mat_paralelos par ON par.paralelo_id = em.paralelo WHERE i.idInstitucion = $institucion AND i.aplica_matricula = 1 AND pi.periodoescolar_idperiodoescolar = $periodo AND em.estado_matricula = $tipo_listado AND p.estado = '1'");
        }else{
            $listado = DB::SELECT("SELECT p.fecha_inicial, p.fecha_final, p.descripcion AS descripcion_periodo, u.cedula, u.institucion_idInstitucion, u.nombres, u.idusuario, u.apellidos, u.email, u.telefono, u.sexo, u.foto_user, u.nacionalidad, u.retirado, em.id_matricula, em.fecha_matricula, em.imagen, em.nivel, em.paralelo, em.estado_matricula, em.observacion, em.url, ni.nivelInstitucion_id, ni.nivel_id, par.paralelo_id, par.descripcion FROM institucion i INNER JOIN periodoescolar_has_institucion pi ON i.idInstitucion = pi.institucion_idInstitucion INNER JOIN usuario u ON u.institucion_idInstitucion = i.idInstitucion INNER JOIN periodoescolar p ON p.idperiodoescolar = pi.periodoescolar_idperiodoescolar LEFT JOIN mat_estudiantes_matriculados em ON em.id_estudiante = u.idusuario LEFT JOIN mat_niveles_institucion ni ON ni.nivelInstitucion_id = em.nivel LEFT JOIN mat_paralelos par ON par.paralelo_id = em.paralelo WHERE i.idInstitucion = $institucion AND i.aplica_matricula = 1 AND pi.periodoescolar_idperiodoescolar = $periodo AND p.estado = '1'");
        }


        $data = array();
        foreach ($listado as $key => $value) {
            $cuotasxcobrar='';

            if ( $export_excel == 1 ){
                $cuotasxcobrar = DB::SELECT("SELECT * FROM mat_cuotas_por_cobrar cxc WHERE cxc.id_matricula = ? ORDER BY cxc.num_cuota ASC", [$value->id_matricula]);
            }

            $data['items'][$key] = [
                "matricula" => $value,
                "nombres_completos" => $value->nombres .' '. $value->apellidos,
                "cxc" => $cuotasxcobrar,
            ];
        }

        return $data;
    }

    public function busqueda_estudiante_mat($periodo, $institucion, $tipo, $valor_filtro)
    {
        $estudiante = DB::SELECT("SELECT p.fecha_inicial, p.fecha_final, p.descripcion AS descripcion_periodo, u.cedula, u.institucion_idInstitucion, u.nombres, u.idusuario, u.apellidos, u.email, u.telefono, u.sexo, u.foto_user, u.nacionalidad, u.retirado, em.id_matricula, em.fecha_matricula, em.imagen, em.nivel, em.paralelo, em.estado_matricula, em.observacion, em.url, ni.nivelInstitucion_id, ni.nivel_id, par.paralelo_id, par.descripcion
        FROM institucion i INNER JOIN periodoescolar_has_institucion pi ON i.idInstitucion = pi.institucion_idInstitucion
        INNER JOIN usuario u ON u.institucion_idInstitucion = i.idInstitucion
        INNER JOIN periodoescolar p ON p.idperiodoescolar = pi.periodoescolar_idperiodoescolar
        LEFT JOIN mat_estudiantes_matriculados em ON em.id_estudiante = u.idusuario
        LEFT JOIN mat_niveles_institucion ni ON ni.nivelInstitucion_id = em.nivel
        LEFT JOIN mat_paralelos par ON par.paralelo_id = em.paralelo
        WHERE i.idInstitucion = $institucion AND u.".$tipo." LIKE ? AND i.aplica_matricula = 1 AND pi.periodoescolar_idperiodoescolar = ? AND p.estado = '1'", ["%".$valor_filtro."%", $periodo]);

        $data = array();
        foreach ($estudiante as $key => $value) {
            $cuotasxcobrar = DB::SELECT("SELECT * FROM mat_cuotas_por_cobrar cxc WHERE cxc.id_matricula = ? ORDER BY cxc.num_cuota ASC", [$value->id_matricula]);

            $data['items'][$key] = [
                "matricula" => $value,
                "nombres_completos" => $value->nombres .' '. $value->apellidos,
                "cxc" => $cuotasxcobrar,
            ];
        }

        return $data;
    }


    public function get_cuotas($id_matricula)
    {
        $cuotasxcobrar = DB::SELECT("SELECT * FROM mat_cuotas_por_cobrar cxc WHERE cxc.id_matricula = ? ORDER BY cxc.num_cuota ASC", [$id_matricula]);

        return $cuotasxcobrar;
    }

    public function store(Request $request)
    {
        if( $request->id_matricula ){

            DB::table('usuario')
            ->where('cedula', $request->cedulaEstudiante)
            ->update(['curso' => $request->nivel]);

            $matricula = EstudianteMatriculado::find($request->id_matricula);
        }else{
            $matricula = new EstudianteMatriculado();
        }

        $matricula->estado_matricula = $request->estado_matricula;
        $matricula->nivel = $request->nivel;
        $matricula->paralelo = $request->paralelo;
        $matricula->id_director = $request->id_director;
        $matricula->save();

        try{

            if( $request->estado_matricula == 1 ){//matriculado
                DB::UPDATE("UPDATE `mat_cuotas_por_cobrar` SET `valor_pendiente` = 0 WHERE `id_matricula` = ? AND `num_cuota` = 1", [$request->id_matricula]);

                DB::UPDATE("UPDATE `usuario` SET `id_group`= 4 WHERE `idusuario` = ?", [$request->id_usuario]);

                DB::table('usuario')
                ->where('idusuario', $request->id_usuario)
                ->update(['update_datos' => '3']);
            }

            if( $request->estado_matricula == 2 ){//reservado todas las cuotas pendientes
                DB::UPDATE("UPDATE `mat_cuotas_por_cobrar` SET `valor_pendiente` = `valor_cuota` WHERE `id_matricula` = ?", [$request->id_matricula]);

                DB::UPDATE("UPDATE `usuario` SET `id_group`= 14 WHERE `idusuario` = ?", [$request->id_usuario]);
            }

            if( $request->estado_matricula == 3 ){//anulado las cuotas no se afectan
                DB::UPDATE("UPDATE `mat_estudiantes_matriculados` SET `estado_matricula`= 3 WHERE `id_matricula` = ?", [$request->id_matricula]);

                DB::UPDATE("UPDATE `usuario` SET `id_group`= 14 WHERE `idusuario` = ?", [$request->id_usuario]);
            }

        } catch (Exception $e) {
            return $matricula;
        }


        return $matricula;
    }

    public function guardar_pago_matricula(Request $request)
    {
        $cuota = DB::SELECT("SELECT * FROM `mat_cuotas_por_cobrar` WHERE `id_cuotas_id` = ?", [$request->id_cuotas_id]);
        $cuotas_pendientes = DB::SELECT("SELECT * FROM `mat_cuotas_por_cobrar` WHERE `id_matricula` = ?", [$request->id_matricula]);

        $total_pendiente = 0;
        foreach ($cuotas_pendientes as $key => $value) {
            $total_pendiente = $total_pendiente + $value->valor_pendiente;
        }

        if( $total_pendiente < $request->val_pagado ){
            return 2; // 2=valor excedido
        }

        $cant_cuotas = $request->val_pagado / $cuota[0]->valor_cuota;
        if( $cant_cuotas > round($cant_cuotas) ){
            $cant_cuotas = (round($cant_cuotas) + 1);
        }else{
            $cant_cuotas = round($cant_cuotas);
        }

        $sobrante = $request->val_pagado;
        // $tot_pagado = $request->val_pagado;
        for( $i=0; $i<$cant_cuotas; $i++ ){

            if( $sobrante <= 0 ){ return; }

            $cuota = DB::SELECT("SELECT * FROM `mat_cuotas_por_cobrar` WHERE `id_matricula` = ? AND `num_cuota` = ?", [$request->id_matricula, ($cuota[0]->num_cuota+$i)]);
            // dump(($request->id_cuotas_id+$i));
            if( $sobrante >= $cuota[0]->valor_pendiente ){
                $sobrante = $sobrante - $cuota[0]->valor_pendiente;
                $valor_ingreso = $cuota[0]->valor_pendiente;
                $saldo_actual = 0;
            }else{
                $saldo_actual = $cuota[0]->valor_pendiente - $sobrante;
                $valor_ingreso = $sobrante;
                $sobrante = 0;
            }

            $this->acentar_pago($request, $saldo_actual, $valor_ingreso, $i);
        }

    }


    public function acentar_pago($request, $saldo_actual, $valor_ingreso, $i)
    {
        $forma_pago = [
            'forma_pago' => $request->forma_pago,
            'banco' => $request->banco,
            'n_tr_ch' => $request->n_tr_ch,
            'val_pagado' => $valor_ingreso
        ];
        $forma_pago = json_encode($forma_pago, JSON_UNESCAPED_UNICODE);
        $pago = DB::INSERT("INSERT INTO `mat_pagos`(`id_cuota`,`forma_pago`,`id_usuario`) VALUES (?,?,?)", [($request->id_cuotas_id+$i), $forma_pago, $request->id_usuario]);
        if( $pago ){
            DB::UPDATE("UPDATE `mat_cuotas_por_cobrar` SET `valor_pendiente` = ? WHERE `id_cuotas_id` = ?", [$saldo_actual,($request->id_cuotas_id+$i)]);
        }
    }


    public function procesar_pagos(Request $request) // excel
    {
        $pagos = json_decode($request->data_pagos);
        $cedulas_no_encontradas = array();
        $acum_err = 0;
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);

        for( $i=0; $i<count($pagos); $i++ ){

            $cuota = DB::SELECT("SELECT u.idusuario, em.id_director, em.id_matricula, cxc.id_cuotas_id, cxc.num_cuota, cxc.valor_cuota, cxc.valor_pendiente FROM usuario u, mat_estudiantes_matriculados em, periodoescolar p, mat_cuotas_por_cobrar cxc WHERE u.cedula = ? AND em.id_estudiante = u.idusuario AND em.id_periodo = p.idperiodoescolar AND p.estado = '1' AND em.id_matricula = cxc.id_matricula AND cxc.valor_pendiente > 0 AND cxc.num_cuota > ? ORDER BY cxc.num_cuota LIMIT 1", [$pagos[$i]->cedula,$request->tipo]);


            if( $cuota ){

                $forma_pago_excel = [
                    'forma_pago' => "pago_excel",
                    'banco' => "",
                    'n_tr_ch' => "",
                    'val_pagado' => $pagos[$i]->valor,
                    'id_cuotas_id' => $cuota[0]->id_cuotas_id,
                    'id_matricula' => $cuota[0]->id_matricula,
                    'id_usuario' => $cuota[0]->id_director,
                    'num_cuota' => $cuota[0]->num_cuota
                ];

                $cuotas_pendientes = DB::SELECT("SELECT * FROM `mat_cuotas_por_cobrar` WHERE `id_matricula` = ? AND `num_cuota` > ?", [$cuota[0]->id_matricula,$request->tipo]);

                $total_pendiente = 0;
                foreach ($cuotas_pendientes as $key => $value) {
                    $total_pendiente = $total_pendiente + $value->valor_pendiente;
                }

                // if( $total_pendiente < $forma_pago_excel['val_pagado'] ){
                //     return 2; // 2=valor excedido
                // }

                $cant_cuotas = $forma_pago_excel['val_pagado'] / $cuota[0]->valor_cuota;
                if( $cant_cuotas > round($cant_cuotas) ){
                    $cant_cuotas = (round($cant_cuotas) + 1);
                }else{
                    $cant_cuotas = round($cant_cuotas);
                }

                $sobrante = $forma_pago_excel['val_pagado'];
                // $tot_pagado = $forma_pago_excel['val_pagado'];
                for( $j=0; $j<15; $j++ ){ // 15 modifiacar si aumenta el numero de cuotas posibles

                    if( $sobrante > 0 ){

                        $cuota = DB::SELECT("SELECT * FROM `mat_cuotas_por_cobrar` WHERE `id_matricula` = ? AND `num_cuota` = ?", [$forma_pago_excel['id_matricula'], ($forma_pago_excel['num_cuota']+$j)]);

                        if( $cuota ){
                            if( $sobrante >= $cuota[0]->valor_pendiente ){
                                $sobrante = $sobrante - $cuota[0]->valor_pendiente;
                                $valor_ingreso = $cuota[0]->valor_pendiente;
                                $saldo_actual = 0;
                            }else{
                                $saldo_actual = $cuota[0]->valor_pendiente - $sobrante;
                                $valor_ingreso = $sobrante;
                                $sobrante = 0;
                            }

                            // $data['items'][$j] = [
                            //     "cuota" => $cuota,
                            //     "sobrante" => $sobrante
                            // ];

                            $forma_pago = json_encode($forma_pago_excel, JSON_UNESCAPED_UNICODE);
                            $pago = DB::INSERT("INSERT INTO `mat_pagos`(`id_cuota`,`forma_pago`,`id_usuario`) VALUES (?,?,?)", [$cuota[0]->id_cuotas_id, $forma_pago, $forma_pago_excel['id_usuario']]);
                            if( $pago ){
                                DB::UPDATE("UPDATE `mat_cuotas_por_cobrar` SET `valor_pendiente` = ? WHERE `id_cuotas_id` = ?", [$saldo_actual,$cuota[0]->id_cuotas_id]);
                            }

                        }
                    }
                }

            }else{ // cedula no encontrada o no tiene cuotas
                array_push($cedulas_no_encontradas, $pagos[$i]->cedula);
            }

        }

        return $cedulas_no_encontradas;

    }


    public function procesar_becas(Request $request){

        $becas = json_decode($request->data_becas);
        $cedulas_no_encontradas = array();
        $acum_err = 0;

        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);

        $condicion = '';
        if( $request->tipo == 0 ){ $condicion = 'cxc.num_cuota = 1'; } // solo a matricula
        if( $request->tipo == 1 ){ $condicion = 'cxc.num_cuota > 1'; } // solo a pensiones
        if( $request->tipo == 2 ){ $condicion = 'cxc.num_cuota > 0'; } // matricula y pensiones
        if( $request->tipo == 3 ){ $condicion = 'cxc.num_cuota = 0'; } // solo a valores pendientes anteriores

        for( $i=0; $i<count($becas); $i++ ){

            $cuotas = DB::SELECT("SELECT u.idusuario, em.id_director, em.id_matricula, cxc.id_cuotas_id, cxc.num_cuota, cxc.valor_cuota, cxc.valor_pendiente FROM usuario u, mat_estudiantes_matriculados em, periodoescolar p, mat_cuotas_por_cobrar cxc WHERE u.cedula = ? AND em.id_estudiante = u.idusuario AND em.id_periodo = p.idperiodoescolar AND p.estado = '1' AND em.id_matricula = cxc.id_matricula AND cxc.valor_pendiente > 0 AND ".$condicion." ORDER BY cxc.num_cuota", [$becas[$i]->cedula]);

            if( $cuotas ){
                DB::INSERT("INSERT INTO `mat_becas_estudiantes`(`id_matricula`, `porcentaje_beca`, `observacion`, `tipo_beca`, `id_user_action`) VALUES (?,?,?,?,?)", [$cuotas[0]->id_matricula, $becas[$i]->valor, 'beca aplicada por excel', $request->tipo, $request->id_usuario]);

                foreach ($cuotas as $key => $value) {
                    $valor_pend = number_format(floatval($value->valor_pendiente) - ((floatval($value->valor_pendiente)*floatval($becas[$i]->valor))/100), 2);
                    $valor_desc = number_format(floatval($value->valor_cuota) - ((floatval($value->valor_cuota)*floatval($becas[$i]->valor))/100), 2);
                    DB::UPDATE("UPDATE `mat_cuotas_por_cobrar` SET `valor_cuota`= ?,`valor_pendiente`= ? WHERE `id_cuotas_id` = ?", [$valor_desc, $valor_pend, $value->id_cuotas_id]);
                }
            }else{// cedula no encontrada o no tiene cuotas
                array_push($cedulas_no_encontradas, $becas[$i]->cedula);
            }

        }

        return $cedulas_no_encontradas;

    }


    public function procesar_matriculas(Request $request){

        $matriculas = json_decode($request->data_matriculas);
        $cedulas_no_encontradas = array();

        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);

        for( $i=0; $i<count($matriculas); $i++ ){

            $cuotas = DB::SELECT("SELECT em.id_matricula FROM usuario u, mat_estudiantes_matriculados em, periodoescolar p, mat_cuotas_por_cobrar cxc WHERE u.cedula = ? AND em.id_estudiante = u.idusuario AND em.id_periodo = p.idperiodoescolar AND p.estado = '1' AND em.id_matricula = cxc.id_matricula AND cxc.valor_pendiente = 0 ORDER BY cxc.num_cuota ASC LIMIT 1", [$matriculas[$i]->cedula]);

            if($cuotas){
                DB::UPDATE("UPDATE mat_estudiantes_matriculados emn SET emn.estado_matricula = $request->tipo WHERE emn.id_matricula = ?", [$cuotas[0]->id_matricula]);
            }else{// cedula no encontrada o no tiene cuotas
                array_push($cedulas_no_encontradas, $matriculas[$i]->cedula);
            }
        }

        return $cedulas_no_encontradas;

    }


    public function aplicar_becas(Request $request)
    {
        $estudiantes = json_decode($request->estudiantes_selected, JSON_UNESCAPED_UNICODE);
        // return $estudiantes[0]['matricula']['id_matricula'];

        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);

        for( $i=0; $i<count($estudiantes); $i++ ){
            $beca = DB::INSERT("INSERT INTO `mat_becas_estudiantes`(`id_matricula`, `porcentaje_beca`, `observacion`, `tipo_beca`, `id_user_action`) VALUES (?,?,?,?,?)", [$estudiantes[$i]['matricula']['id_matricula'], $request->porcentaje_beca, $request->observacion, $request->aplicacion_beca, $request->id_user_action]);

            if( $beca ){
                $this->aplicar_porcentajes($request->aplicacion_beca, $estudiantes[$i]['matricula']['id_matricula'], $request->id_user_action, $request->porcentaje_beca);
            }
        }

    }


    public function aplicar_porcentajes($tipo, $id_matricula, $id_user_action, $porcentaje_beca)
    {

        if( $tipo == 2 ){ // solo matricula
            $valor_cuota = DB::SELECT("SELECT `valor_cuota` FROM `mat_cuotas_por_cobrar` WHERE `id_matricula` = ? AND `num_cuota` = 1",[$id_matricula]);

            $val_porcent = number_format(((floatval($valor_cuota[0]->valor_cuota) * intval($porcentaje_beca)) / 100), 2);
            $val_calculado = floatval($valor_cuota[0]->valor_cuota) - $val_porcent;

            DB::UPDATE("UPDATE `mat_cuotas_por_cobrar` SET `valor_cuota`= $val_calculado, `valor_pendiente`= $val_calculado, `id_usuario`= ? WHERE `id_matricula` = ? AND `num_cuota` = 1", [$id_user_action, $id_matricula]);
        }else{

            if( $tipo == 1  ){ // solo pensiones
                $inicia = 1;
            }else{ // ambas
                $inicia = 0;
            }

            $cuotas_est = DB::SELECT("SELECT `id_cuotas_id`, `valor_cuota` FROM `mat_cuotas_por_cobrar` WHERE `id_matricula` = ? AND `num_cuota` > $inicia",[$id_matricula]); // menos deudas anteriores "cuota 0"

            for( $j=0; $j<count($cuotas_est); $j++ ){
                $val_porcent = number_format(((floatval($cuotas_est[$j]->valor_cuota) * intval($porcentaje_beca)) / 100), 2);
                $val_calculado = floatval($cuotas_est[$j]->valor_cuota) - $val_porcent;

                DB::UPDATE("UPDATE `mat_cuotas_por_cobrar` SET `valor_cuota`= $val_calculado, `valor_pendiente`= $val_calculado, `id_usuario`= ? WHERE `id_cuotas_id` = ?", [$id_user_action, $cuotas_est[$j]->id_cuotas_id]);
            }

        }

    }

    public function editar_cuotas(Request $request)
    {
        $cuota = DB::UPDATE("UPDATE `mat_cuotas_por_cobrar` SET `valor_cuota`= ?, `valor_pendiente`= ?, `id_usuario`= ? WHERE `id_cuotas_id` = ? ", [$request->valor_cuota, $request->valor_pendiente, $request->id_usuario, $request->id_cuota_edit]);

        return $cuota;
    }


    public function enviar_recordatorio()
    {
        $max_send = DB::SELECT("SELECT MAX(`cant_recordatorio_enviado`) AS max_send FROM `mat_estudiantes_matriculados`");

        $fecha_actual = date("Y-m-d");
        $consulta = "SELECT em.id_matricula, u.nombres, u.apellidos, u.cedula, u.email, i.idInstitucion, i.nombreInstitucion, rl.email AS email_rep_legal, re.email AS email_rep_eco, cxc.valor_cuota, cxc.valor_pendiente, cxc.fecha_a_pagar FROM mat_estudiantes_matriculados em
        INNER JOIN mat_cuotas_por_cobrar cxc ON em.id_matricula = cxc.id_matricula
        INNER JOIN usuario u ON em.id_estudiante = u.idusuario
        INNER JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
        LEFT JOIN mat_representante_legal rl ON u.cedula = rl.c_estudiante
        LEFT JOIN mat_representante_economico re ON u.cedula = re.c_estudiante ";

        $matriculas_vencidas = DB::SELECT($consulta . "WHERE cxc.valor_pendiente > 0 AND cxc.fecha_a_pagar < ? AND em.cant_recordatorio_enviado < ? GROUP BY em.id_matricula LIMIT 3", [$fecha_actual, $max_send[0]->max_send]);

        if( count($matriculas_vencidas) == 0 ){
            $matriculas_vencidas = DB::SELECT($consulta . "WHERE cxc.valor_pendiente > 0 AND cxc.fecha_a_pagar < ? AND em.cant_recordatorio_enviado <= ? GROUP BY em.id_matricula LIMIT 3", [$fecha_actual, $max_send[0]->max_send]);
        }
        $cont = 0;

        // return $matriculas_vencidas;

        foreach ($matriculas_vencidas as $key => $value) {
            $email = $value->email;
            $emailCRL = $value->email_rep_legal;
            $emailCRE = $value->email_rep_eco;
            $nombreInstitucion = $value->nombreInstitucion;

            $envio = Mail::send('plantilla.recordatorio_pago',
                [
                    'nombres' => $value->nombres,
                    'apellidos' => $value->apellidos,
                    'cedula' => $value->cedula,
                    'nombreInstitucion' => $value->nombreInstitucion,
                    'valor_cuota' => $value->valor_cuota,
                    'valor_pendiente' => $value->valor_pendiente,
                    'fecha_a_pagar' => $value->fecha_a_pagar
                ],

                function ($message) use ($email, $emailCRE, $nombreInstitucion) {
                    $message->from('noreply@institucion_educativa.com.ec', $nombreInstitucion);
                    $message->to($email)->bcc($emailCRE)->bcc('alexandro2011.x1@gmail.com')->subject('Recordatorio de pago');
                    // $message->to('alexandro2011.x1@gmail.com')->bcc('alexandro2011.x1@gmail.com')->subject('Recordatorio de pago');
                }
            );

            $actualiza_cant_envio = DB::UPDATE("UPDATE mat_estudiantes_matriculados SET cant_recordatorio_enviado = (cant_recordatorio_enviado + 1) WHERE id_matricula = $value->id_matricula");

            if( $actualiza_cant_envio ){ $cont++; }
        }

        return $cont;

    }


}
