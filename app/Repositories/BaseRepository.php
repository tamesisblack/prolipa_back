<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

class BaseRepository
{
    protected $model;
    private $relations;

    public function __construct(Model $model, array $relations = [])
    {
        $this->model = $model;
        $this->relations = $relations;
    }

    public function all()
    {
        $query = $this->model;

        if(!empty($this->relations)) {
            $query = $query->with($this->relations);
        }

        return $query->get();
    }
    public function allDesc()
    {
        $query = $this->model;

        if(!empty($this->relations)) {
            $query = $query->with($this->relations);
        }

        return $query->OrderBy('id','DESC')->get();
    }
    public function getAllXField($cantidad,$field1,$parametro1,$orden){
        $query = $this->model;
        if($cantidad == 1) { return $query->Where($field1,'=',$parametro1)->OrderBy('id',$orden)->get(); }
    }
    public function get(int $id)
    {
        return $this->model->find($id);
    }

    public function save(Model $model)
    {
        $model->save();

        return $model;
    }

    public function delete(Model $model)
    {
        $model->delete();

        return $model;
    }
}
