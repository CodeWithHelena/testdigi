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
    echo json_encode(["message" => "Database connection failed.", "status" => 3]);
    return;
}

$pilot = new Pilot($conn);


// get user_id of user to be edited
$data = json_decode(file_get_contents("php://input"));


// ===== Auth Gate Check =========================
if (!authGateCheck($data)) {
    // set response code - 503 service unavailable
    http_response_code(401);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 30]);
    return;
}

$pilot->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($pilot->user_id)) {
     // set response code - 503 service unavailable
     http_response_code(401);
     echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
     return;
}

$pilotStmt = $pilot->getUser();
$pilotData = $pilotStmt['output']->fetch(PDO::FETCH_ASSOC);


if(!authGateCheck2($pilotData, $data)){
    // set response code - 503 service unavailable
    http_response_code(401);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
    return;
}

// ===== Auth Gate Check ends here ===============

// Handle string return
if ($pilot->userLogout()) {

    // set response code - 200 ok
    http_response_code(200);
    echo json_encode(["message" => "User logged out successfully", "result"=>true, "status" => 1]);
    return;

}  else {
    // if user does not exist

    // set response code - 503 service unavailable
    http_response_code(400);
    echo json_encode(["message" =>"User logout FAILED. Try again.", "result"=> false, "status" => 4]);
    return;

}
