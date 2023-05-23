<?php

namespace Lupennat\NestedForm;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Contracts\BehavesAsPanel;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Collapsable;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\FormData;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\ResourceRelationshipGuesser;
use Laravel\Nova\Fields\SupportsDependentFields;
use Laravel\Nova\Http\Controllers\ResourceDestroyController;
use Laravel\Nova\Http\Controllers\ResourceDetachController;
use Laravel\Nova\Http\Controllers\ResourceStoreController;
use Laravel\Nova\Http\Controllers\ResourceUpdateController;
use Laravel\Nova\Http\Requests\CreateResourceRequest;
use Laravel\Nova\Http\Requests\DeleteResourceRequest;
use Laravel\Nova\Http\Requests\DetachResourceRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Http\Requests\UpdateResourceRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Panel;

class NestedForm extends Field implements BehavesAsPanel, RelatableField
{
    use SupportsDependentFields { dependsOn as novaDependsOn; }
    use Collapsable;

    /**
     * Wrap left.
     *
     * @var string
     */
    public const WRAP_LEFT = '{{';

    /**
     * Wrap right.
     *
     * @var string
     */
    public const WRAP_RIGHT = '}}';

    /**
     * INDEX.
     *
     * @var string
     */
    public const INDEX = 'INDEX';

    /**
     * ID.
     *
     * @var string
     */
    public const ID = 'ID';

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'nested-form';

    /**
     * Indicates if the element should be shown on the index view.
     *
     * @var \Closure|bool
     */
    public $showOnIndex = false;

    /**
     * The field's relationship resource class.
     *
     * @var string
     */
    public $resourceClass;

    /**
     * The field's relationship resource name.
     *
     * @var string
     */
    public $resourceName;

    /**
     * The field's relationship name.
     *
     * @var string
     */
    public $viaRelationship;

    /**
     * The field's singular label.
     *
     * @var string
     */
    public $singularLabel;

    /**
     * The field's plural label.
     *
     * @var string
     */
    public $pluralLabel;

    /**
     * From resource uriKey.
     *
     * @var string
     */
    public $viaResource;

    /**
     * Key name.
     *
     * @var string
     */
    public $keyName;

    /**
     * The maximum number of children.
     *
     * @var int
     */
    public $max = 0;

    /**
     * The minimum number of children.
     *
     * @var int
     */
    public $min = 0;

    /**
     * Lock Add/Remove children.
     *
     * @var bool
     */
    public $locked = false;

    /**
     * Prefill Children with.
     */
    public $prefill = [];

    /**
     * Force Prefill.
     */
    public $forcePrefill = false;

    /**
     * Style Relationship as Tabs.
     *
     *  @var bool
     */
    public $useTabs = false;

    /**
     * Active Tab.
     *
     *  @var int|string|null
     */
    public $activeTab = null;

    /**
     * Show/Hide Duplicate Button.
     *
     * @var bool
     */
    public $canDuplicate = false;

    /**
     * Heading Attributes.
     *
     * @var array<string>
     */
    public $heading = [];

    /**
     * Heading Attributes Should be Unique.
     *
     * @var bool
     */
    public $uniqueHeading = false;

    /**
     * Heading Separator.
     *
     * @var string
     */
    public $separator = ' . ';

    /**
     * Restore After Delete.
     *
     * @var bool
     */
    public $canRestore = false;

    /**
     * The label that should be used for the "add" button.
     *
     * @var string|null
     */
    public $addText;

    /**
     * The label that should be used for the "remove" button.
     *
     * @var string|null
     */
    public $removeText;

    /**
     * The label that should be used for the "duplicate" button.
     *
     * @var string|null
     */
    public $duplicateText;

    /**
     * The label that should be used for the "restore" button.
     *
     * @var string|null
     */
    public $restoreText;

