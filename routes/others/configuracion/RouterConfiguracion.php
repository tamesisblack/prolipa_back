<?php
use Illuminate\Support\Facades\Route;
Route::group(['prefix' => 'plataformas'], function () {
    Route::resource('plataformas','configuracion\PlataformasController');
});
?>
