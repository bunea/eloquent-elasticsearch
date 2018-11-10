<?php

namespace EloquentElastic\Tests;

use EloquentElastic\ElasticsearchConnector;
use PHPUnit\Framework\TestCase;

class ElasticsearchConnectorTest extends TestCase
{
    public function testElasticSearchCallConnectWithProperArguments()
    {
        $config = [
            'default' => [
                'hosts' => 'foo'
            ]
        ];

        $connector = new ElasticsearchConnector();
        $result = $connector->connect($config);

        $this->assertInstanceOf(\Elastica\Client::class, $result);
    }
}
