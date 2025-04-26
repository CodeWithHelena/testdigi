<?php
header("Access-Control-Allow-Origin: *" );
header("Content-Type: application/json");
header("Accept: application/json");
header("Access-Control-Allow-Methods: post");
header("Access-Control-Allow-Headers:" . $configx['dbconnx']['ALLOWED_HEADERS']);

// Set return sessioncode name for easy maintability
// Set them in config file
$usc_name = $configx["dbconnx"]["USC"];
$lsc_name = $configx["dbconnx"]["LSC"];
$ssc_name = $configx["dbconnx"]["SSC"];

// initialize object
$db = new Database($configx);
$conn = $db->getConnection();

// Check connection
if ($conn == null) {
    http_response_code(500);
    echo json_encode(["result"=>false, "message" => "Database connection failed.", "status" => 5]);
    return;
}

$pilot = new Pilot($conn);

// get user_id of pilot to be edited
$data = json_decode(file_get_contents("php://input"));

// Check for valid email
if (empty($data->email) || $data->email == null || cleanData($data->email) == '' || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {

    // set response code - 503 service unavailable
    http_response_code(203);
    echo json_encode(["result"=>false, "message" => "Please provide a valid email", "status" => 4]);
    return;
}

// Check for valid password
if (empty($data->password) || $data->password == null || cleanData($data->password) == '') {

    // set response code - 503 service unavailable
    http_response_code(203);
    echo json_encode(["result"=>false, "message" => "Please provide a valid password", "status" => 3]);
    return;

}

// Sanitize and set email property
$pilot->email = cleanData($data->email);
// Sanitize provided password
$pilot->password = cleanData($data->password);

// Check if pilot with this login details exists
$login_stmt = $pilot->userLogin();

// Check if pilot exists
if ($login_stmt['outputStatus'] == 1000) {
        
    $pilot_to_login = $login_stmt['output']->fetch(PDO::FETCH_ASSOC);

    // If pilot does not exist
    if (!$pilot_to_login || empty($pilot_to_login)) {
        // set response code - 203 not found
        http_response_code(203);
        echo json_encode(["result"=>false, "message" => "Wrong email or Password.", "status" => 0]);
        return;
    }

      
    // confirm password
    $passCheck = $pilot->verifyPass($data->password, $pilot_to_login['password']);

    // If password check is false, update the pilot ad exit
    if (!$passCheck) {
        // set response code - 203 not found
        http_response_code(203);
        echo json_encode(["result"=>false, "message" => "Wrong email or pasword. Try again.", "status" => 2]);
        return;
    } 

        // Else login passes, generate session code and deliver pilot data
        $pilot->user_id = $pilot_to_login['user_id'];
        $genCheck = $pilot->reGenerateUserCode();

        // Update session
        $pilot_to_login['user_code'] = prepareUserCode($pilot->user_code);
        $lost_code = prepareUserCode2();
        $sest_code = prepareUserCode2();

        // Scrub password
        unset($pilot_to_login['password']);

        // set response code - 203 not found
        http_response_code(200);
        echo json_encode(["result"=>true, "message" => "Login successful.", $usc_name => $pilot_to_login['user_code'], $lsc_name => $lost_code, $ssc_name => $sest_code, "status" => 1]);
        return;


    
} elseif ($pilot_stmt['outputStatus'] == 1400) {
     
    // set response code - 503 service unavailable
    http_response_code(203);
    echo json_encode( errorDiag($pilot_stmt['output']) );
    return;
        
} else {
        
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["result"=>false, "message" => "Unable to update user. Please try again.", "status" => 6]);
    return;
        
}
