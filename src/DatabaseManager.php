<?php

namespace EloquentElastic;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Database\ConnectionResolverInterface;

class DatabaseManager implements ConnectionResolverInterface
{

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The database connection factory instance.
     *
     * @var ConnectionFactory
     */
    protected $factory;

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * Create a new database manager instance.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @param  ConnectionFactory                  $factory
     * @return void
     */
    public function __construct($app, ConnectionFactory $factory)
    {
        $this->app     = $app;
        $this->factory = $factory;
    }

    /**
     * Get a database connection instance.
     *
     * @param  string $name
     * @return ElasticsearchConnection
     */
    public function connection($name = null)
    {
        $database = $this->parseConnectionName($name);

        $name = $name ?: $database;

        // If we haven't created this connection, we'll create it based on the config
        // provided in the application. Once we've created the connections we will
        // set the "fetch mode" for PDO which determines the query return types.
        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->configure(
                $this->makeConnection($database), null
            );
        }

        return $this->connections[$name];
    }

    /**
     * Parse the connection into an array of the name and read / write type.
     *
     * @param  string $name
     * @return string
     */
    protected function parseConnectionName($name)
    {
        $name = $name ?: $this->getDefaultConnection();

        return $name;
    }

    /**
     * Make the database connection instance.
     *
     * @param  string $name
     * @return \EloquentElastic\ElasticsearchConnection
     */
    protected function makeConnection($name)
    {
        $config = $this->configuration($name);

        return $this->factory->make($config, $name);
    }

    /**
     * Get the configuration for a connection.
     *
     * @param  string $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration($name)
    {
        $name = $name ?: $this->getDefaultConnection();

        // To get the database connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        $connections = $this->app['config']['elasticsearch.connections'];

        $config = Arr::get($connections, $name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Config for connection [{$name}] not configured in elasticsearch config.");
        }

        return $config;
    }

    /**
     * Prepare the database connection instance.
     *
     * @param  \EloquentElastic\ElasticsearchConnection $connection
     * @param  string                                     $type
     * @return \EloquentElastic\ElasticsearchConnection
     */
    protected function configure(ElasticsearchConnection $connection, $type = null)
    {
        // First we'll set the fetch mode and a few other dependencies of the database
        // connection. This method basically just configures and prepares it to get
        // used by the application. Once we're finished we'll return it back out.
        if ($this->app->bound('events')) {
            $connection->setEventDispatcher($this->app['events']);
        }

        return $connection;
    }

    /**
     * Disconnect from the given database and remove from local cache.
     *
     * @param  string $name
     * @return void
     */
    public function purge($name = null)
    {
        $name = $name ?: $this->getDefaultConnection();

        $this->disconnect($name);

        unset($this->connections[$name]);
    }

    /**
     * Disconnect from the given database.
     *
     * @param  string $name
     * @return void
     */
    public function disconnect($name = null)
    {
        $name = $name ?: $this->getDefaultConnection();

        if (isset($this->connections[$name])) {
            $this->connections[$name]->disconnect();
        }
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->app['config']['elasticsearch.default'];
    }

    /**
     * Set the default connection name.
     *
     * @param  string $name
     * @return void
     */
    public function setDefaultConnection($name)
    {
        $this->app['config']['elasticsearch.default'] = $name;
    }

    /**
     * Return all of the created connections.
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }

}
