<?php
/*
Change Admin Own Password

Method: POST

endpoint: /pilot/passchange

Input: 
cur_pass (the current user password): required | string
session strings (3): required | string

Output: json response

Note: 
All users can access this in order to change their own password only passwordlessly.
User must be logged in and on success, a new pasword will be auto-generated and sent to thier email.
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
    echo json_encode(["message" => "Database connection failed.", "status" => 8]);
    return;
}

$pilot = new Pilot($conn);

// get user_id of user to be edited
$data = json_decode(file_get_contents("php://input"));


// ===== Auth Gate Check =========================

if (!authGateCheck($data)) {
    // set response code - 503 service unavailable
    http_response_code(401);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 30]);
    return;
}

// clean and set user_id properties of user to be edited
$pilot->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($pilot->user_id) || is_null($pilot->user_id) || intval($pilot->user_id) <= 0) {
     // set response code - 503 service unavailable
     http_response_code(401);
     echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
     return;
}

// Retrieve the user details
$pilot_stmt = $pilot->getUser();


// Handle user retrieval errors
if ($pilot_stmt['outputStatus'] == 1000) {
        
    $pilot_to_update = $pilot_stmt['output']->fetch(PDO::FETCH_ASSOC);

    // If user does not exist
    if (!$pilot_to_update || empty($pilot_to_update)) {
        // set response code - 404 not found
        http_response_code(404);
        echo json_encode(["message" => "No user found with this ID.", "status" => 0]);
        return;
    }


    if(!authGateCheck2($pilot_to_update, $data)){
        // set response code - 503 service unavailable
        http_response_code(401);
        echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
        return;
    }

 // ===== Auth Gate Check ends here ===============


  // ===== Authorization Gate Check ===============

    // Check for valid current password
    if (empty($data->cur_pass) || $data->cur_pass == null || cleanData($data->cur_pass) == "") {
        // set response code - 403 forbidden
        http_response_code(403);
        echo json_encode(["message" => "Please provide your current Password", "status" => 4]);
        return;
    }

    if (strpbrk($data->cur_pass, "<>&")) {
        // set response code - 403 forbidden
        http_response_code(403);
        echo json_encode(["message" => "Please your current Password can not conatin <, > or &", "status" => 3]);
        return;
    }

    // clean other properties
    $data->cur_pass = cleanData($data->cur_pass);

    // confirm password
    $passCheck = $pilot->verifyPass($data->cur_pass, $user_to_update['password']);

    // If password check is false, update the user ad exit
    if (!$passCheck) {
        // set response code - 404 not found
        http_response_code(400);
        echo json_encode(["message" => "Wrong user password. Password NOT Updated.", "status" => 5]);
        return;
    }

 // ===== Authorization Gate Check ends here ===============


    // clean and set user passsword property of user to be changed
    $data->new_pass = $pilot->genPass();
    $pilot->password = $data->new_pass;

    // update the pilot
    $updateStatus = $pilot->changePassword();
            
    // Check update status
    if ($updateStatus['outputStatus'] == 1000 && $updateStatus['output'] == true) {

        $mailSent = false;

        $whitelist = ['localhost', '127.0.0.1', '::1'];
        if (!in_array($_SERVER['SERVER_NAME'], $whitelist)) {

            $pilot_firstname = $pilot_to_update['firstname'];

            $to = $pilot_to_update['email'];
            $subject = "Your New Pawword";
            $message = "Good day $pilot_firstname, \r\n\n Your new password is $data->new_pass \r\n\n Your faithfully, \r\n CustomerCare";
            $headers = "From: customercare@bloservices.com \r\n Reply-to: customerercer@bloservices.com \r\n X-Mailer: PHP" . phpversion();

            $mailSent = mail($to, $subject, $message, $headers);

        }
            
        // set response code - 200 ok
        http_response_code(200);
        echo json_encode(["message" => "User password was updated successfully. Your new password is $data->new_pass", "mail_sent"=>$mailSent, "status" => 1]);
        return;
            
    } elseif ($updateStatus['outputStatus'] == 1200  && $updateStatus['output'] == false) {
            
        // set response code - 200 ok
        http_response_code(200);
        echo json_encode(["message" => "New password updated FAILED. Try again", "status" => 1]);
        return;
            
    } elseif ($updateStatus['outputStatus'] == 1400) {
            
         // set response code - 503 service unavailable
        http_response_code(503);
        echo json_encode( errorDiag($updateStatus['output']) );
        return;
            
    } else {
            
        // set response code - 503 service unavailable
        http_response_code(503);
        echo json_encode(["message" => "Unable to update user password. Please try again.", "status" => 6]);
        return;
            
    }
} elseif ($pilot_stmt['outputStatus'] == 1400) {
        
     // set response code - 503 service unavailable
     http_response_code(503);
     echo json_encode( errorDiag($pilot_stmt['output']) );
     return;
        
} else {
        
    // set response code - 503 service unavailable
    http_response_code(503);
    echo json_encode(["message" => "Unable to update user. Please try again.", "status" => 7]);
    return;
        
}
