<?php

namespace App\Services;

use App\Models\ArticleCategory;

class ArticleCategoryService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected ArticleCategory $model)
    {
        //
    }

    public function query()
    {
        return ArticleCategory::query();
    }
    
    public function create(array $data)
    {
        return $this->model->create($data);
    }
    
    public function update($id, array $data)
    {
        return $this->model::where('id', $id)->update($data);
    }
    
    public function delete($id)
    {
        return $this->model::where('id', $id)->delete();
    }
    
    public function find($id, $column = 'id')
    {
        return $this->model::where($column, $id)->first();
    }
    
    public function all()
    {
        return $this->model::all();
    }
}
