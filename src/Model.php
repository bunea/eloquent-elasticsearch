<?php

namespace EloquentElastic;

use EloquentElastic\Relations\HasManyMultipleColumns;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

use EloquentElastic\Traits\HasRelationships;
use Spatie\Tags\HasTags;

class Model extends EloquentModel
{

    use HasRelationships;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected static $resolver;

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new QueryBuilder(
            $connection, $connection->getQueryGrammar()
        );
    }

    /**
     * Get the connection resolver instance.
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public static function getConnectionResolver()
    {
        return static::$resolver;
    }

    /**
     * Set the connection resolver instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface $resolver
     * @return void
     */
    public static function setConnectionResolver(Resolver $resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * @param EloquentModel $model
     */
    public function updateRecord(EloquentModel $model)
    {
        $this->fill($this->toSearchableArray($model));
        $this->save();
    }

    /**
     * @param EloquentModel $model
     *
     * @return array
     */
    public function toSearchableArray(EloquentModel $model)
    {
        $result = [
            'id' => $model->id,
        ];

        foreach ($model->fillable as $field) {
            $result[$field] = $model->$field;
        }

        if (isset($model->translatedAttributes)) {
            foreach ($model->translatedAttributes as $field) {
                $result[$field] = $this->getTranslations($model, $field);
            }
        }

        if($this->has_tenant) {
            $result['tenant_id'] = $model->tenant_id;
        }

        if (in_array(HasTags::class, class_uses($model))) {
            // fetch tags and put them in ES
            $result['tags'] = $model->tags->pluck('name')->all();
        }

        return $result;
    }

    /**
     * @param EloquentModel $model
     * @param string        $field
     *
     * @return array
     */
    public function getTranslations(EloquentModel $model, string $field)
    {
        $locales = config('translatable.locales');

        $response = [];

        foreach ($locales as $locale) {
            $response[$locale] = $model->{$field . ':' . $locale};
        }

        return $response;
    }

    /**
     * Get all of the models from the database.
     *
     * @param  array|mixed $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function all($columns = ['*'])
    {
        return (new static)->newQuery()->limit(10000)->get(
            is_array($columns) ? $columns : func_get_args()
        );
    }

    /**
     * @return string
     */
    public static function getMysqlModelClass()
    {
        return str_replace('\\ES\\', '\\', static::class);
    }

    /**
     * @param string $related
     * @param array  $foreignKeys
     * @param array  $localKeys
     * @return HasManyMultipleColumns
     */
    public function hasManyMultipleColumns(string $related, array $foreignKeys, array $localKeys)
    {
        $instance = $this->newRelatedInstance($related);
        return new HasManyMultipleColumns($instance->newQuery(), $this, $foreignKeys, $localKeys);
    }
}
