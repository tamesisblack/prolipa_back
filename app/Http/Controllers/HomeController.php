<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        Auth::user();
         $user = Auth::user()->load([
            'grupo',
            'institucion:idInstitucion,nombreInstitucion'
        ]);
        return $user;
    }
    public function userInfo()
    {
        Cache::flush();
        return Auth::user();
        // $key = "userInfo";
        // if (Cache::has($key)) {
        //     $query = Cache::get($key);
        // } else {
        //     $query = Auth::user();
        //     Cache::put($key,$query);
        // }
        // return $query;
    }
}
