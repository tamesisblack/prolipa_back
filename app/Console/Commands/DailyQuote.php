<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Http\Request;
use Dirape\Token\Token;
use DB;
use DateTime;
use Mail;


class DailyQuote extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quote:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tarea programada para el envio de correo de recordatorio de pagos.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);

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
