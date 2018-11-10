<?php

namespace EloquentElastic;

use Elastica\Exception\NotImplementedException;
use Illuminate\Database\Query\Grammars\Grammar as BaseGrammar;
use Illuminate\Database\Query\Builder;
use Elastica\Query;
use Elastica\Search;
use Elastica\QueryBuilder as ElasticsearchQueryBuilder;

use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use Elastica\Query\Range;
use Elastica\Query\Exists;
use Elastica\Query\Wildcard;
use Elastica\Query\Terms;
use Elastica\Query\AbstractQuery;
use Illuminate\Support\Str;

use Elastica\Document;

use Elastica\Suggest\Completion;

class Grammar extends BaseGrammar
{

    /**
     * The grammar specific operators.
     *
     * @var array
     */
    protected $operators = [];

    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [
        'columns',
        'from',
        'wheres',
        'orders',
        'limit',
        'offset',
    ];

    /**
     * make a select query into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     *
     * @return \Elastica\Search.
     */
    public function compileSelect(Builder $query)
    {
        return $this->makeComponents($query);
    }

    /**
     * Compile an insert statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  array                              $values
     *
     * @return string
     */
    public function compileInsert(Builder $query, array $values)
    {
        $index = $query->getConnection()->client->getIndex($query->from);

        $type = $index->getType(Str::singular($query->from));

        $documents = [];

        foreach ($values as $record) {
            $documents[] = new Document($record['id'], $record);
        }

        $type->addDocuments($documents);
        $index->refresh();
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  array                              $values
     *
     * @return string
     */
    public function compileUpdate(Builder $query, $values)
    {
        $index = $query->getConnection()->client->getIndex($query->from);

        $type = $index->getType(Str::singular($query->from));

        $documents = [];

        foreach ($values as $record) {
            $documents[] = new Document($record['id'], $record);
        }

        $type->updateDocuments($documents);
        $index->refresh();
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     *
     * @return string
     */
    public function compileDelete(Builder $query)
    {
    }

    /**
     * make the components necessary for a select clause.
     *
     * @param Builder $builder
     *
     * @return Search
     */
    protected function makeComponents(Builder $builder)
    {
        $search = new Search($builder->getConnection()->client);

        $search->addIndex($builder->from);

        $query = new Query();

        if ($builder->columns != ['*']) {
            if(! $builder->columns) {
                $builder->columns = ['*'];
            }
            elseif (!in_array('id', $builder->columns)) {
                $builder->columns[] = 'id';
            }

            $query->setSource($builder->columns);
        }

        if ($builder->orders) {
            $this->makeOrders($builder, $query);
        }

        if ($builder->limit) {
            $query->setSize($builder->limit);
        }

        if ($builder->offset) {
            $query->setFrom($builder->offset);
        }

        $esQuery = null;
        if ($builder->wheres) {
//            $esQuery = new ElasticsearchQueryBuilder;
            $esQuery = $this->makeWheres($builder->wheres);

            $query->setQuery($esQuery);
        }

        if ($builder->groups) {
            $aggregation = new \Elastica\Aggregation\Terms('aggregate');
            $aggregation->setSize($builder->limit ?? 1000);
            $aggregation->setField(current($builder->groups));
            $query->addAggregation($aggregation);

            $query->setSize(0);
        }

        $search->setQuery($query);

        return $search;
    }

    /**
     * make the "where" portions of the query.
     *
     * @param array  $wheres
     * @param string $boolean
     *
     * @return BoolQuery
     */
    protected function makeWheres(array $wheres, $boolean = 'and')
    {
        $boolQuery = new BoolQuery;

        collect($wheres)
            ->map(function ($where) use ($boolQuery, $boolean) {
                $this->{"addWhere{$where['type']}"}($boolQuery, $where, $boolean);
            })
            ->all();

        return $boolQuery;
    }

    /**
     * make a basic addWhere clause.
     *
     * @param BoolQuery $boolQuery
     * @param array     $where
     * @param string    $boolean
     *
     * @return string
     * @throws \Exception
     */
    protected function addWhereBasic(BoolQuery $boolQuery, $where, string $boolean)
    {
        $where['column'] = $this->removeTableName($where['column']);
        switch ($where['operator']) {
            case '=':
                return $this->addToBoolQuery(
                    new Term([$where['column'] => $where['value']]),
                    $boolQuery,
                    $boolean);
            case '!=':
            case '<>':
                return $this->addToBoolQuery(
                    new Term([$where['column'] => $where['value']]),
                    $boolQuery,
                    $boolean, true);
            case '>':
                $options = ['gt' => $where['value']];
                if (isset($where['format'])) {
                    $options['format'] = $where['format'];
                }

                return $this->addToBoolQuery(
                    new Range($where['column'], $options),
                    $boolQuery,
                    $boolean);
            case '>=':
                $options = ['gte' => $where['value']];
                if (isset($where['format'])) {
                    $options['format'] = $where['format'];
                }

                return $this->addToBoolQuery(
                    new Range($where['column'], $options),
                    $boolQuery,
                    $boolean);
            case '<':
                $options = ['lt' => $where['value']];
                if (isset($where['format'])) {
                    $options['format'] = $where['format'];
                }

                return $this->addToBoolQuery(
                    new Range($where['column'], $options),
                    $boolQuery,
                    $boolean);
            case '<=':
                $options = ['lte' => $where['value']];
                if (isset($where['format'])) {
                    $options['format'] = $where['format'];
                }

                return $this->addToBoolQuery(
                    new Range($where['column'], $options),
                    $boolQuery,
                    $boolean);
            case 'exists':
                return $this->addToBoolQuery(
                    new Exists($where['column']),
                    $boolQuery,
                    $boolean,
                    !$where['value']);
            case 'like':
                return $this->addToBoolQuery(
                    new Wildcard($where['column'], $where['value']),
                    $boolQuery,
                    $boolean
                );
            case 'not like':
                return $this->addToBoolQuery(
                    new Wildcard($where['column'], $where['value']),
                    $boolQuery,
                    $boolean,
                    true
                );
            case 'in':
                $values = $where['values'] ?? $where['value'];

                return $this->addToBoolQuery(
                    new Terms($where['column'], is_array($values) ? $values : [$values]),
                    $boolQuery,
                    $boolean
                );
            case 'not in':
                $values = $where['values'] ?? $where['value'];

                return $this->addToBoolQuery(
                    new Terms($where['column'], is_array($values) ? $values : [$values]),
                    $boolQuery,
                    $boolean,
                    true
                );

            default:
                throw new \Exception('Operator "' . $where['operator'] . '" is not implemented on elasticsearch');
        }
    }

    /**
     * @param AbstractQuery $query
     * @param BoolQuery     $boolQuery
     * @param string        $boolean
     * @param bool          $not
     */
    public function addToBoolQuery(AbstractQuery $query, BoolQuery $boolQuery, string $boolean, bool $not = false)
    {
        if ($boolean == 'or') {
            // add should
            if ($not) {
                // should not does not exist. Must do a hack
                $newBool = new BoolQuery();
                $newBool->addMustNot($query);
                $boolQuery->addShould($newBool);
            } else {
                $boolQuery->addShould($query);
            }
        } else {
            // add must
            if ($not) {
                $boolQuery->addMustNot($query);
            } else {
                $boolQuery->addMust($query);
            }
        }
    }

    /**
     * make a "where in" clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param string    $boolean
     *
     * @return string
     * @throws \Exception
     */
    protected function addWhereIn(BoolQuery $boolQuery, array $where, string $boolean)
    {
        $where['operator'] = 'in';
        $this->addWhereBasic($boolQuery, $where, $boolean);
    }

    /**
     * make a "where not in" clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param   string    $boolean
     *
     * @return string
     * @throws \Exception
     */
    protected function addWhereNotIn(BoolQuery $boolQuery, array $where, string $boolean)
    {
        $where['operator'] = 'not in';
        $this->addWhereBasic($boolQuery, $where, $boolean);
    }

    /**
     * make a addWhere in sub-select clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param   string    $boolean
     *
     * @return string
     */
    protected function addWhereInSub(BoolQuery $boolQuery, array $where, string $boolean)
    {
        throw new NotImplementedException('Method "' . $where['type'] . '" not implemented');
    }

    /**
     * make a addWhere in sub-select clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param   string    $boolean
     *
     * @return string
     */
    protected function addWhereNotInSub(BoolQuery $boolQuery, array $where, string $boolean)
    {
        throw new NotImplementedException('Method "' . $where['type'] . '" not implemented');
    }

    /**
     * make a addWhere in sub-select clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param   string    $boolean
     *
     * @return string
     * @throws \Exception
     */
    protected function addWhereNull(BoolQuery $boolQuery, array $where, string $boolean)
    {
        $where['operator'] = '=';
        $where['value']    = Mapping::NULL_VALUE;

        $this->addWhereBasic($boolQuery, $where, $boolean);
    }

    /**
     * make a addWhere in sub-select clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param   string    $boolean
     *
     * @return string
     */
    protected function addWhereNotNull(BoolQuery $boolQuery, array $where, string $boolean)
    {
        throw new NotImplementedException('Method "' . $where['type'] . '" not implemented');
    }

    /**
     * make a "between" addWhere clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param  string    $boolean
     *
     * @return string
     * @throws \Exception
     */
    protected function addWhereBetween(BoolQuery $boolQuery, array $where, string $boolean)
    {
        if (count($where['values']) != 2) {
            throw new \Exception('2nd argument of whereBetween must be an array with 2 values');
        }

        $where['column'] = $this->removeTableName($where['column']);

        $values = array_values($where['values']);
        $this->addToBoolQuery(
            new Range($where['column'], [
                'gte' => $values[0],
                'lte' => $values[1],
            ]),
            $boolQuery,
            $boolean,
            $where['not']
        );
    }

    /**
     * make a "where date" clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param  string    $boolean
     *
     * @return string
     * @throws \Exception
     */
    protected function addWhereDate(BoolQuery $boolQuery, array $where, string $boolean)
    {
        $where['format'] = 'yyyy-MM-dd';

        $this->addWhereBasic($boolQuery, $where, $boolean);
    }

    /**
     * make a "where time" clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param  string    $boolean
     *
     * @return string
     */
    protected function addWhereTime(BoolQuery $boolQuery, array $where, string $boolean)
    {
        throw new NotImplementedException('Method "whereTime" not implemented');
    }

    /**
     * make a "where day" clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param  string    $boolean
     *
     * @return string
     */
    protected function addWhereDay(BoolQuery $boolQuery, array $where, string $boolean)
    {
        throw new NotImplementedException('Method "whereday" not implemented');
    }

    /**
     * make a "where month" clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param  string    $boolean
     *
     * @return string
     */
    protected function addWhereMonth(BoolQuery $boolQuery, array $where, string $boolean)
    {
        throw new NotImplementedException('Method "whereMonth" not implemented');
    }

    /**
     * make a "where year" clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param  string    $boolean
     *
     * @return string
     */
    protected function addWhereYear(BoolQuery $boolQuery, array $where, string $boolean)
    {
        throw new NotImplementedException('Method "whereYear" not implemented');
    }

    /**
     * make a addWhere clause comparing two columns..
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param string    $boolean
     *
     * @return string
     */
    protected function addWhereColumn(BoolQuery $boolQuery, array $where, string $boolean)
    {
        throw new NotImplementedException('Method "whereColumn" not implemented');
    }

    /**
     * make a nested addWhere clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param  string    $boolean
     *
     * @return void
     */
    protected function addWhereNested(BoolQuery $boolQuery, array $where, string $boolean)
    {
        // Here we will calculate what portion of the string we need to remove. If this
        // is a join clause query, we need to remove the "on" portion of the SQL and
        // if it is a normal query we need to take the leading "where" of queries.

        if (!$where['query']->wheres) {
            return;
        }

        if ($boolean == 'or') {
            $boolQuery->addShould($this->makeWheres($where['query']->wheres, $where['boolean']));
        } else {
            $boolQuery->addMust($this->makeWheres($where['query']->wheres, $where['boolean']));
        }
    }

    /**
     * make a addWhere condition with a sub-select.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param  string    $boolean
     *
     * @return string
     */
    protected function addWhereSub(BoolQuery $boolQuery, array $where, string $boolean)
    {
        throw new NotImplementedException('Method "' . $where['type'] . '" not implemented');
    }

    /**
     * make a addWhere exists clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param  string    $boolean
     *
     * @return string
     */
    protected function addWhereExists(BoolQuery $boolQuery, array $where, string $boolean)
    {
        throw new NotImplementedException('Method "' . $where['type'] . '" not implemented');
    }

    /**
     * make a addWhere exists clause.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param  string    $boolean
     *
     * @return string
     */
    protected function addWhereNotExists(BoolQuery $boolQuery, array $where, string $boolean)
    {
        throw new NotImplementedException('Method "' . $where['type'] . '" not implemented');
    }

    /**
     * make a addWhere row values condition.
     *
     * @param BoolQuery $boolQuery
     * @param  array     $where
     * @param  string    $boolean
     *
     * @return string
     */
    protected function addWhereRowValues(BoolQuery $boolQuery, array $where, string $boolean)
    {
        throw new NotImplementedException('Method "' . $where['type'] . '" not implemented');
    }

    /**
     * make the "order by" portions of the query.
     *
     * @param Builder $builder
     * @param Query   $query
     *
     * @return string
     */
    protected function makeOrders(Builder $builder, Query $query)
    {
        $sort = [];
        foreach ($builder->orders as $order) {
            $sort[$order['column']] = $order['direction'];
        }

        $query->setSort($sort);
    }

    /**
     * Determine if the grammar supports savepoints.
     *
     * @return bool
     */
    public function supportsSavepoints()
    {
        return false;
    }

    /**
     * Remove the leading boolean from a statement.
     *
     * @param  string $value
     *
     * @return string
     */
    protected function removeLeadingBoolean($value)
    {
        return preg_replace('/and |or /i', '', $value, 1);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function removeTableName(string $name)
    {
        $parts = explode('.', $name);

        if (count($parts) > 1) {
            unset($parts[0]);
        }

        return implode('.', $parts);
    }

    /**
     * Get the grammar specific operators.
     *
     * @return array
     */
    public function getOperators()
    {
        return $this->operators;
    }
}
