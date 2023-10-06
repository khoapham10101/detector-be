<?php

namespace App\Repositories;

use App\Models\Core\DataTables\DataTableConfig;
use App\Models\GlobalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class BaseRepository
{
    /**
     * @param Model $model
     * @param array $data
     */
    protected function updateData($model, $data)
    {
        foreach ($data as $field => $value) {
            if (method_exists($model, $field) && is_object($model->$field()) && get_class($model->$field()) === Relations\BelongsTo::class) {
                $model->$field()->associate($value);
            } elseif (method_exists($model, $field) && is_object($model->$field()) &&
                (get_class($model->$field()) === Relations\BelongsToMany::class || get_class($model->$field()) === Relations\MorphToMany::class)) {
                if (empty($model->getKey())) {
                    //You will need to call the sync function yourself, after saving the model, because it needs its primary key
                    continue;
                }
                $model->$field()->sync($value);
            } elseif (method_exists($model, $field) && is_object($model->$field()) &&
                (get_class($model->$field()) === Relations\HasOne::class)) {
                if (empty($model->$field()->getRelated()->getForeignKey())) {
                    //You will need to find and assign the model's foreign key yourself
                    continue;
                }
                $model->{$model->$field()->getRelated()->getForeignKey()} = $value;
            } elseif ($model->hasCast($field, ['datetime', 'immutable_datetime'])) {
                //Convert date/time in user format back into UTC before storing in model
                $model->{$field} = $value === null ? null : Date::parse($value, config('app.date.timezone'))->timezone('UTC');
            } else {
                $snake = Str::snake($field);
                $model->{$snake} = $value;
            }
        }
    }

    protected function searchRelations($query, $model, $relations, $text, $found = false)
    {
        $query->where(function($query) use ($model, $relations, $text, $found) {
            $ids = $model::search($text)->get()->pluck($model->getKeyName());
            if ($ids->isNotEmpty()) {
                $query->whereIn($model->getTable() . '.' . $model->getKeyName(), $ids->all());
                $found = true;
            }

            foreach ($relations as $relation) {
                if ($relation instanceof Relations\BelongsToMany) {
                    $relatedModel = $relation->getRelated();
                    $results = $relatedModel::search($text)->get();
                    if ($results->isNotEmpty()) {
                        $ids = DB::table($relation->getTable())
                            ->whereIn($relation->getRelatedPivotKeyName(), $results->pluck($relation->getRelatedKeyName())->all())
                            ->select($relation->getForeignPivotKeyName())
                            ->get()
                            ->pluck($relation->getForeignPivotKeyName());

                        if ($ids->isNotEmpty()) {
                            $found = true;
                            $query->orWhereIn($model->getTable() . '.' . $relation->getParentKeyName(), $ids->all());
                        }
                    }
                } else if ($relation instanceof Relations\BelongsTo) {
                    $relatedModel = $relation->getRelated();
                    $results = $relatedModel::search($text)->get();
                    if ($results->isNotEmpty()) {
                        $found = true;
                        $query->orWhereIn($model->getTable() . '.' . $relation->getForeignKeyName(), $results->pluck($relation->getOwnerKeyName())->all());
                    }
                } else if ($relation instanceof Relations\HasMany) {
                    $relatedModel = $relation->getRelated();
                    $results = $relatedModel::search($text)->get();
                    if ($results->isNotEmpty()) {
                        $found = true;
                        $query->orWhereIn($model->getTable() . '.' . $relation->getLocalKeyName(), $results->pluck($relation->getForeignKeyName())->all());
                    }
                }
            }

            if (!$found) {
                $query->whereRaw('1=0');
            }
        });

        return $query;
    }

    public function dataTableFilter($query, $title, $value, $model)
    {
        if ($title === 'search-bar') {
            //Handled using repository search
        } else if ($title === 'status') {
            $query->where($model->getTable() . '.' . 'status_id', GlobalStatus::where('name', $value)->firstOrFail()->id);
        } else if ($title === 'service-area-dropdown') {
            $query->where($model->getTable() . '.' . 'service_area_id', $value);
        } else if ($title === 'asset-type-dropdown') {
            $query->where($model->getTable() . '.' . 'asset_type_id', $value);
        } else if ($title === 'licence-category-dropdown') {
            $query->where($model->getTable() . '.' . 'licence_category_id', $value);
        } else if ($title === 'class-code-dropdown') {
            $query->where($model->getTable() . '.' . 'class_code_id', $value);
        } else if ($title === 'customer-dropdown') {
            $query->where($model->getTable() . '.' . 'customer_id', $value);
        }
    }

    public function dataTableSort($query, $sortColumn, $sortDirection, $model)
    {
        if (empty($sortColumn)) {
            return;
        }

        if ($sortColumn === 'status.name') {
            //After a discussion with Wade 26/7/22, it is fine for now to sort by status id
            $sortColumn = $model->getTable() . '.' . 'status_id';
        } else if (strpos($sortColumn, '.') !== false) {
            $sortParts = explode('.', $sortColumn);
            $lastPart = array_pop($sortParts);
            $remaining = implode('.', $sortParts);
            $query
                ->leftJoinRelation(Str::camel($remaining) . ' as child')
                ->select($model->getTable() . '.*');
            $sortColumn = 'child.' . $lastPart;
        } else {
            $sortColumn = $model->getTable() . '.' . $sortColumn;
        }

        if (substr($sortColumn, -10, 10) === '_formatted') {
            $sortColumn = substr($sortColumn, 0, strlen($sortColumn) - 10);
        }

        if ($sortDirection) {
            $query = $query->orderByDesc($sortColumn);
        } else {
            $query = $query->orderBy($sortColumn);
        }
    }

    public function cleanSortColumn($dataTableConfig, $sortColumn)
    {
        //Sort column passed by frontend may have been modified, but search for actual database value in headers
        //May run into issues in the future eg. when "status.name" and "status_name" are different columns
        $sortColumn = str_replace('.', '_', $sortColumn);
        $dtHeader = $dataTableConfig->dtHeaders->toBase()->first(fn($dtHeader) => $sortColumn === str_replace('.', '_', $dtHeader->value));
        return $dtHeader->value ?? $sortColumn;
    }

    public function dataTableRows($paginator, $dataTableConfig, $filters)
    {
        return $this->flattenDataTableRows($dataTableConfig, Arr::map(
            $paginator->toBase()->all(),
            fn($item) => $this->getDtItemResource($dataTableConfig, $item, $filters)
        ));
    }

    public function getDtHeaders(DataTableConfig $dataTableConfig, $filters)
    {
       return $dataTableConfig->dtHeaders->toBase()->map(function($dtHeader, $key) {
           $newHeader = clone $dtHeader;
           $newHeader->value = str_replace('.', '_', $newHeader->value);
           return $newHeader;
       });
    }

    public function getDtItemResource($dataTableConfig, $item, $filters)
    {
       $resource = $dataTableConfig->resource_class;
       return $resource ? $resource::make($item) : $item;
    }

    protected function flattenDataTableRows($dataTableConfig, $rows)
    {
        return Arr::map(
            Arr::map($rows, fn($row) => response()->json($row)->getData()),
            fn($row) => $this->flattenDataTableRow($dataTableConfig, $row)
        );
    }

    protected function flattenDataTableRow($dataTableConfig, $row)
    {
        return collect($this->dataTableExtraFields($dataTableConfig, $row))
            ->merge($dataTableConfig->dtHeaders->mapWithKeys(
                fn($dtHeader) => [str_replace('.', '_', $dtHeader->value) => data_get($row, $dtHeader->value)]
            ));
    }

    protected function dataTableExtraFields($dataTableConfig, $row)
    {
        return [
            'id' => $row->id ?? null,
            'status_id' => $row->status_id ?? null
        ];
    }

    public function flattenDropdownRows($resourceCollection)
    {
        $resourceCollection->collection = $resourceCollection->collection->map(
            function($resource) {
                $row = response()->json($resource)->getData(true);

                $newRow = [];
                foreach ($this->dropdownFields() as $field) {
                    if (array_key_exists($field, $row)) {
                        $newRow[$field] = $row[$field];
                    }
                }

                $model = new $resource->resource;
                //Need to clear out the dummy model's appends because we're only using attributes from the $resource
                $model->setAppends([]);
                $model->forceFill($newRow);
                return $model;
            }
        );
        return $resourceCollection;
    }

    protected function dropdownFields()
    {
        return ['id', 'ref', 'name', 'name_formatted', 'code', 'reference_name', 'fleet_number', 'manifest_name', 'disabled'];
    }

    public function getQueryWithFilters($filters, $model)
    {
        $textFilter = null;
        foreach ($filters as $filter) {
            if ($filter->title === 'search-bar') {
                $textFilter = $filter->value;
            }
        }

        if ($textFilter === null) {
            $query = $this->getQuery();
        } else {
            if (method_exists($this, 'searchQuery')) {
                $query = $this->searchQuery($textFilter);
            } else {
                $query = $this->getQuery()
                    ->whereIn($model->getTable() . '.' . $model->getKeyName(), $model::search($textFilter)->get()->pluck($model->getKeyName())->all());
            }
        }

        foreach ($filters as $filter) {
            $this->dataTableFilter($query, $filter->title, $filter->value, $model);
        }

        return $query;
    }

    public function dropdown($data)
    {
        $filters = $data['filters'] ?? [];
        $pagination = $data['pagination'] ?? [];
        $sort = $data['sort'] ?? [];

        $query = $this->getQuery();
        $this->dropdownFilters($query, $filters);
        $this->dropdownSort($query, $sort);
        return $this->dropdownPaginate($query, $pagination);
    }

    protected function dropdownFilters($query, $filters)
    {
        //Nothing by default
    }

    protected function dropdownSort($query, $sort)
    {
        //Nothing by default
    }

    protected function dropdownPaginate($query, $pagination)
    {
        if (isset($pagination['current_page'])) {
            //Include ->items() to remove links, meta
            return $query->paginate($pagination['per_page'] ?? 0 ?: 15, ['*'], 'page', $pagination['current_page']);
        }

        return $query->limit(config('app.dropdown.limit'))->get();
    }
}
