<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialSubirFiles extends Model
{
    use HasFactory;
    protected $table = 'material_subir_files';
    protected $primaryKey = 'id';
    protected $fillable = [
        'material_subir_id', 'archivo','url'
    ];
}
