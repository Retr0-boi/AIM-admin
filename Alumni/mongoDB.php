<?php

require $_SERVER['DOCUMENT_ROOT'] . '/AIM/vendor/autoload.php';

// require 'vendor/autoload.php';

$client = new MongoDB\Client("mongodb://localhost:27017");

$database = $client->AIM;


?>