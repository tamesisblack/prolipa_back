<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificacionGeneral extends Model
{
    use HasFactory;
    protected $table = "notificaciones_general";
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'user_created',
        'user_finaliza',
        'id_periodo',
        'id_padre',
        'estado',
        'fecha_finaliza',
        'created_at'
    ];
}
