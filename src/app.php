<?php

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\RoutingServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use AcmeWebManager\Storage\FileSystem;
use Symfony\Component\Finder\Finder;

$app = new Application();
$app->register(new RoutingServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());

$app->register(new \SoloCreation\AcmePhpServiceProvider(), array(
    'acmephpc.config' => array(
        'storage' => function ($c) {

            $storageConfig = $c['params']['storage'];

            $factory = new \Octopuce\Acme\Storage\Factory(array(
                'filesystem' => function () use ($storageConfig) {
                    return new FileSystem($storageConfig['filesystem'], new Finder);
                },
                'database' => function () use ($storageConfig) {
                    return new \Octopuce\Acme\Storage\DoctrineDbal(
                        $storageConfig['database']['dsn'],
                        $storageConfig['database']['table_prefix']
                    );
                },
            ));

            return $factory->create($storageConfig['type']);
        },
        'params' => array(
            'api' => 'https://acme-staging.api.letsencrypt.org',
            'storage' => array(
                'type' => 'filesystem',
                'filesystem' => __DIR__.'/../var/storage',
            ),
            'account' => 'mathieu.duplouy@gmail.com',
        ),
    ),
));

return $app;
