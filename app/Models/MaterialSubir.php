<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialSubir extends Model
{
    use HasFactory;
    protected $table = 'material_subir';
    protected $primaryKey = 'id';
    protected $fillable = [
        'libro_id', 'nombre_material','descripcion','archivo','url','estado'
    ];
}
