<?php

namespace Lupennat\NestedForm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\FormData;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class NestedFormSchema implements \JsonSerializable
{
    /**
     * Parent form instance.
     *
     * @var NestedForm
     */
    protected $parentForm;

    /**
     * Current model instance.
     *
     * @var Model
     */
    protected $model;

    /**
     * Current index.
     *
     * @var int|string
     */
    protected $index;

    /**
     * Current request.
     */
    protected $request;

    /**
     * List of fields.
     */
    public $fields;

    /**
     * Create a new NestedFormSchema instance.
     */
    public function __construct(Model $model, $index, NestedForm $parentForm, FormData $formData = null)
    {
        $this->model = $model;
        $this->index = $index;
        $this->parentForm = $parentForm;
        $this->request = app(NovaRequest::class);

        $this->fields = $this->fields($formData);
    }

    /**
     * Name of the fields' fitler method.
     *
     * @return string
     */
    protected static function getFilterMethod(NovaRequest $novaRequest)
    {
        return 'onlyCreateFields';
    }

    /**
     * Resolve Field From Resource.
     *
     * @return void
     */
    protected function resolveField(Field $field, NovaRequest $novaRequest)
    {
        $field->resolve($this->model);
    }

    /**
     * Get the fields for the current schema.
     */
    protected function fields(FormData $formData = null)
    {
        $this->request->route()->setParameter('resource', $this->parentForm->resourceName);

        $fields = $this->filterFields($formData)->map(function ($field) {
            if ($field instanceof NestedForm) {
                $field->attribute = $this->attribute($field->attribute);
            } else {
                $field->withMeta([
                    'attribute' => $this->attribute($field->attribute),
                    'validationKey' => $this->attribute($field->attribute),
                    'originalAttribute' => $field->attribute,
                ]);
            }

            $this->resolveField($field, $this->request);

            if ($field instanceof BelongsTo) {
                $this->setComponent($field, $this->request);

                return with($this->request, function ($request) use ($field) {
                    $method = new \ReflectionMethod(BelongsTo::class, 'getDependentsAttributes');

                    $method->setAccessible(true);

                    $viewable = !is_null($field->viewable) ? $field->viewable : $field->resourceClass::authorizedToViewAny($request);

                    $data = array_merge([
                        'belongsToId' => $field->belongsToId,
                        'relationshipType' => $field->relationshipType(),
                        'belongsToRelationship' => $field->belongsToRelationship,
                        'debounce' => $field->debounce,
                        'displaysWithTrashed' => $field->displaysWithTrashed,
                        'label' => $field->resourceClass::label(),
                        'peekable' => $field->isPeekable($request),
                        'hasFieldsToPeekAt' => $field->hasFieldsToPeekAt($request),
                        'resourceName' => $field->resourceName,
                        'reverse' => $field->isReverseRelation($request),
                        'searchable' => $field->isSearchable($request),
                        'withSubtitles' => $field->withSubtitles,
                        'showCreateRelationButton' => $field->createRelationShouldBeShown($request),
                        'singularLabel' => $field->singularLabel,
                        'viewable' => $viewable,
                    ], [
                        'attribute' => $this->attribute($field->attribute),
                        'originalAttribute' => $field->attribute,
                        'component' => $field->component(),
                        'dependentComponentKey' => $field->dependentComponentKey(),
                        'dependsOn' => $method->invoke($field, $request),
                        'displayedAs' => $field->displayedAs,
                        'fullWidth' => $field->fullWidth,
                        'helpText' => $field->getHelpText(),
                        'indexName' => $field->name,
                        'name' => $field->name,
                        'nullable' => $field->nullable,
                        'panel' => $field->panel,
                        'placeholder' => $field->placeholder,
                        'prefixComponent' => true,
                        'readonly' => $field->isReadonly($request),
                        'required' => $field->isRequired($request),
                        'sortable' => $field->sortable,
                        'sortableUriKey' => $this->sortableUriKey($field),
                        'stacked' => $field->stacked,
                        'textAlign' => $field->textAlign,
                        'uniqueKey' => sprintf(
                            '%s-%s-%s',
                            $field->attribute,
                            Str::slug($field->panel ?? 'default'),
                            $field->component()
                        ),
                        'usesCustomizedDisplay' => $field->usesCustomizedDisplay,
                        'validationKey' => $this->attribute($field->attribute),
                        'value' => $field->value,
                        'visible' => $field->visible,
                        'wrapping' => $field->wrapping,
                    ]);

                    return $data;
                });
            }

            $data = $this->setComponent($field, $this->request)->jsonSerialize();

            if (array_key_exists('conditionalField', $data)) {
                $data['conditionalField']['originalAttribute'] = $data['conditionalField']['attribute'];
                $data['conditionalField']['attribute'] = $this->attribute($data['conditionalField']['originalAttribute']);
                $data['conditionalField']['validationKey'] = $this->attribute($data['conditionalField']['originalAttribute']);
            }

            return $data;
        })->values()->toArray();

        $this->request->route()->setParameter('resource', $this->parentForm->viaResource);

        foreach ($fields as $index => $field) {
            if (is_array($field['dependsOn'])) {
                $remapped = [];
                foreach ($field['dependsOn'] as $key => $value) {
                    $remapped[$this->attribute($key)] = $value;
                }
                $fields[$index]['dependsOn'] = $remapped;
            }
        }

        return $fields;
    }

