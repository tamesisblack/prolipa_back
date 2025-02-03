<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CombosCodigos extends Model
{
    use HasFactory;
    protected $table  ="codigos_combos";

    protected $primaryKey = 'codigo';
    public $incrementing    = false;

    protected $fillable = [
        'estado',
        'user_created',
        'created_at',
        'updated_at',
    ];
}
