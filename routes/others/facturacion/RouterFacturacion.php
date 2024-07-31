<?php
use Illuminate\Support\Facades\Route;
//datosFactura
Route::group(['prefix' => 'datosFactura'], function () {
    Route::resource('datosFactura','Facturacion\DatosFactura\DatosFacturaController');
});

?>
