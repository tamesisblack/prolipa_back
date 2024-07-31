<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalleConfiguracion extends Model
{
    use HasFactory;
    protected $table = "salle_configuracion";
    protected $primaryKey = "id_configuracion";
}
