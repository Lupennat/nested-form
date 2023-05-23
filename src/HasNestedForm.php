<?php

namespace Lupennat\NestedForm;

use Illuminate\Support\Arr;
use Laravel\Nova\Http\Requests\NovaRequest;

trait HasNestedForm
{
    /**
     * @return string|null
     */
    public function getNestedFormParentResource()
    {
        return app(NovaRequest::class)->nestedFormResource;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getNestedFormParentContent($key)
    {
        return Arr::get(app(NovaRequest::class)->nestedFormContent ?? [], $key);
    }
}
