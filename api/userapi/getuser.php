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
    http_response_code(203);
    echo json_encode(["message" => "Database connection failed.", "status" => 7]);
    return;
}

$user = new User($conn);

// read user id will be here
$user->user_id = cleanData($param_id);


if ((empty($user->user_id) || is_null($user->user_id) || !is_numeric($user->user_id) || $user->user_id == '')) {
    // set response code - 203 Not found
    http_response_code(203);
    echo json_encode(["message" => "Plaese provide a valid user ID"]);
    return;
}

$data = json_decode(file_get_contents("php://input"));


// ===== Auth Gate Check =========================

if (!authGateCheck($data)) {
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 30]);
    return;
}

$pilot = new Pilot($conn);

$pilot->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($pilot->user_id) || empty($pilot->user_id) || !isset($pilot->user_id) || is_null($pilot->user_id) || !authGateCheck($data)) {
     // set response code - 203 service unavailable
     http_response_code(203);
     echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
     return;
}

$pilot_stmt = $pilot->getUser();

if ($pilot_stmt['outputStatus'] == 1400) {
        
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode( errorDiag($pilot_stmt['output']) );
    return;        
}

$pilotData = $pilot_stmt['output']->fetch(PDO::FETCH_ASSOC);

 // If user does not exist
 if (!$pilotData || !authGateCheck2($pilotData, $data)) {
    // set response code - 203 not found
    http_response_code(203);
    echo json_encode(["message" => "User Not Authenticated.", "status" => 0]);
    return;
}

// ===== Auth Gate Check ends here ===============

// query users
$stmt = $user->getUser();
// var_dump($stmt);
// return;

// check if more than 0 record found
if ($stmt["outputStatus"] == 1000) {
     
    $result = $stmt["output"]->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        // set response code - 200 OK
        http_response_code(203);
        echo json_encode(["message" => "No User found with this ID:$user_id", "status"=>0]);
        return;
    }

    // Remove password   
    unset($result['password']);
    unset($result['user_code']);

    // set response code - 200 OK
    http_response_code(200);
    echo json_encode(["result"=>$result, "status"=>1]);
    return;
} 
elseif ($stmt['outputStatus'] == 1200) {
    // set response code - 200 OK
    http_response_code(200);
    echo json_encode(["message" => "No subject found with this ID:$user_id", "status"=>22]);
    return;
}
elseif ($stmt['outputStatus'] == 1400) {
    //  // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode( errorDiag($stmt['output']) );
    return;
}
else{

    // set response code - 203 Not found
    http_response_code(203);
    echo json_encode(["message" => "Something went wrong. Not able to fetch subject."]);
    return;
}