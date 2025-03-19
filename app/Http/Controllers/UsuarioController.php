<?php

namespace App\Http\Controllers;

use App\Models\AsignaturaDocente;
use App\Models\Cargo;
use App\Models\Usuario;
use App\Models\GenFunctions;
use App\Models\Institucion;
use App\Models\Perfil;
use App\Models\Periodo;
use App\Models\DirectorHasInstitucion;
use App\Models\HistoricoCodigos;
use App\Models\HistoricoVisitas;
use Illuminate\Http\Request;
use App\Quotation;
use App\Traits\Codigos\TraitCodigosGeneral;
use Illuminate\Support\Facades\DB;
use Mail;
use Cookie;
use Dirape\Token\Token;
use Illuminate\Validation\Rule;
use GuzzleHttp\Client;

class UsuarioController extends Controller
{
    use TraitCodigosGeneral;
    public function infoRegistro(Request $request){
        $paralelos = DB::SELECT("SELECT * FROM mat_paralelos");

        $curso  = DB::SELECT("SELECT * FROM nivel
        WHERE orden <> 0
        AND estado = 1
        ORDER BY orden + 0
        ");
        return [
            "paralelos" => $paralelos,
            "cursos" => $curso
        ];
    }
    public function userInformacion(Request $request){
        $usuario = DB::select("SELECT u.idusuario, u.cedula,u.nombres,u.apellidos,
        u.name_usuario,u.email, u.id_group, u.institucion_idInstitucion, u.iniciales,
        u.foto_user,u.telefono, i.idInstitucion, i.nombreInstitucion, c.nombre as ciudad,
        u.nacionalidad, fecha_nacimiento, curso, paralelo, sexo,n.nombrenivel ,p.descripcion
        FROM usuario u
        LEFT JOIN institucion i ON i.idInstitucion = u.institucion_idInstitucion
        LEFT JOIN ciudad c ON  c.idciudad = i.ciudad_id
        LEFT JOIN nivel n ON u.curso = n.orden
        LEFT JOIN mat_paralelos p ON u.paralelo = p.paralelo_id
        WHERE u.idusuario = $request->idusuario
        ");

        return $usuario;
    }
    public function index(Request $request)
    {
        $usuarios = DB::select("CALL `datosusuario` ();");
        return $usuarios;
    }

    public function docente(Request $request)
    {
        $usuarios = DB::select("CALL `docente` ();");
        return $usuarios;
    }

    public function prolipa(Request $request)
    {
        $usuarios = DB::select("CALL `prolipa` ();");
        return $usuarios;
    }

    public function estudiantes(Request $request)
    {
        $usuarios = DB::select("CALL `estudiantes` ();");
        return $usuarios;
    }


    public function aplicativo(Request $request){
        $usuarios = DB::select("CALL usuariologin ( ? , ? )",[$request->username,$request->password]);
        return $usuarios;
    }

    public function aplicativobase(Request $request){
        $usuarios = DB::select("CALL periodofecha (?)",[$request->idusuario]);
        return $usuarios;
    }

    public function getVendedor(){
        $vendedor = DB::SELECT('CALL `selectVendedor`();');
        return $vendedor;
    }

    public function getReporte(Request $request){
        $reporte = DB::SELECT('CALL `reporteVendedor` (?);',[$request->cedula]);
        return $reporte;
    }

    public function getReporteVisitas(Request $request){
        $reporte = DB::SELECT('CALL `vendedor_institucion_docente` (?);',[$request->idInstitucion]);
        return $reporte;
    }

    public function getCompleto(Request $request){
        $reporte = DB::SELECT('CALL `reporteVendedor` (?);',[$request->cedula]);
        if(!empty($reporte)){
            foreach ($reporte as $key => $post) {
                $docentes = DB::SELECT('CALL `vendedor_institucion_docente` (?)', [$post->ID]);
                $data['items'][$key] = [
                    'institucion' => $post,
                    'docentes'=>$docentes,
                ];
            }
            return $data;
        }else{
            $data = null;
        }
    }

    public function activar(Request $request){
        $idusuario = $request->idusuario;
        DB::update("UPDATE `usuario` SET `estado_idEstado`= ?  WHERE `idusuario` = ?",[1,$idusuario]);
    }

    public function desactivar(Request $request){
        $idusuario = $request->idusuario;
        DB::update("UPDATE `usuario` SET `estado_idEstado`= ?  WHERE `idusuario` = ?",[2,$idusuario]);
    }

    public function guardarPassword(Request $request){
        $datosValidados=$request->validate([
            'password' => 'min:8',
            'password_confirmation' => 'required_with:password|same:password|min:8'
        ]);
        $idusuario = auth()->user()->idusuario;
        $password = sha1(md5($request->password));
        $usuario = DB::update("UPDATE `usuario` SET `password`= ?, `p_ingreso`=?   WHERE `idusuario` = ?",[$password,'1',$idusuario]);
    }

    public function loginMac(Request $request)
    {
        $usuarios = DB::select("SELECT * FROM usuario where name_usuario = ? and password = ? ",[$request->usuario,sha1(md5($request->password))]);
        return $usuarios;
    }

    public function buscaUsuario(Request $request)
    {
        $usuarios = DB::SELECT("SELECT * FROM usuario where cedula = ?",[$request->cedula]);
        return $usuarios;
    }

    public function vendedor(Request $request)
    {
        $idusuario = auth()->user()->idusuario;
        if(!empty($idusuario)){
            $usuarios = DB::select("SELECT * FROM usuario where institucion_idInstitucion = 66 ");
            return $usuarios;
        }
    }

    public function select()
    {
        $usuario = DB::table('usuario')
                ->where('institucion_idInstitucion', '66')
                ->get();
        return  $usuario;
    }

    public function docentes(Request $request)
    {
    set_time_limit(6000000);
    ini_set('max_execution_time', 6000000);
    //  $client = new Client([
    //         'base_uri'=> 'https://foro.prolipadigital.com.ec',
    //         // 'timeout' => 60.0,
    // ]);
    $datos = [];
    $idinstitucion = $request->idInstitucion;
     // $consulta=DB::select("CALL `docentes`(?);",[$idinstitucion]);
    $consulta = DB::SELECT("SELECT `usuario`.`idusuario`,`usuario`. `cedula`, UPPER(`usuario`.`nombres`) as nombres,
    UPPER(`usuario`.`apellidos`) as apellidos,
        `usuario`.`name_usuario`, `usuario`.`password`,
        `usuario`.`email`, `usuario`.`date_created`, `usuario`.`id_group`, `usuario`.`p_ingreso`,
        `usuario`.`institucion_idInstitucion`, `usuario`.`estado_idEstado`,
        `usuario`.`idcreadorusuario`, `usuario`.`modificado_por`, `usuario`.`password_status`,
            `usuario`.`remember_token`, `usuario`.`session_id`, `usuario`.`foto_user`, `usuario`.`telefono`,
            `usuario`.`updated_at`, `usuario`.`created_at` , c.cargo, c.id AS cargo_id

        from usuario
        LEFT JOIN institucion_cargos c ON usuario.cargo_id = c.id
        where usuario.institucion_idInstitucion = '$idinstitucion'
        AND (usuario.id_group = '6' OR usuario.id_group = '10')
        AND usuario.estado_idEstado = '1'
    ");
     return $consulta;
        // foreach($consulta as $key => $item){
        //     $response = $client->request('GET','estudiantes?idusuario='.$item->idusuario);
        //     $getDocente =   json_decode($response->getBody()->getContents());
        //     $datos [$key] =[
        //         "idusuario" => $item->idusuario,
        //         "cedula" =>    $item->cedula,
        //         "nombres" =>   $item->nombres,
        //         "apellidos" => $item->apellidos,
        //         "name_usuario" =>$item->name_usuario,
        //         "email" => $item->email,
        //         "date_created" => $item->date_created,
        //         "id_group" => $item->id_group,
        //         "p_ingreso" => $item->p_ingreso,
        //         "institucion_idInstitucion" => $item->institucion_idInstitucion,
        //         "estado_idEstado" => $item->estado_idEstado,
        //         "foto_user" => $item->foto_user,
        //         "password_status" => $item->password_status,
        //         "telefono" => $item->telefono,
        //         "updated_at" => $item->updated_at,
        //         "created_at" => $item->created_at,
        //         "cargo" => $item->cargo,
        //         "cargo_id" => $item->cargo_id,
        //         "visitas" => count($getDocente)

        //     ];
        // }
        // return $datos;
    }
    //visitas docente
    public function docentesVisitas(Request $request)
    {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);

        // $idInstitucion = $request->idInstitucion;
        // $fromDate = $request->fromDate;
        // $toDate = $request->toDate;
        // $usuarios = DB::table('usuario')
        //     ->leftJoin('institucion_cargos', 'usuario.cargo_id', '=', 'institucion_cargos.id')
        //     ->leftJoin('historico_visitas', function ($join) use ($fromDate, $toDate) {
        //         $join->on('usuario.idusuario', '=', 'historico_visitas.idusuario')
        //             ->where('historico_visitas.recurso', '=', 15)
        //             ->whereBetween('historico_visitas.created_at', [$fromDate, $toDate]);
        //     })
        //     ->select('usuario.*', 'institucion_cargos.cargo', 'institucion_cargos.id as cargo_id', DB::raw('COUNT(historico_visitas.id) as visitas'))
        //     ->where('usuario.institucion_idInstitucion', '=', $idInstitucion)
        //     ->where('usuario.id_group', '=', 6)
        //     ->where('usuario.estado_idEstado', '=', '1')
        //     ->groupBy('usuario.idusuario')
        //     ->get();
        // return $usuarios;

        $idInstitucion = $request->idInstitucion;
        $fromDate = $request->fromDate;
        $toDate = $request->toDate;
        $periodoId = $request->periodo_id;

        $result = AsignaturaDocente::select(
            'asignaturausuario.usuario_idusuario',
            'asignaturausuario.periodo_id',
            'usuario.nombres',
            'usuario.name_usuario',
            'usuario.apellidos',
            'usuario.cedula',
            'usuario.email',
            'usuario.idusuario',
            'usuario.created_at',
            'usuario.updated_at',
            'institucion_cargos.cargo',
            'institucion_cargos.id as cargo_id'
        )
        ->distinct()
        ->leftJoin('usuario', 'asignaturausuario.usuario_idusuario', '=', 'usuario.idusuario')
        ->leftJoin('institucion_cargos', 'usuario.cargo_id', '=', 'institucion_cargos.id')
        ->where('usuario.id_group', '=', '6')
        ->where('usuario.institucion_idInstitucion', '=', $idInstitucion)
        ->where('asignaturausuario.periodo_id', '=', $periodoId)
        ->where('usuario.estado_idEstado', '=', '1')
        ->get() // Ejecutar la consulta y obtener los resultados
        ->map(function ($documento) {
            return(Object) [
                "usuario_idusuario" => $documento->usuario_idusuario,
                "periodo_id"        => $documento->periodo_id,
                "nombres"           => $documento->nombres,
                "name_usuario"      => $documento->name_usuario,
                "apellidos"         => $documento->apellidos,
                "cedula"            => $documento->cedula,
                "email"             => $documento->email,
                "idusuario"         => $documento->idusuario,
                'created_at'        => $documento->created_at->format('Y-m-d H:i:s'), // Formatea la fecha
                'updated_at'        => $documento->updated_at->format('Y-m-d H:i:s'), // Formatea la fecha
                "cargo"             => $documento->cargo,
                "cargo_id"          => $documento->cargo_id,
            ];
        });

        $result->map(function($item) use ($fromDate, $toDate) {
            $item->visitas = HistoricoVisitas::where('idusuario', $item->usuario_idusuario)
                ->where('recurso', 15)
                ->where('periodo_id', $item->periodo_id)
                ->whereBetween('historico_visitas.created_at', [$fromDate, $toDate])
                ->count();
            return $item;
        });

        // Devuelve el resultado modificado
        return $result;
    }
    public function usuarioVisitas(Request $request){
        $datos = DB::SELECT("SELECT h.*, i.nombreInstitucion, p.periodoescolar AS periodo,
        g.deskripsi AS rol,
        CONCAT(u.nombres,' ', u.apellidos) as usuario, u.cedula,u.email
        FROM historico_visitas h
       LEFT JOIN usuario u On h.idusuario = u.idusuario
       LEFT JOIN institucion i ON  h.institucion_id = i.idInstitucion
       LEFT JOIN periodoescolar p ON h.periodo_id = p.idperiodoescolar
       LEFT JOIN sys_group_users g ON  h.id_group  = g.id
       WHERE h.idusuario = '$request->idusuario'
       AND h.created_at BETWEEN '$request->fromDate' AND  '$request->toDate'
       ORDER BY h.created_at DESC

       ");
       return $datos;
    }
    //api:get//>usuarioVisitasAll
    public function usuarioVisitasAll(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $usuarios = DB::SELECT("SELECT u.idusuario ,CONCAT(u.nombres,' ', u.apellidos) as usuario, u.cedula,u.email,
            u.cedula
            FROM usuario u
            WHERE u.institucion_idInstitucion = '$request->institucion_id'
            AND u.estado_idEstado = '1'
            AND u.id_group = '6'
        ");
        $datos = [];
        foreach($usuarios as $key => $item){
            $visitas = DB::SELECT("SELECT id,h.recurso FROM historico_visitas h
            where h.idusuario = '$item->idusuario'
            AND h.created_at  BETWEEN '$request->fromDate' AND '$request->toDate'
            ");
            $datos[$key] = [
                "idusuario"     => $item->idusuario,
                "usuario"       => $item->usuario,
                "cedula"        => $item->cedula,
                "email"         => $item->email,
                "visitas"       => $visitas
            ];
        }
        return $datos;
    }

    public function datosUsuario(Request $request)
    {
        $idusuario = auth()->user()->idusuario;
        $idgrupo = auth()->user()->id_group;
        if(!empty($idusuario)){
            if($idgrupo == 4){
                $consulta = DB::SELECT("SELECT * FROM usuario WHERE idusuario = ?",[$idusuario]);
                return $consulta;
            }else{
                $consulta=DB::select("SELECT * FROM usuario left join institucion on usuario.institucion_idInstitucion = institucion.idInstitucion left join periodoescolar_has_institucion on periodoescolar_has_institucion.institucion_idInstitucion = institucion.idInstitucion left join periodoescolar on periodoescolar.idperiodoescolar = periodoescolar_has_institucion.periodoescolar_idperiodoescolar left join sys_group_users on usuario.id_group = sys_group_users.id  WHERE usuario.idusuario = $idusuario AND estado = '1' ORDER BY usuario.idusuario");
                return $consulta;
            }
        }
    }

    public function historial(Request $request)
    {
        $idusuario = $request->idusuario;
        $consulta=DB::select("select * from registro_usuario where usuario_idusuario = $idusuario ORDER BY  `registro_usuario`.`hora_ingreso_usuario` DESC ");
        return $consulta;
    }
    public function historialI(Request $request)
    {
        $idinstitucion = $request->idInstitucion;
        $consulta=DB::select("SELECT * FROM registro_usuario LEFT JOIN usuario ON usuario.idusuario = registro_usuario.usuario_idusuario LEFT JOIN institucion ON institucion.idInstitucion = usuario.institucion_idInstitucion  where institucion.idInstitucion = $idinstitucion ");
        return $consulta;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function perfil(Request $request)
    {
        if(!empty($request->file('archivo'))){
            $idusuario = $request->idusuario;
            $file = $request->file('archivo');
            $ruta = public_path('/perfil');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            DB::UPDATE("UPDATE `usuario` SET `cedula`=?,`nombres`=?,`apellidos`=?,`password`=?,`email`=?,`foto_user`=?,`telefono`=?,`institucion_idInstitucion`=? WHERE `idusuario` = ?",[
                $request->cedula,$request->nombres,$request->apellidos,sha1(md5($request->password)),$request->email,$fileName,$request->telefono,$request->institucion_idInstitucion,$request->idusuario
            ]);
        }else{
            $idusuario = $request->idusuario;
            DB::UPDATE("UPDATE `usuario` SET `cedula`=?,`nombres`=?,`apellidos`=?,`password`=?,`email`=?,`telefono`=?,`institucion_idInstitucion`=? WHERE `idusuario` = ?",[
                $request->cedula,$request->nombres,$request->apellidos,sha1(md5($request->password)),$request->email,$request->telefono,$request->institucion_idInstitucion,$request->idusuario
            ]);
        }
        $usuario = DB::SELECT("SELECT u . *, pi.periodoescolar_idperiodoescolar, i.nombreInstitucion FROM usuario u LEFT JOIN periodoescolar_has_institucion pi ON u.institucion_idInstitucion = pi.institucion_idInstitucion JOIN institucion i ON pi.institucion_idInstitucion = i.idInstitucion WHERE u.idusuario = ? AND pi.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi WHERE phi.institucion_idInstitucion = pi.institucion_idInstitucion)",[$request->idusuario]);
        return $usuario;
    }




    public function cargo(Request $request){

       //para eliminar
       if($request->eliminar){
            $cargo = Cargo::findOrFail($request->id);
            $cargo->estado = $request->estado;
            $cargo->usuario_editor = $request->usuario_id;
            $cargo->save();
            return ["status" => "1", "message" => "Se elimino correctamente"];
       }

        //para editar
        if($request->id){
         $cargo = Cargo::findOrFail($request->id);
         //para guardar
        }else{
          $cargo = new Cargo();
        }
         $cargo->cargo = $request->cargo;
         $cargo->usuario_editor = $request->usuario_id;
         $cargo->save();
         if($cargo){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "0", "message" => "No se pudo guardar"];
        }



    }
    public function store(Request $request)
    {
        // return $request;
        if(!empty($request->idusuario)){

            $usuario = Usuario::findOrFail($request->idusuario);
            $change_password = $usuario->change_password;
            // $user = $request->all();
            // $usuario->fill($user)->save();
            $usuario->cedula = $request->cedula;
            $usuario->nombres = $request->nombres;
            $usuario->apellidos = $request->apellidos;
            $usuario->telefono = $request->telefono;
            $usuario->name_usuario = $request->name_usuario;
            $usuario->email = $request->email;
            $usuario->id_group = $request->id_group;
            if($change_password == 1){
                $usuario->password=sha1(md5($request->cedula));
            }
            $usuario->p_ingreso=0;
            $usuario->institucion_idInstitucion = $request->institucion_idInstitucion;
            $usuario->estado_idEstado = $request->estado_idEstado;
            $usuario->paralelo = $request->paralelo;
            $usuario->curso = $request->curso;
            $usuario->iniciales = $request->iniciales;
            $usuario->capacitador = $request->capacitador;
            $usuario->cli_ins_codigo = $request->cli_ins_codigo == null || $request->cli_ins_codigo == "null" ? null : $request->cli_ins_codigo;
            $email = $request->email;
            $usuario->save();
        }else{
            $datosValidados=$request->validate([
                'cedula' => 'required|max:11|unique:usuario',
                'nombres' => 'required',
                'apellidos' => 'required',
                'email' => 'required|email|unique:usuario',
                'id_group' => 'required',
                'institucion_idInstitucion' => 'required',
            ]);
            $usuario = new Usuario();
            $usuario->cedula = $request->cedula;
            $usuario->nombres = $request->nombres;
            $usuario->apellidos = $request->apellidos;
            $usuario->name_usuario = $request->name_usuario;
            $usuario->email = $request->email;
            $usuario->id_group = $request->id_group;
            $usuario->password=sha1(md5($request->cedula));
            $usuario->p_ingreso=0;
            $usuario->institucion_idInstitucion = $request->institucion_idInstitucion;
            $usuario->estado_idEstado = 1;
            $usuario->paralelo = $request->paralelo;
            $usuario->curso = $request->curso;
            $usuario->iniciales = $request->iniciales;
            $usuario->capacitador = $request->capacitador;
            $email = $request->email;
            $usuario->save();
            $to_name = "Prolipa";
            $to_email = $request->email;
            // $data = array(
            //     'name'=>"Prolipa",
            //     'email' => $request->email,
            //     'codigo' => $request->cedula,
            //     'nombres' => $request->nombres,
            //     'apellidos' => $request->apellidos,
            //     'cedula' => $request->cedula
            // );
			// Mail::send('plantilla.registro',$data, function($message) use ($to_name, $to_email) {
            //     $message->to($to_email, $to_name)
            //     ->subject('Datos de registro');
            //     $message->from($to_email, 'Prolipa');
            // });
        }
        return $usuario;

    }

    public function closeSession(){
        Auth::logout();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Usuario  $usuario
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $usuario = Usuario::find($id);
        return $usuario;
    }

    //api:get>>/obtenerPerfiles
    public function obtenerPerfiles(Request $request){
        if($request->id_group == 11){
            $perfiles = DB::SELECT("SELECT s.* FROM  sys_group_users s
            WHERE (s.id = '4' OR s.id = '6' OR s.id = '10')
            ORDER BY id DESC");
            return $perfiles;
        }
        if($request->id_group == 22 || $request->id_group == 23){
            $perfiles = DB::SELECT("SELECT s.* FROM  sys_group_users s
            WHERE (s.id = '4' OR s.id = '6' OR s.id = '10' OR s.id = '11')
            ORDER BY id DESC");
            return $perfiles;
        }
        else{
            $perfiles = DB::SELECT("SELECT * FROM  sys_group_users ORDER BY id DESC");
            return $perfiles;
        }
    }

    //api::post>>/guardarPerfil
    public function guardarPerfil(Request $request){
        if($request->id){

            $perfil = Perfil::findOrFail($request->id);
            $perfil->level = $request->level;
            $perfil->deskripsi = $request->deskripsi;

           }else{
               $perfil = new Perfil;
               $perfil->level = $request->level;
               $perfil->deskripsi = $request->deskripsi;
           }
           $perfil->save();
           if($perfil){
               return "Se guardo correctamente";
           }else{
               return "No se pudo guardar/actualizar";
           }
    }

    //api:post//eliminarPerfil
    public function eliminarPerfil(Request $request){

        $buscarUsuarios = DB::SELECT("SELECT * FROM menu m WHERE m.sys_group_users_id  ='$request->id'");

        if(count($buscarUsuarios) > 0){
            return ["status" => "0", "message" => "El perfil no se puede eliminar porque tiene usuarios asignados, o está asignado a un menú"];
        }

        $perfil = Perfil::findOrFail($request->id);
        $perfil->delete();

        if($perfil){
            return ["status" => "1", "message" => "Se elimino correctamente el perfil"];
        }else{
            return ["status" => "0", "message" => "No se pudo eliminar"];
        }
    }


    public function update(Request $request)
    {
        $usuario = new Usuario();
        $usuario->cedula = $request->cedula;
        $usuario->nombres = $request->nombres;
        $usuario->apellidos = $request->apellidos;
        $usuario->name_usuario = $request->name_usuario;
        $usuario->password=sha1(md5($request->cedula));
        $usuario->email = $request->email;
        $usuario->id_group = $request->id_group;
        $usuario->institucion_idInstitucion = $request->institucion_idInstitucion;
        $usuario->estado_idEstado=1;
        $usuario->p_ingreso=0;
        $usuario->idusuario = $request->idusuario;

        DB::update("UPDATE `usuario` SET `cedula`= ?,`nombres`= ?,`apellidos`= ?,`name_usuario`= ?,`password`= ?,`email`= ?,`id_group`= ?,`institucion_idInstitucion`= ?,`estado_idEstado`= ?,`p_ingreso`= ? WHERE `idusuario`= ?",[$request->cedula,$request->nombres,$request->apellidos,$request->name_usuario,$usuario->password,$request->email,$request->id_group,$request->institucion_idInstitucion,$request->estado_idEstad,$usuario->p_ingreso,$request->idusuario]);


        $data = array(
            'name'=>"Prolipa",
        );
        Mail::send('plantilla.update', $data,function ($message){
            $message->from($_GET['email'], 'Prolipa');
            $message->to($_GET['email'])->subject('Registro Prolipa');
        });
    }
    public function verificarCorreo(Request $request){
        $usuario = DB::SELECT("SELECT email FROM usuario WHERE email LIKE '$request->email'");
        return $usuario;
    }

    public function restaurarPassword(Request $request){

        $encontrarEmail = Usuario::where('email','=',$request->email)->first();
        $codigo = $encontrarEmail->cedula;
        $res = DB::table('usuario')
        ->where('email',$request->email)
        ->update([
            'password' => sha1(md5($codigo)),
            'change_password' => 1,
            'fecha_change_password' => null
        ]);


    }

    //api para que el usuario pueda restaurar su contrasena
    //api:get>>/restaurarDatos

    public function restaurarDatos(Request $request){

        $datosValidados=$request->validate([
            'email' => 'required|email|exists:usuario',
            'cedula' => 'required| alpha_num | max:15 | min:10 | exists:usuario'
        ]);

        $encontrarEmail = Usuario::where('email','=',$request->email)
        ->Where('cedula','=',$request->cedula)
        ->first();
        $codigo = $encontrarEmail->cedula;



        $res = DB::table('usuario')
        ->where('email',$request->email)
        ->where('cedula',$request->cedula)
        ->update(['password' => sha1(md5($codigo))]);



         return ["status" => "1", "message" => "Usuario reestablecido. Utilice su email, y su número de cédula para acceder al sistema."];


    }





    public function restaurar(Request $request){

        $datosValidados=$request->validate([
            'email' => 'required|email|exists:usuario'
        ]);

        $usuario = Usuario::where('email','=',$request->email)->first();
        $cedula = $usuario->cedula;
        $codigo = $usuario->cedula;
        $password=sha1(md5($codigo));
        $nombres = $usuario->nombres;
        $apellidos = $usuario->apellidos;
        $idusuario = $usuario->idusuario;
        $usuario->password = $password;
        $usuario->password_status = '0';
        $usuario->save();

        $to_name = "Prolipa";
        $to_email = $request->email;
        $data = array(
            'name'=>"Prolipa",
            'email' => $request->email,
            'codigo' => $codigo,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'cedula' => $cedula
        );

        Mail::send('plantilla.restaurar',$data, function($message) use ($to_name, $to_email) {
        $message->to($to_email, $to_name)
        ->subject('Código Temporal');
        $message->from($to_email, 'Prolipa');
        });

        return "Enviado a ".$request->email." los datos de acceso";
    }

    public function passwordC(Request $request){
        $idusuario = $request->idusuario;
        $password = sha1(md5($request->password));
        $usuario = DB::update("UPDATE `usuario` SET `password`= ?, `password_status`=?   WHERE `idusuario` = ?",[$password,'1',$idusuario]);
        $usuario = DB::SELECT("SELECT * FROM usuario WHERE idusuario = ?",[$request->idusuario]);
        $cedula = '';
        $nombres = '';
        $apellidos = '';
        foreach ($usuario as $key => $value) {
            $cedula = $value->cedula;
            $nombres = $value->nombres;
            $apellidos = $value->apellidos;
            $email = $value->email;
        }
        $to_name = "Prolipa";
        $to_email = $email;
        $data = array(
            'name'=>"Prolipa",
            'email' => $email,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'cedula' => $cedula
        );
        Mail::send('plantilla.cambio',$data, function($message) use ($to_name, $to_email) {
        $message->to($to_email, $to_name)
        ->subject('Cambio de Contraseña');
        $message->from($to_email, 'Prolipa');
        });
        return $usuario;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Usuario  $usuario
     * @return \Illuminate\Http\Response
     */
    public function eliminarUsuario(Request $request)
    {
        $usuario = Usuario::find( $request->idusuario );
        $usuario->estado_idEstado = '4';
        $usuario->save();
    }

    public function papeleraUsuario(Request $request)
    {
        $usuarios = DB::select("SELECT * FROM usuarioEliminado");
        return $usuarios;
    }

    public function restaurarUsuario(Request $request)
    {
        $idusuario = $request->idusuario;
        DB::select("INSERT INTO usuario SELECT * FROM usuarioEliminado WHERE idusuario = ?",[$idusuario]);
        DB::delete("DELETE FROM usuarioEliminado WHERE idusuario = ?",[$idusuario]);
    }

    public function guardarUsuarioConectado(Request $request){
        DB::insert("INSERT INTO usuarioConectados(idusuario, claveio, nombres, apellidos, email, Institucion) VALUES (?,?,?,?,?,?)",[$request->idusuario, $request->claveio, $request->nombres, $request->apellidos, $request->email, $request->Institucion]);
    }

    public function eliminarUsuarioConectado(Request $request){
        $usuario = DB::select("SELECT * FROM usuarioConectados WHERE claveio = ? ",[$request->claveio]);
        DB::delete("DELETE FROM usuarioConectados WHERE  claveio = ? ",[$request->claveio]);
        return $usuario;
    }
    public function estudiantesXInstitucion($id){
        $usuario = DB::select("SELECT u.idusuario, u.cedula,u.nombres,u.apellidos,
        u.name_usuario,u.email, u.id_group, u.institucion_idInstitucion, u.iniciales,
        u.foto_user,u.telefono, i.idInstitucion, i.nombreInstitucion, c.nombre as ciudad,
        u.nacionalidad, fecha_nacimiento, curso,  sexo,n.nombrenivel ,p.descripcion,u.estado_idEstado,
        CAST(u.paralelo AS UNSIGNED)  as paralelo
        FROM usuario u
        LEFT JOIN institucion i ON i.idInstitucion = u.institucion_idInstitucion
        LEFT JOIN ciudad c ON  c.idciudad = i.ciudad_id
        LEFT JOIN nivel n ON u.curso = n.orden
        LEFT JOIN mat_paralelos p ON u.paralelo = p.paralelo_id
        WHERE u.institucion_idInstitucion = '$id'
        AND u.id_group = '4'
        and u.estado_idEstado = '1'
        ORDER BY u.idusuario DESC
        ");
        return $usuario;

        // $students = DB::select("SELECT idusuario, nombres, apellidos, cedula, name_usuario,
        //   email, id_group as grupo, id_group, institucion_idInstitucion, estado_idEstado, foto_user,
        //   telefono, created_at, updated_at
        //    FROM usuario WHERE institucion_idInstitucion = $id AND id_group= 4 ");
        // return $students;
    }

    public function traerCantidadUsuarios(Request $request){


        $usuarioActivos = DB::SELECT("SELECT COUNT(*) AS totalactivo FROM usuario u

        WHERE  u.estado_idEstado = '1'
        AND u.institucion_idInstitucion <> 66
        AND u.institucion_idInstitucion <> 981
        ");

        $usuariosProlipa = DB::SELECT("SELECT COUNT(*) AS totalprolipa  FROM usuario u

        WHERE  u.estado_idEstado = '1'
        AND (u.institucion_idInstitucion = '981' OR u.institucion_idInstitucion = '66' )
        ");


          return array(
         'usuarioActivos'=>$usuarioActivos,
         'usuariosProlipa' => $usuariosProlipa,

        );

    }
    ///CONSULTAS SALLE
    public function usuarioSalle()
    {
        $docentes = DB::select("SELECT u.*, i.idInstitucion, i.nombreInstitucion, i.tipo_institucion,
         concat(i.nombreInstitucion,' - ',c.nombre) AS institucion_ciudad, MAX(se.id_evaluacion) AS id_evaluacion,
         u.id_group
         FROM usuario u
         INNER JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
         INNER JOIN ciudad c ON i.ciudad_id = c.idciudad
         lEFT JOIN salle_evaluaciones se ON u.idusuario = se.id_usuario
         WHERE (
            u.id_group = '13'
            OR u.id_group = '6'
        )
         AND i.tipo_institucion = '2'
         GROUP BY u.idusuario
         ");
        $admins = DB::select("SELECT u.*, i.nombreInstitucion, concat(i.nombreInstitucion,' - ',c.nombre) AS institucion_ciudad FROM usuario u, institucion i INNER JOIN ciudad c ON i.ciudad_id = c.idciudad WHERE u.id_group = 12 and u.institucion_idInstitucion = i.idInstitucion ");
        return array('docentes'=>$docentes , 'admins'=>$admins,);
    }
    //API:GET/usuarioSalle/{n_evaluacion}
    public function usuarioSallexEvaluacion($n_evaluacion){
        // $docentes = DB::select("SELECT u.*, i.nombreInstitucion,
        // concat(i.nombreInstitucion,' - ',c.nombre) AS institucion_ciudad, MAX(se.id_evaluacion) AS id_evaluacion
        // FROM usuario u
        // INNER JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
        // INNER JOIN ciudad c ON i.ciudad_id = c.idciudad
        // LEFT JOIN salle_evaluaciones se ON u.idusuario = se.id_usuario
        // WHERE u.id_group = 13
        // GROUP BY u.idusuario
        // ");
        $docentes = DB::select("SELECT u.idusuario, u.nombres,u.apellidos,u.cedula,u.email,u.estado_idEstado,
        u.id_group,u.institucion_idInstitucion,u.telefono,
        i.idInstitucion, i.nombreInstitucion,
        concat(i.nombreInstitucion,' - ',c.nombre) AS institucion_ciudad,
        (SELECT   MAX(se.id_evaluacion)
        FROM salle_evaluaciones se
            WHERE se.id_usuario = u.idusuario
            AND se.n_evaluacion = '$n_evaluacion'
            AND intentos <> '0'
        ) AS id_evaluacion
        FROM usuario u
        INNER JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
        INNER JOIN ciudad c ON i.ciudad_id = c.idciudad
        LEFT JOIN salle_evaluaciones se ON u.idusuario = se.id_usuario
        WHERE (
            u.id_group = '13'
            OR u.id_group = '6'
        )
        AND i.tipo_institucion = '2'
        GROUP BY u.idusuario
        ");
       $admins = DB::select("SELECT u.*, i.nombreInstitucion, concat(i.nombreInstitucion,' - ',c.nombre) AS institucion_ciudad FROM usuario u, institucion i INNER JOIN ciudad c ON i.ciudad_id = c.idciudad WHERE u.id_group = 12 and u.institucion_idInstitucion = i.idInstitucion ");
       return array('docentes'=>$docentes , 'admins'=>$admins,);
    }
    public function add_edit_user_salle(Request $request)
    {

        if(empty( $request->idusuario )){
            $usuario = new Usuario();
            $datosValidados=$request->validate([
                'cedula' => 'required|max:13|unique:usuario',
                'email' => 'required|email|unique:usuario',
                ]);
            $usuario->password=sha1(md5($request->cedula)); //la clave se guarda con la cedula, solo en el registro, mas no en la edicion
        }else{
            $usuario = Usuario::find( $request->idusuario );

            $datosValidados=$request->validate([
                'cedula' => 'required|max:13|unique:usuario,cedula,'.$request->idusuario.',idusuario',
                'email' => 'required|unique:usuario,email,'.$request->idusuario.',idusuario'
                // 'email' => ['required','email',Rule::unique('usuario')->ignore($request->idusuario, 'idusuario')] //opcion 2 tambien funciona
                ]);
        }
        $usuario->cedula = $request->cedula;
        $usuario->nombres = $request->nombre;
        $usuario->apellidos = $request->apellido;
        $usuario->name_usuario = $request->email;
        $usuario->email = $request->email;
        $usuario->telefono = $request->telefono;
        $usuario->id_group = $request->grupo;
        $usuario->p_ingreso=0;
        $usuario->institucion_idInstitucion = $request->idInstitucion;
        $usuario->idcreadorusuario = $request->idcreadorusuario;
        $usuario->modificado_por = $request->idcreadorusuario;
        $usuario->estado_idEstado = $request->estado;
        $usuario->save();

        return $usuario;
    }
    public function activa_desactiva_user(Request $request){
        $edita = DB::update("UPDATE `usuario` SET `estado_idEstado`= $request->estado  WHERE `idusuario` = $request->idusuario");
        return $edita;
    }
    public function cambiarPassword(Request $request){
        // $edita = DB::update("UPDATE `usuario` SET `estado_idEstado`= $request->estado  WHERE `idusuario` = $request->idusuario");
        $usuario = Usuario::find( $request->idusuario );
        $usuario->password = sha1(md5($request->cedula));
        $usuario->save();
        return $usuario;
    }
    public function asesores()
    {
        $asesores = Usuario::select(DB::raw("CONCAT(nombres, ' ', apellidos) as nombres, cedula,idusuario "))
        ->where('id_group', 11)
        ->get();
        return $asesores;
    }

    public function destroy(Usuario $usuario)
    {
        return $usuario;
    }
    public function add_user_admin(Request $request)
    {
        $usuario = new Usuario();
        if($request->validarEmail == 1){
            $datosValidados=$request->validate([
            'cedula' => 'required|max:13|unique:usuario',
            'email' => 'required|email|unique:usuario',
            'name_usuario' => 'required|unique:usuario',
            ]);
        }else{
            $datosValidados=$request->validate([
            'cedula' => 'required|max:13|unique:usuario',
            'email' => 'required|unique:usuario',
            'name_usuario' => 'required|unique:usuario',
            ]);
        }
        $usuario->password          = sha1(md5($request->cedula));
        $usuario->cedula            = $request->cedula;
        $str1                       = strtolower($request->nombre);
        $str2                       = strtolower($request->apellido);
        $usuario->nombres           = ucwords($str1);
        $usuario->apellidos         = ucwords($str2);
        $usuario->name_usuario      = $request->name_usuario;
        $usuario->email             = $request->email;
        $usuario->telefono          = $request->telefono;
        $usuario->id_group          = $request->grupo;
        $usuario->p_ingreso         = 0;
        $usuario->institucion_idInstitucion = $request->idInstitucion;
        $usuario->idcreadorusuario  = $request->idcreadorusuario;
        $usuario->modificado_por    = $request->modificado_por;
        $usuario->estado_idEstado   = $request->estado;
        $usuario->fecha_nacimiento  = $request->fecha_nacimiento;
        $usuario->capacitador       = $request->capacitador;
        $usuario->iniciales         = $request->iniciales;
        $usuario->cli_ins_codigo    = $request->cli_ins_codigo == null || $request->cli_ins_codigo == "null" || $request->cli_ins_codigo == "" ? null : $request->cli_ins_codigo;
        if($request->grupo == 6){
            $usuario->cargo_id      = $request->cargo_id;
        }
        $usuario->save();
        return $usuario;
    }
    public function user_por_grupo()
    {
        $dato = DB::select("SELECT u.*,
            CONCAT(u.cedula, ' ', u.nombres,' ', u.apellidos) as user
            FROM usuario u
            WHERE id_group != '4'
            and id_group != '12'
            and id_group != '13'
            ORDER BY u.idusuario DESC
        ");
        return $dato;
    }
    public function cambiarDirector($id)
    {
        $dato = Usuario::find($id);
        $dato->id_group = '10';
        $dato->save();
        return $dato;

    }

   //para traer directores api::/getDirectores
   public function getDirectores(){
       $directores = DB::select("SELECT u.*,
       (
        SELECT count(di.id)  as contador
        FROM director_has_institucion di
        WHERE di.director_id = u.idusuario
       ) as contador,
       CONCAT(u.nombres,' ', u.apellidos) as director
        FROM usuario u
        WHERE id_group = '10'
        ORDER BY idusuario DESC
       ");
       return $directores;
   }
    //api::post/guardarAsignacionDirector
    //para asignar al director la institucion
    public function guardarAsignacionDirector(Request $request){
        //validar que la institucion no este asignada
         $validate = DB::SELECT("SELECT * FROM director_has_institucion di
         WHERE di.institucion_id = '$request->institucion_id'
         AND di.director_id = '$request->director_id'
        ");
        if(empty($validate)){
            $director = new DirectorHasInstitucion();
            $director->director_id      = $request->director_id;
            $director->institucion_id   = $request->institucion_id;
            $director->user_created     = $request->user_created;
            $director->save();
            if($director){
                return ["status" => "1", "message" =>"Se guardo correctamente"];
            }else{
                return ["status" => "0", "No se pudo guardar"];
            }
        }else{
            return ["status" => "0", "message" => "La institución ya ha sido asignada al director"];
        }
    }
   //para traer los cargos api://traerCargos
   public function traerCargos(Request $request){
       $cargos = DB::SELECT("SELECT * FROM institucion_cargos
    --    WHERE estado = '1'
       ORDER BY id DESC
       ");
       return $cargos;
   }
    //para ver la institucion del director api::/verInstitucionDirector
    public function verInstitucionDirector(Request $request) {
        $consulta = DB::SELECT("SELECT di.*,i.idInstitucion,
        i.nombreInstitucion, c.nombre AS ciudad,i.imgenInstitucion,i.direccionInstitucion,
        i.region_idregion,i.telefonoInstitucion,i.ciudad_id
        FROM director_has_institucion di
        LEFT JOIN institucion i ON di.institucion_id = i.idInstitucion
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        WHERE di.director_id = '$request->idusuario'
        ");
        return $consulta;
    }
    //para quitar la asignacion al director la institucion
    //api::>>post/quitarAsignacion
    public function quitarAsignacion(Request $request){
        DirectorHasInstitucion::findOrFail($request->id)->delete();
    }
    public function institucionesAsesor(Request $request){
        $instituciones =   DB::SELECT("SELECT i.* , zn.zn_nombre as nombreZona
        from institucion i
        LEFT JOIN i_zona zn ON zn.idzona = i.zona_id
        WHERE i.vendedorInstitucion = '$request->cedula'
        AND i.estado_idEstado = '1'
        AND (i.zona_id IS NULL OR i.zona_id ='')");
        return $instituciones;
    }
    //api:get>>/escuelasAsesor
    public function escuelasAsesor(Request $request){
        //Instituciones por region
        if($request->individual){
            if($request->porRegion){
                //obtener el periodo de la region
                $escuelas =   DB::SELECT("SELECT i.idInstitucion as id,i.nombreInstitucion as label, c.nombre as nombre_ciudad,
                    pir.periodoescolar_idperiodoescolar as id_periodo,p.periodoescolar as periodo ,p.estado ,
                IF(p.region_idregion = '1','Sierra','Costa') AS region
                from institucion i, ciudad c, periodoescolar_has_institucion pir,periodoescolar p
                WHERE i.ciudad_id = c.idciudad
                AND i.vendedorInstitucion = '$request->cedula'
                AND i.idInstitucion = pir.institucion_idInstitucion
                AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
                WHERE phi.institucion_idInstitucion = i.idInstitucion)
                AND p.idperiodoescolar = pir.periodoescolar_idperiodoescolar
                AND i.estado_idEstado = '1'
                AND i.region_idregion = '$request->region'
               ");
               return $escuelas;
            }
            if($request->porRegiones){
                $escuelas =   DB::SELECT("SELECT i.idInstitucion as id,i.nombreInstitucion as label, i.region_idregion, c.nombre as nombre_ciudad,
                 pir.periodoescolar_idperiodoescolar as id_periodo,p.periodoescolar  as periodo,
                IF(p.region_idregion = '1','Sierra','Costa') AS region
                from institucion i, ciudad c, periodoescolar_has_institucion pir,periodoescolar p
                WHERE i.ciudad_id = c.idciudad
                AND i.vendedorInstitucion = '$request->cedula'
                AND i.idInstitucion = pir.institucion_idInstitucion
                AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
                 WHERE phi.institucion_idInstitucion = i.idInstitucion)
                 AND p.idperiodoescolar = pir.periodoescolar_idperiodoescolar
                 AND i.estado_idEstado = '1'
                ");
            }

            $datos = [];
            $data = [];
            foreach($escuelas as $key => $item){
                $visitas = DB::SELECT("SELECT s.* FROM seguimiento_cliente s
                WHERE s.institucion_id = '$item->id'
                AND s.asesor_id = '$request->asesor_id'
                AND s.tipo_seguimiento = '1'
                AND s.estado = '0'
                AND s.periodo_id = '$item->id_periodo'
                ");
                $datos[$key]=[
                    "id" => $item->id,
                    "id_periodo" => $item->id_periodo,
                    "region_idregion" => $item->region_idregion,
                    "label" => $item->label,
                    "nombre_ciudad" => $item->nombre_ciudad,
                    "periodo" => $item->periodo,
                    "region" => $item->nombre_ciudad,
                    "visitas" => count($visitas)
                ];
            }
            $data = [
                "instituciones" => $datos
            ];
            return $data;
        }

        if($request->desdeAdmin){
            $periodo = $this->traerPeriodoInstitucion($request->institucion_id);
            return $periodo;
        }
        else{
            //instituciones del asesor
            $institucionAsesor = DB::SELECT("SELECT
            CONCAT(u.nombres, ' ',u.apellidos ) AS asesor,u.cedula,
            (
                SELECT COUNT(i.idInstitucion) FROM institucion i
                LEFT JOIN periodoescolar_has_institucion pir ON i.idInstitucion = pir.institucion_idInstitucion
                WHERE i.vendedorInstitucion = u.cedula
                AND i.estado_idEstado = '1'
                AND (pir.periodoescolar_idperiodoescolar = '$request->sierra'  OR pir.periodoescolar_idperiodoescolar = '$request->costa')
            )   AS cantidad_instituciones
            FROM usuario u
            WHERE u.id_group = '11'
            AND u.estado_idEstado = '1'
            AND u.cedula = '$request->cedula'
            ");
            return $institucionAsesor;
            // $periodo = $this->periodosActivos();
            // if(count($periodo) < 0){
            //     return ["status" => "0","No existe periodos activos"];
            // }
            // $periodo1 = $periodo[0]->idperiodoescolar;
            // $periodo2 = $periodo[1]->idperiodoescolar;
            // $instituciones = DB::SELECT("SELECT   COUNT( DISTINCT t.idInstitucion) AS total FROM temporadas t WHERE t.id_asesor  = '$request->asesor_id'
            // AND (t.id_periodo = '$periodo1' OR t.id_periodo = '$periodo2')
            // AND t.estado = '1'
            // ");
            // return $instituciones;
        }
    }
    public function traerPeriodoInstitucion($institucion){
        $periodoInstitucion = DB::SELECT("SELECT idperiodoescolar AS periodo , periodoescolar AS descripcion FROM periodoescolar WHERE idperiodoescolar = (
            SELECT  pir.periodoescolar_idperiodoescolar as id_periodo
            from institucion i,  periodoescolar_has_institucion pir
            WHERE i.idInstitucion = pir.institucion_idInstitucion
            AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
            WHERE phi.institucion_idInstitucion = i.idInstitucion
            AND i.idInstitucion = '$institucion'))
        ");
        return $periodoInstitucion;
    }

    //api:get>>/contratosAsesor
    public function contratosAsesor(Request $request){

        if($request->ultimaFechaVerificacion){
            $ultimaVerificacion = DB::SELECT("SELECT * FROM verificaciones WHERE contrato =  '$request->contrato'
            AND estado = '1'
            ");

            if(count($ultimaVerificacion) >0){
                return $ultimaVerificacion;
            }else{
                return ["status" => "0" ,"El contrato aun no tiene verificaciones"];
            }
        }

        if($request->traerPeriodos){
            $periodos = DB::SELECT("SELECT * FROM periodoescolar
            WHERE region_idregion = '$request->region'
            AND estado = '$request->periodoEstado'
            ORDER BY idperiodoescolar DESC
            ");
            if(empty($periodos)){
                return ["status" => "0" ,"message" => "No  se encontro  periodos"];
            }else{
                return $periodos;
            }

        }if($request->todo){
            $contratos = DB::SELECT("SELECT t.*, p.descripcion as periodo, CONCAT(ascr.nombres , ' ' , ascr.apellidos ) as asesorProlipa
            FROM temporadas t
            LEFT JOIN periodoescolar p ON t.id_periodo = p.idperiodoescolar
            LEFT JOIN usuario ascr  ON ascr.idusuario = t.id_asesor
            WHERE id_asesor = '$request->idusuario'
            AND id_periodo IS  NOT NULL

            ");
            return $contratos;

        }
        else{
            $contratos = DB::SELECT("SELECT t.*, p.descripcion as periodo, CONCAT(ascr.nombres , ' ' , ascr.apellidos ) as asesorProlipa,
            (SELECT COUNT(*) FROM verificaciones WHERE contrato =  t.contrato and nuevo = '1' and estado = '0') as contContrato

            FROM temporadas t
            LEFT JOIN periodoescolar p ON t.id_periodo = p.idperiodoescolar
            LEFT JOIN usuario ascr  ON ascr.idusuario = t.id_asesor
            WHERE id_asesor = '$request->idusuario'
            AND id_periodo IS  NOT NULL
            AND t.id_periodo = '$request->idperiodoescolar'
            ");

            if(empty($contratos)){
                return ["status" => "0" ,"message" => "No hay contratos para este asesor"];
            }else{
                return $contratos;
            }
        }

    }

    public function reporteria(Request $request){
        set_time_limit(6000);
        ini_set('max_execution_time', 6000);
        //para traer los asesores para la reporteria
        if($request->asesor){
            $asesores = $this->reporteriaAsesor();
            return $asesores;
        }
        //para traer las instituciones de los asesores
        if($request->asesorInstituciones){
            $instituciones = $this->institucionesAsesores($request->idusuario);
            return $instituciones;
        }
        //Para la reporteria de las instituciones
        if($request->rinstitucion){
            $rInstitucion = $this->reporteriaInstitucion();
            return $rInstitucion;
        }
        //para ver la informacion de cada institucion
        if($request->InformacionInstitucion){
            $infoInstitucion = $this->InformacionInstitucion($request->idInstitucion,$request->id_periodo);
            return $infoInstitucion;
        }
    }

    //reporteria para las instituciones
    public function reporteriaInstitucion(){

        $periodo = $this->periodosActivos();
        if(count($periodo) < 0){
            return ["status" => "0","No existe periodos activos"];
        }
        $periodo1 = $periodo[0]->idperiodoescolar;
        $periodo2 = $periodo[1]->idperiodoescolar;

        $instituciones = DB::SELECT("SELECT DISTINCT t.idInstitucion,t.id_periodo ,p.region_idregion, i.nombreInstitucion, p.periodoescolar,
        IF(p.region_idregion = '1','Sierra','Costa') AS region
        FROM temporadas t
        LEFT JOIN  periodoescolar p ON t.id_periodo = p.idperiodoescolar
        LEFT JOIN institucion i ON t.idInstitucion = i.idInstitucion
         WHERE (t.id_periodo = '$periodo1' OR t.id_periodo = '$periodo2')

        ");
        return $instituciones;

    }

    //para la informacion de la institucion estudiantes, docentes, libros
    public function InformacionInstitucion($institucion,$periodo){


            $estudiantes = DB::SELECT("SELECT COUNT(DISTINCT  u.idusuario) as estudiantes
            FROM codigoslibros  c
            LEFT JOIN  usuario u  ON c.idusuario = u.idusuario
            LEFT JOIN  periodoescolar p ON c.id_periodo = p.idperiodoescolar
            WHERE u.id_group = '4'
            AND c.id_periodo = '$periodo'
            AND u.estado_idEstado = '1'
            AND u.institucion_idInstitucion = '$institucion'
            ");

            $docentes = DB::SELECT("SELECT COUNT(DISTINCT u.idusuario)  AS docentes FROM curso c
            LEFT JOIN  usuario u  ON c.idusuario = u.idusuario
            LEFT JOIN  periodoescolar p ON c.id_periodo = p.idperiodoescolar
            WHERE u.id_group = '6'
            AND c.id_periodo = '$periodo'
            AND u.estado_idEstado = '1'
            AND u.institucion_idInstitucion = '$institucion'

            ");

            $libros = DB::SELECT("SELECT COUNT(DISTINCT c.codigo) AS libros
            FROM codigoslibros  c
            LEFT JOIN  usuario u  ON c.idusuario = u.idusuario
            LEFT JOIN  periodoescolar p ON c.id_periodo = p.idperiodoescolar
            WHERE u.id_group = '4'
            AND c.id_periodo = '$periodo'
            AND u.estado_idEstado = '1'
            AND u.institucion_idInstitucion = '$institucion'
            ");

            $informacion =[
                "estudiantes" => $estudiantes[0]->estudiantes,
                "docentes" => $docentes[0]->docentes,
                "libros" => $libros[0]->libros,
            ];
            return ["datos"=> $informacion];

    }
    //reporteria para asesores

    public function reporteriaAsesor(){
        $asesores = DB::SELECT("SELECT DISTINCT u.idusuario,u.cedula, CONCAT(u.nombres, ' ', u.apellidos) as vendedor FROM usuario u, institucion i
        WHERE u.id_group = '11'
     --    AND i.vendedorInstitucion = u.cedula
        AND u.estado_idEstado  ='1'
        ");
         $datos=[];
         $data = [];

        foreach($asesores as $key=> $item){
            //para traer los periodos activos
            $periodo = $this->periodosActivos();
            if(count($periodo) < 0){
                return ["status" => "0","No existe periodos activos"];
            }
            $periodo1 = $periodo[0]->idperiodoescolar;
            $periodo2 = $periodo[1]->idperiodoescolar;
            $instituciones = DB::SELECT("SELECT   COUNT( DISTINCT t.idInstitucion) AS total FROM temporadas t WHERE t.id_asesor  = '$item->idusuario'
            AND (t.id_periodo = '$periodo1' OR t.id_periodo = '$periodo2')
            AND t.estado = '1'
            ");

            $datos[$key]=[
                "usuario" => $item->idusuario,
                "vendedor" => $item->vendedor,
                "cedula" => $item->cedula,
                "escuelas" => $instituciones[0]->total
            ];

        }

        $data = [
            "vendedores" => $datos
        ];
        return $data;
    }

    //para traer los instituciones de los asesores
    public function institucionesAsesores($idusuario){
        $periodo = $this->periodosActivos();
        if(count($periodo) < 0){
            return ["status" => "0","No existe periodos activos"];
        }

        $periodo1 = $periodo[0]->idperiodoescolar;
        $periodo2 = $periodo[1]->idperiodoescolar;

        $instituciones = DB::SELECT("SELECT DISTINCT t.idInstitucion,t.id_periodo ,p.region_idregion, i.nombreInstitucion,
        IF(p.region_idregion = '1','Sierra','Costa') AS region
        FROM temporadas t
        LEFT JOIN  periodoescolar p ON t.id_periodo = p.idperiodoescolar
        LEFT JOIN institucion i ON t.idInstitucion = i.idInstitucion
        WHERE  t.id_asesor = '$idusuario'
         AND (t.id_periodo = '$periodo1' OR t.id_periodo = '$periodo2')
        ");

        return $instituciones;
    }


    public function periodosActivos(){
        $periodo = DB::SELECT("SELECT DISTINCT  p.* FROM periodoescolar p
        LEFT JOIN  codigoslibros c ON p.idperiodoescolar  = c.id_periodo
        WHERE  p.estado = '1'");
        return $periodo;
    }

    public function periodosActivosIndividual($region){
        $periodo = DB::SELECT("SELECT DISTINCT  p.* FROM periodoescolar p
        LEFT JOIN  codigoslibros c ON p.idperiodoescolar  = c.id_periodo
        WHERE  p.estado = '1'
        AND p.region_idregion = '$region'
        ");
        return $periodo;
    }
    public function getAsesoresInstituciones(Request $request){
        $asesoresInstituciones = DB::SELECT("SELECT
        CONCAT(u.nombres, ' ',u.apellidos ) AS asesor,u.cedula,u.name_usuario,
        u.nombres,u.apellidos, u.cargo_id,u.fecha_nacimiento,u.id_group, u.email,u.estado_idEstado,
        u.institucion_idInstitucion,u.telefono,u.iniciales,u.idusuario,u.foto_user,u.cli_ins_codigo,
        (
            SELECT COUNT(i.idInstitucion)
            FROM institucion i
            WHERE i.vendedorInstitucion = u.cedula
        )   AS cantidad_instituciones
        FROM usuario u
        WHERE u.id_group = '11'
        AND u.estado_idEstado = '1'
        ");
        return $asesoresInstituciones;
    }
    public function getInstitucionesxAsesor(Request $request){
        $institucionesAsesor = DB::SELECT("SELECT i.nombreInstitucion,
         i.idInstitucion, c.nombre AS ciudad,i.estado_idEstado
         FROM institucion i
         LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
         WHERE i.vendedorInstitucion = '$request->cedula'
        ");
        $datos = [];
        foreach($institucionesAsesor as $key => $item){
            $getPeriodo = $this->PeriodoInstitucion($item->idInstitucion);
            if(empty($getPeriodo)){
                $datos[$key] = [
                    "nombreInstitucion" => $item->nombreInstitucion,
                    "idInstitucion"     => $item->idInstitucion,
                    "estado_idEstado"   => $item->estado_idEstado,
                    "ciudad"            => $item->ciudad,
                    "periodo"           => "",
                    "estado"            => "",
                    "region"            => ""
                ];
            }else{
                $datos[$key] = [
                    "nombreInstitucion" => $item->nombreInstitucion,
                    "idInstitucion"     => $item->idInstitucion,
                    "estado_idEstado"   => $item->estado_idEstado,
                    "ciudad"            => $item->ciudad,
                    "periodo"           => $getPeriodo[0]->descripcion,
                    "estado"            => $getPeriodo[0]->estado,
                    "region"            => $getPeriodo[0]->region,
                ];
            }

        }
        return $datos;
    }
    public function getPeriodoInvidivual(Request $request){
        $periodo = DB::SELECT("SELECT DISTINCT  p.* FROM periodoescolar p
        LEFT JOIN  codigoslibros c ON p.idperiodoescolar  = c.id_periodo
        WHERE  p.estado = '1'
        AND p.region_idregion = '$request->region'
        ");
        $data= [];
        foreach($periodo as $key => $item){
            $validate = DB::SELECT("SELECT count(c.codigo) AS cantidad,id_periodo FROM codigoslibros c
            WHERE c.id_periodo = '$item->idperiodoescolar'
            ");
            $data[$key] =$validate[0];
        }
        //obtener el periodo activo con mas actividad
        usort($data, function($a, $b) {
            if ($a->cantidad == $b->cantidad) {
                return 0;
            }
            return ($a->cantidad > $b->cantidad) ? -1 : 1;
        });
        return $data[0]->id_periodo;
    }

    public function maxValueInArray($array, $keyToSearch)
    {
        $currentMax = NULL;
        foreach($array as $k=> $arr)
        {
            foreach($arr as $key => $value)
            {
                if ($key == $keyToSearch && ($value >= $currentMax))
                {
                    $currentMax = $arr->id_periodo;
                }
            }
        }
        return $currentMax;
    }

    public function ingresos_masivos (){
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        $contrasenas = [];
        $cedulas = [ '1758495921', '1759149022', '1758744898', '1758255093', '1757995707', '1758019929', '1050725074', '0650776255', '1758176489', '1757714850', '1757574973', '1757589492', '1757660319', '1757742778', '1757338908', '1757742786', '1757732233', '1757456007', '1757400179', '1757586555', '1757185291', '1756928709', '1757793557', '1757323215', '1757324023', '1757505936', '1757146335', '1757196520', '1050606217', '1756771992', '1757212814', '1756906861', '1756870364', '1050561628', '1757103377', '1756489009', '1755567078', '1756513097', '1756404529', '1728381938', '1756416903', '1755335757', '1756433346', '1756009039', '1351919103', '1756093587', '1728383389', '1756585558', '1728288976', '1728297126', '1754512331', '1728309806', '1754341426', '1728114206', '1728270271', '1050111754', '1728378942', '1757114242', '1754635413', '1727995936', '1050049681', '1728219898', '1727993568', '1754000741', '0954284923', '1755972195', '0954083150', '1752406577', '1727841205', '1005269764', '1728061167', '1727843110', '1717171709', '1728841469', '172797669', '1755176995', '1752693935', '1754491825', '1756097786', '1754617403', '1727923490', '1728589753', '1050023074', '1755759790', '1728836444', '1752433944', '1754975454', '1753170099', '1752627065', '1728904556', '1728077452', '1005035835', '1727469528', '1752822690', '1106072554', '1755839220', '0605888080', '1727171678', '1755187364', '1755760764', '1728550995', '1728160381', '1717171710', '1005350119', '1005265267', '1728017147', '1754152450', '1728774496', '1717171708', '1752626976', '1728084755', '1755766621', '1727596320', '1753360567', '1105134041', '1757146335', '1757196520', '1050606217', '1756771992', '1757212814', '1756906861', '1756870364', '1050561628', '1757103377', '1756489009', '1755567078', '1756513097', '1756404529', '1728381938', '1756416903', '1755335757', '1756433346', '1756009039', '1351919103', '1756093587', '1728383389', '1756585558', '1728288976', '1728297126', '1754512331', '1728309806', '1754341426', '1728114206', '1728270271', '1050111754', '1728378942', '1757114242', '1754635413', '1727995936', '1050049681', '1728219898', '1727993568', '1754000741', '0954284923', '1755972195', '0954083150', '1752406577', '1727841205', '1005269764', '1728061167', '1727843110', '1717171709', '1728841469', '172797669', '1755176995', '1752693935', '1754491825', '1756097786', '1754617403', '1727923490', '1728589753', '1050023074', '1755759790', '1728836444', '1752433944', '1754975454', '1753170099', '1752627065', '1728904556', '1728077452', '1005035835', '1727469528', '1752822690', '1106072554', '1755839220', '0605888080', '1727171678', '1755187364', '1755760764', '1728550995', '1728160381', '1717171710', '1005350119', '1005265267', '1728017147', '1754152450', '1728774496', '1717171708', '1752626976', '1728084755', '1755766621', '1727596320', '1753360567', '1105134041' ];

        $codigos = [ 'RINI1-ZWGXM53768562', 'RINI1-BBYPD57279985', 'RINI1-RWTGN50656944', 'RINI2-NPPQH81002420', 'RINI2-ZQRJJ09810753', 'RINI2-HWDCC49129476', 'RINI2-TMHJY46550743', 'RINI2-DNCGJ90112453', 'RINI2-FNYJK73073669', 'ERA1-ZVXG884697', 'ERA1-NHQV027235', 'ERA1-MGWB315685', 'ERA1-PRMF159341', 'ERA1-WGTF399746', 'ERA1-BGDW195053', 'ERA1-FHTX319127', 'ERA1-XFRT133752', 'ERA1-BVPP372947', 'ERA-NGVR921333', 'ERA1-PZPK013004', 'RP1-KBZ091532', 'ERA1-BVXT748468', 'ERA1-GQRZ921053', 'RP1-XGB143802', 'ERA1-WNJY527892', 'ERA1-YYCV4216361', 'MMA2-GMCQD79496590', 'MMA2-FDJD18323366', 'MMA2-DCRQR58681173', 'MMA2-TGWWZ61469175', 'MMA2-GXWRR28653468', 'MMA2-BFWJN12890587', 'MMA2-VMTDQ78695703', 'MMA2-CMDYM84302787', 'MMA2-RHYNC54468223', 'MMA3-RPYGP26482789', 'MMA3-FKFPY87874501', 'MMA3-ZKZFW45732483', 'MMA3-FBZDM94977838', 'MMA3-KZBHX26717923', 'MMA3-HXWVW64092983', 'MMA3-QVJRP11168811', 'MMA3-GYJNZ90354262', 'MMA3-JDQWN16001129', 'MMA3-DDMDJ55634477', 'MMA3-PDXYX22943147', 'MMA3-BXRDP81029081', 'MMA3-NNWTM07791722', 'MMA4-TPXGT87667461', 'MMA4-KPZQC25078214', 'MMA4-BYHFF02256057', 'MMA4-VRTZF22460831', 'MMA4-GTPCD10630899', 'MMA4-RWHMP97906574', 'MMA4-DMBFW50739806', 'MMA4-BWCZX38631792', 'MMA4-DHFTF25857444', 'MMA4-RDBXW42612364', 'MMA4-HJDMM56598641', 'MMA5-FVMXF51226169', 'MMA5-DRMMG1574077', 'MMA5-RCTYP32909436', 'MMA5-CZBTJ96870094', 'MMA5-PVXPW63562620', 'MMA5-QHNRV15271345', 'MMA5-QYGBN84725659', 'MMA5-FZBWG76475352', 'MMA5-QPBYY43293120', 'MMA5-DTVNF69559128', 'MMA5-YMYTR28694334', 'MMA5-XMXDZ00744618', 'MMA5-MBKMK65369800', 'MMA5-YTVDB89407308', 'MM6-VZH439193', 'MM6-XZD026809', 'MMA6-MKCCM50533242', 'MMA6-CMPKP46227532', 'MMA6-DZCGX21272006', 'MM6-YVM180913', 'MM6-KMN114817', 'MM6-NPB649279', 'MM6-FWP589417', 'MMA6-VNKJF89144204', 'MMA6-JQDRX46733285', 'MMA6-MCFKB52853307', 'MMA6-VYZNB63624680', 'MMA6-ZRNMD27022625', 'MM6-THD640494', 'MMA7-GDVHH70195982', 'MMA7-XVVVX75496185', 'MMA7-NHDQG75027712', 'MMA7-ZXPTQ35428311', 'MMA7-YBYQY68754851', 'MMA7-XYGZK99115224', 'MMA7-HHBZR18287598', 'MM8-RRH848114', 'MMA8-TRVQJ60879050', 'MMA8-DZPPF93236079', 'MMA8-ZBTGQ89585430', 'MM8-QQD200993', 'MMA8-VJFCB36269202', 'MMA8-FVWZ89094106', 'MMA8-NHFVQ84253455', 'MMA9-BNQNJ53816038', 'MMA9-TVHKX09610022', 'MM9-ZQF961807', 'MM9-WWZ633645', 'MM9-RPG740302', 'MMA9-ZCZFZ82676433', 'MM10-CKF261828', 'MMA10-MTBRT80478970', 'MMA10-MXGCG34510798', 'MMA10-RTMRN42276592', 'MMA10-MKCYP73318002', 'MMA10-FBTCJ60788835', 'MLE2-KFNHX35994254', 'ML2-CCW664228', 'MLE2-KRBCC04438874', 'MLE2-GRYWZ97893531', 'ML2-CCN550428', 'MLE2-VYKQF73820439', 'ML2-XFN482852', 'ML2-MQV659720', 'ML2-VZW923868', 'MLE3-FFXWR17215857', 'MLE3-PPPQD92205590', 'ML3-GBX466149', 'ML3-WQYXQ15679447', 'ML3-WYR640829', 'ML3-DBJZG64711404', 'ML3-MHX331784', 'ML3XNT455441', 'MLE3-DDGCH06329934', 'MLE3-FNPGD36480546', 'ML3-JPJ202127', 'MLE3-QMJWY09032262', 'ML3-ZYXO42597', 'MLE4-FXFMR73563245', 'MLE4-VNCVF21806406', 'MLE4-NVKBP62707298', 'MLE4-PGGDG61468547', 'MLE4-NHZHN32178954', 'MLE4-TJHTP90636572', 'MLE4-CVHVQ59255588', 'MLE4-YQBNW85196755', 'MLE4-RMYXH12923572', 'MLE4-NPRVW49050704', 'MLE4-HNFQK70243149', 'MLE5-JGFCN13101026', 'MLE5-QQBFX33502954', 'MLE5-GVNMX85608200', 'MLE5-FFMVF66924757', 'MLE5-QKWHB64345248', 'MLE5-HYYDZ46016691', 'MLE5-PHBVN60539477', 'ML5-CYX991852', 'MLE5-FRQZZ49324458', 'MLE5-ZJXDC54856396', 'MLE5-PGZDV19147428', 'ML5-BPJ491319', 'ML5-GWC069887', 'ML5-DCR728494', 'MLE6-TVYXM21065641', 'MLE6-TFKZZ17753520', 'MLE6-CDKGM45922522', 'ML6-KCQ417736', 'ML6-RXG000524', 'MLE6-JRXKZ69941773', 'MLE6-WGBPM91907687', 'ML6-WHT845133', 'ML6-WBN200330', 'ML6-TDR670224', 'ML6-DGZ131921', 'ML6-GTW427259', 'ML6-RCH283706', 'ML6-PNN333918', 'ML6-PDG184851', 'MLE7-NDDNZ85163642', 'ML7-WBZ862084', 'ML7-HCD298093', 'MLE7-ZFGZJ63103499', 'MLE7-XQXJR92075124', 'MLE7-HKVGM49555729', 'ML7-VDH645831', 'ML8-VYC205969', 'MLE8-JBBQY84549615', 'ML8-QDK191691', 'MLE8-JCJHP63226670', 'MLE8-GWCMR30908148', 'MLE8-ZZVVT20880289', 'MLE8-MGYFX86904307', 'ML8-MKY612590', 'ML9-DJX872385', 'ML9-PHD631590', 'MLE9-XGWQP71924249', 'MLE9-QVMQN50811415', 'MLE9-QJTGJ59236019', 'ML9-FNX055779', 'ML10-KTG822615', 'ML10-DZH647874', 'MLE10-ZJJD330479', 'ML10-RBF543617', 'ML10-RVQ768693', 'ML10-TYV683837' ];

        for( $i=0; $i<count($cedulas); $i++ ){
            $usuario_find = DB::SELECT("SELECT * FROM `usuario` WHERE `cedula` = ?", [$cedulas[$i]]);
            dump($usuario_find[0]->idusuario);
            DB::UPDATE("UPDATE `codigoslibros` SET `idusuario` = ?,`idusuario_creador_codigo`=14818, `id_periodo`=16 WHERE `codigo` = ?", [$usuario_find[0]->idusuario, $codigos[$i]]);
        }
    }
    //api:get>>/getAsesoresInstituciones
    public function getUsuariosFacturadores(){
        $facturadores = DB::SELECT("SELECT
        CONCAT(u.nombres, ' ',u.apellidos ) AS usuario,u.cedula,u.name_usuario,
        g.level as grupo,
        u.nombres,u.apellidos, u.cargo_id,u.fecha_nacimiento,u.id_group, u.email,u.estado_idEstado,
        u.institucion_idInstitucion,u.telefono,u.iniciales,u.idusuario,u.foto_user
        FROM usuario u
        LEFT JOIN sys_group_users g ON u.id_group = g.id
        WHERE (u.id_group = '22' OR u.id_group = '23')
        AND u.estado_idEstado = '1'
        ORDER BY u.id_group DESC
        ");
        return $facturadores;
    }
    //api:get>>/salleadministrador
    public function salleadministrador(){
        $adminsSalle = DB::SELECT("SELECT
        CONCAT(u.nombres, ' ',u.apellidos ) AS usuario,u.cedula,u.name_usuario,
        g.level as grupo,
        u.nombres,u.apellidos, u.cargo_id,u.fecha_nacimiento,u.id_group, u.email,u.estado_idEstado,
        u.institucion_idInstitucion,u.telefono,u.iniciales,u.idusuario,u.foto_user
        FROM usuario u
        LEFT JOIN sys_group_users g ON u.id_group = g.id
        WHERE u.id_group = '12'
        AND u.estado_idEstado = '1'
        ORDER BY u.id_group DESC
        ");
        return $adminsSalle;
    }
    //api:post>>/import/usuarios
    public function importUsuarios(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos = json_decode($request->data_codigos);
        $codigosNoCambiados=[];
        $codigoLiquidados =[];
        $codigoNoExiste = [];
        $porcentaje = 0;
        $contador = 0;
        foreach($codigos as $key => $item){
            //validar si el codigo existe
            $validar = $this->getCodigos($item->codigo,0);
            //valida que el codigo existe
            if(count($validar)>0){
                $ifLiquidado        = $validar[0]->estado_liquidacion;
                //validar si el codigo no este liquidado
                $ifBloqueado        = $validar[0]->estado;
                if($ifLiquidado !='0' && $ifBloqueado !=2){
                    $codigo =  DB::table('codigoslibros')
                    ->where('codigo', $item->codigo)
                    ->where('estado','<>', '2')
                    ->where('estado_liquidacion','<>', '0')
                    ->update([
                        'estado'             => 2,
                    ]);
                    if($codigo){
                        $porcentaje++;
                        //ingresar en el historico
                        $historico                  = new HistoricoCodigos();
                        $historico->codigo_libro    = $item->codigo;
                        $historico->usuario_editor  = $request->institucion_id;
                        $historico->idInstitucion   = $request->id_usuario;
                        $historico->id_periodo      = $request->periodo_id;
                        $historico->observacion     = $request->comentario;
                        $historico->save();
                    }else{
                        $codigosNoCambiados[$key] =[
                            "codigo" => $item->codigo
                        ];
                    }
                }else{
                    $codigoLiquidados[$contador] = [
                        "codigo" => $item->codigo,
                        "barrasEstado" => $validar[0]->barrasEstado,
                        "codigoEstado" => $validar[0]->codigoEstado,
                        "liquidacion" => $validar[0]->liquidacion,
                        "ventaEstado" => $validar[0]->ventaEstado,
                        "idusuario" => $validar[0]->idusuario,
                        "estudiante" => $validar[0]->estudiante,
                        "nombreInstitucion" => $validar[0]->nombreInstitucion,
                        "institucionBarra" => $validar[0]->institucionBarra,
                        "periodo" => $validar[0]->periodo,
                        "periodo_barras" => $validar[0]->periodo_barras,
                        "cedula" => $validar[0]->cedula,
                        "email" => $validar[0]->email,
                        "estado_liquidacion" => $validar[0]->estado_liquidacion,
                        "estado" => $validar[0]->estado,
                        "status" => $validar[0]->status,
                        "contador" => $validar[0]->contador
                    ];
                    $contador++;
                }
            }else{
                $codigoNoExiste[$key] =[
                    "codigo" => $item->codigo
                ];
            }
        }
        return [
            "cambiados" => $porcentaje,
            "codigosNoCambiados" => $codigosNoCambiados,
            "codigoLiquidados" => $codigoLiquidados,
            "codigoNoExiste" => $codigoNoExiste
        ];
    }
    public function getUsuario($cedula){
        $user = DB::SELECT("SELECT * FROM usuario u
        WHERE u.cedula = '$cedula'
        ");
        return $user;
    }
    public function actualizarIdusuarioInstitucion(Request $request){
        //actualizar los idusuario del asesor de la institucion
        $validate =DB::SELECT("SELECT * FROM usuario u
        WHERE u.cedula = '$request->cedula'
        ");
        if(count($validate) > 0){
            $idusuario = $validate[0]->idusuario;
            //actualizar
            DB::UPDATE("UPDATE institucion i
            SET i.asesor_id = '$idusuario'
             WHERE i.vendedorInstitucion  = '$request->cedula'
            ");
        }
    }
    public function getUsuariosPorRol($id)
    {
        $dato = DB::table('usuario as u')
        ->leftjoin('sys_group_users as g','u.id_group','=','g.id')
        ->leftjoin('estado as e','u.estado_idEstado','=','e.idEstado')
        ->leftjoin('institucion as i','u.institucion_idInstitucion','=','i.idInstitucion')
        ->select(
            DB::raw('CONCAT(u.nombres, " " ,u.apellidos ) AS usuario'),'u.cedula','u.name_usuario',
            'g.level as grupo', 'u.nombres','u.apellidos',
            'u.cargo_id','u.fecha_nacimiento','u.id_group', 'u.email','u.estado_idEstado',
            'u.institucion_idInstitucion','u.telefono','u.iniciales','u.idusuario','u.foto_user',
            'i.nombreInstitucion','e.nombreestado','u.capacitador','u.cli_ins_codigo'
        )
        ->where('u.id_group','=',$id)
        ->get();
        return $dato;
    }
    public function changePassword(Request $request){
        $fechaActual = date('Y-m-d');
        $fecha30dias = date('Y-m-d', strtotime($fechaActual . '+30 days'));
        $usuario = Usuario::findOrFail($request->idusuario);
        if($request->tipo == 1){
            $usuario->password              = sha1(md5($request->password));
            $usuario->fecha_change_password = null;
            $usuario->change_password       = "0";
        }
        if($request->tipo == 0){
            $usuario->fecha_change_password = $fecha30dias;
        }
        $usuario->save();
        return $usuario;
    }
    public function buscarXCedula($cedula){
        $dato = DB::table('usuario as u')
        ->where('u.cedula','=',$cedula)
        ->leftjoin('sys_group_users as gr','u.id_group','=','gr.id')
        ->select('u.*','gr.level','gr.deskripsi')
        ->get();
        return $dato;
    }

    public function GetUsuarios_PerfilLibrerias(){
        $query = DB::SELECT("SELECT u.*, CONCAT(u.nombres, ' ',u.apellidos,' - ', u.cedula) datosbusqueda FROM usuario u WHERE id_group = 33");
        return $query;
    }
    public function docentes_x_institucion(Request $request){
        $docentes = DB::table('usuario as u')
        ->join('asignaturausuario as au', 'u.idusuario', '=', 'au.usuario_idusuario')
        ->join('asignatura as a', 'au.asignatura_idasignatura', '=', 'a.idasignatura')
        ->select(
            'u.idusuario',
            'u.cedula',
            DB::raw("CONCAT(u.nombres, ' ', u.apellidos) AS docente"),
            'a.nombreasignatura',
            'au.updated_at'
        )
        ->where('u.institucion_idInstitucion', $request->idInstitucion)
        ->where('u.estado_idEstado', 1)
        ->where('au.periodo_id', $request->periodo_id)
        ->orderBy('u.nombres')
        ->get();

    // Agrupar los docentes por ID y asociar sus asignaturas
    $docentesAgrupados = $docentes->groupBy('idusuario')->map(function ($grupo) {
        return [
            'idusuario' => $grupo->first()->idusuario,
            'cedula' => $grupo->first()->cedula,
            'docente' => $grupo->first()->docente,
            'asignaturas' => $grupo->map(function ($asignatura) {
                return [
                    'nombre' => $asignatura->nombreasignatura,
                    'updated_at' => $asignatura->updated_at
                ];
            })->toArray()
        ];
    })->values(); // Convertimos la colección en un array indexado

    return response()->json($docentesAgrupados);
    }

    //Inicio Metodos Jeyson
    public function VerifcarMetodosGet_UsuarioController(Request $request)
    {
        $action = $request->query('action'); // Leer el parámetro `action` desde la URL

        switch ($action) {
            case 'Get_Busqueda_Representante_Institucion':
                return $this->busquedaUsuarioxCedula_Nombre_Apellido($request);
            case 'Get_Busqueda_Representante_InstitucionxID':
                return $this->busquedaUsuarioxidusuario($request);
            default:
                return response()->json(['error' => 'Acción no válida'], 400);
        }
    }
    public function busquedaUsuarioxCedula_Nombre_Apellido($request)
    {
        $busquedarepresentante  = $request->filtrorepresentante;
        $usuarios = DB::SELECT("SELECT us.*, sg.level 
        FROM usuario us 
        INNER JOIN sys_group_users sg on us.id_group = sg.id
        WHERE us.nombres LIKE '%$busquedarepresentante%' OR us.cedula LIKE '%$busquedarepresentante%' OR us.apellidos LIKE '%$busquedarepresentante%' ");
        return $usuarios;
    }
    public function busquedaUsuarioxidusuario($request)
    {
        $busquedaxid  = $request->idusuario;
        $usuarios = DB::SELECT("SELECT us.*
        FROM usuario us 
        WHERE us.idusuario = '$busquedaxid'");
        return $usuarios;
    }
    //Fin Metodos Jeyson

    public function usuarioConVentas($cedula)
    {
        $usuarios = DB::SELECT("SELECT fv.ven_codigo,fv.id_empresa, fv.ruc_cliente, u.cedula 
        FROM f_venta fv
        INNER JOIN usuario u ON fv.ven_cliente = u.idusuario
        WHERE fv.est_ven_codigo <> 3
        AND fv.idtipodoc IN (1, 3, 4)
        AND fv.ruc_cliente = '$cedula'");
        if(count($usuarios) > 0){
            return response()->json(['SinVentas' => false, 'ventas' => $usuarios]);
        }else{
            return response()->json(['SinVentas' => true]);
        }
    }
}
