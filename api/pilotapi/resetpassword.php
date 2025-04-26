<?php
/*
Reset Admin Password

Method: POST

endpoint: /pilot/resetpass

Input: 
email: required | string
evcode (the code sent to user email): required | string

Output: json response

Note: On success, an evcode is auto-generated and sent to user email
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
    echo json_encode(["message" => "Database connection failed.", "status" => 4]);
    return;
}

$pilot = new Pilot($conn);

// get user_id of user to be edited
$data = json_decode(file_get_contents("php://input"));

// Check for valid user email
if (empty($data->email) || $data->email == null || cleanData($data->email) == "" || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    // set response code - 403 forbidden
    http_response_code(403);
    echo json_encode(["message" => "Please provide your valid email", "status" => 3]);
    return;
}

// Check for valid email verification code
if (empty($data->evcode) || $data->evcode == null || cleanData($data->evcode) == "") {
    // set response code - 403 forbidden
    http_response_code(403);
    echo json_encode(["message" => "Please provide your valid emial verification code sent to your email.", "status" => 2]);
    return;
}


// clean and set user_id properties of user to be edited
$pilot->email = cleanData($data->email);
$data->evcode = cleanData($data->evcode);

// Retrieve the user details
$pilot_stmt = $pilot->verifyEmailEvcode($data->evcode);

// Handle user retrieval errors
if ($pilot_stmt['outputStatus'] == 1000) {
        
    $pilot_to_update = $pilot_stmt['output']->fetch(PDO::FETCH_ASSOC);
    

    // If user does not exist
    if (!$pilot_to_update || empty($pilot_to_update)) {
        // set response code - 404 not found
        http_response_code(404);
        echo json_encode(["message" => "No user found or invalid email verification code. Try again.", "status" => 0]);
        return;
    }


    // clean and set pilot passsword property of user to be changed
    $data->new_pass = $pilot->genPass();
    $pilot->password = $data->new_pass;
    $pilot->user_id = $pilot_to_update['user_id'];

    // update the user
    $updateStatus = $pilot->changePassword();
            
    // Check update status
    if ($updateStatus['outputStatus'] == 1000 && $updateStatus['output'] == true) {
            
        
        // ===== Send evcode to user email =================
        $mailSent = false;

        if (!in_array($_SERVER['SERVER_NAME'], ["localhost", "127.0.0.1", "::1"])) {

            $to = $pilot_to_update['email'];
            $subject = "New Password Sent";
            $message = "Hello, " . $pilot_to_update['firstname'] . "\n";
            $message .= "User password was updated successfully. Your new password is $data->new_pass \n";
            $message .= "Customercare Manager \n";
            $message .= "Blog Services \n";
            $headers = ["From" => "noreply@blogservices.com", "Reply-To" => "customercare@blogservices.com", "X-Mailer" => "PHP/" . phpversion()];


            $mailSent = mail($to, $subject, $message, $headers);

            http_response_code(200);
            echo json_encode(["message" => "User new password was updated successfully and sent to your email.", "result"=>true, "status" => 1]);
            return;

        }


            // set response code - 400 ok
            http_response_code(400);
            echo json_encode(["message" => "New User password reset succesful but FAILED to send it to your email. Please try again.","new_password"=>$data->new_pass, "result"=>false, "status" => 0]);
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
        echo json_encode(["message" => "Unable to update pilot password. Please try again.", "status" => 6]);
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
    echo json_encode(["message" => "Unable to update pilot. Please try again.", "status" => 7]);
    return;
        
}
