<?php

namespace App\Models\Models\Distribuidor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distribuidor extends Model
{
    use HasFactory;
    protected $table        = "distribuidor";
    protected $primaryKey   = "distribuidor_id";
}