    /**
     * Create a new nested form.
     *
     * @param string|null $attribute
     * @param string|null $resource
     *
     * @return void
     */
    public function __construct(string $name, $attribute = null, $resource = null)
    {
        parent::__construct($name, $attribute);
        $resource = $resource ?? ResourceRelationshipGuesser::guessResource($name);

        $this->resourceClass = $resource;
        $this->resourceName = $resource::uriKey();
        $this->viaRelationship = $this->attribute;
        $this->singularLabel = method_exists($this->resourceClass, 'singularLabel') ? $this->resourceClass::singularLabel() : Str::singular($this->name);
        $this->pluralLabel = method_exists($this->resourceClass, 'label') ? $this->resourceClass::label() : Str::singular($this->name);

        $this->keyName = (new $this->resourceClass::$model())->getKeyName();
        $this->viaResource = app(NovaRequest::class)->route('resource');
        $this->parentResourceName = app(NovaRequest::class)->resourceName;
        // Nova ^3.3.x need this to fix cannot add relation on create mode
        // $this->resolve(app(NovaRequest::class)->model());
    }

    /**
     * Get the relationship name.
     *
     * @return string
     */
    public function relationshipName()
    {
        //        return $this->viaRelationship;
    }

    /**
     * Get the relationship type.
     *
     * @return string
     */
    public function relationshipType()
    {
        //        return $this->getRelationshipType();
    }

    /**
     * Resolve the form fields.
     *
     * @return void
     */
    public function resolve($resource, $attribute = null)
    {
        $this->resource = $resource;
        $this->generateSchemaAndChildren();
    }

    /**
     * Generate Schema and Children.
     *
     * @return void
     */
    protected function generateSchemaAndChildren(FormData $formData = null)
    {
        $children = $this->children($this->resource, $formData);

        $this->withMeta([
            'children' => $children,
            'schema' => $this->schema($this->resource, $formData),
            'viaResourceId' => $this->resource->{ $this->resource->getKeyName()},
        ]);
    }

    /**
     * Get the schema.
     */
    public function schema($resource, $formData = null)
    {
        if (method_exists($resource, $this->viaRelationship)) {
            return NestedFormSchema::make($resource->{$this->viaRelationship}()->getModel(), static::wrapIndex(), $this, $formData);
        }

        return false;
    }

    /**
     * Get the children.
     */
    public function children($resource, $formData = null)
    {
        if (method_exists($resource, $this->viaRelationship)) {
            return $resource->{$this->viaRelationship}()->get()->map(function ($model, $index) use ($formData) {
                return NestedFormChild::make($model, $index, $this, $formData);
            })->all();
        }

        return false;
    }

    /**
     * Set the maximum number of children.
     *
     * @return $this
     */
    public function max(int $max)
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Set the minimum number of children.
     *
     * @return $this
     */
    public function min(int $min)
    {
        $this->min = $min;

        return $this;
    }

    /**
     * Lock Add/Remove children.
     *
     * @return $this
     */
    public function lock($lock = true)
    {
        $this->locked = $lock;

        return $this;
    }

    /**
     * Prefill Childrens.
     *
     * @param array<array<string,mixed>>
     *
     * @return $this
     */
    public function prefill($prefill, bool $force = false)
    {
        $this->min = 0;
        $this->forcePrefill = $force;
        $this->prefill = array_map(function ($item) {
            return array_reduce(array_keys($item), function ($carry, $key) use ($item) {
                $carry['[' . $key . ']'] = $item[$key];

                return $carry;
            }, []);
        }, $prefill);

        return $this;
    }

    /**
     * Style Relationship as Tabs.
     *
     * @return $this
     */
    public function useTabs(bool $useTabs = true)
    {
        $this->useTabs = $useTabs;

        return $this;
    }

    /**
     * Set Active Tab By Number.
     *
     * @param int|null $activeTab
     *
     * @return $this
     */
    public function activeTab($activeTab)
    {
        $this->activeTab = is_null($activeTab) ? null : (int) $activeTab;

        return $this;
    }

    /**
     * Set Active Tab By Heading.
     *
     * @param string|null $activeTab
     *
     * @return $this
     */
    public function activeTabByHeading($activeTab)
    {
        $this->activeTab = is_null($activeTab) ? null : (string) $activeTab;

        return $this;
    }

