<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProvisionServer;
use App\Http\Controllers\J_juegosController;
use App\Http\Controllers\JuegosController;
use App\Http\Controllers\TemporadaController;
use App\Http\Controllers\EstudianteController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('apimilton/', [TemporadaController::class, 'generarApiTemporada']);
Route::get('juego_y_contenido/{id}', [J_juegosController::class, 'juego_y_contenido']);
Route::post('j_guardar_calificacion', [J_juegosController::class, 'j_guardar_calificacion']);
Route::post('calificacion_estudiante', [J_juegosController::class, 'calificacion_estudiante']);
Route::get('estudiante_sopa/{id}', [EstudianteController::class, 'show']);

///=======PERSEO=========
require_once "others/perseo/PerseoRouter.php";
////ACORTADORES==
Route::get('verDataLink/{codigo}', 'LinkAcortadorController@verDataLink');
