<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use DB;
use Illuminate\Support\Facades\Cache;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        Cache::flush();

        // Recupera el usuario basado en el campo 'usuario'
        $user = User::where('name_usuario', $request->name_usuario)->first();
        // Verifica si el usuario existe
        if ($user) {
            //encontrar el estado_idEstado
            $encontrarEstado = $user->estado_idEstado;
            if ($encontrarEstado == 2 || $encontrarEstado == 3 || $encontrarEstado == 4 || $encontrarEstado == null) {
                return response()->json(['errors' => 'Su usuario se encuentra bloqueado, por favor envíe un correo a soporte@prolipa.com.ec, para reestablecer su acceso.'], 412);
            }
            $password2 = $user->password;
            //si el password2 es null creo el hash a bcrypt
            // if ($password2 == null) {
            //     $password2       = Hash::make($request->password);
            //     $user->password2 = $password2;
            //     $user->save();
            // }
            // Verifica la contraseña contra el hash SHA1-MD5
            $sha1md5Password = sha1(md5($request->password));

            if ($user->password === $sha1md5Password) {
                // Autenticación exitosa para el hash SHA1-MD5
                Auth::login($user);
            } elseif (Hash::check($request->password, $user->password)) {
                // Autenticación exitosa para el hash bcrypt
                Auth::login($user);
            } else {
                // Contraseña incorrecta
                return response()->json(['errors' => 'Su contraseña es incorrecta', 'tipo' => 'password'], 412);
            }
        } else {
            // Usuario no encontrado
            return response()->json(['errors' => 'Su usuario no existe', 'tipo' => 'user'], 412);
        }



        //$credentials=$request->only('name_usuario', 'password');

        // if (Auth::attempt(['name_usuario' => $request->name_usuario, 'password' => $request->password, 'estado_idEstado' => 1])) {


        // }
        // else{
        //     $buscarUser =DB::select("SELECT * FROM usuario where name_usuario = '$request->name_usuario'");
        //     if(count($buscarUser) > 0){
        //         $encontrarEstado = $buscarUser[0]->estado_idEstado;
        //         if($encontrarEstado == 2){
        //             return response()->json(['errors' => 'Su usuario se encuentra bloqueado, por favor envíe un correo a soporte@prolipa.com.ec, para reestablecer su acceso.'], 412);
        //         }
        //         if($encontrarEstado == 3){
        //             return response()->json(['errors' => 'Su usuario se encuentra bloqueado, por favor envíe un correo a soporte@prolipa.com.ec, para reestablecer su acceso.'], 412);
        //         }
        //         if($encontrarEstado == 4){
        //             return response()->json(['errors' => 'Su usuario se encuentra bloqueado, por favor envíe un correo a soporte@prolipa.com.ec, para reestablecer su acceso.'], 412);
        //         }
        //         if($encontrarEstado == null){
        //             return response()->json(['errors' => 'Su usuario se encuentra bloqueado, por favor envíe un correo a soporte@prolipa.com.ec, para reestablecer su acceso.'], 412);
        //         }else{
        //             return response()->json(['errors' => 'Su contraseña es incorrecta'], 412);
        //         }
        //     }
        //     else{
        //         return response()->json(['errors' => 'Su usuario no coinciden con nuestros registros'], 412);
        //     }

        // }
    }

    public function validateCredentials($user, $credentials)
    {
        // Implementa tu lógica de validación personalizada aquí
        $plain = $credentials['password'];
        $hashed_value = $user->getAuthPassword();
        return $hashed_value == sha1(md5($plain));
    }


    public function username()
    {
        return 'name_usuario';
    }
}
