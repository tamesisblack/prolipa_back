<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class i_zona extends Model
{
    use HasFactory;
    protected $table  ="i_zona";

    protected $primaryKey = 'idzona';

    protected $fillable = [
        'idzona',
        'zn_nombre',
        'zn_estado',
        'created_at',
        'updated_at',
        'user_created',
    ];
}
