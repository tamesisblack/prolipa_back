<?php

namespace App\Http\Controllers;

use App\Models\Pacientes;
use DB;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NorthospitalController extends Controller
{
    public function index()
    {
        $pacientes = DB::select("SELECT * FROM `nh_pacientes`");

        return $pacientes;
    }


    public function get_razonsocial()
    {
        $razones = DB::select("SELECT * FROM `nh_razon_social`");

        return $razones;
    }

    public function get_pacientes_filtros($tipo, $razon, $genero, $edad_ini, $edad_fin)
    {
        $pacientes = array();

        if( $razon == 'Todos' ){ $razon = ''; }
        if( $genero == 'T' ){ $genero = ''; }

        $fecha_actual = date("Y-m-d");
        $fecha_ini = date("Y-m-d", strtotime($fecha_actual . "- ".$edad_fin." year"));
        $fecha_fin = date("Y-m-d", strtotime($fecha_actual . "- ".$edad_ini." year"));

        // return $fecha_ini.'  -  '.$fecha_fin;
        // SELECT * FROM `nh_pacientes` WHERE `fechanacimiento` BETWEEN '23/06/2002' AND '23/06/2012' ORDER BY `nh_pacientes`.`fechanacimiento` DESC;


        $pacientes = DB::select("SELECT * FROM `nh_pacientes` WHERE
        `razonsocial` LIKE '%$razon%' AND
        `sexo` LIKE '%$genero%' AND
        `fechanacimiento` BETWEEN '$fecha_ini' AND '$fecha_fin'");

        return $pacientes;
    }


    public function store(Request $request)
    {

    }


    public function cargar_razonsocial()
    {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);

        $razones = ['BELLGENICA', 'BIOCUPACIONAL CIA LT', 'BIODILAB', 'BMI IGUALAS MEDICAS ', 'BUPA ECUADOR S.A CIA', 'CALMEDIAV CONSTRUCTO', 'CARIDEL S.A', 'CENTRO MEDICO METROP', 'CLINEF NORTE', 'CLINICA DE ESPECIALI', 'CLINICA OLYMPUS', 'CLINICA ZYMASALUD S.', 'CONFIAMED', 'CORIS DEL ECUADOR', 'CORIS DEL ECUADOR S.', 'ECUASANITAS S.A.', 'EL JORDAN', 'EMPLEADOS NORTHOSPIT', 'FARMAENLACE', 'FUNDACION AFAC', 'FUNDACION PATRONATO ', 'FUNDACION ROCIO DE V', 'GABRIELA PEÃ‘AFIEL', 'GALLEGOS BARRERA CAT', 'GRAFITEXT', 'GUSTAVO TERAN GARCES', 'IESS', 'INMEDICAL', 'INSTITUTO DE SEGURID', 'INTEROCEANICA C.A.', 'INVITROMED-VISALMED', 'JHONSON Y JHONSON', 'LATINA SALUD', 'LATINA SEGUROS Y REA', 'MAXIMIZACION DE BENE', 'MED-EC S.A.', 'MEDICINA PREPAGADA C', 'MGRV LABORATORIO', 'MINISTERIO DE SALUD ', 'PAN AMERICAN LIFE DE', 'PARTICULAR', 'PLENISALUD', 'PLUSMEDICAL', 'PRIVILEGIO', 'PROLIPA CIA. LTDA.', 'ROCARSYSTEM S.A.', 'SALUD S.A.', 'SAN FRANCISCO', 'SEGUROS ALIANZA S.A', 'SEGUROS BOLIVAR', 'SPPAT', 'TOACLINICA CIA. LTDA', 'TRANSMEDICAL', 'TRIVELLI', 'UNIDAD METROPOLITANA', 'VIDA SANA'];

        // return count($razones);

        for ($i = 0; $i < count($razones); $i++) {
            DB::INSERT("INSERT INTO `nh_razon_social`(`nombre`) VALUES (?)", [$razones[$i]]);
            dump($razones[$i]);
        }
    }

    public function cargar_pacientes(){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);

        $pacientes = Http::get('http://186.46.24.108:9096/apiNH/Clientes');
        $json_pacientes = json_decode($pacientes, true);
        // return count($json_pacientes);

        foreach ($json_pacientes as $key => $value) {
            try {
                $fecha_modificada = str_replace("/","-", $value['fechanacimiento']);
                $datenew = DateTime::createFromFormat("d-m-Y", $fecha_modificada);
                $fecha_formato = $datenew->format("Y-m-d");

                // return $fecha_formato;

                $paciente = new Pacientes();
                $paciente->historia         = $value['historia'];
                $paciente->cedula           = $value['cedula'];
                $paciente->apellidos        = $value['apellidos'];
                $paciente->nombres          = $value['nombres'];
                $paciente->fechanacimiento  = $fecha_formato;
                $paciente->sexo             = $value['sexo'];
                $paciente->direccion        = $value['direccion'];
                $paciente->telefono         = $value['telefono'];
                $paciente->email            = $value['email'];
                $paciente->razonsocial      = $value['razonsocial'];

                $paciente->save();

            } catch (\Throwable $th) {
                dump($th);
            }
        }


    }

    public function cargar_institucion_codigo(){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);

        $estudiantes = DB::SELECT("SELECT u.idusuario, u.institucion_idInstitucion FROM usuario u INNER JOIN codigoslibros c ON u.idusuario = c.idusuario AND c.id_institucion IS NULL WHERE u.institucion_idInstitucion IS NOT NULL GROUP BY u.idusuario");
        $cont_modif = 0;
        foreach ($estudiantes as $key => $value) {
            DB::UPDATE("UPDATE `codigoslibros` SET `id_institucion`= ? WHERE `idusuario` = ? AND `id_institucion` IS NULL ", [$value->institucion_idInstitucion, $value->idusuario]);
            $cont_modif++;
            dump($value->institucion_idInstitucion.'-'.$value->idusuario);
        }

        return $cont_modif;

    }



}
