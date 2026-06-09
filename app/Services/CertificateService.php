<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateType;

class CertificateService
{
    /**
     * Create a new class instance.
     */
    public function __construct(private Certificate $model)
    {
        //
    }
   public function query(){
        return Certificate::query()->with(['certificateType','user']);
    }
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function find($value , $column = 'id')
    {
        return $this->model->where($column, $value)->first();
    }

    public function delete(int $id)
    {
        return $this->model->destroy($id);
    }
}
