<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class f_movimientos_producto extends Model
{
    use HasFactory;
    protected $table = "f_movimientos_producto";
    protected $primaryKey = 'fmp_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'fmp_id',
        'id_periodo',
        'fmp_estado',
        'user_created',
        'created_at',
        'updated_at',        
    ];
}
