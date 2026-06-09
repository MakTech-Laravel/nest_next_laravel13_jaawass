<?php

namespace App\Filters\Api\V1\Manufacturer;

use Illuminate\Database\Eloquent\Builder;

class CertificateFilter
{


    private function __construct(private Builder $query) {}

    public static function apply(Builder $query, $req): Builder
    {
        return (new self($query))
            ->withSearch($req->searchTerm())
            ->where('user_id', $req->user()->id)
            ->withStatus($req->status())
            ->withCertificateType($req->certificateTypeId())
            ->query;
    }


    public function where(string $column, int|string $value) {
        $this->query->where($column, $value);
        return $this;
    }

    public function withSearch($term) {
        if(!$term) return $this;

         $this->query->where('certificate_number', 'like', '%'.$term.'%')
              ->orWhere('issuing_body', 'like', '%'.$term.'%');
      
         return $this;   
    }

    public function withStatus($status) {
        if(!$status) return $this;

        $this->query->where('status', $status);
        return $this;   
    }

    public function withCertificateType($certificateTypeId) {
        if(!$certificateTypeId) return $this;

        $this->query->where('certificate_type_id', $certificateTypeId);
        return $this;   
    }
}
