<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProveedorOrdentrabajo extends Model
{
    use HasFactory;
     protected $table = '1_4_proveedor';
	

    protected $primaryKey = 'prov_codigo';
    public $timestamps = false;

    protected $fillable = [
        'prov_codigo',
        'ciu_codigo',
        'prov_nombre', 
        'prov_descripcion',
        'prov_direccion',
        'prov_ruc',       
        'prov_telefono', 
    ];
}