    /**
     * Show/Hide Duplicate Button.
     *
     * @return $this
     */
    public function canDuplicate(bool $canDuplicate = true)
    {
        $this->canDuplicate = $canDuplicate;

        return $this;
    }

    /**
     * Enable Restore After Delete.
     *
     * @return $this
     */
    public function canRestore(bool $canRestore = true)
    {
        $this->canRestore = $canRestore;

        return $this;
    }

    /**
     * The label that should be used for the add button.
     *
     * @return $this
     */
    public function addText(string $label)
    {
        $this->addText = $label;

        return $this;
    }

    /**
     * The label that should be used for the restore button.
     *
     * @return $this
     */
    public function restoreText(string $label)
    {
        $this->restoreText = $label;

        return $this;
    }

    /**
     * The label that should be used for the remove button.
     *
     * @return $this
     */
    public function removeText(string $label)
    {
        $this->removeText = $label;

        return $this;
    }

    /**
     * The label that should be used for the duplicate button.
     *
     * @return $this
     */
    public function duplicateText(string $label)
    {
        $this->duplicateText = $label;

        return $this;
    }

    /**
     * Define Heading Attributes.
     *
     * @param array<string> $attributes
     *
     * @return $this
     */
    public function heading($attributes, bool $unique = false)
    {
        $this->heading = $attributes;
        $this->uniqueHeading = $unique;

        return $this;
    }

    /**
     * Define Heading Separator.
     *
     * @return $this
     */
    public function separator(string $separator)
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * Set custom validation rules.
     */
    public function rules($rules)
    {
        parent::rules(($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules);

        return $this;
    }

    /**
     * Get the relationship type.
     */
    protected function getRelationshipType()
    {
        return (new \ReflectionClass(Nova::modelInstanceForKey($this->viaResource)->{$this->viaRelationship}()))->getShortName();
    }

    /**
     * Whether the current relationship if many or one.
     */
    protected function isManyRelationsip()
    {
        return Str::contains($this->getRelationshipType(), 'Many');
    }

    /**
     * Fills the attributes of the model within the container if the dependencies for the container are satisfied.
     *
     * @param string $requestAttribute
     * @param object $model
     * @param string $attribute
     */
    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        if ($model->exists) {
            $newRequest = NovaRequest::createFrom($request);
            if (!$model->{$model->getKeyName()} && $request->has($model->getKeyName())) {
                $model->{$model->getKeyName()} = $request->get($model->getKeyName());
            }
            $children = collect($newRequest->get($requestAttribute));
            $newRequest->route()->setParameter('resource', $this->resourceName);
            $this->deleteChildren($newRequest, $model, $children);
            $this->createOrUpdateChildren($newRequest, $model, $children, $requestAttribute, $this->getRelatedKeys($newRequest));
        } else {
            $model::saved(function ($model) use ($request, $requestAttribute, $attribute) {
                $this->fillAttributeFromRequest($request, $requestAttribute, $model, $attribute);
            });
        }
    }

    /**
     * Reject related fields.
     */
    public function isRelatedField($field)
    {
        if ($field instanceof BelongsTo || $field instanceof BelongsToMany) {
            return $field->resourceName === $this->viaResource;
        } elseif ($field instanceof MorphTo) {
            return collect($field->morphToTypes)->pluck('value')->contains($this->viaResource);
        }

        return false;
    }

    /**
     * Get the related key name for filling the attribute.
     */
    protected function getRelatedKeys(NovaRequest $request)
    {
        $field = collect(Nova::resourceInstanceForKey($this->resourceName)->fields($request))->first(function ($field) {
            return $this->isRelatedField($field);
        });

        if (!$field) {
            throw new \Exception(__('A field defining the inverse relationship needs to be set on your related resource (e.g. MorphTo, BelongsTo, BelongsToMany...)'));
        }

        if ($field instanceof MorphTo) {
            return [$field->attribute => self::ID, $field->attribute . '_type' => $this->viaResource];
        }

        return [$field->attribute => self::ID];
    }

