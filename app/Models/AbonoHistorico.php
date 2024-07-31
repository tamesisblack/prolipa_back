<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbonoHistorico extends Model
{
    use HasFactory;
    protected $table = 'abono_historico';
    protected $primaryKey = 'ab_historico_id';
    
    protected $fillable = [
        'ab_historico_id',
        'abono_id',
        'ab_histotico_tipo',
        'ab_historico_values',
        'user_created',
        'created_at',
        'updated_at',
    ];
}
