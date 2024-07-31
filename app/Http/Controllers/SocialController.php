<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Socialite;
use DB;
use App\Quotation;

class SocialController extends Controller
{
    public function redirectToProvider($provider){
        return Socialite::driver($provider)->redirect();
    }
    public function handleProviderCallback($provider){
        try{
            $user = Socialite::driver($provider)->user();
            $createUser = User::firstOrNew([
                'email' => $user->getEmail()
            ]);
            auth()->login($createUser);
            return redirect('/home');
        }catch(\GuzzleHttp\Exception\ClientException $e){
            dd($e);
        }
    }
}
