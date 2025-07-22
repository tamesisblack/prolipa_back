<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $credentials=$request->only('name_usuario', 'password');

        if (Auth::attempt(['name_usuario' => $request->name_usuario, 'password' => $request->password, 'estado_idEstado' => 1])) {
            // Authentication passed...
            // return redirect()->intended('dashboard');

        }
        else{
            $buscarUser =DB::select("SELECT * FROM usuario where name_usuario = '$request->name_usuario'");
            if(count($buscarUser) > 0){
                $encontrarEstado = $buscarUser[0]->estado_idEstado;
                if($encontrarEstado == 2){
                    return response()->json(['errors' => 'Su usuario se encuentra bloqueado, por favor envíe un correo a soporte@prolipa.com.ec, para reestablecer su acceso.'], 412);
                }
                if($encontrarEstado == 3){
                    return response()->json(['errors' => 'Su usuario se encuentra bloqueado, por favor envíe un correo a soporte@prolipa.com.ec, para reestablecer su acceso.'], 412);
                }
                if($encontrarEstado == 4){
                    return response()->json(['errors' => 'Su usuario se encuentra bloqueado, por favor envíe un correo a soporte@prolipa.com.ec, para reestablecer su acceso.'], 412);
                }
                if($encontrarEstado == null){
                    return response()->json(['errors' => 'Su usuario se encuentra bloqueado, por favor envíe un correo a soporte@prolipa.com.ec, para reestablecer su acceso.'], 412);
                }else{
                    return response()->json(['errors' => 'Su contraseña es incorrecta'], 412);
                }
            }
            else{
                return response()->json(['errors' => 'Su usuario no coinciden con nuestros registros'], 412);
                // return response()->json(['errors' => 'Estas credenciales no coinciden con nuestros registros.'], 412);
            }

        }
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
