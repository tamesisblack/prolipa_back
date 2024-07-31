<?php

namespace App\Http\Controllers;

use App\Models\Archivos;
use Illuminate\Http\Request;

class ArchivosController extends Controller
{
    public function planlector(Request $request)
    {
        $planlector = DB::select('SELECT * FROM planlector');
        return $planlector;
    }

    public function libro(){
        $archivos = array();
        $directorio = opendir("../public/upload/libro/"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }
    public function cuadernodigital(){
        $archivos = array();
        $directorio = opendir("../public/upload/cuadernodigital/"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }
    public function guiadigital(){
        $archivos = array();
        $directorio = opendir("../public/upload/guia/"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }
    public function planlectordigital(){
        $archivos = array();
        $directorio = opendir("../public/upload/planlectordigital/"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }

    public function exe(){
        $archivos = array();
        $directorio = opendir("../public/upload/exe/"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }

    public function cuadernoexe(){
        $archivos = array();
        $directorio = opendir("../public/upload/cuadernoexe/"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }

    public function guiaexe(){
        $archivos = array();
        $directorio = opendir("../public/upload/guiasexe/"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }

    public function planlectorexe(){
        $archivos = array();
        $directorio = opendir("../public/upload/planlectorexe/"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }

    public function pdfguiadidactica(){
        $archivos = array();
        $directorio = opendir("../public/upload/pdfguiadidactica/"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }

    public function pdfsinguia(){
        $archivos = array();
        $directorio = opendir("../public/upload/pdfsinguia/"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }

    public function pdfconguia(){
        $archivos = array();
        $directorio = opendir("../public/upload/pdfconguia/"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }

    public function planificacion(){
        $archivos = array();
        $directorio = opendir("../public/upload/planificacion/"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }

    public function material(){
        $archivos = array();
        $directorio = opendir("../public/upload/mtapoyoexe/"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }

    public function bases(){
        $archivos = array();
        $directorio = opendir("./../../../Bases"); //ruta actual
        while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
        {
            if ($archivo=='.' or $archivo=='..' or $archivo=='index.php')//verificamos si es o no un directorio
            {
                // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
            }
            else
            {
                array_push($archivos,$archivo);
            }
        }
        return $archivos;
    }
}
