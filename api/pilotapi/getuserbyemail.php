<?php
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

$pilot = new Pilot($conn);


$data = json_decode(file_get_contents("php://input"));

if (empty($data->email) || $data->email == NULL || cleanData($data->email) == "" || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(300);
    echo json_encode(["message" => "Please provide a valid email", "status" => 7]);
    return;
}

if (strpbrk($data->email, "<>&")) {
    http_response_code(300);
    echo json_encode(["message" => "Email can not contain <, > or &", "status" => 8]);
    return;
}


// ===== Auth Gate Check =========================

if (!authGateCheck($data)) {
    // set response code - 503 service unavailable
    http_response_code(401);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 30]);
    return;
}

$pilot2 = new Pilot($conn);

$pilot2->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($pilot2->user_id) || !isset($pilot2->user_id) || is_null($pilot2->user_id) || !authGateCheck($data)) {
     // set response code - 503 service unavailable
     http_response_code(401);
     echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
     return;
}

$pilot2_stmt = $pilot2->getUser();

if ($pilot2_stmt['outputStatus'] == 1400) {
        
    // set response code - 503 service unavailable
    http_response_code(503);
    echo json_encode( errorDiag($pilot2_stmt['output']) );
    return;        
}

$pilot2Data = $pilot2_stmt['output']->fetch(PDO::FETCH_ASSOC);

 // If user does not exist
 if (!$pilot2Data || empty($pilot2Data) || !authGateCheck2($pilot2Data, $data)) {
    // set response code - 404 not found
    http_response_code(404);
    echo json_encode(["message" => "User Not Authenticated.", "status" => 0]);
    return;
}

// ===== Auth Gate Check ends here ===============


// query users
$pilot->email = cleanData($data->email);

$pilot_stmt = $pilot->getUserByEmail();

// check if more than 0 record found
if ($pilot_stmt["outputStatus"] == 1000) {
     
    $pilotData = $pilot_stmt["output"]->fetch(PDO::FETCH_ASSOC);
    
    if (!$pilotData) {
        // set response code - 200 OK
        http_response_code(404);
        echo json_encode(["message" => "No User found with this ID", "status"=>0]);
        return;
    }


// ===== Authorisation Gate Check ===============

if($pilot2Data['role_id'] < $pilotData['role_id']){

    // set response code - 200 OK
    http_response_code(404);
    echo json_encode(["message" => "User not authorized to perfrom this action.", "status"=>40]);
    return;
    
}

// ===== Authorisation Gate Check ends here ===============

    // Remove password   
    unset($pilotData['password']);
    unset($pilotData['user_code']);

    http_response_code(200);
    echo json_encode(["pilotData"=>$pilotData, "status"=>1]);
    return;

} 
elseif ($pilot_stmt['outputStatus'] == 1200) {
    // set response code - 200 OK
    http_response_code(200);
    echo json_encode(["message" => "No User found with this email", "status"=>22]);
    return;
}
elseif ($pilot_stmt['outputStatus'] == 1400) {
    //  // set response code - 503 service unavailable
    http_response_code(503);
    echo json_encode(errorDiag($pilot_stmt['output']));
    return;
}
else{
    http_response_code(404);
    echo json_encode(["message" => "Something went wrong. Not able to fetch subject."]);
}