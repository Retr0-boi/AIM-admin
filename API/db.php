<?php

require '../vendor/autoload.php'; // Include the Composer autoloader

use MongoDB\Client;

$mongoUri = "mongodb://localhost:27017";
$databaseName = "AIM";

$mongoClient = new Client($mongoUri);

$database = $mongoClient->selectDatabase($databaseName);

$collection = $database->selectCollection("users");

?>
