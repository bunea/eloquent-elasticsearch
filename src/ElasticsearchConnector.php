<?php

namespace EloquentElastic;

use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;

use Elastica\Client;

class ElasticsearchConnector extends Connector implements ConnectorInterface
{

    /**
     * Establish a database connection.
     *
     * @param  array $config
     *
     * @return Client
     */
    public function connect(array $config)
    {
        return new Client([
            'servers' => $config
        ]);
    }

}
