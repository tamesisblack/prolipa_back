<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolinfaFactura extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table      = 'sell';
    //timestamps false
    public $timestamps    = false;
}
