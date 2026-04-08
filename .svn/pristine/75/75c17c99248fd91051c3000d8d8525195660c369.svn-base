<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseModel extends Model
{
    use HasFactory, HasUlids, SoftDeletes;


    public function scopeSearch(Builder $query, array $searchTerms)
    {
        foreach ($searchTerms as $field => $term) {
            if (isset($term)) {

                if ($field == 'date_start') {
                    $query->whereDate('created_at', '>=', $term);
                    continue;
                }

                if ($field == 'date_end') {
                    $query->whereDate('created_at', '<=', $term);
                    continue;
                }

                $operator = $this->searchAction[$field] ?? 'like';

                if ($operator === 'in') {
                    $query->whereIn($field, $term);
                } else if ($operator === '=') {
                    $query->where($field, '=', $term);
                } else {
                    $value = $operator === 'like' ? "%{$term}%" : $term;
                    $query->where($field, $operator, $value);
                }
            }
        }
        return $query;
    }

    public function scopeSearchByTagNames($query, array $tagNames)
    {
        if (!empty($tagNames)) {
            return $query->whereHas('tags', function ($query) use ($tagNames) {
                $query->where(function ($query) use ($tagNames) {
                    foreach ($tagNames as $tagName) {
                        $query->orWhere('name', '=', $tagName );
                    }
                });
            });
        }

        return $query;
    }
}
