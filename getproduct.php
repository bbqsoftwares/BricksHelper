<?php

//Set maximum execution time to 60 sec. If LEGO hasn't responded by then, too bad !
ini_set('max_execution_time', 60);

require_once("config.php");
require_once("functions_sessions.php");

//Variables
$productNumber = request_var("productnumber", 0);
$country = request_var("country", "CA");

// Create a stream
$opts = array(
  'http'=>array(
    'method'=>"GET",
    'header'=>"Accept-language: en\r\n" .
              "Cookie: csAgeAndCountry={'age':60,'countrycode':'".$country."'}\r\n"
  )
);
$context = stream_context_create($opts);

// Open the file using the HTTP headers set above
$data = file_get_contents('https://wwwsecure.us.lego.com/en-us/service/rpservice/getproduct?issalesflow=true&productnumber=' . $productNumber, false, $context);

//Return everything
header('Content-type: application/json');
echo $data;

?>