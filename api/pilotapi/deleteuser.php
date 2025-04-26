<?php
header("Access-Control-Allow-Origin: *" );
header("Content-Type: application/json");
header("Accept: application/json");
header("Access-Control-Allow-Methods: delete");
header("Access-Control-Allow-Headers:" . $configx['dbconnx']['ALLOWED_HEADERS']);

// initialize object
$db = new Database($configx);
$conn = $db->getConnection();

// Check connection
if ($conn == null) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed.", "status" => 7]);
    return;
}

$pilot = new Pilot($conn);

 $data = json_decode(file_get_contents("php://input"));

//  Chceck for valid user id to delete
if (empty($param_id) || $param_id == null || !is_numeric($param_id)) {
    // set response code - 503 service unavailable
    http_response_code(401);
    echo json_encode(["message" => "Please provide a valis user ID to be deleted.", "status" => 34]);
    return;
}


// ===== Auth Gate Check ==================================

$pilot2 = new Pilot($conn);

if (!authGateCheck($data)) {
    // set response code - 503 service unavailable
    http_response_code(401);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 30]);
    return;
}

$pilot2->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($pilot2->user_id) || !isset($pilot2->user_id) || is_null($pilot2->user_id)) {
     // set response code - 503 service unavailable
     http_response_code(401);
     echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
     return;
}

$pilot2_stmt = $pilot2->getUser();

if ($pilot2_stmt['outputStatus'] == 1400) {
        
    // set response code - 503 service unavailable
    http_response_code(503);
    echo json_encode( errorDiag($pilot2_stmt['output']) );
    return;        
}

$pilot2Data = $pilot2_stmt['output']->fetch(PDO::FETCH_ASSOC);

 // If user does not exist
 if (!$pilot2Data || empty($pilot2Data) || !authGateCheck2($pilot2Data, $data)) {
    // set response code - 404 not found
    http_response_code(404);
    echo json_encode(["message" => "User Not Authenticated.", "status" => 0]);
    return;
}

// ===== Auth Gate Check ends here=========================



// Delete the user with param id provided ==========

$pilot->user_id = cleanData($param_id);

$pilot_stmt = $pilot->getUser();

if ($pilot_stmt['outputStatus'] == 1400) {
        
    // set response code - 503 service unavailable
    http_response_code(503);
    echo json_encode( errorDiag($pilot_stmt['output']) );
    return;        
}

// Handle User retrieval errors
if ($pilot_stmt['outputStatus'] == 1000) {
        
    $pilot_to_delete = $pilot_stmt['output']->fetch(PDO::FETCH_ASSOC);


    // If User does not exist
    if (!$pilot_to_delete || empty($pilot_to_delete)) {
        // set response code - 404 not found
        http_response_code(404);
        echo json_encode(["message" => "No User found with this ID.", "status" => 0]);
        return;
    }


// ====== Authorization gate check  =============

// Make sure that a user cant modify a pilot with hihger role than itself
if($pilot2Data['role_id'] < $pilotData['role_id']){
    // set response code - 200 OK
    http_response_code(404);
    echo json_encode(["message" => "User not authorized to perfrom this action.", "status"=>40]);
    return;
}

// ====== Authorization gate check  ends here =============


    // Delete the user
    $deleteStatus = $pilot->deleteUser();
        
                   
    // Check delete status
    if ($deleteStatus['outputStatus'] == 1000 && $deleteStatus['output'] == true) {
            
        // set response code - 200 ok
        http_response_code(200);
        echo json_encode(["message" => "User was deleted successfully.", "status" => 1]);
        return;
            
    } elseif ($deleteStatus['outputStatus'] == 1200 && $deleteStatus['output'] == false) {
            
        // set response code - 200 ok
        http_response_code(200);
        echo json_encode(["message" => "User was deleted FAILED. Try again.", "status" => 7]);
        return;
            
    } elseif ($deleteStatus['outputStatus'] == 1400) {
            
       // set response code - 503 service unavailable
         http_response_code(503);
         echo json_encode( errorDiag($deleteStatus['output']) );
         return;  
            
    } else {
            
        // set response code - 503 service unavailable
        http_response_code(503);
        echo json_encode(["message" => "Unable to delete user. Please try again.", "status" => 5]);
        return;
            
    }
} 
else {
        
    // set response code - 503 service unavailable
    http_response_code(503);
    echo json_encode(["message" => "Unable to delete user. Please try again.", "status" => 6]);
    return;
        
}
