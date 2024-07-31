<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HLogin extends Model
{
    use HasFactory;
    protected $table = "hsp_usuarios";
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombres',
        'apellidos',
        'cedula',
        'email',
        'password',
        'id_group',
        'estado',
    ];

}
