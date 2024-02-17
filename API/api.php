<?php

include_once("db.php");

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("AIM");
$userCollection = $database->selectCollection("users");
$conversationCollection = $database->selectCollection("conversations");
$messageCollection = $database->selectCollection("messages");
$postsCollections = $database->selectCollection("posts");
$authCollections = $database->selectCollection("auth");
$filtersCollections = $database->selectCollection("filters");

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
        $filters = [
            'sort' => ['latest_timestamp' => -1]
        ];
        // Find conversations where the user is a participant excluding the user's own ID
        $cursor = $conversationCollection->find(['participants' => $mongoId], $filters);

        $conversations = iterator_to_array($cursor);
        // For each conversation, fetch the latest message
        foreach ($conversations as &$conversation) {
            $latestMessage = $messageCollection->findOne(
                ['conversation_id' => $conversation['_id']],
                ['sort' => ['latest_timestamp' => -1]]
            );

            if ($latestMessage) {
                $conversation['latest_message'] = $latestMessage['latest_message'];
                $conversation['latest_timestamp'] = $latestMessage['latest_timestamp'];
                // $conversation['latest_timestamp'] = date('Y-m-d H:i:s', $latestMessage['latest_timestamp']->sec);
            }

            // Fetch user details for each participant (only name and profile picture)
            $userDetails = [];
            foreach ($conversation['participants'] as $participantId) {

                if ($participantId != $mongoId) {
                    $userDetail = $userCollection->findOne(
                        ['_id' => new MongoDB\BSON\ObjectId($participantId)],
                        ['projection' => ['_id' => 1, 'name' => 1, 'profile_picture' => 1]]
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

function initiateConversation($senderId, $recipientId)
{
    global $conversationCollection;

    try {
        // Check if a conversation already exists between the sender and recipient
        $existingConversation = $conversationCollection->findOne([
            'participants' => ['$all' => [$senderId, $recipientId]],
        ]);

        if ($existingConversation) {
            $conversationId = $existingConversation['_id'];
            return ['success' => true, 'conversationId' => $conversationId];
        }

        // If no existing conversation, create a new one
        $newConversation = [
            'participants' => [$senderId, $recipientId],
            'latest_message' => "no new messages",
            'latest_timestamp' => new MongoDB\BSON\UTCDateTime(),
        ];

        $insertResult = $conversationCollection->insertOne($newConversation);

        if ($insertResult->getInsertedCount() > 0) {
            $insertedId = $insertResult->getInsertedId();
            return ['success' => true, 'conversationId' => $insertedId];
        } else {
            return ['success' => false, 'error' => 'Failed to initiate conversation'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to initiate conversation'];
    }
}

function fetchMessages($conversationObjectId)
{
    global $messageCollection;

    try {
        $filters = [
            'sort' => ['timestamp' => -1]
        ];
        // Assuming $collection is your MongoDB collection for messages
        $messages = $messageCollection->find(['conversationId' => $conversationObjectId], $filters);

        // Convert MongoDB cursor to array
        $messagesArray = iterator_to_array($messages);

        return ['success' => true, 'messages' => $messagesArray];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
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
        // Exclude password from user details
        $userData = $data;
        unset($userData['password']);

        // Insert user details into "user" collection
        $insertResult = $userCollection->insertOne($userData);

        // Get the inserted user's ID
        $userId = $insertResult->getInsertedId();
        $hashedPassword = md5($data['password']);
        // Prepare data for "auth" collection
        $authData = [
            // "user_id" => $userId,
            '_id' => new MongoDB\BSON\ObjectId($userId),
            "email" => $data['email'],
            "password" => $hashedPassword
        ];

        // Insert email/password into "auth" collection
        $authInsertResult = $authCollections->insertOne($authData);

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

    // Query the "auth" collection to find the user based on email
    $authUser = $authCollections->findOne(['email' => $userEmail]);

    if ($authUser) {
        // Compare hashed password from the database with the hashed password provided by the user
        if (md5($userPassword) === $authUser['password']) {
            // Retrieve the user ID
            $userId = $authUser['_id'];

            // Query the "user" collection for the account status
            $user = $userCollection->findOne(['_id' => new MongoDB\BSON\ObjectID($userId)]);

            if ($user && $user['account_status'] === 'approved') {
                $response = [
                    "success" => true,
                    "mongo_id" => (string)$user['_id'],
                    "name" => (string)$user['name'],
                    "email" => (string)$userEmail,
                    "password" => (string)$userPassword,
                    "department" => (string)$user['department'],
                    "batch_from" => (string)$user['batch_from'],
                    "batch_to" => (string)$user['batch_to'],
                ];
                echo json_encode($response);
                exit;
            }
        }
    }

    // If no user found or password doesn't match, or account not approved
    $response = ["success" => false, "error" => "Invalid email or password or not approved"];
    echo json_encode($response);
    exit;
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
        'department' => $data['department'],
        'updation_date' => "",
        'updated_by' => "",
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['action']) && $_GET['action'] == 'updateProfilePicture') {
    $userId = $_POST['mongoId'];
    error_log("user ID: " . $userId);
    // Check if userId is provided
    if (!empty($userId)) {
        error_log("Got inside the first if with userId: " . $userId);

        // Check if file is uploaded
        if (isset($_FILES['profile_image']) && is_uploaded_file($_FILES['profile_image']['tmp_name'])) {
            $image_name = $_FILES['profile_image']['name'];
            $image_tmp = $_FILES['profile_image']['tmp_name'];
            $destination = '../../AIM/Alumni/user_management/assets/profile_pictures/' . $image_name;
            error_log("Directory: " . $destination);

            // Move the uploaded file to the server
            if (move_uploaded_file($image_tmp, $destination)) {
                try {
                    // Update the user's profile picture path in the database
                    $updateResult = $userCollection->updateOne(
                        ['_id' => new MongoDB\BSON\ObjectId($userId)],
                        ['$set' => ['profile_picture' => $destination]]
                    );

                    if ($updateResult->getModifiedCount() > 0) {
                        http_response_code(200); // OK
                        $response = ["success" => true, "message" => "Profile picture updated successfully"];
                        echo json_encode($response);
                        exit;
                    } else {
                        http_response_code(500); // Internal Server Error
                        $response = ["success" => false, "error" => "Failed to update profile picture"];
                        echo json_encode($response);
                        exit;
                    }
                } catch (Exception $e) {
                    error_log("Exception occurred: " . $e->getMessage());
                    http_response_code(500); // Internal Server Error
                    $response = ["success" => false, "error" => "Failed to update profile picture: " . $e->getMessage()];
                    echo json_encode($response);
                    exit;
                }
            } else {
                error_log("Failed to move uploaded file");
                http_response_code(500); // Internal Server Error
                $response = ["success" => false, "error" => "Failed to move uploaded file"];
                echo json_encode($response);
                exit;
            }
        } else {
            error_log("No file uploaded");
            http_response_code(400); // Bad Request
            $response = ["success" => false, "error" => "No file uploaded"];
            echo json_encode($response);
            exit;
        }
    } else {
        error_log("Invalid userId");
        http_response_code(400); // Bad Request
        $response = ["success" => false, "error" => "Invalid userId"];
        echo json_encode($response);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['action']) && $_GET['action'] == 'postContent') {
    $subject = $_POST['subject'];
    $content = $_POST['content'];
    $postedBy = $_POST['posted_by'];
    $type = $_POST['type'];
    $department = $_POST['department'];

    if (!empty($subject) && !empty($content) && !empty($postedBy) && !empty($type)) {
        if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $image_name = $_FILES['image']['name'];
            $image_tmp = $_FILES['image']['tmp_name'];
            $destination = '../../AIM/Alumni/post_management/assets/uploads/' . $image_name;


            if (move_uploaded_file($image_tmp, $destination)) {
                error_log("trying to move uploaded files");

                try {
                    $insertResult = $postsCollections->insertOne([
                        'subject' => $subject,
                        'content' => $content,
                        'posted_by' => $postedBy,
                        'type' => $type,
                        'department' => $department,
                        'image' => $destination,
                        'likeCount' => 0,
                        'comments' => [],
                        'likes' => [],
                        'created_at' => new MongoDB\BSON\UTCDateTime(),

                    ]);

                    if ($insertResult->getInsertedCount() > 0) {
                        http_response_code(200);
                        $response = ["success" => true, "message" => "Content posted successfully"];
                        echo json_encode($response);
                        exit;
                    } else {
                        http_response_code(500);
                        $response = ["success" => false, "error" => "Failed to post content"];
                        echo json_encode($response);
                        exit;
                    }
                } catch (Exception $e) {
                    error_log("Exception occurred: " . $e->getMessage());
                    http_response_code(500);
                    $response = ["success" => false, "error" => "Failed to post content: " . $e->getMessage()];
                    echo json_encode($response);
                    exit;
                }
            } else {
                http_response_code(500);
                $response = ["success" => false, "error" => "Failed to move uploaded file"];
                echo json_encode($response);
                exit;
            }
        } else {
            try {
                $insertResult = $postsCollections->insertOne([
                    'subject' => $subject,
                    'content' => $content,
                    'posted_by' => $postedBy,
                    'type' => $type,
                    'department' => $department,
                    'likeCount' => 0,
                    'comments' => [],
                    'likes' => [],
                    'created_at' => new MongoDB\BSON\UTCDateTime(),

                ]);

                if ($insertResult->getInsertedCount() > 0) {
                    http_response_code(200); // OK
                    $response = ["success" => true, "message" => "Content posted successfully"];
                    echo json_encode($response);
                    exit;
                } else {
                    http_response_code(500); // Internal Server Error
                    $response = ["success" => false, "error" => "Failed to post content"];
                    echo json_encode($response);
                    exit;
                }
            } catch (Exception $e) {
                error_log("Exception occurred: " . $e->getMessage());
                http_response_code(500); // Internal Server Error
                $response = ["success" => false, "error" => "Failed to post content: " . $e->getMessage()];
                echo json_encode($response);
                exit;
            }
        }
    } else {
        http_response_code(400); // Bad Request
        $response = ["success" => false, "error" => "Missing parameters"];
        echo json_encode($response);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'getPosts') {
    $department = $_GET['department'];

    $options = [
        'sort' => ['created_at' => -1]
    ];
    $filter = [
        '$and' => [
            ['$or' => [
                ['type' => 'post'],
                ['type' => 'admin']
            ]],
            ['department' => $department]
        ]
    ];
    $cursor = $postsCollections->find($filter, $options);
    // Convert the cursor to an array
    $posts = iterator_to_array($cursor);

    if (!empty($posts)) {
        // Prepare the response with user information
        $responsePosts = [];
        foreach ($posts as $post) {
            // Fetch user information based on 'postedBy' ObjectId
            $user = $userCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($post['posted_by'])]);
            if ($user) {
                // Convert BSONDocument to array
                $userArray = [];
                foreach ($user as $key => $value) {
                    $userArray[$key] = $value;
                }

                // Create a new associative array for the response post
                $responsePost = [];

                // Merge post and user information into the response post array
                foreach ($post as $key => $value) {
                    $responsePost[$key] = $value;
                }
                $responsePost['user'] = $userArray;

                // Add the response post array to the response posts array
                $responsePosts[] = $responsePost;
            }
        }

        // Prepare the response
        $response = ["success" => true, "posts" => $responsePosts];
        echo json_encode($response);
        exit;
    } else {
        $response = ["success" => false, "error" => "No posts found"];
        echo json_encode($response);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && (isset($_GET['action']) && $_GET['action'] == 'getMessages')) {
    $conversationObjectId = $_GET['conversationId'];

    if (!empty($conversationObjectId)) {
        $result = fetchMessages($conversationObjectId);

        // Handle database query errors
        if (!$result['success'] && isset($result['error'])) {
            echo json_encode(['success' => false, 'error' => $result['error']]);
            exit;
        }

        // Check if there are no messages
        if (empty($result['messages'])) {
            echo json_encode(['success' => true, 'messages' => []]);
            exit;
        }

        echo json_encode($result);
        exit;
    } else {
        $response = ["success" => false, "error" => "Invalid objectId"];
        echo json_encode($response);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_GET['action']) && $_GET['action'] == 'sendMessage')) {
    // Assuming you have sanitized the input values to prevent SQL injection

    $conversationId = $_POST['conversationId'];
    $message = $_POST['message'];
    $receiverMongoId = $_POST['receiverMongoId'];
    $senderMongoId = $_POST['senderMongoId'];

    if (!empty($conversationId) && !empty($message) && !empty($receiverMongoId) && !empty($senderMongoId)) {

        $messageData = [
            'conversationId' => $conversationId,
            'message' => $message,
            'receiverMongoId' => $receiverMongoId,
            'senderMongoId' => $senderMongoId,
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
        ];

        $result = $messageCollection->insertOne($messageData);

        if ($result->getInsertedCount() > 0) {
            // Update latest_timestamp and latest_message in the conversations table
            $updateResult = $conversationCollection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectID($conversationId)],
                ['$set' => [
                    'latest_timestamp' => new MongoDB\BSON\UTCDateTime(),
                    'latest_message' => $message,
                ]]
            );

            if ($updateResult->getModifiedCount() > 0) {
                echo json_encode(['success' => true]);
                exit;
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update conversation']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to insert message into database']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'getDepartmentsAndPrograms') {
    $result = $filtersCollections->find();

    if ($result) {
        $departments = [];

        foreach ($result as $doc) {
            $departments[] = $doc['department'];
        }

        echo json_encode(['success' => true, 'departments' => $departments]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to fetch departments data']);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'getCourses') {
    $department = $_GET['department'];

    $result = $filtersCollections->findOne(['department' => $department]);

    if ($result) {
        $courses = $result['courses'];
        echo json_encode(['success' => true, 'courses' => $courses]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to fetch courses for the department']);
        exit;
    }
}
