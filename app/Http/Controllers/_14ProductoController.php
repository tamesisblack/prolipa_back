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
        DB::beginTransaction();
        
        try {
            // Crear o actualizar el producto
            $producto = _14Producto::updateOrCreate(
                ['pro_codigo' => $request->pro_codigo],
                [
                    'gru_pro_codigo' => $request->gru_pro_codigo,
                    'pro_nombre' => $request->pro_nombre,
                    'pro_descripcion' => $request->pro_descripcion,
                    'pro_iva' => $request->pro_iva ? 1 : 0, // Convertir el valor booleano a 1 o 0
                    'pro_valor' => $request->pro_valor,
                    'pro_descuento' => $request->pro_descuento,
                    'pro_deposito' => $request->pro_deposito,
                    'pro_reservar' => $request->pro_reservar,
                    'pro_stock' => $request->pro_stock,
                    'pro_costo' => $request->pro_costo,
                    'pro_peso' => $request->pro_peso,
                    'pro_depositoCalmed' => $request->pro_depositoCalmed,
                    'pro_stockCalmed' => $request->pro_stockCalmed,
                    'user_created' => $request->user_created,
                    'updated_at' => now()
                ]
            );
    
            if (!$producto) {
                throw new \Exception('Error al crear o actualizar el producto');
            }
    
            // Crear o actualizar el libro
            $libro = Libro::updateOrCreate(
                ['nombrelibro' => $request->nombrelibro],
                [
                    'nombre_imprimir' => $request->nombrelibro,
                    'descripcionlibro' => $request->descripcionlibro,
                    'serie' => $request->serie,
                    'titulo' => $request->titulo,
                    'portada' => $request->portada,
                    'weblibro' => $request->weblibro,
                    'exelibro' => $request->exelibro,
                    'pdfsinguia' => $request->pdfsinguia,
                    'pdfconguia' => $request->pdfconguia,
                    'guiadidactica' => $request->guiadidactica,
                    'Estado_idEstado' => $request->Estado_idEstado ?? 1,
                    'asignatura_idasignatura' => $request->asignatura_idasignatura,
                    'ziplibro' => $request->ziplibro,
                    'libroFechaModificacion' => now(),
                    'grupo' => $request->grupo ?? '0',
                    'puerto' => $request->puerto ?? 0,
                    's_weblibro' => $request->s_weblibro,
                    's_pdfsinguia' => $request->s_pdfsinguia,
                    's_pdfconguia' => $request->s_pdfconguia,
                    's_guiadidactica' => $request->s_guiadidactica,
                    's_portada' => $request->s_portada ?? 'portada.png',
                    'c_weblibro' => $request->c_weblibro,
                    'c_pdfsinguia' => $request->c_pdfsinguia,
                    'c_pdfconguia' => $request->c_pdfconguia,
                    'c_guiadidactica' => $request->c_guiadidactica,
                    'c_portada' => $request->c_portada ?? 'portada.png',
                    'demo' => $request->demo
                ]
            );
    
            if (!$libro) {
                throw new \Exception('Error al crear o actualizar el libro');
            }
    
            // Crear o actualizar la serie del libro
            $libroSerie = LibroSerie::updateOrCreate(
                [
                    'codigo_liquidacion' => $request->codigo_liquidacion,
                    'idLibro' => $libro->idlibro
                ],
                [
                    'id_serie' => $request->id_serie,
                    'nombre' => $request->nombrelibro,
                    'year' => $request->year,
                    'version' => $request->version2,
                    'boton' => $request->boton ?? 'success',
                    'estado' => $request->estado ?? 1,
                    'cantidad' => $request->cantidad ?? 0,
                    'iniciales' => $request->codigo_liquidacion,
                ]
            );
    
            if (!$libroSerie) {
                throw new \Exception('Error al crear o actualizar la serie del libro');
            }
    
            // Confirmar la transacción
            DB::commit();
            return response()->json(['message' => 'Se guardó correctamente']);
        } catch (\Exception $e) {
            // Deshacer la transacción en caso de error
            DB::rollBack();
            // Manejar el error
            return response()->json([
                'error' => 'No se pudo actualizar/guardar',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    
    
    public function Registrar_modificar_producto(Request $request) {
        try {
            DB::beginTransaction();
        
            // Buscar el producto por su código antiguo
            $producto = _14Producto::where('pro_codigo', $request->pro_codigo_antiguo)->first();
        
            if (!$producto) {
                // Crear un nuevo producto si no existe
                $producto = _14Producto::create([
                    'pro_codigo' => $request->pro_codigo,
                    'gru_pro_codigo' => $request->gru_pro_codigo,
                    'pro_nombre' => $request->pro_nombre,
                    'pro_descripcion' => $request->pro_descripcion,
                    'pro_iva' => $request->pro_iva,
                    'pro_valor' => $request->pro_valor,
                    'pro_descuento' => $request->pro_descuento,
                    'pro_deposito' => $request->pro_deposito,
                    'pro_reservar' => $request->pro_reservar,
                    'pro_stock' => $request->pro_stock,
                    'pro_costo' => $request->pro_costo,
                    'pro_peso' => $request->pro_peso,
                    'user_created' => $request->user_created,
                    'pro_depositoCalmed' => $request->pro_depositoCalmed,
                    'pro_stockCalmed' => $request->pro_stockCalmed,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                // Actualizar el producto existente
                $producto->update([
                    'pro_codigo' => $request->pro_codigo,
                    'gru_pro_codigo' => $request->gru_pro_codigo,
                    'pro_nombre' => $request->pro_nombre,
                    'pro_descripcion' => $request->pro_descripcion,
                    'pro_iva' => $request->pro_iva,
                    'pro_valor' => $request->pro_valor,
                    'pro_descuento' => $request->pro_descuento,
                    'pro_deposito' => $request->pro_deposito,
                    'pro_reservar' => $request->pro_reservar,
                    'pro_stock' => $request->pro_stock,
                    'pro_costo' => $request->pro_costo,
                    'pro_peso' => $request->pro_peso,
                    'pro_depositoCalmed' => $request->pro_depositoCalmed,
                    'pro_stockCalmed' => $request->pro_stockCalmed,
                    'updated_at' => now()
                ]);
            }
        
            if (in_array($request->gru_pro_codigo, [1, 3, 6])) {
                $librosSerie = LibroSerie::where('codigo_liquidacion', $producto->pro_codigo)->first();
                
                if ($librosSerie) {
                    // Libro y serie del libro ya existen
                    $libro = Libro::find($librosSerie->idLibro);
    
                    if ($libro) {
                        // Actualizar el libro existente
                        DB::table('libro')->where('idLibro', $libro->idLibro)->update([
                            'nombrelibro' => $request->pro_nombre,
                            'nombre_imprimir' => $request->pro_nombre,
                            'descripcionlibro' => $request->pro_nombre,
                        ]);
    
                        // Actualizar la serie del libro existente
                        DB::table('libros_series')->where('idLibro', $librosSerie->idLibro)->update([
                            'id_serie' => $request->id_serie,
                            'year' => $request->year,
                            'version' => $request->version2,
                            'nombre' => $request->pro_nombre,
                            'iniciales' => $request->codigo_liquidacion,
                        ]);
                    } else {
                        // El libro asociado a la serie no existe, manejar el error
                        throw new \Exception('Libro asociado a la serie no encontrado');
                    }
                } else {
                    // Crear un nuevo libro
                    $libroId = DB::table('libro')->insertGetId([
                        'nombrelibro' => $request->pro_nombre,
                        'nombre_imprimir' => $request->pro_nombre,
                        'descripcionlibro' => $request->pro_nombre,
                    ]);
    
                    if (!$libroId) {
                        throw new \Exception('No se pudo crear el libro');
                    }
    
                    // Crear la serie del libro
                    $serieCreated = DB::table('libros_series')->insert([
                        'idLibro' => $libroId,  // Usa el ID del nuevo libro
                        'codigo_liquidacion' => $request->pro_codigo,
                        'id_serie' => $request->id_serie,
                        'year' => $request->year,
                        'version' => $request->version2,
                        'nombre' => $request->pro_nombre,
                        'iniciales' => $request->codigo_liquidacion,
                        'boton' => 'success',
                    ]);
    
                    if (!$serieCreated) {
                        throw new \Exception('No se pudo crear la serie del libro');
                    }
                }
            }
    
            // Confirmar la transacción
            DB::commit();
            return response()->json(['message' => 'Se guardó correctamente']);
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollback();
            return response()->json([
                'error' => 'No se pudo actualizar/guardar',
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }
    }
    
    
    
    
    
    
    
    
    // public function Registrar_modificar_producto(Request $request) {
    //     try {
    //         DB::beginTransaction();
    
    //         // Buscar el producto por su código antiguo
    //         $producto = _14Producto::where('pro_codigo', $request->pro_codigo_antiguo)->first();
    
    //         if ($producto) {
    //             // Actualizar el producto existente
    //             $producto->pro_codigo = $request->pro_codigo; // Asegúrate de que pro_codigo no sea nulo
    //             $producto->gru_pro_codigo = $request->gru_pro_codigo;
    //             $producto->pro_nombre = $request->pro_nombre;
    //             $producto->pro_descripcion = $request->pro_descripcion;
    //             $producto->pro_iva = $request->pro_iva;
    //             $producto->pro_valor = $request->pro_valor;
    //             $producto->pro_descuento = $request->pro_descuento;
    //             $producto->pro_deposito = $request->pro_deposito;
    //             $producto->pro_reservar = $request->pro_reservar;
    //             $producto->pro_stock = $request->pro_stock;
    //             $producto->pro_costo = $request->pro_costo;
    //             $producto->pro_peso = $request->pro_peso;
    //             $producto->pro_depositoCalmed = $request->pro_depositoCalmed;
    //             $producto->pro_stockCalmed = $request->pro_stockCalmed;
    //             $producto->updated_at = now();
    //             $producto->save();

    //             if ($request->gru_pro_codigo == 1 || $request->gru_pro_codigo == 3 || $request->gru_pro_codigo == 6) {
    //                 $librosSerie = LibroSerie::where('codigo_liquidacion', $producto->pro_codigo)
    //                 ->first();

    //                 $libro = $librosSerie ? Libro::find($librosSerie->idlibro) : null;
        
    //                 if ($libro) {
    //                     // Actualizar el libro existente
    //                     $libro->update([
    //                         'nombrelibro' => $request->pro_nombre,
    //                         'nombre_imprimir' => $request->pro_nombre,
    //                         'descripcionlibro' => $request->pro_nombre,
    //                         // Puedes agregar más campos si es necesario
    //                     ]);
    //                     $librosSerie = LibroSerie::where('idLibro', $libro->idLibro)
    //                     ->first();
    //                     if ($librosSerie) {
    //                         // Actualizar la serie del libro existente
    //                         $librosSerie->update([
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     } else {
    //                         return $libro + 'con libro existente';
    //                     // Crear una nueva serie del libro si no existe
    //                         LibroSerie::create([
    //                             'idLibro' => $libro->idLibro,
    //                             'codigo_liquidacion' => $request->pro_codigo_antiguo,
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     }
    //                 } else {
    //                     // Crear un nuevo libro si no existe
    //                     $libro = Libro::create([
    //                         'idLibro' => $request->idlibro,
    //                         'nombrelibro' => $request->pro_nombre,
    //                         'nombre_imprimir' => $request->pro_nombre,
    //                         'descripcionlibro' => $request->pro_nombre,
    //                         // Puedes agregar más campos si es necesario
    //                     ]);
    //                     return $libro;
    //                     $librosSerie = LibroSerie::where('idLibro', $libro->idLibro)
    //                     ->first();
    //                     if ($librosSerie) {
    //                         $librosSerie->update([
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     } else {
    //                         LibroSerie::create([
    //                             'idLibro' => $libro->idLibro,
    //                             'codigo_liquidacion' => $request->pro_codigo_antiguo,
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     }
    //                 }
    //             }
                

    //         } else {
    //             // Crear un nuevo producto si no existe
    //             $producto = _14Producto::create([
    //                 'pro_codigo' => $request->pro_codigo,
    //                 'gru_pro_codigo' => $request->gru_pro_codigo,
    //                 'pro_nombre' => $request->pro_nombre,
    //                 'pro_descripcion' => $request->pro_descripcion,
    //                 'pro_iva' => $request->pro_iva,
    //                 'pro_valor' => $request->pro_valor,
    //                 'pro_descuento' => $request->pro_descuento,
    //                 'pro_deposito' => $request->pro_deposito,
    //                 'pro_reservar' => $request->pro_reservar,
    //                 'pro_stock' => $request->pro_stock,
    //                 'pro_costo' => $request->pro_costo,
    //                 'pro_peso' => $request->pro_peso,
    //                 'user_created' => $request->user_created,
    //                 'pro_depositoCalmed' => $request->pro_depositoCalmed,
    //                 'pro_stockCalmed' => $request->pro_stockCalmed,
    //                 'created_at' => now(),
    //                 'updated_at' => now()
    //             ]);
    //             if ($request->gru_pro_codigo == 1 || $request->gru_pro_codigo == 3 || $request->gru_pro_codigo == 6) {
    //                 $librosSerie = LibroSerie::where('codigo_liquidacion', $producto->pro_codigo)
    //                 ->first();

    //                 $libro = $librosSerie ? Libro::find($librosSerie->idlibro) : null;
        
    //                 if ($libro) {
    //                     // Actualizar el libro existente
    //                     $libro->update([
    //                         'nombrelibro' => $request->pro_nombre,
    //                         'nombre_imprimir' => $request->pro_nombre,
    //                         'descripcionlibro' => $request->pro_nombre,
    //                     ]);

    //                     $librosSerie = LibroSerie::where('idLibro', $libro->idLibro)
    //                     ->first();
                        
    //                     if ($librosSerie) {
    //                         // Actualizar la serie del libro existente
    //                         $librosSerie->update([
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     } else {
    //                         // Crear una nueva serie del libro si no existe
    //                         LibroSerie::create([
    //                             'idLibro' => $libro->idLibro,
    //                             'codigo_liquidacion' => $request->pro_codigo_antiguo,
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     }
    //                 } else {
    //                     // Crear un nuevo libro si no existe
    //                     $libro = Libro::create([
    //                         'idLibro' => $request->idlibro,
    //                         'nombrelibro' => $request->pro_nombre,
    //                         'nombre_imprimir' => $request->pro_nombre,
    //                         'descripcionlibro' => $request->pro_nombre,
    //                         // Puedes agregar más campos si es necesario
    //                     ]);
                        
    //                     $librosSerie = LibroSerie::where('idLibro', $libro->idLibro)
    //                     ->first();
    //                     if ($librosSerie) {
    //                         // Actualizar la serie del libro existente
    //                         $librosSerie->update([
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     } else {
    //                         // Crear una nueva serie del libro si no existe
    //                         LibroSerie::create([
    //                             'idLibro' => $libro->idLibro,
    //                             'codigo_liquidacion' => $request->pro_codigo_antiguo,
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     }
    //                 }
    //             }
    //         } 
    
    //         // Confirmar la transacción
    //         DB::commit();
    //         return response()->json(['message' => 'Se guardó correctamente']);
    //     } catch (\Exception $e) {
    //         // Revertir la transacción en caso de error
    //         DB::rollback();
    //         return response()->json([
    //             'error' => 'No se pudo actualizar/guardar',
    //             'message' => $e->getMessage(),
    //             'line' => $e->getLine()
    //         ]);
    //     }
    // }

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
                $producto->pro_depositoCalmed = $request->pro_depositoCalmed;
                $producto->pro_stockCalmed = $request->pro_stockCalmed; 
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
                        $producto->pro_depositoCalmed = $request->pro_depositoCalmed;
                        $producto->pro_stockCalmed = $request->pro_stockCalmed;
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
                $producto->pro_depositoCalmed = $request->pro_depositoCalmed;
                $producto->pro_stockCalmed = $request->pro_stockCalmed;
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
    public function getProductosSuma() {
        $query = DB:: SELECT("SELECT 
            SUM(pro_reservar) AS total_reservar,
            SUM(pro_stock) AS total_stock,
            SUM(pro_deposito) AS total_deposito,
            SUM(pro_stockCalmed) AS total_stockCalmed,
            SUM(pro_depositoCalmed) AS total_depositoCalmed
            FROM 
            1_4_cal_producto;");
        return $query;
    }
}
