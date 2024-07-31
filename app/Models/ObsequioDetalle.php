<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObsequioDetalle extends Model
{
    use HasFactory;
    protected $table = "obsequios_detalle";
    protected $primaryKey = "id";
}
