<?php

use Phalcon\DI;
use Phalcon\DI\FactoryDefault;
use Phalcon\Config;
use Phalcon\Mvc\Micro;

ini_set('display_errors',1);

set_include_path(
    __DIR__ . PATH_SEPARATOR . get_include_path()
);

// Required for phalcon/incubator
include __DIR__ . "/../vendor/autoload.php";

// Use the application autoloader to autoload the classes
$loader = new \Phalcon\Loader();
$loader->registerNamespaces(
	array(
   		'API' => "../api/",
	), true
);
$loader->registerDirs(
    array(
        __DIR__,
        "../models/"
        
    )
);

$loader->register();

// Initialize DB Adapter
$database_adapter =  new API\Database_adapter();

$di = new FactoryDefault();
DI::reset();

// Load configuration file
require "config_test.php";
$config = new Config($settings);

$di->set('db', function () use ($config, $database_adapter) {
   $conn = $database_adapter->getDBAdapter($config);
   $conn->setNestedTransactionsWithSavepoints(true);
   return $conn;
}, true);

// Add any needed services to the DI here

DI::setDefault($di);
