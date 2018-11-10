<?php

namespace EloquentElastic;

use Closure;
use Elastica\Exception\NotImplementedException;
use Illuminate\Database\Query\Builder as EloquentQueryBuilder;

class QueryBuilder extends EloquentQueryBuilder
{

    /**
     * All of the available clause operators.
     *
     * @var array
     */
    public $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like',
        'regexp', 'not regexp', 'exists', 'in',
        'not in',
    ];

    /**
     * Add an exists clause to the query.
     *
     * @param  \Closure $callback
     * @param  string   $boolean
     * @param  bool     $not
     *
     * @return $this
     */
    public function whereExists(Closure $callback, $boolean = 'and', $not = false)
    {
        throw new NotImplementedException('Method "whereExists" not implemented on elasticsearch');
    }

    /**
     * Add an exists clause to the query.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  string                             $boolean
     * @param  bool                               $not
     *
     * @return $this
     */
    public function addWhereExistsQuery(parent $query, $boolean = 'and', $not = false)
    {
        throw new NotImplementedException('Method "addWhereExistsQuery" not implemented on elasticsearch');
    }

    /**
     * Add a where between statement to the query.
     *
     * @param  string $column
     * @param  array  $values
     * @param  string $boolean
     * @param  bool   $not
     *
     * @return $this
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        $type = 'between';

        $this->wheres[] = compact('column', 'type', 'values', 'boolean', 'not');

        return $this;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array $columns
     * @param int   $totalHits
     *
     * @return array
     */
    public function getWithTotalHits($columns = ['*'], &$totalHits = 0)
    {
        $original = $this->columns;

        if (!$original) {
            $this->columns = $columns;
        }

        $response = $this->runSelect();

        $totalHits = $response->getTotalHits();

        $results = $this->processor->processSelect($this, $response);

        $this->columns = $original;

        return $results;
    }
}
