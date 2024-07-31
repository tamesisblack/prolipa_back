<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class f_contrato_usuario_agrupados extends Model
{
    use HasFactory;
    protected $table = "f_contratos_usuarios_agrupados";
    protected $primaryKey = 'fcu_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'ca_id',
        'idusuario',
        'fcu_estado',
        'user_created',
        'created_at',
        'updated_at',
    ];
}