    /**
     * Throw validation exception with correct attributes.
     */
    protected function throwValidationException(ValidationException $exception, int $index)
    {
        throw $exception::withMessages($this->getValidationErrors($exception, $index));
    }

    /**
     * Get validation errors with correct attributes.
     */
    protected function getValidationErrors(ValidationException $exception, int $index)
    {
        return collect($exception->errors())->mapWithKeys(function ($value, $key) use ($index) {
            return [$this->getValidationErrorAttribute($index, $key) => $value];
        })->toArray();
    }

    /**
     * Get validation error attribute.
     */
    protected function getValidationErrorAttribute(int $index, string $key)
    {
        return preg_replace('/(?<=\])((?!\[).+?(?!\]))(?=\[|$)/', '[$1]', $this->attribute . '[' . $index . ']' . $key);
    }

    /**
     * Delete the children not sent through the request.
     */
    protected function deleteChildren(NovaRequest $request, $model, $children)
    {
        if ($this->getRelationshipType() === 'BelongsToMany') {
            return (new ResourceDetachController())->__invoke($this->getDetachRequest($request, $model, $children));
        }

        return (new ResourceDestroyController())->__invoke($this->getDeleteRequest($request, $model, $children));
    }

    /**
     * Create or update the children sent through the request.
     */
    protected function createOrUpdateChildren(NovaRequest $request, $model, $children, $requestAttribute, $relatedKeys)
    {
        $children->each(function ($child, $index) use ($request, $model, $requestAttribute, $relatedKeys) {
            try {
                if (isset($child[$this->keyName])) {
                    return $this->updateChild($request, $model, $child, $index, $requestAttribute, $relatedKeys);
                }

                return $this->createChild($request, $model, $child, $index, $requestAttribute, $relatedKeys);
            } catch (ValidationException $exception) {
                $this->throwValidationException($exception, $index);
            }
        });
    }

    /**
     * Create the child sent through the request.
     */
    protected function createChild(NovaRequest $request, $model, $child, $index, $requestAttribute, $relatedKeys)
    {
        return (new ResourceStoreController())->__invoke($this->getCreateRequest($request, $model, $child, $index, $requestAttribute, $relatedKeys));
    }

    /**
     * Update the child sent through the request.
     */
    protected function updateChild(NovaRequest $request, $model, $child, $index, $requestAttribute, $relatedKeys)
    {
        return (new ResourceUpdateController())->__invoke($this->getUpdateRequest($request, $model, $child, $index, $requestAttribute, $relatedKeys));
    }

    /**
     * Get a request for detach.
     */
    protected function getDetachRequest(NovaRequest $request, $model, $children)
    {
        return DetachResourceRequest::createFrom($request->replace([
            'viaResource' => $this->viaResource,
            'viaResourceId' => $model->getKey(),
            'viaRelationship' => $this->viaRelationship,
            'resources' => $model->{$this->viaRelationship}()->select($this->attribute . '.' . $this->keyName)->whereNotIn($this->attribute . '.' . $this->keyName, $children->pluck($this->keyName))->pluck($this->keyName),
        ]));
    }

    /**
     * Get a request for delete.
     */
    protected function getDeleteRequest(NovaRequest $request, $model, $children)
    {
        return DeleteResourceRequest::createFrom($request->replace([
            'viaResource' => null,
            'viaResourceId' => null,
            'viaRelationship' => null,
            'resources' => $model->{$this->viaRelationship}()->whereNotIn($this->keyName, $children->pluck($this->keyName))->pluck($this->keyName),
        ]));
    }

    /**
     * Get a request for create.
     */
    protected function getCreateRequest(NovaRequest $request, $model, $child, $index, $requestAttribute, $relatedKeys)
    {
        $createRequest = CreateResourceRequest::createFrom($request->replace([
            'viaResource' => $this->viaResource,
            'viaResourceId' => $model->getKey(),
            'viaRelationship' => $this->viaRelationship,
        ])->merge($child)->merge(collect($relatedKeys)->map(function ($value) use ($model) {
            return $value === self::ID ? $model->getKey() : $value;
        })->toArray()));

        $createRequest->files = collect($request->file($requestAttribute . '.' . $index));

        return $createRequest;
    }

