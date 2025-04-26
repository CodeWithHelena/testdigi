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
    // tell the user
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 32]);
    return;
}

// Session check
$pilot->user_id = scrubUserCode2($data);
$pilotStmt = $pilot->getUser();
$pilotData = $pilotStmt['output']->fetch(PDO::FETCH_ASSOC);

if (!$pilotData) {
    // set response code - 503 service unavailable
    http_response_code(401);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 32]);
    return;
}


if(!authGateCheck2($pilotData, $data)){
    // set response code - 503 service unavailable
    http_response_code(401);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 30]);
    return;
}

 // set response code - 503 service unavailable
 http_response_code(200);
 echo json_encode(["message" => "User authentificated.","result" => true, "status" => 31]);
 return;