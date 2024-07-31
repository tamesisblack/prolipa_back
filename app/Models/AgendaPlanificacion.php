<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class AgendaPlanificacion extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = "planificacion_agenda";
    protected $primaryKey = 'id';
}
