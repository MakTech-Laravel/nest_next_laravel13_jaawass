<?php

namespace App\Filters\Api\V1\Admin;

use Illuminate\Database\Eloquent\Builder;

class CertificateFilter
{


    private function __construct(private Builder $query) {}

    public static function apply(Builder $query, $req): Builder
    {
        return (new self($query))
            ->withSearch($req->searchTerm())
            ->withExpired($req->expired())
            ->query;
    }


    public function withSearch($term) {
        if(!$term) return $this;

         $this->query->where('certificate_number', 'like', '%'.$term.'%')
              ->orWhere('issuing_body', 'like', '%'.$term.'%')
              ->orWhereHas('user', function ($query) use ($term) {
                  $query->where('email', 'like', '%'.$term.'%')
                  ->orWhere('first_name', 'like', '%'.$term.'%')
                  ->orWhere('last_name', 'like', '%'.$term.'%');
              });
      
         return $this;   
    }

    public function withExpired($expired) {
        if($expired === null) return $this;

        if($expired) {
            $this->query->where('expiry_date', '>', now());
        } else {
            $this->query->where('expiry_date', '<', now());
        }
      
         return $this;   
    }
}
