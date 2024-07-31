<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeetUploadFiles extends Model
{
    use HasFactory;
    protected $table = "neet_upload_files";
    protected $primaryKey = "id";
    protected $fillable = [
        "neet_upload_id",
        "archivo",
        "url",
        "ext"
    ];
}
