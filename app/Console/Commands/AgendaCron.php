<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use DateTime;
use Mail;

class AgendaCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agenda:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $email = 'alexandro2011.x1@gmail.com';
        $emailCRE = 'alexandro2011.x1@gmail.com';
        $nombreInstitucion = 'Asesor';

        $envio = Mail::send('plantilla.recordatorio_pago',
            [
                'nombres' => 'asdasdas',
                'apellidos' => 'asdasdas',
                'cedula' => 'asdasdas',
                'nombreInstitucion' => 'asdasdas',
                'valor_cuota' => 'asdasdas',
                'valor_pendiente' => 'asdasdas',
                'fecha_a_pagar' => 'asdasdas'
            ],
            function ($message) use ($email, $emailCRE, $nombreInstitucion) {
                $message->from('noreply@prolipadigital.com.ec', $nombreInstitucion);
                $message->to($email)->bcc($emailCRE)->bcc('alexandro2011.x1@gmail.com')->subject('Agenda de asesores');
            }
        );
    }
}
