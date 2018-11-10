<?php

namespace EloquentElastic;

use Illuminate\Database\Connectors\ConnectionFactory as IlluminateConnectionFactory;
use Elastica\Client;

class ConnectionFactory extends IlluminateConnectionFactory
{

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Establish a PDO connection based on the configuration.
     *
     * @param  array  $config
     * @param  string $name
     * @return ElasticsearchConnection
     */
    public function make(array $config, $name = null)
    {
        $config = $this->parseConfig($config, $name);

        return $this->createSingleConnection($config);
    }

    /**
     * Parse and prepare the database configuration.
     *
     * @param  array  $config
     * @param  string $name
     * @return array
     */
    protected function parseConfig(array $config, $name)
    {
        $servers = explode(',', $config['hosts']);
        $servers = array_map(function($item) {
            $parts = explode(':', trim($item));
            return [
                'host' => $parts[0],
                'port' => $parts[1] ?? 9200
            ];
        }, $servers);

        return $servers;
    }

    /**
     * Create a single database connection instance.
     *
     * @param  array $config
     * @return ElasticsearchConnection
     */
    protected function createSingleConnection(array $config)
    {
        return $this->getConnection(
            $config,
            $this->createConnector($config)
        );
    }

    /**
     * Create a connector instance based on the configuration.
     *
     * @param  array $config
     * @return Client
     *
     * @throws \InvalidArgumentException
     */
    public function createConnector(array $config)
    {
        $key = "db.connector.elasticsearch";

        if ($this->container->bound($key)) {
            return $this->container->make($key);
        }

        return (new ElasticsearchConnector())->connect($config);
    }

    /**
     * Create a new connection instance.
     *
     * @param  array  $config
     * @param Client $connector
     *
     * @return ElasticsearchConnection
     */
    protected function getConnection(array $config, Client $connector)
    {
        return new ElasticsearchConnection($config, $connector);
    }

}
