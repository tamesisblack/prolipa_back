<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormatoPedidoNew extends Model
{
    use HasFactory;

    protected $table  ="pedidos_formato_new";

    protected $primaryKey = 'pfn_id';
    // public $timestamps = false;

    protected $fillable = [
        'idlibro',
        'idperiodoescolar',
        'pfn_pvp',
        'pfn_orden',
        'pfn_estado',
        'updated_at',
        'user_editor',
    ];
}
