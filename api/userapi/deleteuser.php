<?php
header("Access-Control-Allow-Origin: *" );
header("Content-Type: application/json");
header("Accept: application/json");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Allow-Headers:" . $configx['dbconnx']['ALLOWED_HEADERS']);

// initialize object
$db = new Database($configx);
$conn = $db->getConnection();

// Check connection
if ($conn == null) {
    http_response_code(203);
    echo json_encode(["message" => "Database connection failed.", "status" => 7]);
    return;
}


$user = new User($conn);


 $data = json_decode(file_get_contents("php://input"));

//  Chceck for valid user id to delete
if (empty($param_id) || $param_id == null || !is_numeric($param_id)) {
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "Please provide a valis user ID to be deleted.", "status" => 34]);
    return;
}


// ===== Auth Gate Check ==================================

$pilot = new Pilot($conn);

if (!authGateCheck($data)) {
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 30]);
    return;
}

$pilot->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($pilot->user_id) || !isset($pilot->user_id) || is_null($pilot->user_id)) {
     // set response code - 203 service unavailable
     http_response_code(203);
     echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
     return;
}

$pilot_stmt = $pilot->getUser();

if ($pilot_stmt['outputStatus'] == 1400) {
        
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode( errorDiag($pilot_stmt['output']) );
    return;        
}

$pilotData = $pilot_stmt['output']->fetch(PDO::FETCH_ASSOC);

 // If user does not exist
 if (!$pilotData || empty($pilotData) || !authGateCheck2($pilotData, $data)) {
    // set response code - 203 not found
    http_response_code(203);
    echo json_encode(["message" => "User Not Authenticated.", "status" => 0]);
    return;
}

// ===== Auth Gate Check ends here=========================



// Delete the user with param id provided ==========

$user->user_id = cleanData($param_id);
$user_stmt = $user->getUser();

if ($user_stmt['outputStatus'] == 1400) {
        
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode( errorDiag($user_stmt['output']) );
    return;        
}

// Handle user retrieval errors
if ($user_stmt['outputStatus'] == 1000) {
        
    $user_to_delete = $user_stmt['output']->fetch(PDO::FETCH_ASSOC);


    // If user does not exist
    if (!$user_to_delete || empty($user_to_delete)) {
        // set response code - 203 not found
        http_response_code(203);
        echo json_encode(["message" => "No user found with this ID.", "status" => 0]);
        return;
    }


    // Delete the user
    $deleteStatus = $user->deleteUser();
        
                   
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
            
       // set response code - 203 service unavailable
         http_response_code(203);
         echo json_encode( errorDiag($deleteStatus['output']) );
         return;  
            
    } else {
            
        // set response code - 203 service unavailable
        http_response_code(203);
        echo json_encode(["message" => "Unable to delete user. Please try again.", "status" => 5]);
        return;
            
    }
} 
else {
        
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "Unable to delete user. Please try again.", "status" => 6]);
    return;
        
}
