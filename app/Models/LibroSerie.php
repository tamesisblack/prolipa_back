<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibroSerie extends Model
{
    protected $table = "libros_series";
    protected $primaryKey = 'id_libro_serie';
    protected $fillable = [
        'idLibro',
        'id_serie',
        'iniciales',
        'codigo_liquidacion',
        'nombre',
        'year',
        'version',
        'boton',
       
    ];
}
