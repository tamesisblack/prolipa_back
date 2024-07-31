<?php

namespace App\Http\Controllers;

use App\Models\_14ProductoCaracteristica;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class _14ProductoCaracteristicaController extends Controller
{
    public function GetCarateristicaProducto_todo(){
        $query = DB::SELECT("SELECT * FROM 1_4_cal_producto_caracteristica ORDER BY pro_car_codigo DESC");
        return $query;
    }

    public function GetCaracteristicaProducto_pro_cub_int(){
        $query = DB::SELECT("SELECT pc.*, p.pro_nombre, p.pro_codigo, mc.*, mi.*
        FROM 1_4_cal_producto_caracteristica pc
        LEFT JOIN 1_4_cal_producto p ON pc.pro_car_codigo = p.pro_codigo
        INNER JOIN 1_4_cal_material_cubierta mc ON pc.mat_cub_codigo = mc.mat_cub_codigo
        INNER JOIN 1_4_cal_material_interior mi ON pc.mat_in_codigo = mi.mat_in_codigo");
        return $query;
    }

    public function GetCaracteristicaProducto_pro_cub_int_xfiltro(Request $request){
        if ($request->busqueda == 'codigopro') {
            $query = DB::SELECT("SELECT pc.*, p.pro_nombre, p.pro_codigo, mc.*, mi.*
            FROM 1_4_cal_producto_caracteristica pc
            LEFT JOIN 1_4_cal_producto p ON pc.pro_car_codigo = p.pro_codigo
            INNER JOIN 1_4_cal_material_cubierta mc ON pc.mat_cub_codigo = mc.mat_cub_codigo
            INNER JOIN 1_4_cal_material_interior mi ON pc.mat_in_codigo = mi.mat_in_codigo
            WHERE pc.pro_car_codigo LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
        if ($request->busqueda == 'undefined') {
            $query = DB::SELECT("SELECT pc.*, p.pro_nombre, p.pro_codigo, mc.*, mi.*
            FROM 1_4_cal_producto_caracteristica pc
            LEFT JOIN 1_4_cal_producto p ON pc.pro_car_codigo = p.pro_codigo
            INNER JOIN 1_4_cal_material_cubierta mc ON pc.mat_cub_codigo = mc.mat_cub_codigo
            INNER JOIN 1_4_cal_material_interior mi ON pc.mat_in_codigo = mi.mat_in_codigo
            WHERE pc.pro_car_codigo LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
        if ($request->busqueda == 'nombres') {
            $query = DB::SELECT("SELECT pc.*, p.pro_nombre, p.pro_codigo, mc.*, mi.*
            FROM 1_4_cal_producto_caracteristica pc
            LEFT JOIN 1_4_cal_producto p ON pc.pro_car_codigo = p.pro_codigo
            INNER JOIN 1_4_cal_material_cubierta mc ON pc.mat_cub_codigo = mc.mat_cub_codigo
            INNER JOIN 1_4_cal_material_interior mi ON pc.mat_in_codigo = mi.mat_in_codigo
            WHERE p.pro_nombre LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
    }

    public function Registrar_modificar_CaracteristicaProducto(Request $request)
    {
        // Buscar el producto por su pro_codigo o crear uno nuevo
        $productocaracteristica = _14ProductoCaracteristica::firstOrNew(['pro_car_codigo' => $request->pro_car_codigo]);
        // Asignar los demás datos del producto
        $productocaracteristica->pro_tamaño = $request->pro_tamaño;
        $productocaracteristica->pro_int_pagina = $request->pro_int_pagina;
        $productocaracteristica->mat_in_codigo = $request->mat_in_codigo;
        $productocaracteristica->pro_int_tinta = $request->pro_int_tinta;
        $productocaracteristica->mat_cub_codigo = $request->mat_cub_codigo;
        $productocaracteristica->pro_cub_recubrimiento = $request->pro_cub_recubrimiento;
        $productocaracteristica->pro_cub_tintas = $request->pro_cub_tintas;
        $productocaracteristica->pro_acabados = $request->pro_acabados;
        $productocaracteristica->pro_guia = $request->pro_guia;
        // Guardar el productocaracteristica
        if ($productocaracteristica->exists) {
            // Si ya existe, omitir el campo user_created para evitar que se establezca en null
            $productocaracteristica->updated_at = now();
            // Guardar el productocaracteristica sin modificar user_created
            $productocaracteristica->save();
        } else {
            // Si es un nuevo registro, establecer user_created y updated_at
            $productocaracteristica->updated_at = now();
            $productocaracteristica->user_created = $request->user_created;
            $productocaracteristica->save();
        }

        // Verificar si el productocaracteristica se guardó correctamente
        if ($productocaracteristica->wasRecentlyCreated || $productocaracteristica->wasChanged()) {
            return $productocaracteristica;
        } else {
            return "No se pudo guardar/actualizar";
        }
    }

    public function Eliminar_CaracteristicaProducto(Request $request)
    {
        //return $request;
        //$Rol = Rol::destroy($request->id);
        $productocaracteristica = _14ProductoCaracteristica::findOrFail($request->pro_car_codigo);
        $productocaracteristica->delete();
        return $productocaracteristica;
        //Esta función obtendra el id de la tarea que hayamos seleccionado y la borrará de nuestra BD
    }
}