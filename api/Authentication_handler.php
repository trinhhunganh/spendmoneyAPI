<?php
namespace API;

class Authentication_handler
{
    // API Authenticate request
    function checkAuthenticationID($auth_id)
    {
        if( is_null($auth_id) || $auth_id != 'TASK24H-TEST' )
        {
            return false;
        }
        return true;
    }
}