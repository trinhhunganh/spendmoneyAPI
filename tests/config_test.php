<?php

$settings = array(
    "database" => array(
        "adapter"  => "mysql",
        "config_params" => array(
        	"host"     => "localhost:3770",
	        "username" => "root",
	        "dbname"   => "spendmoneydb_test"
        )
    ),
    "mail" => array(
        "from" => "spendmoneyapi.no.reply@gmail.com",
        "subject" => "Spend money notification",
		"content_type" => "text/html; charset=iso-8859-1",
		"system_mail_params" => array( 
			"host" => "ssl://smtp.gmail.com", 
		    "port" => "465", 
		    "auth" => true, 
			"username" => "spendmoneyapi.no.reply@gmail.com", 
		    "password" => "12345678api"
		)
    )
);
