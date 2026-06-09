<?php

namespace App\Services;

use App\Models\CertificateType;

class CertificateTypeService
{
    /**
     * Create a new class instance.
     */
    public function __construct(private CertificateType $model)
    {
        //
    }
    
    public function query(){
        return CertificateType::query();
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
