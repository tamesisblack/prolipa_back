<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbonoRetencionPorcentaje extends Model
{
    use HasFactory;
    protected $table  = "abono_retencion_porcentaje";

    protected $primaryKey = 'arp_id';

    protected $fillable = [
        'arp_nombre',
        'arp_nombre',
        'arp_valor',
        'arp_estado',
        'created_at',
        'updated_at',
        'user_created',
    ];
}
