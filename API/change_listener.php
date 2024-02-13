<?php

include_once("db.php");
// OLD CODEEEE
// $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
// $database = $mongoClient->selectDatabase("AIM");
// $userCollection = $database->selectCollection("users");
// $conversationCollection = $database->selectCollection("conversations");
// $messageCollection = $database->selectCollection("messages");
// $postsCollections = $database->selectCollection("posts");



// // Define the pipeline to filter insert operations with type 'job' or 'event'
// $pipeline = [
//     ['$match' => ['operationType' => 'insert']], // Filter insert operations
//     ['$match' => ['fullDocument.type' => ['$in' => ['job', 'event']]]] // Filter documents with type 'job' or 'event'
// ];

// // Set up the change stream
// $changeStream = $postsCollections->watch($pipeline);

// // Iterate over the change stream
// foreach ($changeStream as $change) {
//     // Extract the document from the change event
//     $document = $change['fullDocument'];

//     // Print or process the notification
//     echo "New notification: " . json_encode($document) . "\n";
// }
// END OF OLD CODE



function setupChangeStream()
{
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase("AIM");
    $postsCollections = $database->selectCollection("posts");

    // Create a change stream cursor
    $changeStreamCursor = $postsCollections->watch([
        [
            '$match' => [
                'operationType' => 'update',
                'updateDescription.updatedFields.status' => 'approved',
                'updateDescription.updatedFields.type' => 'job'
            ]
        ],
        [
            '$match' => [
                'operationType' => 'insert',
                'fullDocument.type' => 'event'
            ]
        ]
    ]);

    $notifications = [];

    // Iterate over the change stream cursor
    foreach ($changeStreamCursor as $changeDocument) {
        // Handle change events
        switch ($changeDocument['operationType']) {
            case 'update':
                // Handle update event
                handleJobUpdate($changeDocument);
                break;
            case 'insert':
                // Handle insert event
                handleEventInsert($changeDocument, $notifications);
                break;
            default:
                // Handle other types of events if necessary
                break;
        }
    }

    // Output notifications as JSON
    header('Content-Type: application/json');
    echo json_encode($notifications);
}

// Handle update events for job documents
function handleJobUpdate($changeDocument)
{
    // Implement as needed
}

// Handle insert events for event documents
function handleEventInsert($changeDocument, &$notifications)
{
    // Extract relevant information from the change document
    $eventDocument = $changeDocument['fullDocument'];
    // Add event notification to array
    $notifications[] = $eventDocument;
}

// Call the function to set up the change stream
setupChangeStream();
?>