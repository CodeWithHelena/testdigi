<?php
//Only User has access 
// All users can access this in order to change their own password only
// User must be logged in and and must provide a new valid password
// on success, user can login immediately with the new password he provided.

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
$user->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($user->user_id) | $user->user_id == NULL || $user->user_id == "" || intval($user->user_id) <= 0) {
     // set response code - 203 service unavailable
     http_response_code(203);
     echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
     return;
}

$userStmt = $user->getUser();
$userData = $userStmt['output']->fetch(PDO::FETCH_ASSOC);


if(!$userData || !authGateCheck2($userData, $data)){
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
    return;
}

// ===== Auth Gate Check ends here ===============


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


    // Current password gate check ====================================================

    if (empty($data->cur_pass) || $data->cur_pass == null || $data->cur_pass == "") {
        // set response code - 203 forbidden
        http_response_code(203);
        echo json_encode(["message" => "Please provide your current Password to authorize this kind of update", "status" => 3]);
        return;
    }

    if (strpbrk($data->cur_pass, "<>&")) {
        // set response code - 203 forbidden
        http_response_code(203);
        echo json_encode(["message" => "Please your current Password can not conatin <, > or &", "status" => 3]);
        return;
    }

    // clean other properties
    $data->cur_pass = cleanData($data->cur_pass);

      // confirm password
      $passCheck = $user->verifyPass($data->cur_pass, $user_to_update['password']);

      // If password check is false, update the user ad exit
      if (!$passCheck) {
          // set response code - 203 not found
          http_response_code(203);
          echo json_encode(["message" => "Wrong user password. Password NOT Updated.", "status" => 5]);
          return;
      }



    // Current password gate check ====================================================



    // Checks If password is to be updated
    if (!empty($data->password) && ($data->password == null || cleanData($data->password) == "")) {
        // set response code - 203 forbidden
        http_response_code(203);
        echo json_encode(["message" => "Please provide your valid password", "status" => 6]);
        return;
    }

    // Check for valis lenght of password
    if (!empty($data->password) && strlen($data->password) < 12) {
        // set response code - 203 forbidden
        http_response_code(203);
        echo json_encode(["message" => "Your password must at least 12 characters long", "status" => 7]);
        return;
    }

    // Check for not allowed characters
    if ((!empty($data->firstname) && strpbrk($data->firstname, "<>&")) || (!empty($data->lastname) && strpbrk($data->lastname, "<>&")) || (!empty($data->password) && strpbrk($data->password, "<>&"))) {
        // set response code - 203 forbidden
        http_response_code(203);
        echo json_encode(["message" => "Any Data you provided to be updated can not conatin <, >, or &", "status" => 7]);
        return;
    }


        // Update user details
        $user->firstname = empty($data->firstname) ? $user_to_update['firstname'] : cleanData($data->firstname);
        $user->lastname = empty($data->lastname) ? $user_to_update['lastname'] : cleanData($data->lastname);
        $user->password = empty($data->password) ? $user_to_update['password'] : cleanData(password_hash($data->password, PASSWORD_DEFAULT));


        // update the user
        $updateStatus = $user->updateUser();
            
    // Check update status
    if ($updateStatus['outputStatus'] == 1000 && $updateStatus['output'] == true) {
            
        // set response code - 200 ok
        http_response_code(200);
        echo json_encode(["message" => "User was updated successfully.", "status" => 1]);
        return;
            
    } elseif ($updateStatus['outputStatus'] == 1200  && $updateStatus['output'] == false) {
            
        // set response code - 200 ok
        http_response_code(202);
        echo json_encode(["message" => "User updated FAILED. Try again.", "status" => 7]);
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
        echo json_encode(["message" => "Unable to update user. Please try again.", "status" => 5]);
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
    echo json_encode(["message" => "Unable to update user. Please try again.", "status" => 6]);
    return;
        
}
