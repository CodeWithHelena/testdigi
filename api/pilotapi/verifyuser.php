<?php
/*
Admin Forget Password

endpoint: /pilot/emailverify

Input: 
email: required | string

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
    echo json_encode(["message" => "Database connection failed.", "status" => 3]);
    return;
}

$pilot = new Pilot($conn);


// get user_id of user to be edited
$data = json_decode(file_get_contents("php://input"));

// Check for valid user email
if (empty($data->email) || $data->email == null || cleanData($data->email) == "" || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    // set response code - 403 forbidden
    http_response_code(403);
    echo json_encode(["message" => "Please provide a valid email", "status" => 2]);
    return;

    
}


// clean and set user_id properties of user to be edited
$pilot->email = cleanData($data->email);

// Retrieve the user details
$pilot_stmt = $pilot->getUserByEmail();

// Handle user retrieval errors
if ($pilot_stmt['outputStatus'] == 1000) {
        
    $pilot_to_update = $pilot_stmt['output']->fetch(PDO::FETCH_ASSOC);

    // If user does not exist
    if (!$pilot_to_update || empty($pilot_to_update)) {
        // set response code - 404 not found
        http_response_code(404);
        // tell the user
        echo json_encode(["message" => "No user found with this email.", "status" => 0]);
        return;
    }

    unset($pilot_to_update['password']);

    // Regenerte evc
    $pilot->reGenerateUserCode();

    // ===== Send evcode to user email =================

        $uevc = scrubUcode($pilot);

        $mailSent = false;

    if (!in_array($_SERVER['SERVER_NAME'], ["localhost", "127.0.0.1", "::1"])) {

        $pilot->firstname = $pilot2_to_update['firstname'];

        $to = $pilot_to_update['email'];
        $subject = "Email Verification Code Sent";
        $message = "Good day $pilot->firstname, \r\n\n";
        $message .= "Your Email Verification Code is $uevc \r\n\n";
        $message .= "Customercare Manager \r\n";
        $message .= "Blog Services";
        $headers = ["From" => "noreply@blogservices.com", "Reply-To" => "customercare@blogservices.com", "X-Mailer" => "PHP/" . phpversion()];

        $mailSent = mail($to, $subject, $message, $headers);
    }


    // ======== send mail ends here =================

    if($mailSent){
        http_response_code(404);
        echo json_encode(["message"=>"Your Email verification code has been sent to your email.","mail_sent"=>$mailSent, "status" => 1]);
        return;
    }

    // Remove the uecv item in production
         http_response_code(404);
         echo json_encode(["message"=>"Your Email verification code FAILED. Please try again.","mail_sent"=>$mailSent, "evcode"=>$uevc, "status" => 0]);
         return;
    

} elseif ($pilot_stmt['outputStatus'] == 1400) {
    http_response_code(404);
    echo json_encode(errorDiag($pilot_stmt['output']));
    return;
        
} else {
        
    // set response code - 503 service unavailable
    http_response_code(503);
    echo json_encode(["message" => "Unable to send evcode. Please try again.", "status" => 7]);
    return;
        
}
