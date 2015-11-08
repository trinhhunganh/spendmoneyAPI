<?php
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\DI\FactoryDefault;

use Phalcon\Http\Response;
use Phalcon\Config;
include('Mail.php');

// Use Loader() to autoload our models and API controllers
$loader = new Loader();
$loader->registerNamespaces(
	array(
   		'API' => __DIR__ . '/api/'
	), true
);
$loader->registerDirs(
    array(
        __DIR__ . '/models/'
    )
);

$loader->register();
// Initialize error handler
$error_handler = new API\Error_handler();

// Initialize authentication handler
$authentication_handler = new API\Authentication_handler();

// Initialize DB Adapter
$database_adapter =  new API\Database_adapter();

// Initialize API controller
$api_controller = new API\Spendmoneyapi();

// Load configuration file
require "config.php";
$config = new Config($settings);

// Set up system mail headers
$headers["From"]    = $config->mail->from; 
$headers["Subject"] = $config->mail->subject; 
$headers["Content-type"] = $config->mail->content_type;

// Create the mail object using the Mail::factory method 
$mail_object = Mail::factory("smtp", $config->mail->system_mail_params);

$di = new FactoryDefault();

// Set up the database service
$di->set('db', function () use ($config, $database_adapter) {
    return $database_adapter->getDBAdapter($config);
});

// Create and bind the DI to the application
$app = new Micro($di);

// Define the routes here
// Retrieve account info
$app->get('/account/{id:[0-9]+}', function ($id) use ($app, $api_controller, $error_handler, $authentication_handler) {
	$auth_id = isset($_GET['auth_id']) ? $_GET['auth_id'] : null;
   	
   	if(!$authentication_handler->checkAuthenticationID($auth_id))
   		return $error_handler->throw_error("ERROR", "FAILED TO AUTHENTICATE");

   	return $api_controller->getAccountById($id, $app, $error_handler);
});

// Retrieve account balance
$app->get('/account/{id:[0-9]+}/balance', function ($id) use ($app, $api_controller, $error_handler, $authentication_handler) {
	$auth_id = isset($_GET['auth_id']) ? $_GET['auth_id'] : null;
	
   	if(!$authentication_handler->checkAuthenticationID($auth_id))
   		return $error_handler->throw_error("ERROR", "FAILED TO AUTHENTICATE");

   	return $api_controller->getAccountBalanceById($id, $app, $error_handler);
});

// Spend money from an account
$app->put('/account/{id:[0-9]+}/spend', function ($id) use ($app, $api_controller, $mail_object, $headers, $error_handler, $authentication_handler) {
	$post_params = $app->request->getJsonRawBody();
	$auth_id = isset($post_params->auth_id) ? $post_params->auth_id : null;
	
	if(!$authentication_handler->checkAuthenticationID($auth_id))
   		return $error_handler->throw_error("ERROR", "FAILED TO AUTHENTICATE");

	return $api_controller->spendMoneyFromAccount( $app, $id, $mail_object, $headers, $error_handler, $post_params );
});

$app->handle();