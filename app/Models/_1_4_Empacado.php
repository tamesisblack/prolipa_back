<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class _1_4_Empacado extends Model
{
    use HasFactory;
    protected $table = "rempacado";
    protected $primaryKey = "empa_codigo";
    public $incrementing = false;
    protected static function boot()
    {
        parent::boot();

        // Definir el manejador para el evento creating
        static::creating(function ($model) {
            // Extraer el último número de la cadena
            $lastNumber         = (int) substr($model->empa_codigo, 1);
            return $lastNumber;
            // Incrementar el número
            $newNumber          = $lastNumber + 1;

            // Generar el nuevo ID con el mismo formato
            $model->empa_codigo = 'E' . str_pad($newNumber, 8, '0', STR_PAD_LEFT);
        });
    }
    }
