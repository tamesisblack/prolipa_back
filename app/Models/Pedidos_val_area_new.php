<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedidos_val_area_new extends Model
{
    use HasFactory; 

    protected $table  ="pedidos_val_area_new";

    protected $primaryKey = 'pvn_id';
    // public $timestamps = false;

    protected $fillable = [
        'id_pedido',
        'idlibro',
        'pvn_cantidad', 
        'pvn_tipo',
        'updated_at',
    ];
}
