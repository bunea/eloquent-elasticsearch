<?php

namespace EloquentElastic;

use Elastica\Exception\NotImplementedException;
use Elastica\ResultSet;
use Elastica\Search;
use Illuminate\Database\ConnectionInterface;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Elastica\Client;

class ElasticsearchConnection implements ConnectionInterface
{

    private $queryGrammar = null;

    private $postProcessor = null;

    public $client;

    public $events;

    public $config;

    /**
     * ElasticsearchConnection constructor.
     *
     * @param mixed  $config
     * @param Client $client
     */
    public function __construct($config, Client $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * Begin a fluent query against a database table.
     *
     * @param  string $table
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function table($table)
    {
        throw new NotImplementedException('Method table() is not implemented on elasticsearch');
    }

    /**
     * Get a new raw query expression.
     *
     * @param  mixed $value
     *
     * @return \Illuminate\Database\Query\Expression
     */
    public function raw($value)
    {
        throw new NotImplementedException('Raw queries are not implemented on elasticsearch');
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param  string $query
     * @param  array  $bindings
     * @param  bool   $useReadPdo
     *
     * @return mixed
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        throw new NotImplementedException('selectOne() not implemented');
    }

    /**
     * Run a select statement against the database.
     *
     * @param  Search $query
     * @param  array  $bindings
     * @param  bool   $useReadPdo
     *
     * @return ResultSet
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        $items = $query->search();

        return $items;
    }

    /**
     * Run an insert statement against the database.
     *
     * @param  string $query
     * @param  array  $bindings
     *
     * @return bool
     */
    public function insert($query, $bindings = [])
    {
    }

    /**
     * Run an update statement against the database.
     *
     * @param  string $query
     * @param  array  $bindings
     *
     * @return int
     */
    public function update($query, $bindings = [])
    {
    }

    /**
     * Run a delete statement against the database.
     *
     * @param  string $query
     * @param  array  $bindings
     *
     * @return int
     */
    public function delete($query, $bindings = [])
    {
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string $query
     * @param  array  $bindings
     *
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        throw new NotImplementedException('Statements are not implemented on elasticsearch');
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string $query
     * @param  array  $bindings
     *
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        throw new NotImplementedException('Statements are not implemented on elasticsearch');
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param  string $query
     *
     * @return bool
     */
    public function unprepared($query)
    {
        throw new NotImplementedException('Unprepared queries are not implemented on elasticsearch');
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array $bindings
     *
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        throw new NotImplementedException('Bindings are not implemented on elasticsearch');
    }

    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure $callback
     * @param  int      $attempts
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
    }

    /**
     * Start a new database transaction.
     *
     * @return void
     */
    public function beginTransaction()
    {
    }

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit()
    {
    }

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollBack()
    {
    }

    /**
     * Get the number of active transactions.
     *
     * @return int
     */
    public function transactionLevel()
    {
    }

    /**
     * Execute the given callback in "dry run" mode.
     *
     * @param  \Closure $callback
     *
     * @return array
     */
    public function pretend(Closure $callback)
    {
    }

    /**
     * Get the event dispatcher used by the connection.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance on the connection.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher $events
     *
     * @return void
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Unset the event dispatcher for this connection.
     *
     * @return void
     */
    public function unsetEventDispatcher()
    {
        $this->events = null;
    }

    /**
     * Get the query grammar used by the connection.
     *
     * @return Grammar
     */
    public function getQueryGrammar()
    {
        if (!$this->queryGrammar) {
            $this->queryGrammar = new Grammar;
        }

        return $this->queryGrammar;
    }

    /**
     * Get the query post processor used by the connection.
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    public function getPostProcessor()
    {
        if (!$this->postProcessor) {
            $this->postProcessor = new Processor;
        }

        return $this->postProcessor;
    }

    /**
     * Get the database connection name.
     *
     * @return string|null
     */
    public function getName()
    {
        return 'default';
    }

    /**
     * Run a select statement against the database and returns a generator.
     *
     * @param  string $query
     * @param  array $bindings
     * @param  bool $useReadPdo
     * @return \Generator
     */
    public function cursor($query, $bindings = [], $useReadPdo = true)
    {
        throw new NotImplementedException('cursor is not implemented on elasticsearch');
    }
}
