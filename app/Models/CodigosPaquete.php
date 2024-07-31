<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigosPaquete extends Model
{
    use HasFactory;
    protected $table        = "codigos_paquetes";
    protected $primaryKey   = "codigo";
    public $incrementing    = false;
}
