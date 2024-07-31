<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materialarchivo extends Model
{
    use HasFactory;
    protected $table = "material_archivos";
    protected $primaryKey = 'id_archivo';
    protected $fillable = [
        'id_material',
        'nombre_archivo',
        'archivo',
        'url',
        'id_asignatura'
    
    ];
}
