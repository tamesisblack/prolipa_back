<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materialunidad extends Model
{
    use HasFactory;
    protected $table = 'material_unidades';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_material', 'id_unidad'
    ];
}
