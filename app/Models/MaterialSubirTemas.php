<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialSubirTemas extends Model
{
    use HasFactory;
    protected $table = 'material_subir_temas';
    protected $primaryKey = 'id';
    protected $fillable = [
        'material_subir_id', 'unidad','temas'
    ];
}
