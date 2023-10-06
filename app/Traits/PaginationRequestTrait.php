<?php

namespace App\Traits;

trait PaginationRequestTrait
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    protected function paginationRules()
    {
        return [
            'pagination.per_page' => 'integer|sometimes|required',
            'pagination.current_page' => 'integer|sometimes|required',
        ];
    }
}
