<?php

namespace App\Http\Controllers;

use App\Models\_14Producto;
use App\Models\Libro;
use App\Models\LibroSerie;
use App\Models\Institucion;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class _14ProductoController extends Controller {
    public function GetProducto() {
        $query = DB:: SELECT("SELECT * FROM 1_4_cal_producto ORDER BY pro_nombre ASC");
        return $query;
    }

    public function GetProductoxcodynombre(Request $request) {
        $query = DB:: SELECT("SELECT * FROM 1_4_cal_producto
        WHERE pro_codigo LIKE '%$request->busquedacodnombre%' || pro_nombre LIKE '%$request->busquedacodnombre%'
        ORDER BY pro_nombre ASC");
        return $query;
    }

    public function GetProducto_Inactivo() {
        $query = DB:: SELECT("SELECT * FROM 1_4_cal_producto p
        INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo
        WHERE p.pro_estado = 0
        ORDER BY p.pro_nombre ASC");
        return $query;
    }

    public function GetProducto_ComienzaconG() {
        $query = DB:: SELECT("SELECT * FROM 1_4_cal_producto 
        WHERE pro_codigo 
        LIKE 'G%' 
        ORDER BY pro_codigo ASC");
        return $query;
    }

    public function GetProductoxFiltro(Request $request) {
        if ($request -> busqueda == 'codigopro') {
            $query = DB:: SELECT("SELECT p.*, g.gru_pro_nombre
            FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo
            WHERE pro_codigo 
            LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
        if ($request -> busqueda == 'undefined') {
            $query = DB:: SELECT("SELECT p.*, g.gru_pro_nombre
            FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo
            WHERE pro_codigo 
            LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
        if ($request -> busqueda == 'nombres') {
            $query = DB:: SELECT("SELECT p.*, g.gru_pro_nombre
            FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo
            WHERE pro_nombre 
            LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
    }

    public function GetProductoActivosxFiltro(Request $request) {
        if ($request -> busqueda == 'codigopro') {
            $query = DB:: SELECT("SELECT p.*, g.gru_pro_nombre, ls.iniciales, ls.id_serie, s.nombre_serie, l.nombrelibro, l.idlibro, ls.year
            FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo            
            LEFT JOIN libros_series ls ON p.pro_codigo = ls.codigo_liquidacion
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN series s ON s.id_serie = ls.id_serie
            WHERE p.pro_codigo LIKE '%$request->razonbusqueda%' AND p.pro_estado = 1");
            return $query;
        }
        if ($request -> busqueda == 'undefined' || $request -> busqueda == '' || $request -> busqueda == null) {
            $query = DB:: SELECT("SELECT p.*, g.gru_pro_nombre, ls.iniciales, ls.id_serie, s.nombre_serie, l.nombrelibro, l.idlibro, ls.year
            FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo            
            LEFT JOIN libros_series ls ON p.pro_codigo = ls.codigo_liquidacion
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN series s ON s.id_serie = ls.id_serie
            WHERE p.pro_codigo LIKE '%$request->razonbusqueda%' AND p.pro_estado = 1");
            return $query;
        }
        if ($request -> busqueda == 'nombres') {
            $query = DB:: SELECT("SELECT p.*, g.gru_pro_nombre, ls.iniciales, ls.id_serie, s.nombre_serie, l.nombrelibro, l.idlibro, ls.year
            FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo            
            LEFT JOIN libros_series ls ON p.pro_codigo = ls.iniciales
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN series s ON s.id_serie = ls.id_serie
            WHERE pro_nombre LIKE '%$request->razonbusqueda%' AND pro_estado = 1");
            return $query;
        }
    }

    public function Registrar_modificar_producto_libro(Request $request) {
        try {
            DB:: beginTransaction();
            // Buscar el material_cubierta por su mat_cub_codigo o crear uno nuevo
            $producto = _14Producto:: firstOrNew(['pro_codigo' => $request -> pro_codigo_antiguo]);
            // Asignar los demás datos del producto
            $producto -> gru_pro_codigo = $request -> gru_pro_codigo;
            $producto -> pro_nombre = $request -> pro_nombre;
            $producto -> pro_descripcion = $request -> pro_descripcion;
            $producto -> pro_iva = $request -> pro_iva;
            $producto -> pro_valor = $request -> pro_valor;
            $producto -> pro_descuento = $request -> pro_descuento;
            $producto -> pro_deposito = $request -> pro_deposito;
            $producto -> pro_reservar = $request -> pro_reservar;
            $producto -> pro_stock = $request -> pro_stock;
            $producto -> pro_costo = $request -> pro_costo;
            $producto -> pro_peso = $request -> pro_peso;
            $producto->pro_depositoCalmed = $request->pro_depositoCalmed;
            $producto->pro_stockCalmed = $request->pro_stockCalmed;
            // return 'antiguo codigo ' . $request->pro_codigo_antiguo . ' nuevo codigo ' . $request->pro_codigo;
            // Verificar si es un nuevo registro o una actualización ->exists
            if ($producto -> exists) {
                // return 'entro al producto existente' ;
                $librosSerie = LibroSerie:: where('codigo_liquidacion', $request -> pro_codigo_antiguo) -> delete ();
                $producto = _14Producto:: findOrFail($request -> pro_codigo_antiguo);
                $producto -> delete ();
                $producto -> pro_codigo = $request -> pro_codigo;
                $producto -> gru_pro_codigo = $request -> gru_pro_codigo;
                $producto -> pro_nombre = $request -> pro_nombre;
                $producto -> pro_descripcion = $request -> pro_descripcion;
                $producto -> pro_iva = $request -> pro_iva;
                $producto -> pro_valor = $request -> pro_valor;
                $producto -> pro_descuento = $request -> pro_descuento;
                $producto -> pro_deposito = $request -> pro_deposito;
                $producto -> pro_reservar = $request -> pro_reservar;
                $producto -> pro_stock = $request -> pro_stock;
                $producto -> pro_costo = $request -> pro_costo;
                $producto -> pro_peso = $request -> pro_peso;
                $producto -> user_created = $request -> user_created;
                // Si ya existe, omitir el campo user_created para evitar que se establezca en null
                $producto -> updated_at = now();
                // Guardar el producto sin modificar user_created
                $producto -> save();                
                if ($request -> idlibro) {
                    $libro = DB:: table('libro')
                        -> where('idLibro', $request -> idlibro)
                        -> update([
                            'nombrelibro' => $request -> pro_nombre,
                            'descripcionlibro' => $request -> pro_descripcion,
                            'nombrelibro'=> $request -> nombrelibro,
                            'nombre_imprimir'=> $request -> nombre_imprimir,
                            'descripcionlibro'=> $request -> descripcionlibro,
                            'serie'=> $request -> serie,
                            'weblibro'=> $request -> weblibro,
                            'pdfsinguia'=> $request -> pdfsinguia ,
                            'pdfconguia'=> $request -> pdfconguia,
                            'guiadidactica'=> $request -> guiadidactica,
                            'asignatura_idasignatura'=> $request -> asignatura_idasignatura,
                            'portada'=> $request -> portada,
                            'demo'=> $request -> demo,
                            's_weblibro'=> $request -> s_weblibro,
                            's_pdfsinguia'=> $request -> s_pdfsinguia,
                            's_pdfconguia'=> $request -> s_pdfconguia,
                            's_guiadidactica'=> $request -> s_guiadidactica,
                            's_portada'=> $request -> s_portada,
                            'c_weblibro'=> $request -> c_weblibro,
                            'c_pdfsinguia'=> $request -> c_pdfsinguia,
                            'c_pdfconguia'=> $request -> c_pdfconguia,
                            'c_guiadidactica'=> $request -> c_guiadidactica,
                            'c_portada'=> $request -> c_portada,
                            // 'nombre_serie'=> $request -> nombre_serie,
                        ]);  
                    if ($librosSerie > 0) {
                        $librosSerie = new LibroSerie();
                        $librosSerie -> idLibro = $request -> idlibro;
                        $librosSerie -> iniciales = $request -> codigo_liquidacion;
                        $librosSerie -> id_serie = $request -> id_serie;
                        $librosSerie -> year = $request -> year;
                        $librosSerie -> version = $request -> version2;
                        $librosSerie -> codigo_liquidacion = $request -> codigo_liquidacion;
                        $librosSerie -> nombre = $request->pro_nombre;
                        $librosSerie -> boton = "success";
                        $librosSerie -> save();
                    }else {
                        return 'Error al crear el libro-serie';
                    }                  
                    // $librosSerie  = DB::table('libros_series')
                    // ->where('idLibro',$request->idlibro)
                    // ->update([
                    //     'id_serie' => $request->id_serie,
                    //     'codigo_liquidacion' => $request->pro_codigo,
                    //     'nombre' => $request->pro_nombre,
                    // ]);
                }else {
                    if ($request -> gru_pro_codigo == 1 || $request -> gru_pro_codigo == 3 || $request -> gru_pro_codigo == 6) {
                        if ($request -> idlibro) {
                            $libro = Libro:: findOrFail($request -> idlibro);
                        }else {
                            $libro = new Libro;
                            $libro->nombrelibro                 = $request->nombrelibro;
                            $libro->nombre_imprimir             = $request->nombre_imprimir;
                            $libro->descripcionlibro            = $request->descripcionlibro;
                            $libro->serie                       = $request->serie;
                            $libro->weblibro                    = $request->weblibro;
                            $libro->pdfsinguia                  = $request->pdfsinguia;
                            $libro->pdfconguia                  = $request->pdfconguia;
                            $libro->guiadidactica               = $request->guiadidactica;
                            $libro->asignatura_idasignatura     = $request->asignatura_idasignatura;
                            $libro->portada                     = $request->portada;
                            $libro->demo                        = $request->demo;
                            //DATOS SIERRA
                            $libro->s_weblibro                  = ($request->s_weblibro      == null     || $request->s_weblibro      == "null") ? null : $request->s_weblibro;
                            $libro->s_pdfsinguia                = ($request->s_pdfsinguia    == null     || $request->s_pdfsinguia    == "null") ? null : $request->s_pdfsinguia;
                            $libro->s_pdfconguia                = ($request->s_pdfconguia    == null     || $request->s_pdfconguia    == "null") ? null : $request->s_pdfconguia;
                            $libro->s_guiadidactica             = ($request->s_guiadidactica == null     || $request->s_guiadidactica == "null") ? null : $request->s_guiadidactica;
                            $libro->s_portada                   = ($request->s_portada       == null     || $request->s_portada       == "null") ? null : $request->s_portada;
                            //DATOS COSTA
                            $libro->c_weblibro                  = ($request->c_weblibro      == null     || $request->c_weblibro      == "null") ? null : $request->c_weblibro;
                            $libro->c_pdfsinguia                = ($request->c_pdfsinguia    == null     || $request->c_pdfsinguia    == "null") ? null : $request->c_pdfsinguia;
                            $libro->c_pdfconguia                = ($request->c_pdfconguia    == null     || $request->c_pdfconguia    == "null") ? null : $request->c_pdfconguia;
                            $libro->c_guiadidactica             = ($request->c_guiadidactica == null     || $request->c_guiadidactica == "null") ? null : $request->c_guiadidactica;
                            $libro->c_portada                   = ($request->c_portada       == null     || $request->c_portada       == "null") ? null : $request->c_portada;
                            // Guardar el libro
                            $libro -> save();
                            if ($request -> idlibro) {
                                $librosSerie = DB:: table('libros_series')
                                    -> where('idLibro', $request -> idlibro)
                                    -> update([
                                        'id_serie' => $request -> id_serie,
                                        'codigo_liquidacion' => $request -> codigo_liquidacion,
                                        'year' => $request -> year,
                                        'version' => $request -> version2,
                                    ]);
                            }else {
                                // Verificar si ya existe un registro con el mismo id_serie
                                // $existingLibrosSerie = LibroSrie::where('id_serie', $request->id_serie)->first();
                                //return $libro->idlibro;
                                $librosSerie = new LibroSerie();
                                $librosSerie -> idLibro = $libro -> idlibro;
                                $librosSerie -> iniciales = $request -> codigo_liquidacion;
                                $librosSerie -> id_serie = $request -> id_serie;
                                $librosSerie -> year = $request -> year;
                                $librosSerie -> version = $request -> version2;
                                $librosSerie -> codigo_liquidacion = $request -> codigo_liquidacion;
                                $librosSerie -> nombre = $libro -> nombrelibro;
                                $librosSerie -> boton = "success";
                                $librosSerie -> save();
                            }
                        }

                    }else {
                        $producto = _14Producto:: findOrFail($request -> pro_codigo_antiguo);
                        $producto -> delete ();
                        $producto -> pro_codigo = $request -> pro_codigo;
                        $producto -> user_created = $request -> user_created;
                        $producto -> gru_pro_codigo = $request -> gru_pro_codigo;
                        $producto -> pro_nombre = $request -> pro_nombre;
                        $producto -> pro_descripcion = $request -> pro_descripcion;
                        $producto -> pro_iva = $request -> pro_iva;
                        $producto -> pro_valor = $request -> pro_valor;
                        $producto -> pro_descuento = $request -> pro_descuento;
                        $producto -> pro_deposito = $request -> pro_deposito;
                        $producto -> pro_reservar = $request -> pro_reservar;
                        $producto -> pro_stock = $request -> pro_stock;
                        $producto -> pro_costo = $request -> pro_costo;
                        $producto -> pro_peso = $request -> pro_peso;
                        $producto -> user_created = $request -> user_created;
                        $producto->pro_depositoCalmed = $request->pro_depositoCalmed;
                        $producto->pro_stockCalmed = $request->pro_stockCalmed;
                        $producto -> updated_at = now();
                        // Guardar el producto
                        $producto -> save();
                    }
                }
            }else {
                // return 'entro al producto no existente' ;  
                // Si es un nuevo registro, establecer user_created y updated_at
                $producto -> pro_codigo = $request -> pro_codigo;
                $producto -> user_created = $request -> user_created;
                $producto -> gru_pro_codigo = $request -> gru_pro_codigo;
                $producto -> pro_nombre = $request -> pro_nombre;
                $producto -> pro_descripcion = $request -> pro_descripcion;
                $producto -> pro_iva = $request -> pro_iva;
                $producto -> pro_valor = $request -> pro_valor;
                $producto -> pro_descuento = $request -> pro_descuento;
                $producto -> pro_deposito = $request -> pro_deposito;
                $producto -> pro_reservar = $request -> pro_reservar;
                $producto -> pro_stock = $request -> pro_stock;
                $producto -> pro_costo = $request -> pro_costo;
                $producto -> pro_peso = $request -> pro_peso;
                $producto->pro_depositoCalmed = $request->pro_depositoCalmed;
                $producto->pro_stockCalmed = $request->pro_stockCalmed;
                $producto -> updated_at = now();
                // Guardar el producto
                $producto -> save();
                // Verificar si se debe ejecutar el bloque de código para el libro y la serie
                if (($request -> gru_pro_codigo == 1 || $request -> gru_pro_codigo == 3 || $request -> gru_pro_codigo == 6)) {
                    if ($request -> idlibro) {
                        $libro = Libro:: findOrFail($request -> idlibro);
                    }else {
                        $libro = new Libro;
                        $libro->nombrelibro                 = $request->nombrelibro;
                        $libro->nombre_imprimir             = $request->nombre_imprimir;
                        $libro->descripcionlibro            = $request->descripcionlibro;
                        $libro->serie                       = $request->serie;
                        $libro->weblibro                    = $request->weblibro;
                        $libro->pdfsinguia                  = $request->pdfsinguia;
                        $libro->pdfconguia                  = $request->pdfconguia;
                        $libro->guiadidactica               = $request->guiadidactica;
                        $libro->asignatura_idasignatura     = $request->asignatura_idasignatura;
                        $libro->portada                     = $request->portada;
                        $libro->demo                        = $request->demo;
                        //DATOS SIERRA
                        $libro->s_weblibro                  = ($request->s_weblibro      == null     || $request->s_weblibro      == "null") ? null : $request->s_weblibro;
                        $libro->s_pdfsinguia                = ($request->s_pdfsinguia    == null     || $request->s_pdfsinguia    == "null") ? null : $request->s_pdfsinguia;
                        $libro->s_pdfconguia                = ($request->s_pdfconguia    == null     || $request->s_pdfconguia    == "null") ? null : $request->s_pdfconguia;
                        $libro->s_guiadidactica             = ($request->s_guiadidactica == null     || $request->s_guiadidactica == "null") ? null : $request->s_guiadidactica;
                        $libro->s_portada                   = ($request->s_portada       == null     || $request->s_portada       == "null") ? null : $request->s_portada;
                        //DATOS COSTA
                        $libro->c_weblibro                  = ($request->c_weblibro      == null     || $request->c_weblibro      == "null") ? null : $request->c_weblibro;
                        $libro->c_pdfsinguia                = ($request->c_pdfsinguia    == null     || $request->c_pdfsinguia    == "null") ? null : $request->c_pdfsinguia;
                        $libro->c_pdfconguia                = ($request->c_pdfconguia    == null     || $request->c_pdfconguia    == "null") ? null : $request->c_pdfconguia;
                        $libro->c_guiadidactica             = ($request->c_guiadidactica == null     || $request->c_guiadidactica == "null") ? null : $request->c_guiadidactica;
                        $libro->c_portada                   = ($request->c_portada       == null     || $request->c_portada       == "null") ? null : $request->c_portada;
                        // Guardar el libro
                        $libro -> save();
                        if ($request -> idlibro) {
                            $librosSerie = DB:: table('libros_series')
                                -> where('idLibro', $request -> idlibro)
                                -> update([
                                    'id_serie' => $request -> id_serie,
                                    'codigo_liquidacion' => $request -> pro_codigo,
                                    'nombre' => $libro -> nombrelibro,
                                    'version' => $request -> version2,
                                ]);
                        }else {
                            $librosSerie = new LibroSerie();
                            $librosSerie -> idLibro = $libro -> idlibro;
                            $librosSerie -> iniciales = $request -> codigo_liquidacion;
                            $librosSerie -> id_serie = $request -> id_serie;
                            $librosSerie -> codigo_liquidacion = $request -> codigo_liquidacion;
                            $librosSerie -> year = $request -> year;
                            $librosSerie -> version = $request -> version2;
                            $librosSerie -> nombre = $libro -> nombrelibro;
                            $librosSerie -> boton = "success";
                            $librosSerie -> save();
                        }
                    }
                }
            }
            DB:: commit();
            return response()->json(['message' => 'Se guardó correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo actualizar/guardar', 'message' => $e->getMessage(),'line' => $e->getLine()]);
            DB:: rollback();
        }
        // Verificar si el producto se guardó correctamente
        if ($producto -> wasRecentlyCreated || $producto -> wasChanged()) {
            return "Se guardó correctamente";
        }else {
            return "No se pudo guardar/actualizar";
        }
    }

    public function Registrar_modificar_producto(Request $request) {
        try {
            DB::beginTransaction();
            
            // Buscar el producto por su código antiguo o crear uno nuevo
            $producto = _14Producto::firstOrNew(['pro_codigo' => $request->pro_codigo_antiguo]);

            // Asignar los datos del producto
            $producto->pro_codigo = $request->pro_codigo; // Asegúrate de que pro_codigo no sea nulo
            $producto->gru_pro_codigo = $request->gru_pro_codigo;
            $producto->pro_nombre = $request->pro_nombre;
            $producto->pro_descripcion = $request->pro_descripcion;
            $producto->pro_iva = $request->pro_iva;
            $producto->pro_valor = $request->pro_valor;
            $producto->pro_descuento = $request->pro_descuento;
            $producto->pro_deposito = $request->pro_deposito;            
            $producto->pro_reservar = $request->pro_reservar;
            $producto->pro_stock = $request->pro_stock;
            $producto->pro_costo = $request->pro_costo;
            $producto->pro_peso = $request->pro_peso;
            $producto->user_created = $request->user_created;
            $producto->pro_depositoCalmed = $request->pro_depositoCalmed;
            $producto->pro_stockCalmed = $request->pro_stockCalmed;
            $producto->updated_at = now();

            // Guardar el producto
            $producto->save();

            // Verificar si se debe eliminar registros relacionados antes de actualizar el producto
            if ($producto->exists) {
                $librosSerie = LibroSerie::where('codigo_liquidacion', $request->pro_codigo_antiguo)->delete();
            }

            // Verificar si se debe crear o actualizar un libro y serie
            if ($request->gru_pro_codigo == 1 || $request->gru_pro_codigo == 3 || $request->gru_pro_codigo == 6) {
                if ($request->idlibro) {
                    $libro = Libro::findOrFail($request->idlibro);
                } else {
                    $libro = new Libro;
                    $libro->nombrelibro = $request->pro_nombre;
                    $libro->nombre_imprimir = $request->pro_nombre;
                    $libro->descripcionlibro = $request->pro_nombre;
                    $libro->save();
                }

                // Crear o actualizar la serie del libro
                try {
                    $librosSerie = new LibroSerie();
                    $librosSerie->idLibro = $libro->idlibro;
                    $librosSerie->iniciales = $request->codigo_liquidacion;
                    $librosSerie->id_serie = $request->id_serie;
                    $librosSerie->codigo_liquidacion = $request->codigo_liquidacion;
                    $librosSerie->year = $request->year;
                    $librosSerie->version = $request->version2;
                    $librosSerie->nombre = $libro->nombrelibro;
                    $librosSerie->boton = "success";
                    $librosSerie->save();
                } catch (\Exception $e) {
                    Log::error('Error al crear LibroSerie: ' . $e->getMessage());
                }
            }

            DB::commit();
            return response()->json(['message' => 'Se guardó correctamente']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'No se pudo actualizar/guardar', 'message' => $e->getMessage(), 'line' => $e->getLine()]);
        }
    }

    
    public function Registrar_modificar_producto_backup(Request $request) {
        try {
            DB:: beginTransaction();
            $producto = _14Producto:: firstOrNew(['pro_codigo' => $request -> pro_codigo_antiguo]);
            $producto -> gru_pro_codigo = $request -> gru_pro_codigo;
            $producto -> pro_nombre = $request -> pro_nombre;
            $producto -> pro_descripcion = $request -> pro_descripcion;
            $producto -> pro_iva = $request -> pro_iva;
            $producto -> pro_valor = $request -> pro_valor;
            $producto -> pro_descuento = $request -> pro_descuento;
            $producto -> pro_deposito = $request -> pro_deposito;
            $producto -> pro_reservar = $request -> pro_reservar;
            $producto -> pro_stock = $request -> pro_stock;
            $producto -> pro_costo = $request -> pro_costo;
            $producto -> pro_peso = $request -> pro_peso;
            // return 'antiguo codigo ' . $request->pro_codigo_antiguo . ' nuevo codigo ' . $request->pro_codigo;
            // Verificar si es un nuevo registro o una actualización ->exists
            if ($producto -> exists) {
                // return 'entro al producto existente' ;
                $librosSerie = LibroSerie:: where('codigo_liquidacion', $request -> pro_codigo_antiguo) -> delete ();
                $producto = _14Producto:: findOrFail($request -> pro_codigo_antiguo);
                $producto -> delete ();
                $producto -> pro_codigo = $request -> pro_codigo;
                $producto -> gru_pro_codigo = $request -> gru_pro_codigo;
                $producto -> pro_nombre = $request -> pro_nombre;
                $producto -> pro_descripcion = $request -> pro_descripcion;
                $producto -> pro_iva = $request -> pro_iva;
                $producto -> pro_valor = $request -> pro_valor;
                $producto -> pro_descuento = $request -> pro_descuento;
                $producto -> pro_deposito = $request -> pro_deposito;
                $producto -> pro_reservar = $request -> pro_reservar;
                $producto -> pro_stock = $request -> pro_stock;
                $producto -> pro_costo = $request -> pro_costo;
                $producto -> pro_peso = $request -> pro_peso;
                $producto -> user_created = $request -> user_created;
                // Si ya existe, omitir el campo user_created para evitar que se establezca en null
                $producto -> updated_at = now();
                // Guardar el producto sin modificar user_created
                $producto -> save();                
                if ($request -> idlibro) {
                    $libro = DB:: table('libro')
                        -> where('idLibro', $request -> idlibro)
                        -> update([
                            'nombrelibro' => $request -> pro_nombre,
                            'descripcionlibro' => $request -> pro_descripcion,
                        ]);  
                    if ($librosSerie > 0) {
                        $librosSerie = new LibroSerie();
                        $librosSerie -> idLibro = $request -> idlibro;
                        $librosSerie -> iniciales = $request -> codigo_liquidacion;
                        $librosSerie -> id_serie = $request -> id_serie;
                        $librosSerie -> year = $request -> year;
                        $librosSerie -> version = $request -> version2;
                        $librosSerie -> codigo_liquidacion = $request -> codigo_liquidacion;
                        $librosSerie -> nombre = $request->pro_nombre;
                        $librosSerie -> boton = "success";
                        $librosSerie -> save();
                    }else {
                        return 'Error al crear el libro-serie';
                    }
                }else {
                    if ($request -> gru_pro_codigo == 1 || $request -> gru_pro_codigo == 3 || $request -> gru_pro_codigo == 6) {
                        if ($request -> idlibro) {
                            $libro = Libro:: findOrFail($request -> idlibro);
                        }else {
                            $libro = new Libro;
                            $libro -> nombrelibro = $request -> pro_nombre;
                            $libro -> nombre_imprimir = $request -> pro_nombre;
                            $libro -> descripcionlibro = $request -> pro_nombre;
                            // Guardar el libro
                            $libro -> save();
                            if ($request -> idlibro) {
                                $librosSerie = DB:: table('libros_series')
                                    -> where('idLibro', $request -> idlibro)
                                    -> update([
                                        'id_serie' => $request -> id_serie,
                                        'codigo_liquidacion' => $request -> pro_codigo,
                                        'nombre' => $libro -> nombrelibro,
                                    ]);
                            }else {
                                $librosSerie = new LibroSerie();
                                $librosSerie -> idLibro = $libro -> idlibro;
                                $librosSerie -> iniciales = $request -> codigo_liquidacion;
                                $librosSerie -> id_serie = $request -> id_serie;
                                $librosSerie -> year = $request -> year;
                                $librosSerie -> version = $request -> version2;
                                $librosSerie -> codigo_liquidacion = $request -> codigo_liquidacion;
                                $librosSerie -> nombre = $libro -> nombrelibro;
                                $librosSerie -> boton = "success";
                                $librosSerie -> save();
                            }
                        }

                    }else {
                        $producto = _14Producto:: findOrFail($request -> pro_codigo_antiguo);
                        $producto -> delete ();
                        $producto -> pro_codigo = $request -> pro_codigo;
                        $producto -> user_created = $request -> user_created;
                        $producto -> gru_pro_codigo = $request -> gru_pro_codigo;
                        $producto -> pro_nombre = $request -> pro_nombre;
                        $producto -> pro_descripcion = $request -> pro_descripcion;
                        $producto -> pro_iva = $request -> pro_iva;
                        $producto -> pro_valor = $request -> pro_valor;
                        $producto -> pro_descuento = $request -> pro_descuento;
                        $producto -> pro_deposito = $request -> pro_deposito;
                        $producto -> pro_reservar = $request -> pro_reservar;
                        $producto -> pro_stock = $request -> pro_stock;
                        $producto -> pro_costo = $request -> pro_costo;
                        $producto -> pro_peso = $request -> pro_peso;
                        $producto -> user_created = $request -> user_created;
                        $producto -> updated_at = now();
                        // Guardar el producto
                        $producto -> save();
                    }
                }
            }else {
                // Si es un nuevo registro, establecer user_created y updated_at
                $producto -> pro_codigo = $request -> pro_codigo;
                $producto -> user_created = $request -> user_created;
                $producto -> gru_pro_codigo = $request -> gru_pro_codigo;
                $producto -> pro_nombre = $request -> pro_nombre;
                $producto -> pro_descripcion = $request -> pro_descripcion;
                $producto -> pro_iva = $request -> pro_iva;
                $producto -> pro_valor = $request -> pro_valor;
                $producto -> pro_descuento = $request -> pro_descuento;
                $producto -> pro_deposito = $request -> pro_deposito;
                $producto -> pro_reservar = $request -> pro_reservar;
                $producto -> pro_stock = $request -> pro_stock;
                $producto -> pro_costo = $request -> pro_costo;
                $producto -> pro_peso = $request -> pro_peso;
                $producto -> updated_at = now();
                // Guardar el producto
                $producto -> save();
                // Verificar si se debe ejecutar el bloque de código para el libro y la serie
                if (($request -> gru_pro_codigo == 1 || $request -> gru_pro_codigo == 3 || $request -> gru_pro_codigo == 6)) {
                    if ($request -> idlibro) {
                        $libro = Libro:: findOrFail($request -> idlibro);
                    }else {
                        $libro = new Libro;
                        $libro -> nombrelibro = $request -> pro_nombre;
                        $libro -> nombre_imprimir = $request -> pro_nombre;
                        $libro -> descripcionlibro = $request -> pro_nombre;
                        // Guardar el libro
                        $libro -> save();
                        if ($request -> idlibro) {
                            $librosSerie = DB:: table('libros_series')
                                -> where('idLibro', $request -> idlibro)
                                -> update([
                                    'id_serie' => $request -> id_serie,
                                    'codigo_liquidacion' => $request -> pro_codigo,
                                    'nombre' => $libro -> nombrelibro,
                                ]);
                        }else {
                            $librosSerie = new LibroSerie();
                            $librosSerie -> idLibro = $libro -> idlibro;
                            $librosSerie -> iniciales = $request -> codigo_liquidacion;
                            $librosSerie -> id_serie = $request -> id_serie;
                            $librosSerie -> codigo_liquidacion = $request -> codigo_liquidacion;
                            $librosSerie -> year = $request -> year;
                            $librosSerie -> version = $request -> version2;
                            $librosSerie -> nombre = $libro -> nombrelibro;
                            $librosSerie -> boton = "success";
                            $librosSerie -> save();
                        }
                    }
                }
            }
            DB:: commit();
            return response()->json(['message' => 'Se guardó correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo actualizar/guardar', 'message' => $e->getMessage(),'line' => $e->getLine()]);
            DB:: rollback();
        }
        // Verificar si el producto se guardó correctamente
        if ($producto -> wasRecentlyCreated || $producto -> wasChanged()) {
            return "Se guardó correctamente";
        }else {
            return "No se pudo guardar/actualizar";
        }
    }

    public function GetSeriesL() {
        $query = DB:: SELECT("SELECT * FROM series ORDER BY id_serie ASC");
        return $query;
    }

    public function Desactivar_producto(Request $request) {
        if ($request -> pro_codigo) {
            $producto = _14Producto:: find($request -> pro_codigo);

            if (!$producto) {
                return "El pro_codigo no existe en la base de datos";
            }

            $producto -> pro_estado = $request -> pro_estado;
            $producto -> save();

            return $producto;
        }else {
            return "No está ingresando ningún pro_codigo";
        }
    }

    public function Eliminar_producto(Request $request) {

        LibroSerie:: where('iniciales', $request -> pro_codigo) -> delete ();
        // Buscar el producto por su ID
        $producto = _14Producto:: find($request -> pro_codigo);

        // Verificar si el producto existe
        if (!$producto) {
            // Manejar el caso en el que el producto no existe
            return response() -> json(['message' => 'Producto no encontrado'], 404);
        }

        // Eliminar el producto
        $producto -> delete ();

        // Eliminar registros relacionados (si es necesario)
        Libro:: where('nombrelibro', $request -> pro_nombre) -> delete ();
        // Retornar una respuesta exitosa
        return response() -> json(['message' => 'Producto eliminado correctamente'], 200);
    }
    public function getProductosStockMinimo() {
        $query = DB:: SELECT("SELECT * FROM 1_4_cal_producto p WHERE p.gru_pro_codigo = 1 OR p.gru_pro_codigo = 3 OR p.gru_pro_codigo = 6");
        return $query;
    }
    public function getConfiguracionStock(Request $request) {
        if($request->empresaStock == 'prolipaF'){
            $query = DB:: SELECT("SELECT * FROM configuracion_general c WHERE c.id = 2");
        }else if($request->empresaStock == 'prolipaN'){
            $query = DB:: SELECT("SELECT * FROM configuracion_general c WHERE c.id = 4");
        }
        if($request->empresaStock == 'calmedF'){
            $query = DB:: SELECT("SELECT * FROM configuracion_general c WHERE c.id = 3");
        }else if($request->empresaStock == 'calmedN'){
            $query = DB:: SELECT("SELECT * FROM configuracion_general c WHERE c.id = 5");
        }
        if($request->empresaStock == 'general'){
            $query = DB:: SELECT("SELECT * FROM configuracion_general c WHERE c.id = 1");
        }
        return $query;
    }
    public function getProductosStockMinimoNotas() {
        $query = DB:: SELECT("SELECT * FROM configuracion_general c WHERE c.nombre LIKE '%notas%'");
        return $query;
    }
}
