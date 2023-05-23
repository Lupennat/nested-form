<?php

namespace Lupennat\NestedForm\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Http\Requests\ResourceCreateOrAttachRequest;
use Laravel\Nova\Http\Requests\ResourceUpdateOrUpdateAttachedRequest;
use Laravel\Nova\Http\Resources\CreateViewResource;
use Laravel\Nova\Http\Resources\ReplicateViewResource;
use Laravel\Nova\Http\Resources\UpdateViewResource;
use Lupennat\NestedForm\NestedForm;

class FieldSyncController extends Controller
{
    /**
     * Synchronize the field for creation view.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(ResourceCreateOrAttachRequest $request, $nestedPrefix)
    {
        $this->nestedParametersToOriginalParameter($request, $nestedPrefix);

        $resource = $request->has('fromResourceId')
        ? ReplicateViewResource::make($request->fromResourceId)->newResourceWith($request)
        : CreateViewResource::make()->newResourceWith($request);

        return response()->json(
            $this->adaptFieldForNestedForm($request, $nestedPrefix, $resource->creationFields($request)
                ->filter(function ($field) use ($request) {
                    return $request->query('field') === $field->attribute &&
                            $request->query('component') === $field->dependentComponentKey();
                })->each->syncDependsOn($request)
                ->first())
        );
    }

    /**
     * Synchronize the field for updating.
     */
    public function update(ResourceUpdateOrUpdateAttachedRequest $request, $nestedPrefix)
    {
        $this->nestedParametersToOriginalParameter($request, $nestedPrefix);

        $resource = UpdateViewResource::make()->newResourceWith($request);

        return response()->json(
            $this->adaptFieldForNestedForm($request, $nestedPrefix, $resource->updateFields($request)
                ->filter(function ($field) use ($request) {
                    return $request->query('field') === $field->attribute &&
                            $request->query('component') === $field->dependentComponentKey();
                })->each->syncDependsOn($request)
                ->first())
        );
    }

    protected function nestedParametersToOriginalParameter(NovaRequest $request, string $nestedPrefix)
    {
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, $nestedPrefix)) {
                $request->query->set(substr(str_replace($nestedPrefix, '', $key), 1, -1), $value);
            }
        }

        $field = $request->query->get('field');
        if (str_starts_with($field, $nestedPrefix)) {
            $request->query->set('field', substr(str_replace($nestedPrefix, '', $field), 1, -1));
        }
    }

    protected function adaptFieldForNestedForm(NovaRequest $request, string $nestedPrefix, Field $field = null)
    {
        if ($field) {
            if ($field instanceof NestedForm) {
                $field->attribute = $nestedPrefix . "[$field->attribute]";
            } else {
                $field->withMeta([
                    'attribute' => $nestedPrefix . "[$field->attribute]",
                    'validationKey' => $nestedPrefix . "[$field->attribute]",
                    'originalAttribute' => $field->attribute,
                ]);
            }

            $data = $field->jsonSerialize();

            if (array_key_exists('conditionalField', $data)) {
                $data['conditionalField']['originalAttribute'] = $data['conditionalField']['attribute'];
                $data['conditionalField']['attribute'] = $nestedPrefix . '[' . $data['conditionalField']['originalAttribute'] . ']';
                $data['conditionalField']['validationKey'] = $nestedPrefix . '[' . $data['conditionalField']['originalAttribute'] . ']';
            }

            if (is_array($data['dependsOn'])) {
                $remapped = [];
                foreach ($data['dependsOn'] as $key => $value) {
                    $remapped[$nestedPrefix . "[$key]"] = $value;
                }
                $data['dependsOn'] = $remapped;
            }
        }

        return $data;
    }
}
