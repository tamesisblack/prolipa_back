<?php
namespace App\Repositories;
use App\Models\NeetTema;
class  NeetRepository extends BaseRepository
{
    public function __construct(NeetTema $NeetTema)
    {
        parent::__construct($NeetTema);
    }
}
?>