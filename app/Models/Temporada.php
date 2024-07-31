<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable;

class Temporada extends Model implements Auditable
//class Temporada extends Model
{
    //para auditar

     use \OwenIt\Auditing\Auditable;
    //para el nombre de la table
    protected $table = "temporadas";
    protected $primaryKey = 'id_temporada';
    protected $fillable = [
        'contrato',
        'year',
        'ciudad',
        'temporada',
        'temporal_nombre_docente',
        'temporal_cedula_docente',
        'temporal_institucion',
        'nombre_asesor',
        'id_profesor',
        'idInstitucion',
        'estado' 
        
   
       
    ];

   // ya y si mejor le probamos en el crear store  es el desactivar desaparece cuando esta activo y llama al meotod activar
}
