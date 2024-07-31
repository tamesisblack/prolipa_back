<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColegioRecursosAdicionales extends Model
{
    use HasFactory;
    protected $table = "colegio_recursos_adicionales";
    protected $primaryKey = 'radicional_id';
    protected $fillable = [
        'zona_diversion_mi_juego',
        'zona_diversion_juego_prolipa',
        'material_apoyo_digital',
        'material_apoyo_pdf',
        'propuestas_metodologicas',
        'adaptaciones',
        'articulos',
        'documentos_ministeriales', 
        'colegio_permiso_id'
    ];
}
