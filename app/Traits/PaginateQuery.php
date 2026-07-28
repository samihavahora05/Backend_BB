<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait PaginateQuery
{
    /**
     * Paginate query with sorting, searching, and metadata.
     */
    protected function paginateWithMeta(
        $query,
        Request $request,
        array $allowedSortFields = ['created_at'],
        array $searchableFields = [],
        array $filters = []
    ) {
        // 1. Search
        $search = $request->input('search');
        if ($search && !empty($searchableFields)) {
            $query->where(function (Builder $q) use ($search, $searchableFields) {
                foreach ($searchableFields as $index => $field) {
                    if (str_contains($field, '.')) {
                        // Handle relation search: e.g. 'user.name'
                        [$relation, $relField] = explode('.', $field);
                        $q->orWhereHas($relation, function (Builder $relQ) use ($relField, $search) {
                            $relQ->where($relField, 'like', '%' . $search . '%');
                        });
                    } else {
                        if ($index === 0) {
                            $q->where($field, 'like', '%' . $search . '%');
                        } else {
                            $q->orWhere($field, 'like', '%' . $search . '%');
                        }
                    }
                }
            });
        }

        // 2. Filters
        foreach ($filters as $key => $column) {
            if ($request->has($key) && $request->input($key) !== null && $request->input($key) !== '') {
                $query->where($column, $request->input($key));
            }
        }

        // 3. Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // 4. Pagination
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage < 1) {
            $perPage = 10;
        } elseif ($perPage > 100) {
            $perPage = 100;
        }

        $paginated = $query->paginate($perPage);

        return [
            'data' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ];
    }
}
