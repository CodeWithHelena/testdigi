<?php
//Only admins has access 
// All admins can access this in order to change the password of any pilot
// admin must be logged in and and must provide a new valid password
// on success, new password he provided will be sent to the pilot email.

/*
Update an Admin

endpoint: /pilot/updateone/user_id

Input: 
user_id: required | integer
session strings (3): required | string

Output: json response
*/

header("Access-Control-Allow-Origin: *" );
header("Content-Type: application/json");
header("Accept: application/json");
header("Access-Control-Allow-Methods: post");
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

$pilot = new pilot($conn);

// get user_id of pilot to be edited
$data = json_decode(file_get_contents("php://input"));


// ===== Auth Gate Check =========================

$pilot2 = new pilot($conn);

if (!authGateCheck($data)) {
    // set response code - 503 service unavailable
    http_response_code(401);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 30]);
    return;
}

// Session check
$pilot2->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($pilot2->user_id) || empty($pilot2->user_id) || $pilot2->user_id == NULL || intval($pilot2->user_id) <= 0) {
     // set response code - 503 service unavailable
     http_response_code(401);
     echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
     return;
}

$pilot2Stmt = $pilot2->getUser();
$pilot2Data = $pilot2Stmt['output']->fetch(PDO::FETCH_ASSOC);


if(!$pilot2Data || !authGateCheck2($pilot2Data, $data)){
    // set response code - 503 service unavailable
    http_response_code(401);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
    return;
}


// ===== Auth Gate Check ends here ===============



// set user_id property of pilot to be updated ============

$pilot->user_id = is_null($param_id) || empty($param_id) ? $pilot2->user_id : cleanData($param_id);


// Checks if active is to be updated
if (empty($pilot->user_id) || intval($pilot->user_id) <= 0 || is_null($pilot->user_id) || !is_numeric($pilot->user_id)) {
    // set response code - 403 forbidden
    http_response_code(403);
    echo json_encode(["message" => "Please provide your valid pilot Id to be updated", "status" => 6]);
    return;
} 


// Retrieve the pilot details
$pilot_stmt = $pilot->getUser();

// Handle pilot retrieval errors
if ($pilot_stmt['outputStatus'] == 1000) {
        
    $pilot_to_update = $pilot_stmt['output']->fetch(PDO::FETCH_ASSOC);
        
    // If pilot does not exist
    if (!$pilot_to_update || empty($pilot_to_update)) {
        // set response code - 404 not found
        http_response_code(404);
        echo json_encode(["message" => "No user found with this ID.", "status" => 0]);
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

// Make sure user does not have access to change their own role or active status
if ($pilot2Data['user_id'] == $pilotData['user_id'] && ($pilot2Data['role_id'] == $xPilot || $pilot2Data['role_id'] == $yPilot)) {
        unset($data->active);
        unset($data->role_id);
}

// ====== Authorization gate check  ends here =============
    


     // Checks if active is to be updated
     if (isset($data->active) && ($data->active == null || cleanData($data->active) == "" || !is_numeric($data->active))) {
        // set response code - 403 forbidden
        http_response_code(403);
        echo json_encode(["message" => "Please provide your valid active status", "status" => 6]);
        return;
    } 
    
    // Checks if role_id is to be updated
     if (isset($data->role_id) && ($data->role_id == null || cleanData($data->role_id) == "" || !is_numeric($data->role_id))) {
        // set response code - 403 forbidden
        http_response_code(403);
        echo json_encode(["message" => "Please provide your valid role_id", "status" => 5]);
        return;
    }
    
    // Checks If password is to be updated
    if (!empty($data->password) && ($data->password == null || cleanData($data->password) == "")) {
        // set response code - 403 forbidden
        http_response_code(403);
        echo json_encode(["message" => "Please provide your valid password", "status" => 4]);
        return;
    }

    // Check for valis lenght of password
    if (!empty($data->password) && strlen($data->password) < 12) {
        // set response code - 403 forbidden
        http_response_code(403);
        echo json_encode(["message" => "Your password must at least 12 characters long", "status" => 3]);
        return;
    }

    // Check for not allowed characters
    if ((!empty($data->firstname) && strpbrk($data->firstname, "<>&")) || (!empty($data->lastname) && strpbrk($data->lastname, "<>&")) || (!empty($data->password) && strpbrk($data->password, "<>&"))) {
        // set response code - 403 forbidden
        http_response_code(403);
        echo json_encode(["message" => "Any Data you provided to be updated can not conatin <, >, or &", "status" => 2]);
        return;
    }

  
        
    // Update pilot details
        $pilot->firstname = empty($data->firstname) ? $pilot_to_update['firstname'] : cleanData($data->firstname);
        $pilot->lastname = empty($data->lastname) ? $pilot_to_update['lastname'] : cleanData($data->lastname);
        $pilot->password = empty($data->password) ? $pilot_to_update['password'] : password_hash(cleanData($data->password), PASSWORD_DEFAULT);

        $pilot->active =  !isset($data->active) ? $pilot_to_update['active'] : cleanData($data->active);
        $pilot->role_id = !isset($data->role_id) ? $pilot_to_update['role_id'] : cleanData($data->role_id);

        // update the pilot
        $updateStatus = $pilot->updateUser2();
            
    
    // Check update status
    if ($updateStatus['outputStatus'] == 1000 && $updateStatus['output'] == true) {

        $mailSent = false;

        $whitelist = ['localhost', '127.0.0.1', '::1'];
        if (!in_array($_SERVER['SERVER_NAME'], $whitelist) && !empty(cleanData($data->password))) {

            $pilot_firstname = $pilot_to_update['firstname'];

            $to = $pilot_to_update['email'];
            $subject = "Your New Pawword";
            $message = "Good day $pilot_firstname, \r\n\n Your new password is $data->new_pass \r\n\n Your faithfully, \r\n CustomerCare";
            $headers = "From: customercare@bloservices.com \r\n Reply-to: customerercer@bloservices.com \r\n X-Mailer: PHP" . phpversion();

            $mailSent = mail($to, $subject, $message, $headers);

        }
            
        // set response code - 200 ok
        http_response_code(200);
        echo json_encode(["message" => "User was updated successfully.", "mail_sent"=>$mailSent, "status" => 1]);
        return;
            
    } elseif ($updateStatus['outputStatus'] == 1200  && $updateStatus['output'] == false) {
            
        // set response code - 200 ok
        http_response_code(202);
        echo json_encode(["message" => "User updated FAILED. Try again.", "status" => 8]);
        return;
            
    } elseif ($updateStatus['outputStatus'] == 1400) {
        // set response code - 200 ok
        http_response_code(202);
        echo json_encode(errorDiag($updateStatus['output']));
        return;
            
    } else {
            
        // set response code - 503 service unavailable
        http_response_code(503);
        // tell the pilot
        echo json_encode(["message" => "Unable to update pilot. Please try again.", "status" => 9]);
        return;
            
    }
} elseif ($pilot_stmt['outputStatus'] == 1200) {
    // set response code - 200 ok
    http_response_code(202);
    echo json_encode(errorDiag($pilot_stmt['output']));
    return;
        
} else {
        
    // set response code - 503 service unavailable
    http_response_code(503);
    echo json_encode(["message" => "Unable to update pilot. Please try again.", "status" => 10]);
    return;
        
}
