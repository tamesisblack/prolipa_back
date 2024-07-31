<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class _14GrupoProducto extends Model
{
    use HasFactory;
    protected $table  ="1_4_grupo_productos";

    protected $primaryKey = 'gru_pro_codigo';
    public $timestamps = false;

    protected $fillable = [
        'gru_pro_codigo',
        'gru_pro_nombre',
        'gru_pro_estado',
        'updated_at',
        'user_created',
    ];
}