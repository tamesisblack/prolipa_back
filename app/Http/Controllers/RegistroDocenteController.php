<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AsignaturaDocente;
use Illuminate\Http\Request;
use App\Models\User;
use DB;
use PhpParser\Node\Stmt\Else_;

class RegistroDocenteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //api:get/registroDocente
    public function index(Request $request)
    {
        $institucion = $request->institucion_id;
        $periodo     = $request->periodo_id;
        $cedula      = $request->cedula;
        if($request->busquedaUserXCedula){
            return $this->busquedaUserXCedula($cedula,$institucion);
        }
        //traer las areas x serie
        if($request->areasxSerie){
            return $this->areasxSerie($request->id_serie,$request->periodo_id,$request->docente_id);
        }
        //traer las areas x serie PLAN LECTOR
        if($request->areasxSeriePlanLector){
            return $this->areasxSeriePlanLector($request->id_serie,$request->periodo_id,$request->docente_id);
        }
    }
    public function busquedaUserXCedula($cedula,$institucion){
        $query = DB::SELECT("SELECT u.idusuario, u.nombres,u.apellidos,u.cedula,u.telefono,u.email,
            u.id_group,u.institucion_idInstitucion,i.nombreInstitucion,
            g.deskripsi as rol
            FROM usuario u
            LEFT JOIN institucion i ON i.idInstitucion = u.institucion_idInstitucion
            LEFT JOIN sys_group_users g ON u.id_group = g.id
            WHERE u.cedula LIKE '%$cedula%'
            AND (u.id_group = '6' OR u.id_group = '10')
            AND u.institucion_idInstitucion = '$institucion'
        ");
        return $query;
    }
    //api:get>>registroDocente?areasxSerie=yes&id_serie=1&docente_id=6625&periodo_id=22
    public function areasxSerie($id_serie,$periodo,$docente){
        //AREAS
        $query = DB::SELECT("SELECT DISTINCT ar.idarea, ar.nombrearea,ls.id_serie
        FROM libros_series ls
        LEFT JOIN libro l ON ls.idLibro = l.idlibro
        LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
        LEFT JOIN area ar ON a.area_idarea = ar.idarea
        WHERE ls.id_serie = '$id_serie'
        AND l.Estado_idEstado = '1'
        AND a.estado = '1'
        ");
        $datos      = [];
        $libros     = [];
        $contador   = 0;
        foreach($query as $key => $item){
            $contador2  = 0;
            for($i =1; $i<=10; $i++){
                $query2 = DB::SELECT("SELECT l.nombrelibro,  l.idlibro,l.asignatura_idasignatura , ls.*
                FROM libros_series ls
                LEFT JOIN libro l ON ls.idLibro = l.idlibro
                LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
                LEFT JOIN area ar ON a.area_idarea = ar.idarea
                WHERE ls.id_serie = '$id_serie'
                AND a.area_idarea  = '$item->idarea'
                AND l.Estado_idEstado = '1'
                AND a.estado = '1'
                AND ls.year = '$i'
                ");
                if(empty($query2)){
                    $libros[$contador2] = [
                        "nivel"             => 0,
                        "nombrelibro"       => "",
                        // "id_serie"          => "",
                        // "idasiguser"    => 0,
                        // "formato"           => "",
                        // "selected"          => false
                    ];
                }else{
                    $idlibro        = $query2[0]->idlibro;
                    $idasignatura   = $query2[0]->asignatura_idasignatura;
                    //validar si el docente tiene el libro
                    $query3 = DB::SELECT("SELECT * FROM asignaturausuario ad
                    WHERE ad.usuario_idusuario = '$docente'
                    AND ad.periodo_id  = '$periodo'
                    AND ad.asignatura_idasignatura = '$idasignatura'");
                    $idasiguser = 0 ;
                    $selected       = false;
                    if(count($query3) > 0){
                        $idasiguser = $query3[0]->idasiguser;
                        $selected       = true;
                    }
                    $libros[$contador2] = [
                        "nivel"             => $query2[0]->year,
                        "nombrelibro"       => $query2[0]->nombrelibro,
                        "idlibro"           => $idlibro,
                        "idasignatura"      => $idasignatura,
                        "idasiguser"        => $idasiguser,
                        //si no ha seleccionado es 0 si ha seleccionado es 1
                        "formato"           => 0,
                        "selected"          => $selected
                    ];
                }
                $contador2++;
            }
            $getLibros = $this->setearValores($libros);
            $datos[$contador] = [
                "idarea"        => $item->idarea,
                "nombrearea"    => $item->nombrearea,
                "id_serie"      => $item->id_serie,
                "libros"        => $getLibros
            ];
            $contador++;
        }
        return $datos;
    }
    public function setearValores($array){
        $librosFiltrados = array_filter($array, function ($libro) {
            return $libro["nivel"] > 0;
        });
        return array_values($librosFiltrados);
    }
    public function areasxSeriePlanLector($id_serie,$periodo,$docente){
        $query = DB::SELECT("SELECT l.nombrelibro,  l.idlibro,l.asignatura_idasignatura , ls.*
            FROM libros_series ls
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
            LEFT JOIN area ar ON a.area_idarea = ar.idarea
            WHERE ls.id_serie = '$id_serie'
            AND l.Estado_idEstado = '1'
            -- AND ls.estado = '1'
            AND a.estado = '1'
        ");
        $datos = [];
        $contador = 0;
        foreach($query as $key => $item){
            //validar si el docente tiene el libro
            $idasignatura = $item->asignatura_idasignatura;
            $query2 = DB::SELECT("SELECT * FROM asignaturausuario ad
            WHERE ad.usuario_idusuario = '$docente'
            AND ad.periodo_id  = '$periodo'
            AND ad.asignatura_idasignatura = '$idasignatura'");
            $idasiguser = 0 ;
            $selected       = false;
            if(count($query2) > 0){
                $idasiguser = $query2[0]->idasiguser;
                $selected   = true;
            }
            $datos[$contador] = [
                "nombrelibro"       => $item->nombrelibro,
                "idlibro"           => $item->idlibro,
                "idasignatura"      => $idasignatura,
                "idasiguser"        => $idasiguser,
                //si no ha seleccionado es 0 si ha seleccionado es 1
                "formato"           => 0,
                "selected"          => $selected
            ];
            $contador++;
        }
        return $datos;
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
    public function guardarDocente(Request $request){
        $password                           = sha1(md5($request->cedula));
        if($request->idusuario > 0){
            $datosValidados = $request->validate([
                'nombres'                   => 'required',
                'apellidos'                 => 'required',
                'email'                     => 'required|email',
            ]);
            //validar que no coloque un email que le corresponde a otro usuario
            $query = DB::SELECT("SELECT * FROM usuario u
            WHERE u.email ='$request->email'
            ");
            if(count($query) > 0){
                $idusuario = $query[0]->idusuario;
                if($idusuario != $request->idusuario){
                    return ["status" => "0", "message" => "El email le corresponde a otro usuario"];
                }
            }
            //validar que no coloque una cedula que le corresponde a otro usuario
            $query = DB::SELECT("SELECT * FROM usuario u
            WHERE u.cedula ='$request->cedula'
            ");
            if(count($query) > 0){
                $idusuario = $query[0]->idusuario;
                if($idusuario != $request->idusuario){
                    return ["status" => "0", "message" => "La cédula le corresponde a otro usuario"];
                }
            }
            $user                           = User::findOrFail($request->idusuario);
        }else{
            $datosValidados = $request->validate([
                'cedula'                    => 'required|max:15|unique:usuario',
                'nombres'                   => 'required',
                'apellidos'                 => 'required',
                'email'                     => 'required|email|unique:usuario',
                'institucion_id'            => 'required',
            ]);
            $user                           = new User();
            $user->institucion_idInstitucion = $request->institucion_id;
        }
        $user->cedula                       = $request->cedula;
        $user->nombres                      = $request->nombres;
        $user->apellidos                    = $request->apellidos;
        $user->name_usuario                 = $request->email;
        $user->password                     = $password;
        $user->email                        = $request->email;
        $user->id_group                     = 6;
        $user->estado_idEstado              = 1;
        $user->idcreadorusuario             = $request->idcreadorusuario;
        $user->telefono                     = $request->telefono;
        $user->save();
        return $user;
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $getbooks = json_decode($request->data_libros);
        foreach($getbooks as $key => $item){
            $docenteBook = [];
            if($item->selected == "1" ){
                //si no agrego
                $docenteBook = $this->getDocenteLibro($request->docente,$request->periodo,$item->idasignatura);
                if(empty($docenteBook)){
                    $asignatura = new AsignaturaDocente();
                    $asignatura->usuario_idusuario       = $request->docente;
                    $asignatura->asignatura_idasignatura = $item->idasignatura;
                    $asignatura->periodo_id              = $request->periodo;
                    $asignatura->save();
                }
            }
            //eliminar libro
            else{
                $docenteBook = $this->getDocenteLibro($request->docente,$request->periodo,$item->idasignatura);
                if(!empty($docenteBook) > 0){
                    AsignaturaDocente::findOrFail($item->idasiguser)->delete();
                }
            }
        }

    }
    public function getDocenteLibro($docente,$periodo,$idasignatura){
        $query = DB::SELECT("SELECT * FROM asignaturausuario ad
        WHERE ad.usuario_idusuario = '$docente'
        AND ad.periodo_id = '$periodo'
        AND ad.asignatura_idasignatura = '$idasignatura'
       ");
       return $query;
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
}
