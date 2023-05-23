<?php

namespace Lupennat\NestedForm;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Http\Requests\ResourceDetailRequest;

class NestedFormChild extends NestedFormSchema
{
    /**
     * Name of the fields' fitler method.
     *
     * @return string
     */
    protected static function getFilterMethod(NovaRequest $novaRequest)
    {
        return $novaRequest instanceof ResourceDetailRequest ? 'filterForDetail' : 'onlyUpdateFields';
    }

    /**
     * Resolve Field From Resource.
     *
     * @return void
     */
    protected function resolveField(Field $field, NovaRequest $novaRequest)
    {
        $novaRequest instanceof ResourceDetailRequest ? $field->resolveForDisplay($this->model) : parent::resolveField($field, $novaRequest);
    }

    /**
     * Set the custom component if need be.
     */
    protected function setComponent(Field $field, NovaRequest $novaRequest)
    {
        return $novaRequest instanceof ResourceDetailRequest ? $field : parent::setComponent($field, $novaRequest);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_merge(parent::jsonSerialize(), [
            'resourceId' => $this->model->getKey(),
            $this->parentForm->keyName => $this->model->getKey(),
        ]);
    }
}
