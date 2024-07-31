<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class _14ClienteInstitucion extends Model
{
    use HasFactory;
    protected $table  ="1_4_cliente_institucion";

    protected $primaryKey = 'cli_ins_codigo';
    public $timestamps = false;

    protected $fillable = [
        'cli_ci',
        'ins_codigo',
        'ven_d_codigo',
    ];
}
