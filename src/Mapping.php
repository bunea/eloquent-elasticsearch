<?php

namespace EloquentElastic;

class Mapping
{

    const NULL_VALUE = -9999;

    /**
     * @param string $type
     *
     * @return array
     */
    public static function translatable(string $type)
    {
        $nameProperties = [];

        foreach (config('translatable.locales') as $locale) {
            $nameProperties[$locale] = [
                'type' => $type,
            ];
        }

        return $nameProperties;
    }
}
