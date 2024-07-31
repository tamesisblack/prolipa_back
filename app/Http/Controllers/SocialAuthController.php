 <?php
// namespace App\Http\Controllers;

use App\Models\User;
use Socialite;

class SocialAuthController extends Controller
{
    public function redirectToProvider($provider){
    }
    public function handleProviderCallback($provider){
        try{
            $user = Socialite::driver($provider)->user();
            $createUser = User::firstOrCreate([
                'email' => $user->getEmail()
            ]);
            auth()->login($createUser);
            return redirect('/home');
        }catch(\GuzzleHttp\Exception\ClientException $e){
            dd($e);
        }
    }
}
