<?php
header("Access-Control-Allow-Origin: *" );
header("Content-Type: application/json");
header("Accept: application/json");
header("Access-Control-Allow-Headers:" . $configx['dbconnx']['ALLOWED_HEADERS']);
header("Access-Control-Allow-Methods:" . $configx["dbconnx"]["POST_METHOD"]);


// initialize object
$db = new Database($configx);
$conn = $db->getConnection();

// Check connection
if ($conn == null) {
    http_response_code(203);
    echo json_encode(["message" => "Database connection failed.", "status" => 13]);
    return;
}

$user = new User($conn);


// get user_id of user to be edited
$data = json_decode(file_get_contents("php://input"));

// Check for valid user email
if (empty($data->email) || $data->email == null || cleanData($data->email) == "" || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    // set response code - 203 forbidden
    http_response_code(203);
    echo json_encode(["message" => "Please provide a valid email", "status" => 2]);
    return;

    
}


// clean and set user_id properties of user to be edited
$user->email = cleanData($data->email);

// Retrieve the user details
$user_stmt = $user->getUserByEmail();

// Handle user retrieval errors
if ($user_stmt['outputStatus'] == 1000) {
        
    $user_to_update = $user_stmt['output']->fetch(PDO::FETCH_ASSOC);

 
    // If user does not exist
    if (!$user_to_update || empty($user_to_update)) {
        // set response code - 203 not found
        http_response_code(203);
        echo json_encode(["message" => "No user found with this email.", "status" => 0]);
        return;
    }

    unset($user_to_update['password']);

    // Regenerte evc
    $user->reGenerateUserCode();

    // ===== Send evcode to user email =================

        $uevc = scrubUcode($user);

        $mailSent = false;

    if (!in_array($_SERVER['SERVER_NAME'], ["localhost", "127.0.0.1", "::1"])) {

        $to = $user_to_update['email'];
        $subject = "Email Verification Code Sent";
        $message = "Your Email Verification Code has been sent to your mail \r\n\n";
        $message .= "Your evcode is $uevc \r\n\n";
        $message .= "Customercare Manager \r\n";
        $message .= "Akanta";
        $headers = ["From" => "noreply@akanta.com", "Reply-To" => "customercare@akanta.com", "X-Mailer" => "PHP/" . phpversion()];

        $mailSent = mail($to, $subject, $message, $headers);

        if ($mailSent) {
            http_response_code(200);
            echo json_encode(["message" => "Your Email verification code has been sent to your email.", "mail_sent" => $mailSent, "status" => 1]);
            return;
        }
        else{
            http_response_code(203);
            echo json_encode(["message" => "FAILED. Your Email verification failed. Please try again.", "mail_sent" => $mailSent, "status" => 2]);
            return;
        }

    }
    else{
        // Only for development testing. Please remove on live application
        http_response_code(203);
        echo json_encode(["message" => "You seem to be either offline or on localhost. Please try again when you get online or check your netwrok strength .", "mail_sent" => $mailSent, "status" => 1, "evcode" => $uevc]);
        return;
    }

   

    

} elseif ($user_stmt['outputStatus'] == 1400) {
    http_response_code(203);
    echo json_encode(errorDiag($user_stmt['output']));
    return;
        
} else {
        
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "Unable to send evcode. Please try again.", "status" => 7]);
    return;
        
}
