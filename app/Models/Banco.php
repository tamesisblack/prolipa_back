<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    use HasFactory;
    protected $table = '1_1_bancos';
    protected $primaryKey = 'ban_codigo';
    
    protected $fillable = [
        'ban_nombre',
        'ban_direccion',
        'bono_totalFacturas',
        'ban_telefono',
        'ban_estado',
        'created_at',
        'updated_at',
        'user_created',
    ];
}
