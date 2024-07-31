<?php

namespace App\Models\Models\Configuracion\Plataforma;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plataforma extends Model
{
    use HasFactory;
    protected $table = "plataformas";
    protected $guarded = ["archivo","url"];
}