    /**
     * Get a request for update.
     */
    protected function getUpdateRequest(NovaRequest $request, $model, $child, $index, $requestAttribute, $relatedKeys)
    {
        return UpdateResourceRequest::createFrom($this->getCreateRequest($request, $model, $child, $index, $requestAttribute, $relatedKeys)->merge([
            'resourceId' => $child[$this->keyName],
        ]));
    }

    /**
     * Make current field behaves as panel.
     *
     * @return \Laravel\Nova\Panel
     */
    public function asPanel()
    {
        return Panel::make($this->name, [$this])
            ->withMeta([
                'prefixComponent' => true,
            ])->withComponent('nested-panel');
    }

    /**
     * Wrap an attribute into a dynamic attribute
     * value.
     *
     * @param string $default
     */
    public static function wrapAttribute(string $attribute, $default = '')
    {
        return self::WRAP_LEFT . $attribute . '|' . $default . self::WRAP_RIGHT;
    }

    /**
     * Turn a given attribute string into
     * a conditional attribute.
     */
    public static function conditional(string $attribute)
    {
        return preg_replace('/\.(.*?)(?=\.|$)/', '\[$1\]', preg_replace('/\.\$\./', '.' . static::wrapIndex() . '.', preg_replace('/\.\*\./', '\.[0-9]+\.', $attribute)));
    }

    /**
     * Wrap the index key.
     */
    public static function wrapIndex()
    {
        return self::WRAP_LEFT . self::INDEX . self::WRAP_RIGHT;
    }

    /**
     * Check showing on index.
     *
     * @param mixed $resource
     */
    public function isShownOnIndex(NovaRequest $request, $resource): bool
    {
        return false;
    }

    /**
     * Register depends on to a field.
     *
     * @param string|\Laravel\Nova\Fields\Field|array<int, string|\Laravel\Nova\Fields\Field> $attributes
     * @param (callable(static, \Laravel\Nova\Http\Requests\NovaRequest, \Laravel\Nova\Fields\FormData):(void))|class-string|null  $mixin
     *
     * @return $this
     */
    public function dependsOn($attributes, $mixin = null)
    {
        $this->novaDependsOn($attributes, function (NestedForm $field, NovaRequest $novaRequest, FormData $formData) use ($mixin) {
            if (!is_null($mixin)) {
                if (is_string($mixin) && class_exists($mixin)) {
                    $mixin = new $mixin();
                }
                call_user_func($mixin, $field, $novaRequest, $formData);
            }

            $this->generateSchemaAndChildren($formData);
        });

        return $this;
    }

    /**
     * Prepare the field for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'singularLabel' => $this->singularLabel,
                'pluralLabel' => $this->pluralLabel,
                'indexKey' => static::wrapIndex(),
                'wrapLeft' => self::WRAP_LEFT,
                'wrapRight' => self::WRAP_RIGHT,
                'resourceName' => $this->resourceName,
                'viaRelationship' => $this->viaRelationship,
                'viaResource' => $this->viaResource,
                'keyName' => $this->keyName,
                'prefill' => $this->prefill,
                'forcePrefill' => $this->forcePrefill,
                'min' => $this->min,
                'max' => $this->isManyRelationsip() ? $this->max : 1,
                'locked' => $this->locked,
                'useTabs' => $this->useTabs,
                'activeTab' => $this->activeTab,
                'canDuplicate' => $this->canDuplicate,
                'heading' => $this->heading,
                'uniqueHeading' => $this->uniqueHeading,
                'separator' => $this->separator,
                'canRestore' => $this->canRestore,
                'addText' => $this->addText ?? __('Add ' . $this->singularLabel),
                'duplicateText' => $this->duplicateText ?? __('Duplicate ' . $this->singularLabel),
                'restoreText' => $this->restoreText ?? __('Restore ' . $this->singularLabel),
                'removeText' => $this->removeText ?? __('Remove ' . $this->singularLabel),
            ]
        );
    }
}
