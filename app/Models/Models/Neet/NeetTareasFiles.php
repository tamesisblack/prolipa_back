<?php

namespace App\Models\Models\Neet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeetTareasFiles extends Model
{
    use HasFactory;
    protected $table = "neet_tareas_files";
    protected $primaryKey = "id";
    protected $fillable = [
        "neet_tareas_respuestas_id",
        "nombre",
        "archivo",
        "ext"
    ];
}
