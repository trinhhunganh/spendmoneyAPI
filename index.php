<?php

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\DI\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Db\Adapter\Pdo\Postgresql as PdoPostgresql;
use Phalcon\Db\Adapter\Pdo\Sqlite as PdoSqlite;
use Phalcon\Db\Adapter\Pdo\Oracle as PdoOracle;
use Phalcon\Http\Response;
use Phalcon\Config;
include('Mail.php');

// API Error handler
function throw_error($status, $message)
{
	$response = new Response();
	$response->setJsonContent(
        array(
            $status => $message
        )
    );
    return $response;
}

// API Authenticate request
function checkAuthenticationID($auth_id)
{
	if( is_null($auth_id) || $auth_id != 'TASK24H-TEST' )
    {
    	return false;
    }
    return true;
}

// Adapter handler
function getDBAdapter($configuration)
{
	$adapter = $configuration->database->adapter;
	switch ($adapter) {
		case 'mysql':
			return new PdoMysql(
		        (array)$configuration->database->config_params
		    );
			break;
		case 'postgresql':
			return new PdoPostgresql(
		        (array)$configuration->database->config_params
		    );
			break;
		case 'sqlite':
			return new PdoSqlite(
		        (array)$configuration->database->config_params
		    );
			break;
		case 'oracle':
			return new PdoOracle(
		        (array)$configuration->database->config_params
		    );
			break;	
		default:
			return new PdoMysql(
		        (array)$configuration->database->config_params
		    );
			break;
	}
}

// Load configuration file
require "config.php";
$config = new Config($settings);

// Set up system mail headers
$headers["From"]    = $config->mail->from; 
$headers["Subject"] = $config->mail->subject; 
$headers["Content-type"] = $config->mail->content_type;

// Create the mail object using the Mail::factory method 
$mail_object = Mail::factory("smtp", $config->mail->system_mail_params);

// Use Loader() to autoload our model
$loader = new Loader();

$loader->registerDirs(
    array(
        __DIR__ . '/models/'
    )
)->register();

$di = new FactoryDefault();

// Set up the database service
$di->set('db', function () use ($config) {
    return getDBAdapter($config);
});

// Create and bind the DI to the application
$app = new Micro($di);

// Define the routes here
// Retrieve account info
$app->get('/account/{id:[0-9]+}', function ($id) use ($app) {
	$auth_id = isset($_GET['auth_id']) ? $_GET['auth_id'] : null;
   	
   	if(!checkAuthenticationID($auth_id))
   		return throw_error("ERROR", "FAILED TO AUTHENTICATE");

   	// Create a response
    $response = new Response();

	$phql = "SELECT accountName, accountNumber, currentBalance, email FROM account WHERE id = :id:";
    $account = $app->modelsManager->executeQuery(
    	$phql, 
    	array(
    		'id' => $id
		)
	)->getFirst();
    if ($account == false) 
    {
        return throw_error( "STATUS", "ACCOUNT NOT FOUND" );
    } 
    else
    {
        $response->setJsonContent(
            array(
                'STATUS' => 'ACCOUNT FOUND',
                'DATA'   => array(
                    'NAME'   => $account->accountName,
                    'NUMBER' => $account->accountNumber,
                    'CURRENT_BALANCE' => $account->currentBalance,
                    'EMAIL' => $account->email
                )
            )
        );
    }
	return $response;
});

// Retrieve account balance
$app->get('/account/{id:[0-9]+}/balance', function ($id) use ($app) {
	$auth_id = isset($_GET['auth_id']) ? $_GET['auth_id'] : null;
	
   	if(!checkAuthenticationID($auth_id))
   		return throw_error("ERROR", "FAILED TO AUTHENTICATE");

   	// Create a response
    $response = new Response();

	$phql = "SELECT currentBalance FROM account WHERE id = :id:";
    $account = $app->modelsManager->executeQuery(
    	$phql, 
    	array(
    		'id' => $id
		)
	)->getFirst();

    if ($account == false) 
    {
        return throw_error( "STATUS", "ACCOUNT NOT FOUND" );
    } 
    else
    {
        $response->setJsonContent(
            array(
                'STATUS' => 'ACCOUNT FOUND',
                'DATA'   => array(
                    'CURRENT_BALANCE' => $account->currentBalance,
                )
            )
        );
    }

    return $response;
});

// Spend money from an account
$app->put('/account/{id:[0-9]+}/spend', function ($id) use ($app, $mail_object, $headers) {
	$post_params = $app->request->getJsonRawBody();
	$auth_id = isset($post_params->auth_id) ? $post_params->auth_id : null;
	
	if(!checkAuthenticationID($auth_id))
   		return throw_error("ERROR", "FAILED TO AUTHENTICATE");

	// Create a response
    $response = new Response();

	$phql = "SELECT currentBalance, email FROM account WHERE id = :id:";
    $account = $app->modelsManager->executeQuery(
    	$phql, 
    	array(
    		'id' => $id
		)
	)->getFirst();
    if ($account == false) 
    {
        return throw_error( "STATUS", "ACCOUNT NOT FOUND" );
    } 
    else
    {
        $spend_amount = isset($post_params->amount) ? intval($post_params->amount) : null;
        if( is_null($spend_amount) || $spend_amount == 0)
		{
			return throw_error( "ERROR", "INVALID AMOUNT" );
		}
		else
		{
			//check if amount higher than current balance
			if($spend_amount > $account->currentBalance)
			{
				return throw_error( "ERROR", "SPEND AMOUNT EXCEEDS CURRENT BALANCE" );
			}
			else
			{
				$phql = "UPDATE account SET currentBalance = :newbalance: WHERE id = :id:";
			    $status = $app->modelsManager->executeQuery($phql, array(
			        'id' => $id,
			        'newbalance' => $account->currentBalance - $spend_amount
			    ));

			    //update database successfully
			    if ($status->success() == true) 
			    {
			        $response->setJsonContent(
			            array(
			                'STATUS' => "SUCCESS"
			            )
			        );

			        //send mail
					$message = 'Your old balance is <b>' . $account->currentBalance . '</b><br>';
					$message .= 'You have spent <b>' . $spend_amount . '</b> from your account<br>';
					$message .= 'Your current balance is <b>' . ($account->currentBalance - $spend_amount) . '</b>';
					$recipient = $account->email;
					$headers["To"] = $account->email;
					$mail_object->send($recipient, $headers, $message);
					if (PEAR::isError($mail_object)) 
					{
					    $response->setJsonContent(
				            array(
				                'STATUS' => "SUCCESS",
				                'SEND MAIL STATUS' => "FAIL"
				            )
			        	);
					} 
					else 
					{
					    $response->setJsonContent(
				            array(
				                'STATUS' => "SUCCESS",
				                'SEND MAIL STATUS' => "SUCCESS"
				            )
			        	);
					}
					//
			    } 
			    else 
			    {
			        // Change the HTTP status
			        $response->setStatusCode(409, "Conflict");

			        $errors = array();
			        foreach ($status->getMessages() as $message) {
			            $errors[] = $message->getMessage();
			        }

			        $response->setJsonContent(
			            array(
			                'STATUS'   => 'ERROR',
			                'MESSAGES' => $errors
			            )
			        );
			        //
			    }
			}
		}
    }
    return $response;
});

$app->handle();