<?php

include_once("db.php");

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("AIM");
$userCollection = $database->selectCollection("users");
$conversationCollection = $database->selectCollection("conversations");
$messageCollection = $database->selectCollection("messages");
$postsCollections = $database->selectCollection("posts");

file_put_contents('php://stdout', file_get_contents('php://input'));

// CREATION VALIDATION
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


function fetchConversations($mongoId)
{
    global $conversationCollection, $messageCollection, $userCollection;

    // Check if collections are initialized
    if (!$conversationCollection || !$messageCollection || !$userCollection) {
        return ['success' => false, 'error' => 'Collections not properly initialized'];
    }

    try {
        // Find conversations where the user is a participant excluding the user's own ID
        $cursor = $conversationCollection->find(['participants' => $mongoId]);

        $conversations = iterator_to_array($cursor);
        // For each conversation, fetch the latest message
        foreach ($conversations as &$conversation) {
            $latestMessage = $messageCollection->findOne(
                ['conversation_id' => $conversation['_id']],
                ['sort' => ['timestamp' => -1]]
            );

            if ($latestMessage) {
                $conversation['latest_message'] = $latestMessage['content'];
                $conversation['latest_timestamp'] = $latestMessage['timestamp'];
            }

            // Fetch user details for each participant (only name and profile picture)
            $userDetails = [];
            foreach ($conversation['participants'] as $participantId) {

                if ($participantId != $mongoId) {
                    $userDetail = $userCollection->findOne(
                        ['_id' => new MongoDB\BSON\ObjectId($participantId)],
                        ['projection' => ['name' => 1, 'profile_picture' => 1]]
                    );

                    // Check if user details are not null before adding to the array
                    if ($userDetail !== null) {
                        $userDetails[] = $userDetail;
                    }
                }
            }


            // Add user details to conversation
            $conversation['user_details'] = $userDetails;
        }

        return ['success' => true, 'conversations' => $conversations];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to fetch conversations'];
    }
}


if ($_SERVER["REQUEST_METHOD"] == "GET" && (isset($_GET['action']) && $_GET['action'] == 'getConversations')) {
    $mongoId = $_GET['mongoId'];

    if (!empty($mongoId)) {
        $result = fetchConversations($mongoId);

        // Check if collections were not properly initialized
        if (!$result['success'] && isset($result['error']) && $result['error'] === 'Collections not properly initialized') {
            echo json_encode(['success' => false, 'error' => 'Collections not properly initialized']);
            exit;
        }

        // Check if there are no conversations
        if (empty($result['conversations'])) {
            echo json_encode(['success' => true, 'conversations' => []]);
            exit;
        }

        echo json_encode($result);
        exit;
    } else {
        $response = ["success" => false, "error" => "Invalid mongoId"];
        echo json_encode($response);
        exit;
    }
}





function initiateConversation($senderId, $recipientId)
{
    global $conversationCollection;

    try {
        // Check if a conversation already exists between the sender and recipient
        $existingConversation = $conversationCollection->findOne([
            'participants' => ['$all' => [$senderId, $recipientId]],
        ]);

        if ($existingConversation) {
            return ['success' => true];
        }

        // If no existing conversation, create a new one
        $newConversation = [
            'participants' => [$senderId, $recipientId],
        ];

        $insertResult = $conversationCollection->insertOne($newConversation);

        if ($insertResult->getInsertedCount() > 0) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Failed to initiate conversation'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to initiate conversation'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_GET['action']) && $_GET['action'] == 'initiateConversation')) {
    $senderId = $_POST['senderId'];
    $recipientId = $_POST['recipientId'];

    if (!empty($senderId) && !empty($recipientId)) {
        $result = initiateConversation($senderId, $recipientId);

        echo json_encode($result);
        exit;
    } else {
        $response = ["success" => false, "error" => "Invalid senderId or recipientId"];
        echo json_encode($response);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_GET['action']) && $_GET['action'] == 'register')) {
    $data = json_decode(file_get_contents("php://input"), true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        $response = ["success" => false, "error" => "Invalid JSON format"];
        echo json_encode($response);
        exit;
    }

    if (validateData($data)) {
        $insertResult = $userCollection->insertOne($data);
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

    $user = $userCollection->findOne(['email' => $userEmail]);

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
        $user = $userCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($mongoId)]);
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

    $users = $userCollection->find($query);

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
    $mongoId = $_GET['mongoId'];

    $cursor = $userCollection->find([
        'batch_from' => $batchFrom,
        'batch_to' => $batchTo,
        'department' => $department,
        'program' => $program,
        'account_status' => 'approved',
        '_id' => ['$ne' => new MongoDB\BSON\ObjectId($mongoId)]
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
