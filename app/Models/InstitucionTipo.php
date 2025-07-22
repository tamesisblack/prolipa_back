<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitucionTipo extends Model
{
    use HasFactory;
    protected $table = 'institucion_tipo_institucion';
    protected $primaryKey = 'id';
}
