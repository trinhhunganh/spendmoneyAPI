<?php

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\DI\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Http\Response;
include('Mail.php');

//Set system mail account
$headers["From"]    = "spendmoneyapi.no.reply@gmail.com"; 
$headers["Subject"] = "Spend money notification"; 
$headers["Content-type"] = "text/html; charset=iso-8859-1";
$params["host"] = "ssl://smtp.gmail.com"; 
$params["port"] = "465"; 
$params["auth"] = true; 
$params["username"] = "spendmoneyapi.no.reply@gmail.com"; 
$params["password"] = "12345678api"; 

// Create the mail object using the Mail::factory method 
$mail_object = Mail::factory("smtp", $params);

// Use Loader() to autoload our model
$loader = new Loader();

$loader->registerDirs(
    array(
        __DIR__ . '/models/'
    )
)->register();

$di = new FactoryDefault();

// Set up the database service
$di->set('db', function () {
    return new PdoMysql(
        array(
            "host"     => "localhost:3770",
            "username" => "root",
            "dbname"   => "spendmoneydb"
        )
    );
});

// Create and bind the DI to the application
$app = new Micro($di);

// Define the routes here
// Retrieve account info
$app->get('/account/{id:[0-9]+}', function ($id) use ($app) {
	$auth_id = isset($_GET['auth_id']) ? $_GET['auth_id'] : null;
	// Create a response
    $response = new Response();
   	//check authentication
    if(is_null($auth_id) || $auth_id != 'TASK24H-TEST')
    {
    	$response->setJsonContent(
            array(
                'error' => 'FAILED TO AUTHENTICATE'
            )
        );
    }
    else
    {
    	$phql = "SELECT accountName, accountNumber, currentBalance, email FROM account WHERE id = :id:";
	    $account = $app->modelsManager->executeQuery(
	    	$phql, 
	    	array(
        		'id' => $id
    		)
    	)->getFirst();
	    if ($account == false) 
	    {
	        $response->setJsonContent(
	            array(
	                'status' => 'ACCOUNT NOT FOUND'
	            )
	        );
	    } 
	    else
	    {
	        $response->setJsonContent(
	            array(
	                'status' => 'ACCOUNT FOUND',
	                'data'   => array(
	                    'name'   => $account->accountName,
	                    'number' => $account->accountNumber,
	                    'current_balance' => $account->currentBalance,
	                    'email' => $account->email
	                )
	            )
	        );
	    }
	}
	return $response;
});

// Retrieve account balance
$app->get('/account/{id:[0-9]+}/balance', function ($id) use ($app) {
	$auth_id = isset($_GET['auth_id']) ? $_GET['auth_id'] : null;
	// Create a response
    $response = new Response();
   	//check authentication
    if(is_null($auth_id) || $auth_id != 'TASK24H-TEST')
    {
    	$response->setJsonContent(
            array(
                'error' => 'FAILED TO AUTHENTICATE'
            )
        );
    }
    else
    {
    	$phql = "SELECT currentBalance FROM account WHERE id = :id:";
	    $account = $app->modelsManager->executeQuery(
	    	$phql, 
	    	array(
        		'id' => $id
    		)
    	)->getFirst();
	    if ($account == false) 
	    {
	        $response->setJsonContent(
	            array(
	                'status' => 'ACCOUNT NOT FOUND'
	            )
	        );
	    } 
	    else
	    {
	        $response->setJsonContent(
	            array(
	                'status' => 'ACCOUNT FOUND',
	                'data'   => array(
	                    'current_balance' => $account->currentBalance,
	                )
	            )
	        );
	    }
    }
    return $response;
});

// Spend money from an account
$app->put('/account/{id:[0-9]+}/spend', function ($id) use ($app, $mail_object, $headers) {
	$post_params = $app->request->getJsonRawBody();
	$auth_id = isset($post_params->auth_id) ? $post_params->auth_id : null;
	// Create a response
    $response = new Response();
   	//check authentication
    if( is_null($auth_id) || $auth_id != 'TASK24H-TEST')
    {
    	$response->setJsonContent(
            array(
                'error' => 'FAILED TO AUTHENTICATE'
            )
        );
    }
    else
    {
    	$phql = "SELECT currentBalance, email FROM account WHERE id = :id:";
	    $account = $app->modelsManager->executeQuery(
	    	$phql, 
	    	array(
        		'id' => $id
    		)
    	)->getFirst();
	    if ($account == false) 
	    {
	        $response->setJsonContent(
	            array(
	                'status' => 'ACCOUNT NOT FOUND'
	            )
	        );
	    } 
	    else
	    {
	        $spend_amount = isset($post_params->amount) ? intval($post_params->amount) : null;
	        if( is_null($spend_amount) || $spend_amount == 0)
			{
				$response->setJsonContent(
		            array(
		                'error' => 'INVALID AMOUNT'
		            )
	        	);
			}
			else
			{
				//check if amount higher than current balance
				if($spend_amount > $account->currentBalance)
				{
					$response->setJsonContent(
			            array(
			                'error' => 'SPEND AMOUNT EXCEEDS CURRENT BALANCE'
			            )
	        		);
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
    }
    return $response;
});

$app->handle();