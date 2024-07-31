<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeetUsuarioDocumento extends Model
{
    use HasFactory;
    protected $table ="neet_usuario_documento";
    protected $primaryKey = "id";
}
