<?php

namespace App\Filters\Api\V1\Admin;

use Illuminate\Database\Eloquent\Builder;

class CertificateTypeFilter
{


    private function __construct(private Builder $query) {}

    public static function apply(Builder $query, $req): Builder
    {
        return (new self($query))
            ->withSearch($req->searchTerm())
            ->query;
    }


    public function withSearch($term) {
        if(!$term) return $this;

         $this->query->where('name', 'like', '%'.$term.'%');
      
         return $this;   
    }
}
