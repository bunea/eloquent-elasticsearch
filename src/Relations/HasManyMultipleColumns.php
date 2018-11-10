<?php
/**
 * Created by PhpStorm.
 * User: mirceasoaica
 * Date: 03/06/2018
 * Time: 11:38
 */

namespace EloquentElastic\Relations;

use EloquentElastic\Model;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;
use Illuminate\Database\Eloquent\Collection;

class HasManyMultipleColumns extends EloquentHasMany
{

    /**
     * The foreign key of the parent model.
     *
     * @var mixed
     */
    protected $foreignKey;

    /**
     * The local key of the parent model.
     *
     * @var mixed
     */
    protected $localKey;

    protected $defaultColumns = [];

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints && $this->parent->exists) {
            $that = $this;

            $this->query->orWhere(function ($query) use ($that) {
                foreach ($that->localKey as $index => $key) {
                    $values = $that->parent->getAttribute($that->foreignKey[$index]);
                    if (!is_array($values)) {
                        $values = [$values];
                    }
                    $query->whereIn($that->foreignKey[$index], $values);
                }
            });
            //            $this->query->whereNotNull($this->foreignKey);
        }
    }

    /**
     * @param array $models
     */
    public function addEagerConstraints(array $models)
    {
        $models = collect($models);

        $this->query->orWhere(function ($query) use ($models) {
            foreach ($this->localKey as $index => $key) {
                $data = $models->pluck($this->foreignKey[$index])->filter()->unique()->all();
                if (!$data) {
                    continue;
                }

                if (is_array($data[0])) {
                    $data = collect($data)->collapse()->unique()->all();
                }

                $query->whereIn($key, $data);
            }
        });
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param  array                                    $models
     * @param  \Illuminate\Database\Eloquent\Collection $results
     * @param  string                                   $relation
     * @param  string                                   $type
     *
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
    {
        foreach ($models as $model) {
            $model->setRelation(
                $relation, $this->getRelationResults($results, $model)
            );
        }

        return $models;
    }

    /**
     * @param Collection $results
     * @param Model      $model
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getRelationResults(Collection $results, Model $model)
    {
        $relationResults = [];

        foreach ($this->localKey as $index => $key) {
            $newResults = $results->filter(function ($item) use ($key) {
                    return $item->getAttribute($key) ? true : false;
            })->mapToDictionary(function ($item) use ($key) {
                    return [$item->getAttribute($key) => $item];
            })->all();

            if (!$newResults) {
                continue;
            }

            $values = $model->getAttribute($this->foreignKey[$index]);
            if (!is_array($values)) {
                $values = [$values];
            }

            foreach ($values as $value) {
                if (isset($newResults[$value])) {
                    $relationResults = array_merge($relationResults, $newResults[$value]);
                }
            }
        }

        return collect($relationResults);
    }

    /**
     * Get the fully qualified parent key name.
     *
     * @return string
     */
    public function getQualifiedParentKeyName()
    {
        return $this->localKey;
    }

    /**
     * @param array $columns
     *
     * @return HasManyMultipleColumns
     */
    public function setDefaultColumns(array $columns) : self
    {
        $this->defaultColumns = $columns;

        return $this;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        if($columns != ['*']) {
            $columns = array_merge($columns, $this->defaultColumns);
        }

        $results = $this->query->get($columns);

        return $results;
    }
}
