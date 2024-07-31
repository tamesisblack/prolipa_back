<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materialtema extends Model
{
    use HasFactory;
    protected $table = 'material_temas';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_material', 'id_tema','id_unidad'
    ];
}
