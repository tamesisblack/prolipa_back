<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class _14ProductoStockHistorico extends Model
{
    use HasFactory;
    protected $table  = "1_4_cal_producto_stock_historico";

    protected $primaryKey = 'psh_id';

    protected $fillable = [
        'psh_old_values',
        'psh_new_values',
        'user_created',
        'created_at',
        'updated_at',
    ];
}
