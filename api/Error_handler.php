<?php
namespace API;

use Phalcon\Http\Response;

class Error_handler
{
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
}