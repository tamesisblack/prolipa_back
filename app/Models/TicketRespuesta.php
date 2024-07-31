<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketRespuesta extends Model
{
    use HasFactory;
    protected $table = "ticket_respuesta";
    protected $primaryKey = 'ticket_res_id';
	public $timestamps = false;
    protected $fillable = [
        
        'ticket_id',
        'fecha',
        'descripcion',
        'group_id',
        'usuario_id',
        'soporte_id',
    ];
}
