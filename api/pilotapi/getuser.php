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

// read pilot id will be here
$pilot->user_id = cleanData($param_id);


if ((empty($pilot->user_id) || is_null($pilot->user_id) || !is_numeric($pilot->user_id) || $pilot->user_id == '')) {
    // set response code - 404 Not found
    http_response_code(404);
    echo json_encode(["message" => "Plaese provide a valid pilot ID"]);
    return;
}

$data = json_decode(file_get_contents("php://input"));


// ===== Auth Gate Check =========================

if (!authGateCheck($data)) {
    // set response code - 503 service unavailable
    http_response_code(401);
    echo json_encode(["message" => "User not authentificated.","result" => false, "status" => 30]);
    return;
}

$pilot2 = new Pilot($conn);

$pilot2->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($pilot2->user_id) || empty($pilot2->user_id) || !isset($pilot2->user_id) || is_null($pilot2->user_id) || !authGateCheck($data)) {
     // set response code - 503 service unavailable
     http_response_code(401);
     echo json_encode(["message" => "User not authentificated.","result" => false, "status" => 31]);
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

 // If pilot2 does not exist
 if (!$pilot2Data || !authGateCheck2($pilot2Data, $data)) {
    // set response code - 404 not found
    http_response_code(404);
    echo json_encode(["message" => "User not Authenticated.", "status" => 0]);
    return;
}

// ===== Auth Gate Check ends here ===============


// query pilots
$pilot_stmt = $pilot->getUser();

// check if more than 0 record found
if ($pilot_stmt["outputStatus"] == 1000) {
     
    $pilotData = $pilot_stmt["output"]->fetch(PDO::FETCH_ASSOC);
    
    if (!$pilotData) {
        // set response code - 200 OK
        http_response_code(404);
        echo json_encode(["message" => "No User found for this ID", "status"=>0]);
        return;
    }


    
// ====== Authorization gate check =============

if($pilot2Data['role_id'] < $pilotData['role_id']){

     // set response code - 200 OK
     http_response_code(404);
     echo json_encode(["message" => "User not authorized to perfrom this action.", "status"=>40]);
     return;
     
}

// ====== Authorization gate check ends here =============

    // Remove password   
    unset($pilotData['password']);
    unset($pilotData['user_code']);

    // set response code - 200 OK
    http_response_code(200);
    echo json_encode(["result"=>$pilotData, "status"=>1]);
    return;
} 
elseif ($stmt['outputStatus'] == 1400) {
    //  // set response code - 503 service unavailable
    http_response_code(503);
    echo json_encode( errorDiag($stmt['output']) );
    return;
}
else{

    // set response code - 404 Not found
    http_response_code(404);
    echo json_encode(["message" => "Something went wrong. Not able to fetch subject."]);
    return;
}