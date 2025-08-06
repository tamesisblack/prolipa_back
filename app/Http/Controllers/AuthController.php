<?php
namespace App\Http\Controllers;
use App\Http\Requests\RegisterAuthRequest;
use App\Models\SeminarioHasUsuario;
use App\Models\User;
use DB;
use App\Quotation;
use Illuminate\Http\Request;
use JWTAuth;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
class AuthController extends Controller {
    public $loginAfterSignUp = true;

    public function register(Request $request) {

        //SI YA EXISTE LA CEDULA para el usuario para el webinar
        if($request->cedulaExiste == "1"){
            $BuscarUsuarioSeminario = DB::SELECT("SELECT s.* FROM seminario_has_usuario s
            WHERE cedula = '$request->cedula'
            AND seminario_id = '$request->seminario'
            ");
            if(count($BuscarUsuarioSeminario) >0){
                return ["status" =>"0","message"=>"Webinar ya existe asignado a su usuario"];
            }
            $datosValidados=$request->validate([
                'cedula' => 'required|max:15',
                'seminario' => 'required', 
            ]);
            $buscarCedula = DB::SELECT("SELECT * FROM usuario WHERE cedula = '$request->cedula'
            ");
            $usuario = $buscarCedula[0]->idusuario;
            $intitucion = $buscarCedula[0]->institucion_idInstitucion;
            $seminario = new SeminarioHasUsuario();
            $seminario->usuario_id = $usuario;
            $seminario->seminario_id = $request->seminario;
            $seminario->cedula = $request->cedula;
            $seminario->institucion_id = $intitucion;
            $seminario->save();

            return $seminario;

        }
        //SI EL USUARIO ES NUEVO
        if($request->seminario){
            if($request->institucion_nombre){
                $datosValidados=$request->validate([
                    'cedula' => 'required|max:15|unique:usuario', 
                    'nombres' => 'required',
                    'apellidos' => 'required',
                    'email' => 'required|email|unique:usuario',
                    'seminario' => 'required',
                ]);
            }else{
                $datosValidados=$request->validate([
                    'cedula' => 'required|max:15|unique:usuario',
                    'nombres' => 'required',
                    'apellidos' => 'required',
                    'email' => 'required|email|unique:usuario',
                    'seminario' => 'required',
                
                ]);
            }
        
           
        }else{
            $datosValidados=$request->validate([
                'cedula' => 'required|max:15|unique:usuario',
                'nombres' => 'required',
                'apellidos' => 'required',
                'email' => 'required|email|unique:usuario',
                'institucion' => 'required',
                'grado' => 'required|max:20',
                'paralelo' => 'required|max:20',
            ]);
        }
        $user = new User();
        $user->cedula = $request->cedula;
        $str1 = strtolower($request->nombres);
        $str2 = strtolower($request->apellidos);
        $user->nombres = ucwords($str1);
        $user->apellidos = ucwords($str2);
        $user->email = $request->email;
        $user->name_usuario = $request->email;
        $user->paralelo = $request->paralelo;
        $user->curso = $request->grado;
        if($request->seminario){
            $user->id_group = '6';
        }else{
            $user->id_group = '4';
        }
        if($request->otraInstitucion){
            $user->estado_institucion_temporal = $request->estado_institucion_temporal;
            $user->institucion_temporal_id = $request->institucion_temporal_id;
        }else{
            $user->institucion_idInstitucion = $request->institucion;
        }
        $user->password = sha1(md5($request->cedula));
        $user->save();
        if($request->seminario){
            if($user){
                $seminario = new SeminarioHasUsuario();
                $seminario->usuario_id = $user->idusuario;
                $seminario->seminario_id = $request->seminario;
                $seminario->cedula = $request->cedula;
                if($request->otraInstitucion){
                    $seminario->institucion_nombre = $request->nombre_institucion_temporal;
                    $seminario->estado_institucion_temporal = $request->estado_institucion_temporal;
                    $seminario->institucion_temporal_id = $request->institucion_temporal_id;
                }else{
                    $seminario->institucion_id = $request->institucion;
                }
                $seminario->save();
            }
        }
        return response()->json([
        'status' => 'ok',
        'data' => $user
        ], 200);
    }
    //api:get>//verificarCedula

    public function verificarCedula(Request $request){
        $buscarCedula = DB::SELECT("SELECT * FROM usuario WHERE cedula = '$request->cedula'
        ");
        
        if(count($buscarCedula) >0){
            return ["status" =>"1","message" => "Ya existe la cedula","usuario"=> $buscarCedula];
        }else{
            return ["status" =>"0","message" => "No existe la cedula"];
        }
    }
    // public function login(Request $request) {
    //     $usuario = DB::SELECT("SELECT usuario.*,periodoescolar.idperiodoescolar AS periodoescolar_idperiodoescolar FROM `usuario` 
    //     LEFT JOIN institucion ON institucion.idInstitucion = usuario.institucion_idInstitucion 
    //     LEFT JOIN periodoescolar_has_institucion ON periodoescolar_has_institucion.institucion_idInstitucion = institucion.idInstitucion 
    //     LEFT JOIN periodoescolar ON periodoescolar.idperiodoescolar = periodoescolar_has_institucion.periodoescolar_idperiodoescolar
    //     WHERE `name_usuario` LIKE ? AND `password` = ? AND periodoescolar.estado like '1' LIMIT 1",[$request->name_usuario,sha1(md5($request->password))]);           
    //     if(empty($usuario)){
    //         $usuario = DB::SELECT("SELECT * FROM `usuario` WHERE `name_usuario` = ? AND `password` = ?",[$request->name_usuario,sha1(md5($request->password))]);           
    //     }
    //     $input = $request->only('name_usuario', 'password');
    //     $jwt_token = JWTAuth::attempt($input);
    //     if (!$jwt_token = JWTAuth::attempt($input)) {
    //         return response()->json([
    //         'status' => 'invalid_credentials',
    //         'message' => 'Correo o contraseña no válidos.',
    //         ], 401);
    //     }else{
    //         $id = 0;
    //         foreach ($usuario as $key => $value) {
    //             $id = $value->idusuario;
    //         }
    //         DB::update('update usuario set remember_token = ? where idusuario = ?', [$jwt_token,$id]);
    //         return response()->json([
    //             'datos' =>$usuario,
    //             'status' => 'ok',
    //             'token' => $jwt_token,
    //         ]);
    //     }
    // }
    public function login(Request $request)
    {
        $request->validate([
            'name_usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('name_usuario', 'password');

        // IMPORTANTE: Indica explícitamente que 'name_usuario' es el campo de usuario
        if (!Auth::attempt($credentials)) {
            return response()->json(['errors' => 'Credenciales incorrectas'], 412);
        }

         // Carga las relaciones grupo e institucion
        $user = Auth::user()->load([
            'grupo',
            'institucion:idInstitucion,nombreInstitucion'
        ]);

        // Crea un token personal (para Flutter o React Native)
        $token = $user->createToken('react-native')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }
    public function logout(Request $request) {
        $this->validate($request, [
            'token' => 'required'
        ]);
        try {
            JWTAuth::invalidate($request->token);
            return response()->json([
            'status' => 'ok',
            'message' => 'Cierre de sesión exitoso.'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'status' => 'unknown_error',
            'message' => 'Al usuario no se le pudo cerrar la sesión.'
            ], 500);
        }
    }
    public function getAuthUser(Request $request) {
        $this->validate($request, [
            'token' => 'required'
        ]);
        $user = JWTAuth::authenticate($request->token);
        return response()->json(['user' => $user]);
    }
    protected function jsonResponse($data, $code = 200){
        return response()->json($data, $code,
        ['Content-Type' => 'application/json;charset=UTF8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
    }
}

