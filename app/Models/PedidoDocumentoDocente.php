<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoDocumentoDocente extends Model
{
    use HasFactory;
    protected $table        = "pedidos_documentos_docentes";
    protected $primaryKey   = "id";
}
