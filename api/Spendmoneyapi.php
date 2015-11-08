<?php
namespace API;

use Phalcon\Http\Response;
use PEAR;

class Spendmoneyapi
{
    function getAccountById( $id, $app, $error_handler )
    {
        // Create a response
        $response = new Response();
        $response->setHeader("Content-Type", "application/json");
        $phql = "SELECT accountName, accountNumber, currentBalance, email FROM account WHERE id = :id:";
        $account = $app->modelsManager->executeQuery(
            $phql, 
            array(
                'id' => $id
            )
        )->getFirst();
        if ($account == false) 
        {
            return $error_handler->throw_error( "STATUS", "ACCOUNT NOT FOUND" );
        } 
        else
        {
            $response->setJsonContent(
                array(
                    "STATUS" => "ACCOUNT FOUND",
                    "DATA"   => array(
                        "NAME"   => $account->accountName,
                        "NUMBER" => $account->accountNumber,
                        "CURRENT_BALANCE" => $account->currentBalance,
                        "EMAIL" => $account->email
                    )
                )
            );
        }
        return $response;
    }

    // @codeCoverageIgnoreStart
    function getAccountBalanceById( $id, $app, $error_handler )
    {
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
            return $error_handler->throw_error( "STATUS", "ACCOUNT NOT FOUND" );
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
    }
    // @codeCoverageIgnoreEnd

    function spendMoneyFromAccount( $app, $id, $mail_object, $headers, $error_handler, $post_params )
    {
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
            return $error_handler->throw_error( "ERROR", "ACCOUNT NOT FOUND" );
        } 
        else
        {
            $spend_amount = isset($post_params->amount) ? intval($post_params->amount) : null;
            if( is_null($spend_amount) || $spend_amount == 0)
            {
                return $error_handler->throw_error( "ERROR", "INVALID AMOUNT" );
            }
            else
            {
                //check if amount higher than current balance
                if($spend_amount > $account->currentBalance)
                {
                    return $error_handler->throw_error( "ERROR", "SPEND AMOUNT EXCEEDS CURRENT BALANCE" );
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
                                    'SEND_MAIL_STATUS' => "FAIL"
                                )
                            );
                        } 
                        else 
                        {
                            $response->setJsonContent(
                                array(
                                    'STATUS' => "SUCCESS",
                                    'SEND_MAIL_STATUS' => "SUCCESS"
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
                        return $error_handler->throw_error( "ERROR", $errors );
                        //
                    }
                }
            }
        }
        return $response;
    }
}