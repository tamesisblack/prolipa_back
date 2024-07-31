<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class f_tipo_documento extends Model
{
    use HasFactory;
    protected $table  ="f_tipo_documento";

    protected $primaryKey = 'tdo_id';
    // public $timestamps = false;

    protected $fillable = [
        'tdo_id',
        'tdo_nombre',
        'tdo_secuencial_calmed',
        'tdo_letra',
        'tdo_estado',
        'created_at',
        'updated_at',
        'user_created',
    ];
}
