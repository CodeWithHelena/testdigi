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
    echo json_encode(["message" => "Database connection failed.", "status" => 4]);
    return;
}

$user = new User($conn);


// get user_id of user to be edited
$data = json_decode(file_get_contents("php://input"));

// Check for valid user email
if (empty($data->email) || $data->email == null || cleanData($data->email) == "" || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    // set response code - 203 forbidden
    http_response_code(203);
    echo json_encode(["message" => "Please provide your valid email", "status" => 3]);
    return;
}

// Check for valid email verification code
if (empty($data->evcode) || $data->evcode == null || cleanData($data->evcode) == "") {
    // set response code - 203 forbidden
    http_response_code(203);
    echo json_encode(["message" => "Please provide your valid emial verification code sent to your email.", "status" => 2]);
    return;
}


// clean and set user_id properties of user to be edited
$user->email = cleanData($data->email);
$data->evcode = cleanData($data->evcode);

// Retrieve the user details
$user_stmt = $user->verifyEmailEvcode($data->evcode);

// Handle user retrieval errors
if ($user_stmt['outputStatus'] == 1000) {
        
    $user_to_update = $user_stmt['output']->fetch(PDO::FETCH_ASSOC);
    

    // If user does not exist
    if (!$user_to_update || empty($user_to_update)) {
        // set response code - 203 not found
        http_response_code(203);
        echo json_encode(["message" => "No user found or invalid email verification code. Try again.", "status" => 0]);
        return;
    }


    // clean and set user passsword property of user to be changed
    $data->new_pass = $user->genPass();
    $user->password = $data->new_pass;
    $user->user_id = $user_to_update['user_id'];

    // update the user
    $updateStatus = $user->changePassword();
            
    // Check update status
    if ($updateStatus['outputStatus'] == 1000 && $updateStatus['output'] == true) {
            
        
        // ===== Send evcode to user email =================
        $mailSent = false;

        if (!in_array($_SERVER['SERVER_NAME'], ["localhost", "127.0.0.1", "::1"])) {

            $to = $user_to_update->email;
            $subject = "New Password";
            $message = "Hello, " . $user_to_update->firstname . "\n";
            $message .= "User password was updated successfully. Your new password is $data->new_pass \n";
            $message .= "Customercare Manager \n";
            $message .= "Globe Track Services \n";
            $headers = ["From" => "noreply@blogservices.com", "Reply-To" => "customercare@blogservices.com", "X-Mailer" => "PHP/" . phpversion()];


            $mailSent = mail($to, $subject, $message, $headers);

            if ($mailSent) {
                http_response_code(200);
                echo json_encode(["message" => "User new password was updated successfully and sent to your email.", "result" => true, "status" => 1]);
                return;
            }
            else{

                // set response code - 203 ok
                http_response_code(203);
                echo json_encode(["message" => "User password reset FAILED. Please try again.","new_password"=>$data->new_pass, "result"=>false, "status" => 0]);
                return;

            }

        }
        else{
            http_response_code(203);
                echo json_encode(["message" => "You seem to be offline. Please try again when you get online or check your netwrok strength .", "mail_sent" => $mailSent, "status" => 2, "new_pass" => $data->new_pass]);
                return;
        }

            
            
        
            
    } elseif ($updateStatus['outputStatus'] == 1200  && $updateStatus['output'] == false) {
            
        // set response code - 200 ok
        http_response_code(200);
        echo json_encode(["message" => "New password updated FAILED. Try again", "status" => 1]);
        return;
            
    } elseif ($updateStatus['outputStatus'] == 1400) {
            
        // set response code - 203 service unavailable
            http_response_code(203);
            echo json_encode( errorDiag($updateStatus['output']) );
            return;
            
    } else {
            
        // set response code - 203 service unavailable
        http_response_code(203);
        echo json_encode(["message" => "Unable to update user password. Please try again.", "status" => 6]);
        return;
            
    }
} elseif ($user_stmt['outputStatus'] == 1400) {
        
   // set response code - 203 service unavailable
   http_response_code(203);
   echo json_encode( errorDiag($user_stmt['output']) );
   return;
        
} else {
        
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "Unable to update user. Please try again.", "status" => 7]);
    return;
        
}
