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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['action']) && $_GET['action'] == 'postContent') {
    // Extract data from the request
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if the data is valid
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        $response = ["success" => false, "error" => "Invalid JSON format"];
        echo json_encode($response);
        exit;
    }

    // Check if subject and content are provided
    if (!isset($data['subject']) || !isset($data['content'])) {
        $response = ["success" => false, "error" => "Subject and content are required"];
        echo json_encode($response);
        exit;
    }

    // Prepare data to insert into the database
    $postData = [
        'subject' => $data['subject'],
        'content' => $data['content'],
        'posted_by' => $data['posted_by'],
        // Add other fields as needed
        'created_at' => new MongoDB\BSON\UTCDateTime(), // Add current timestamp
    ];

    // Insert data into the posts collection
    $insertResult = $postsCollections->insertOne($postData);

    if ($insertResult->getInsertedCount() > 0) {
        $response = ["success" => true, "message" => "Content posted successfully"];
        echo json_encode($response);
        exit;
    } else {
        $response = ["success" => false, "error" => "Failed to post content"];
        echo json_encode($response);
        exit;
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['action']) && $_GET['action'] == 'postJobsEvents') {
    // Extract data from the request
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if the data is valid
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        $response = ["success" => false, "error" => "Invalid JSON format"];
        echo json_encode($response);
        exit;
    }

    // Check if subject and job details are provided
    if (!isset($data['subject']) || !isset($data['job_details'])) {
        $response = ["success" => false, "error" => "Subject and job details are required"];
        echo json_encode($response);
        exit;
    }

    // Prepare data to insert into the database
    $jobData = [
        'posted_by' => $data['posted_by'],
        'type' => $data['type'],
        'subject' => $data['subject'],
        'job_details' => $data['job_details'],
        'link' => $data['link'],
        'status' => $data['status'],
        'created_at' => new MongoDB\BSON\UTCDateTime(), // Add current timestamp
    ];

    // Insert data into the jobs collection
    try {
        // Insert data into the jobs collection
        $insertResult = $postsCollections->insertOne($jobData);

        if ($insertResult->getInsertedCount() > 0) {
            $response = ["success" => true, "message" => "Job posted successfully"];
            echo json_encode($response);
            exit;
        } else {
            throw new Exception("Failed to insert job data");
        }
    } catch (Exception $e) {
        $response = ["success" => false, "error" => $e->getMessage()];
        echo json_encode($response);
        exit;
    }
}
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'getJobs') {
    // Retrieve job data from the database
    $cursor = $postsCollections->aggregate([
        ['$match' => ['type' => 'job', 'status' => 'approved']],
        ['$project' => ['subject' => 1, 'job_details' => 1, 'created_at' => 1, 'link' => 1]],
    ]);
    // Convert the cursor to an array
    $jobs = iterator_to_array($cursor);

    if (!empty($jobs)) {
        // Convert 'created_at' field to string format and sort by descending order
        foreach ($jobs as &$job) {
            $job['created_at'] = $job['created_at']->toDateTime()->format('Y-m-d H:i:s');
        }
        usort($jobs, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Prepare the response
        $response = ["success" => true, "jobs" => $jobs];
        echo json_encode($response);
        exit;
    } else {
        $response = ["success" => false, "error" => "No jobs found"];
        echo json_encode($response);
        exit;
    }
}
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'getEvents') {
    // Retrieve job data from the database
    $cursor = $postsCollections->aggregate([
        ['$match' => ['type' => 'event']],
        ['$project' => ['subject' => 1, 'job_details' => 1, 'created_at' => 1, 'link' => 1]],
    ]);
    // Convert the cursor to an array
    $jobs = iterator_to_array($cursor);

    if (!empty($jobs)) {
        // Convert 'created_at' field to string format and sort by descending order
        foreach ($jobs as &$job) {
            $job['created_at'] = $job['created_at']->toDateTime()->format('Y-m-d H:i:s');
        }
        usort($jobs, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Prepare the response
        $response = ["success" => true, "jobs" => $jobs];
        echo json_encode($response);
        exit;
    } else {
        $response = ["success" => false, "error" => "No jobs found"];
        echo json_encode($response);
        exit;
    }
}
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'searchAlumni') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Initialize an empty array to store the query conditions
    $queryConditions = [];

    if (isset($data['name']) && $data['name'] != '') {
        $queryConditions['name'] = $data['name'];
        error_log("passed query: name - " . $data['name']);
    }

    if (isset($data['batchFrom']) && $data['batchFrom'] != '') {
        $queryConditions['batch_from'] = $data['batchFrom'];
        error_log("passed query: batchFrom - " . $data['batchFrom']);
    }

    if (isset($data['batchTo']) && $data['batchTo'] != '') {
        $queryConditions['batch_to'] = $data['batchTo'];
        error_log("passed query: batchTo - " . $data['batchTo']);
    }

    if (isset($data['department']) && $data['department'] != '') {
        $queryConditions['department'] = $data['department'];
        error_log("passed query: department - " . $data['department']);
    }

    if (isset($data['course']) && $data['course'] != '') {
        $queryConditions['program'] = $data['course'];
        error_log("passed query: course - " . $data['course']);
    }

    $queryConditions['account_status'] = 'approved';

    // $constructedQuery = json_encode($queryConditions);

    // error_log("constructed query: $constructedQuery");s
    // error_log("query var dump: " . var_dump($constructedQuery));

    $fields = [
        'name' => 1,
        'profile_picture' => 1,
        '_id' => 1 // Include the Mongo Object ID as well
    ];

    // Find users based on the constructed query conditions
    $cursor = $userCollection->find($queryConditions, $fields);


    // Manually count the number of documents
    $documentCount = 0;
    foreach ($cursor as $_) {
        $documentCount++;
    }
    // error_log("Constructed query: $constructedQuery");
    // Log the number of documents returned by the find operation
    error_log("Number of documents found: " . $documentCount);
    $cursor = $userCollection->find($queryConditions, $fields);

    $matchingUsers = [];
    foreach ($cursor as $document) {
        // Extract the required fields from the document
        $userData = [
            'name' => $document->name,
            'profile_picture' => $document->profile_picture,
            '_id' => $document->_id->__toString() // Convert MongoDB ObjectId to string
        ];
        // Add the user data to the array
        $matchingUsers[] = $userData;
    }
    error_log("documents before returning: " . json_encode($matchingUsers));

    if (!empty($matchingUsers)) {
        $response = ["success" => true, "matchedUsers" => $matchingUsers];
        error_log("documents returned: " . json_encode($response));

        echo json_encode($response);
        exit;
    } else {
        // Log that no matching users were found
        error_log("No matching users found");
        $response = ["success" => false, "error" => "No matching users found"];
        echo json_encode($response);
        exit;
    }
}