    /**
     * Return the sortable uri key for the field.
     *
     * @return string
     */
    protected function sortableUriKey(BelongsTo $field)
    {
        $method = new \ReflectionMethod(BelongsTo::class, 'getRelationForeignKeyName');

        $method->setAccessible(true);

        return $method->invoke($field, $this->resourceInstance()->resource->{$field->attribute}());
    }

    /**
     * Set the custom component if need be.
     */
    protected function setComponent(Field $field, NovaRequest $novaRequest)
    {
        if ($field instanceof BelongsTo) {
            $field->component = 'nested-form-belongs-to-field';
        } elseif ($field instanceof File) {
            $field->component = 'nested-form-file-field';
        } elseif ($field instanceof MorphTo) {
            $field->component = 'nested-form-morph-to-field';
        }

        return $field;
    }

    /*
     * Turn an attribute into a nested attribute.
     */
    protected function attribute(string $attribute = null)
    {
        return $this->parentForm->attribute . '[' . $this->index . ']' . ($attribute ? '[' . $attribute . ']' : '');
    }

    /**
     * Return the method reflection to filter
     * the fields.
     */
    protected function filterFields(FormData $formData = null)
    {
        $method = new \ReflectionMethod($this->parentForm->resourceClass, 'resolveFields');

        $method->setAccessible(true);

        $path = parse_url($this->request->headers->get('referer'), PHP_URL_PATH);

        if ($path && str_starts_with($path, '/resources/')) {
            $exploded = explode('/', $path);
            $this->request->merge(['nestedFormResource' => $exploded[2] ?? '']);
        }

        $this->request->merge(['nestedFormContent' => []]);

        if ($formData) {
            $this->request->merge(['nestedFormContent' => $formData->toArray()]);
        }

        $fields =
             $method->invoke($this->resourceInstance(), $this->request)->map(function ($field) {
                 if ($field instanceof Panel) {
                     return collect($field->data)->map(function ($field) {
                         $field->panel = null;

                         return $field;
                     })->values();
                 }

                 return $field;
             })
                 ->flatten()
                 ->reject(function ($field) {
                     return $this->parentForm->isRelatedField($field);
                 });

        return call_user_func([$fields, static::getFilterMethod($this->request)], $this->request, $this->resourceInstance()->resource);
    }

    /**
     * Return an instance of the nested form resource class.
     */
    protected function resourceInstance()
    {
        return new $this->parentForm->resourceClass($this->model);
    }

    /**
     * Create a new NestedFormSchema instance.
     */
    public static function make(...$arguments)
    {
        return new static(...$arguments);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'fields' => $this->fields,
            'opened' => !$this->parentForm->collapsedByDefault,
            'deleted' => false,
            'attribute' => $this->attribute(),
        ];
    }
}
