<?php
use Illuminate\Support\Facades\Route;
//facturacion
Route::group(['prefix' => 'perseo/facturacion'], function () {
    Route::resource('facturacion','Perseo\PerseoFacturacionController');
    //consulta de factura
    Route::post('facturas_consulta','Perseo\PerseoFacturacionController@facturas_consulta');
    //Creacion de factura
    Route::post('facturas_crear','Perseo\PerseoFacturacionController@facturas_crear');
    //facturas_autorizar
    Route::post('facturas_autorizar','Perseo\PerseoFacturacionController@facturas_autorizar');
});
//clientes
Route::group(['prefix' => 'perseo/clientes'], function () {
    Route::resource('cliente','Perseo\PerseoClienteController');
    //crear cliente
    Route::post('clientes_crear','Perseo\PerseoClienteController@clientes_crear');
    //editar cliente
    Route::post('clientes_editar','Perseo\PerseoClienteController@clientes_editar');
    //eliminar cliente
    Route::post('clientes_eliminar','Perseo\PerseoClienteController@clientes_eliminar');
    //consulta de clientes
    Route::post('clientes_consulta','Perseo\PerseoClienteController@clientes_consulta');
    //crear cliente prospecto
    Route::post('clientes_prospectos_crear','Perseo\PerseoClienteController@clientes_prospectos_crear');
    //consulta de grupos de los clientes
    Route::post('clientes_consulta_grupos','Perseo\PerseoClienteController@clientes_consulta_grupos');
    //consulta de rutas de los clientes
    Route::post('clientes_consulta_rutas','Perseo\PerseoClienteController@clientes_consulta_rutas');
    //consulta de zonas de los clientes
    Route::post('clientes_consulta_zonas','Perseo\PerseoClienteController@clientes_consulta_zonas');
});
//productos
Route::group(['prefix' => 'perseo/productos'], function () {
    Route::resource('producto','Perseo\PerseoProductoController');
    //crear producto
    Route::post('productos_crear','Perseo\PerseoProductoController@productos_crear');
    //editar producto
    Route::post('productos_editar','Perseo\PerseoProductoController@productos_editar');
    //eliminar producto
    Route::post('productos_eliminar','Perseo\PerseoProductoController@productos_eliminar');
    //consultar producto
    Route::post('productos_consulta','Perseo\PerseoProductoController@productos_consulta');
    //consultar imagenes de producto
    Route::post('productos_imagenes_consulta','Perseo\PerseoProductoController@productos_imagenes_consulta');
    //consulta de lineas de producto
    Route::post('productos_lineas_consulta','Perseo\PerseoProductoController@productos_lineas_consulta');
    //consulta de categorias de producto
    Route::post('productos_categorias_consulta','Perseo\PerseoProductoController@productos_categorias_consulta');
    //consulta de subcategorias de productos
    Route::post('productos_subcategorias_consulta','Perseo\PerseoProductoController@productos_subcategorias_consulta');
    //consulta de subgrupos de productos
    Route::post('productos_subgrupos_consulta','Perseo\PerseoProductoController@productos_subgrupos_consulta');
    //consulta de existencia de productos
    Route::post('existencia_producto','Perseo\PerseoProductoController@existencia_producto');
});
//contabilidad
Route::group(['prefix' => 'perseo/contabilidad'], function () {
    Route::resource('contabilidad','Perseo\PerseoContabilidadController');
    //consulta asiento contable
    Route::post('asientoscontables_consulta','Perseo\PerseoContabilidadController@asientoscontables_consulta');
    //crear asiento contable
    Route::post('asientocontable_individual_crear','Perseo\PerseoContabilidadController@asientocontable_individual_crear');
    //extraer listado de cuentas contables
    Route::post('cuentascontables_consulta','Perseo\PerseoContabilidadController@cuentascontables_consulta');
    //extraer listado de centros de costos existentes
    Route::post('centrocosto_consulta','Perseo\PerseoContabilidadController@centrocosto_consulta');
});
//transaccion
Route::group(['prefix' => 'perseo/transaccion'], function () {
    Route::resource('transaccion','Perseo\PerseoTransaccionController');
    //crear proforma
    Route::post('proformas_crear','Perseo\PerseoTransaccionController@proformas_crear');
    //crear pedido
    Route::post('pedidos_crear','Perseo\PerseoTransaccionController@pedidos_crear');
    //crear entrega
    Route::post('entregas_crear','Perseo\PerseoTransaccionController@entregas_crear');
    //crear cobro
    Route::post('cobros_crear','Perseo\PerseoTransaccionController@cobros_crear');
});
//consultas
Route::group(['prefix' => 'perseo/consultas'], function () {
    Route::resource('consultas','Perseo\PerseoConsultasController');
    //consulta de agentes de ventas
    Route::post('facturadores_consulta','Perseo\PerseoConsultasController@facturadores_consulta');
    //consulta de almacenes
    Route::post('almacenes_consulta','Perseo\PerseoConsultasController@almacenes_consulta');
    //consulta de provincias
    Route::post('consulta_provincias','Perseo\PerseoConsultasController@consulta_provincias');
    //consulta de parroquias
    Route::post('consulta_parroquias','Perseo\PerseoConsultasController@consulta_parroquias');
    //consulta de ciudades
    Route::post('consulta_ciudades','Perseo\PerseoConsultasController@consulta_ciudades');
    //consulta de bancos
    Route::post('bancos_consulta','Perseo\PerseoConsultasController@bancos_consulta');
    //consulta de centros de costos
    Route::post('centrocosto_consulta','Perseo\PerseoConsultasController@centrocosto_consulta');
    //consulta de bancos de los clientes
    Route::post('clientes_bancos_consulta','Perseo\PerseoConsultasController@clientes_bancos_consulta');
    //consulta de cajas
    Route::post('cajas_consulta','Perseo\PerseoConsultasController@cajas_consulta');
    //consulta de formas de pago
    Route::post('formapagoempresa_consulta','Perseo\PerseoConsultasController@formapagoempresa_consulta');
    //consulta de formas de pago del SRI
    Route::post('formapagosri_consulta','Perseo\PerseoConsultasController@formapagosri_consulta');
    //consulta de secuencias
    Route::post('secuencias_consulta','Perseo\PerseoConsultasController@secuencias_consulta');
    //consulta de tarjetas de credito
    Route::post('tarjetas_consulta','Perseo\PerseoConsultasController@tarjetas_consulta');
    //consulta de unidades de medidas
    Route::post('medidas_consulta','Perseo\PerseoConsultasController@medidas_consulta');
    //consulta de tarifas
    Route::post('tarifas_consulta','Perseo\PerseoConsultasController@tarifas_consulta');
    //consulta de tipos de iva
    Route::post('tipoiva_consulta','Perseo\PerseoConsultasController@tipoiva_consulta');
});
//moderna
Route::group(['prefix' => 'perseo/moderna'], function () {
    Route::resource('moderna','Perseo\PerseoModernaController');
    //consulta de clientes, que se ha ingresado en la ventana de mantenimiento de clientes.
    Route::post('CUSTOMERS','Perseo\PerseoModernaController@CUSTOMERS');
    //Recibe la información de las ventas realizadas
    Route::post('SALES','Perseo\PerseoModernaController@SALES');
    //Devuelve el listado de todas las sucursales de los clientes
    Route::post('CUSTOMERS_ADDRESSES','Perseo\PerseoModernaController@CUSTOMERS_ADDRESSES');
    //Listado de los productos ingresado en la ventana de mantenimiento de productos
    Route::post('ARTICLES','Perseo\PerseoModernaController@ARTICLES');
    //Historial del movimiento de cada uno de los productos.
    Route::post('INVENTORY','Perseo\PerseoModernaController@INVENTORY');
    //Devuelve el listado de las rutas creadas en el sistema Perseo.
    Route::post('ROUTES','Perseo\PerseoModernaController@ROUTES');
    //Se extrae de los datos ingresados en las visitas de los clientes.
    Route::post('ROUTES_DETAILS','Perseo\PerseoModernaController@ROUTES_DETAILS');
    //Se extrae de los datos ingresados en las visitas de los clientes, incluyendo la geo ubicación de cada visita.
    Route::post('USERS_HISTORY','Perseo\PerseoModernaController@USERS_HISTORY');
    //Muestra la información de los vendedores.
    Route::post('USER_IN_ROUTE','Perseo\PerseoModernaController@USER_IN_ROUTE');
});
?>
