<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materialcargar extends Model
{
    use HasFactory;
    protected $table = "material_cargar";
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_libro',
        'estado',
    ];
}
