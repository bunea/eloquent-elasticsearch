<?php

namespace EloquentElastic;

use Elastica\ResultSet;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor as IlluminateProcessor;

class Processor extends IlluminateProcessor
{

    /**
     * Process the results of a "select" query.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  ResultSet                          $results
     *
     * @return array
     */
    public function processSelect(Builder $query, $results) : array
    {
        $data = $results->getResults();

        if (!$data) {
            $agg = $results->getResponse()->getData();

            if (isset($agg['aggregations']['filter']['aggregate']['buckets'])) {
                return $agg['aggregations']['filter']['aggregate']['buckets'];
            }

            if (isset($agg['aggregations']['aggregate']['buckets'])) {
                return $agg['aggregations']['aggregate']['buckets'];
            }
        }

        $documents = [];

        foreach ($data as $record) {
            $documents[] = $record->getData();
        }

        return $documents;
    }

    /**
     * Process the results of a column listing query.
     *
     * @param  array $results
     *
     * @return array
     */
    public function processColumnListing($results)
    {
        return $results;
    }

}
