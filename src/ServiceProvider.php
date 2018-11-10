<?php

namespace EloquentElastic;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Schema;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/config/elasticsearch.php';
        $this->publishConfig($configPath);

        $connectionFactory = new ConnectionFactory(new Container);
        $resolver          = new DatabaseManager(app(), $connectionFactory);
        Model::setConnectionResolver($resolver);

        Schema::defaultStringLength(191);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    protected function getConfigPath()
    {
        return config_path('elasticsearch.php');
    }

    protected function publishConfig($configPath)
    {
        $this->publishes([$configPath => config_path('elasticsearch.php')], 'config');
    }
}
