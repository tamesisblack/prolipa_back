<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeetUpload extends Model
{
    use HasFactory;
    protected $table = "neet_upload";
    protected $primaryKey = "id";
}
