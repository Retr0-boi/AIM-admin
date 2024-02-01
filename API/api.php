<?php

include_once("db.php");

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("AIM");
$collection = $database->selectCollection("users");

file_put_contents('php://stdout', file_get_contents('php://input'));


function validateData($data)
{
    $requiredFields = [
        'department',
        'program',
        'batch_from',
        'batch_to',
        'name',
        'DOB',
        'email',
        'password',
        'account_status',
        'identification',
        'updation_date',
        'updation_time',
        'current_status', 
    ];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === null) {
            return false;
        }
    }

    return validateAdditionalFields($data); // Also validate additional fields
}

function validateAdditionalFields($data)
{
    if (isset($data['current_status']) && $data['current_status'] === 'Student') {
        return isset($data['current_institution'])
            && isset($data['programme'])
            && isset($data['expected_year_of_passing']);
    }

    if (in_array($data['current_status'], ['Working (Govt)', 'Working (Non Govt)', 'Entrepreneur'])) {
        return isset($data['current_organisation'])
            && isset($data['designation']);
    }

    // Add validations for other current_status values as needed

    // If no specific conditions are met, return true
    return true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_GET['action']) && $_GET['action'] == 'register')) {
    $data = json_decode(file_get_contents("php://input"), true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        $response = ["success" => false, "error" => "Invalid JSON format"];
        echo json_encode($response);
        exit;
    }

    if (validateData($data)) {
        $insertResult = $collection->insertOne($data);
        $response = ["success" => true];
        echo json_encode($response);
        exit;
    } else {
        $response = ["success" => false, "error" => "Invalid data"];
        echo json_encode($response);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_GET['action']) && $_GET['action'] == 'login')) {
    $loginData = json_decode(file_get_contents("php://input"), true);

    if ($loginData === null && json_last_error() !== JSON_ERROR_NONE) {
        $response = ["success" => false, "error" => "Invalid JSON format"];
        echo json_encode($response);
        exit;
    }

    $userEmail = $loginData['email'];
    $userPassword = $loginData['password'];

    $user = $collection->findOne(['email' => $userEmail]);

    if ($user && $userPassword === $user['password'] && $user['account_status'] === 'approved') {
        $response = [
            "success" => true,
            "mongo_id" => (string)$user['_id'],
            "name" => (string)$user['name']
        ];
        echo json_encode($response);
        exit;
    } else {
        $response = ["success" => false, "error" => "Invalid email or password or not approved"];
        echo json_encode($response);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && (isset($_GET['action']) && $_GET['action'] == 'getUserData')) {
    $mongoId = $_GET['mongoId'];

    if (!empty($mongoId)) {
        $user = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($mongoId)]);
    } else {
        $response = ["success" => false, "error" => "Invalid mongoId"];
        echo json_encode($response);
        exit;
    }

    if ($user) {
        $response = ["success" => true, "userData" => $user];
        echo json_encode($response);
        exit;
    } else {
        $response = ["success" => false, "error" => "User data not found"];
        echo json_encode($response);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_GET['action']) && $_GET['action'] == 'searchUsers')) {
    $searchCriteria = json_decode(file_get_contents("php://input"), true);

    if ($searchCriteria === null && json_last_error() !== JSON_ERROR_NONE) {
        $response = ["success" => false, "error" => "Invalid JSON format"];
        echo json_encode($response);
        exit;
    }

    $query = [];

    foreach ($searchCriteria as $key => $value) {
        $query[$key] = $value;
    }

    $users = $collection->find($query);

    $usersArray = iterator_to_array($users);

    if (!empty($usersArray)) {
        $response = ["success" => true, "usersData" => $usersArray];
        echo json_encode($response);
        exit;
    } else {
        $response = ["success" => false, "error" => "No matching users found"];
        echo json_encode($response);
        exit;
    }
}
if ($_SERVER["REQUEST_METHOD"] == "GET" && (isset($_GET['action']) && $_GET['action'] == 'searchUsers')) {
    $batchFrom = $_GET['batchFrom'];
    $batchTo = $_GET['batchTo'];
    $department = $_GET['department'];
    $program = $_GET['program'];

    $cursor = $collection->find([
        'batch_from' => $batchFrom,
        'batch_to' => $batchTo,
        'department' => $department,
        'program' => $program,
        'account_status'=>'approved',
    ]);

    $matchingUsers = iterator_to_array($cursor);
    if (!empty($matchingUsers)) {
        $response = ["success" => true, "matchedUsers" => $matchingUsers];
        echo json_encode($response);
        exit;
    } else {
        $response = ["success" => false, "error" => "No matching users found"];
        echo json_encode($response);
        exit;
    }
}
