<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $table = "tickets";
    protected $primaryKey = 'ticket_id';
	public $timestamps = false;
    protected $fillable = [
        
        'cedula',
        'codigo',
        'institucion_id',
        'usuario_id',
        'group_id',
        'descripcion',
        'nombreInstitucion',
        'telefono',
        'fecha_ticket',
        'fecha_ticket_final',
        'estado',
        'cantidad_tickets_usuario',
        'cantidad_tickets_admin',
    ];
}
