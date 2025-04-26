<?php
//Only admins has access 
// All admins can access this in order to change the password of any user
// admin must be logged in and and must provide a new valid password
// on success, new password he provided will be sent to the user email.

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
    echo json_encode(["message" => "Database connection failed.", "status" => 7]);
    return;
}

$user = new User($conn);
$pilot = new pilot($conn);


// get user_id of user to be edited
$data = json_decode(file_get_contents("php://input"));


// ===== Auth Gate Check =========================

if (!authGateCheck($data)) {
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 30]);
    return;
}

// Session check
$pilot->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($pilot->user_id) || empty($pilot->user_id) || $pilot->user_id == NULL || intval($pilot->user_id) <= 0) {
     // set response code - 203 service unavailable
     http_response_code(203);
     echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
     return;
}

$pilotStmt = $pilot->getUser();
$pilotData = $pilotStmt['output']->fetch(PDO::FETCH_ASSOC);


if(!$pilotData || !authGateCheck2($pilotData, $data)){
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
    return;
}


// ===== Auth Gate Check ends here ===============

// set user_id property of user to be updated
$user->user_id = cleanData($param_id);

// Checks if active is to be updated
if (empty($user->user_id) || intval($user->user_id) <= 0 || is_null($user->user_id) || !is_numeric($user->user_id)) {
    // set response code - 203 forbidden
    http_response_code(203);
    echo json_encode(["message" => "Please provide your valid user Id to be updated", "status" => 6]);
    return;
} 


// Retrieve the user details
$user_stmt = $user->getUser();


// Handle user retrieval errors
if ($user_stmt['outputStatus'] == 1000) {
        
    $user_to_update = $user_stmt['output']->fetch(PDO::FETCH_ASSOC);
        
    // If user does not exist
    if (!$user_to_update || empty($user_to_update)) {
        // set response code - 203 not found
        http_response_code(203);
        echo json_encode(["message" => "No user found with this ID.", "status" => 0]);
        return;
    }


    
     // Checks if active is to be updated
     if (isset($data->active) && ($data->active == null || cleanData($data->active) || !is_numeric($data->active))) {
        // set response code - 203 forbidden
        http_response_code(203);
        echo json_encode(["message" => "Please provide your valid active status", "status" => 6]);
        return;
    } 
    
    // Checks if role_id is to be updated
     if (isset($data->role_id) && ($data->role_id == null || cleanData($data->role_id) || !is_numeric($data->role_id))) {
        // set response code - 203 forbidden
        http_response_code(203);
        echo json_encode(["message" => "Please provide your valid role_id", "status" => 5]);
        return;
    }
    
    // Checks If password is to be updated
    if (!empty($data->password) && ($data->password == null || cleanData($data->password) == "")) {
        // set response code - 203 forbidden
        http_response_code(203);
        echo json_encode(["message" => "Please provide your valid password", "status" => 4]);
        return;
    }

    // Check for valis lenght of password
    if (!empty($data->password) && strlen($data->password) < 12) {
        // set response code - 203 forbidden
        http_response_code(203);
        echo json_encode(["message" => "Your password must at least 12 characters long", "status" => 3]);
        return;
    }

    // Check for not allowed characters
    if ((!empty($data->firstname) && strpbrk($data->firstname, "<>&")) || (!empty($data->lastname) && strpbrk($data->lastname, "<>&")) || (!empty($data->password) && strpbrk($data->password, "<>&"))) {
        // set response code - 203 forbidden
        http_response_code(203);
        echo json_encode(["message" => "Any Data you provided to be updated can not conatin <, >, or &", "status" => 2]);
        return;
    }

    // http_response_code(203);
    // echo json_encode(["message" => isset($data->active), "status" => 7]);
    // return;
        
    // Update user details
        $user->firstname = empty($data->firstname) ? $user_to_update['firstname'] : cleanData($data->firstname);
        $user->lastname = empty($data->lastname) ? $user_to_update['lastname'] : cleanData($data->lastname);
        $user->password = empty($data->password) ? $user_to_update['password'] : password_hash(cleanData($data->password), PASSWORD_DEFAULT);

        $user->active =  !isset($data->active) ? $user_to_update['active'] : cleanData($data->active);
        $user->role_id = !isset($data->role_id) ? $user_to_update['role_id'] : cleanData($data->role_id);

        // update the user
        $updateStatus = $user->updateUser2();
            
    
    // Check update status
    if ($updateStatus['outputStatus'] == 1000 && $updateStatus['output'] == true) {

        $mailSent = false;

        $whitelist = ['localhost', '127.0.0.1', '::1'];
        if (!in_array($_SERVER['SERVER_NAME'], $whitelist) && !empty(cleanData($data->password))) {

            $user_firstname = $user_to_update['firstname'];

            $to = $user_to_update['email'];
            $subject = "Your New Pawword";
            $message = "Good day $user_firstname, \r\n\n Your new password is $data->new_pass \r\n\n Your faithfully, \r\n CustomerCare";
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
            
        // set response code - 203 service unavailable
        http_response_code(203);
        // tell the user
        echo json_encode(["message" => "Unable to update user. Please try again.", "status" => 9]);
        return;
            
    }
} elseif ($user_stmt['outputStatus'] == 1200) {
    // set response code - 200 ok
    http_response_code(202);
    echo json_encode(errorDiag($user_stmt['output']));
    return;
        
} else {
        
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "Unable to update user. Please try again.", "status" => 10]);
    return;
        
}
