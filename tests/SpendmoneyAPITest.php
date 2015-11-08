<?php
namespace Test;

use Phalcon\DI;
use Phalcon\DI\FactoryDefault;
use Phalcon\Config;
use Phalcon\Mvc\Micro;
use API;
use PEAR;

/**
 * Class UnitTest
 */
class UnitTest extends \UnitTestCase 
{
    /**
     * @covers API\Spendmoneyapi::getAccountById
     */
    public function testGetAccountById()
    {
        $di = DI::getDefault();
        $app = new Micro($di);

        // Insert new test account into test DB for unit testing (will be removed after test)
        $phql = "INSERT INTO account (accountName, accountNumber, currentBalance, email) VALUES (:accname:, :accnumber:, :curbalance:, :email:)";

        $status = $app->modelsManager->executeQuery($phql, array(
            'accname' => 'TEST ACCOUNT',
            'accnumber' => 12345678,
            'curbalance' => 10000,
            'email' => 'inthunganh@gmail.com'
        ));

        // Check if the insertion was successful
        $this->assertTrue($status->success());
        $id = $status->getModel()->id;

        // Initialize and check error handler
        $error_handler = new API\Error_handler();

        // Initialize and check API controller
        $api_controller = new API\Spendmoneyapi();

        // Test result(s) of function normal flow
        $result = $api_controller->getAccountById( $id, $app, $error_handler );

        $status = json_decode($result->getContent())->STATUS;
        $name = json_decode($result->getContent())->DATA->NAME;
        $number = json_decode($result->getContent())->DATA->NUMBER;
        $current_balance = json_decode($result->getContent())->DATA->CURRENT_BALANCE;
        $email = json_decode($result->getContent())->DATA->EMAIL;

        // Fails if cannot find an account or info is wrong
        $this->assertTrue($status == "ACCOUNT FOUND");
        $this->assertTrue($name == "TEST ACCOUNT");
        $this->assertTrue($number == 12345678);
        $this->assertTrue($current_balance == 10000);
        $this->assertTrue($email == "inthunganh@gmail.com");

        // Test result(s) of function wrong flow
        $result = $api_controller->getAccountById( 0, $app, $error_handler );

        $status = json_decode($result->getContent())->STATUS;
        // Fails if status is wrong
        $this->assertTrue($status == "ACCOUNT NOT FOUND");
        
    }

    /**
     * @covers API\Spendmoneyapi::spendMoneyFromAccount
     */
    public function testSpendMoneyFromAccount()
    {
        $di = DI::getDefault();
        $app = new Micro($di);

        // Insert new test account into test DB for unit testing (will be removed after test)
        $phql = "INSERT INTO account (accountName, accountNumber, currentBalance, email) VALUES (:accname:, :accnumber:, :curbalance:, :email:)";

        $status = $app->modelsManager->executeQuery($phql, array(
            'accname' => 'TEST ACCOUNT',
            'accnumber' => 12345678,
            'curbalance' => 10000,
            'email' => 'inthunganh@gmail.com'
        ));

        // Check if the insertion was successful
        $this->assertTrue($status->success());
        $id = $status->getModel()->id;

        // Initialize and check error handler
        $error_handler = new API\Error_handler();

        // Initialize and check API controller
        $api_controller = new API\Spendmoneyapi();

        // Load configuration file
        require "config_test.php";
        $config = new Config($settings);
        $this->assertTrue(is_a($config, "Phalcon\Config"));

        // Set up test system mail headers
        $headers["From"]    = $config->mail->from; 
        $headers["Subject"] = $config->mail->subject; 
        $headers["Content-type"] = $config->mail->content_type;

        // Create the mail object using the Mail::factory method 
        include("Mail.php");
        $mail_object = \Mail::factory("smtp", $config->mail->system_mail_params);
        

        // Test result(s) of function wrong flow
        $post_params = json_decode(json_encode(array("amount" => 100)));
        $this->assertTrue($post_params->amount == 100);
        $result = $api_controller->spendMoneyFromAccount( $app, 0, $mail_object, $headers, $error_handler, $post_params );
        $status = json_decode($result->getContent())->ERROR;
        // Fails if cannot find an account or info is wrong
        $this->assertTrue($status == "ACCOUNT NOT FOUND");

        // Test result(s) of function wrong flow invalid amount
        $post_params = json_decode(json_encode(array("amount" => 100000)));
        $this->assertTrue($post_params->amount == 100000);
        $result = $api_controller->spendMoneyFromAccount( $app, $id, $mail_object, $headers, $error_handler, $post_params );
        $status = json_decode($result->getContent())->ERROR;
        // Fails if cannot find an account or info is wrong
        $this->assertTrue($status == "SPEND AMOUNT EXCEEDS CURRENT BALANCE");

        // Test result(s) of function normal flow
        $post_params = json_decode(json_encode(array("amount" => 5000)));
        $this->assertTrue($post_params->amount == 5000);
        $result = $api_controller->spendMoneyFromAccount( $app, $id, $mail_object, $headers, $error_handler, $post_params );
        $status = json_decode($result->getContent())->STATUS;
        $mail_status = json_decode($result->getContent())->SEND_MAIL_STATUS;
        // Fails if statuses are wrong
        $this->assertTrue($status == "SUCCESS");
        $this->assertTrue($mail_status == "SUCCESS");
    }
}