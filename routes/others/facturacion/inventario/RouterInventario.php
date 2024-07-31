<?php
use Illuminate\Support\Facades\Route;
Route::group(['prefix' => 'inventario'], function () {
    Route::resource('configuracion','Facturacion\Inventario\ConfiguracionController');
});
?>
