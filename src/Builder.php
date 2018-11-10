<?php

namespace EloquentElastic;

use Elastica\Exception\NotImplementedException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\Paginator;

class Builder extends EloquentBuilder
{

    /**
     * Increment a column's value by a given amount.
     *
     * @param  string    $column
     * @param  float|int $amount
     * @param  array     $extra
     * @return int
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        throw new NotImplementedException('Method "increment" not implemented on elasticsearch');
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * @param  string    $column
     * @param  float|int $amount
     * @param  array     $extra
     * @return int
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        throw new NotImplementedException('Method "decrement" not implemented on elasticsearch');
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array $columns
     * @return \Illuminate\Support\Collection
     */
    public function getRawCollection($columns = ['*'])
    {
        $builder = $this->applyScopes();

        return collect($this->query->get($columns)->all());
    }

    /**
     * Paginate the given query.
     *
     * @param  int      $perPage
     * @param  array    $columns
     * @param  string   $pageName
     * @param  int|null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        $builder = $this->applyScopes();

        $total   = 0;
        $results = $builder->query->forPage($page, $perPage)->getWithTotalHits($columns, $total);


        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded, which will solve the
        // n+1 query issue for the developers to avoid running a lot of queries.
        if (count($results) > 0) {
            $results = $builder->eagerLoadRelations($this->hydrate($results)->all());
        }

        return $this->paginator($results, $total, $perPage, $page, [
            'path'     => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        $builder = $this->applyScopes();

        // must add a bigger limit. Elastiocsearch only returns first 20 records
        if (!$builder->query->limit) {
            $builder->query->limit = 10000;
        }

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded, which will solve the
        // n+1 query issue for the developers to avoid running a lot of queries.
        $models = $builder->getModels($columns);
        if (count($models) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $builder->getModel()->newCollection($models);
    }
}
