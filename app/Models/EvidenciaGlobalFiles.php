<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvidenciaGlobalFiles extends Model
{
    use HasFactory;
    protected $table = "evidencia_global_files";

    protected $primaryKey = 'egf_id';

    protected $fillable = [
        'egft_id',
        'egf_archivo',
        'egf_url',
        'created_at',
        'updated_at',
        'user_created',
    ];
}
