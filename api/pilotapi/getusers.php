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

// get posted data
$data = json_decode(file_get_contents("php://input"));


// ===== Auth Gate Check =========================

if (!authGateCheck($data)) {
    // set response code - 503 service unavailable
    http_response_code(401);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 30]);
    return;
}


$pilot->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($pilot->user_id) || !isset($pilot->user_id) || is_null($pilot->user_id)) {
     // set response code - 503 service unavailable
     http_response_code(401);
     echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
     return;
}

$pilot_stmt = $pilot->getUser();

if ($pilot_stmt['outputStatus'] == 1400) {
        
    // set response code - 503 service unavailable
    http_response_code(503);
    echo json_encode( errorDiag($pilot_stmt['output']) );
    return;        
}

$pilotData = $pilot_stmt['output']->fetch(PDO::FETCH_ASSOC);

 // If user does not exist
 if (!$pilotData || empty($pilotData) || !authGateCheck2($pilotData, $data)) {
    // set response code - 404 not found
    http_response_code(404);
    echo json_encode(["message" => "User Not Authenticated.", "status" => 0]);
    return;
}

// ===== Auth Gate Check ends here ===============


// Get all users ==================================
$stmt = $pilot->getAllUsers();

// check if more than 0 record found
if ($stmt["outputStatus"] == 1000) {
     
    $result2 = $stmt["output"]->fetchAll(PDO::FETCH_ASSOC);

    if (count($result2) == 0 || !$result2) {
        // set response code - 200 OK
        http_response_code(404);
        echo json_encode(["message" => "No users found.", "status"=>0]);
        return;
    }

   
    $result = [];
    $respage = 1;

    if (isset($data->page) && is_numeric($data->page)) {

        $respage = intval($data->page);

    }

    // Get all eligible admins absed on seniority (authorization added in 3rd condition)
    foreach ($result2 as $res) {
        if ($res['user_id'] >= ($respage*100)-99 && $res['user_id'] <= $respage * 100 && $pilotData['role_id'] > $res['role_id']) {

            unset($res['password']);
            unset($res['user_code']);

            array_push($result, $res);
        }
    }
  

  
    // set response code - 200 OK
    http_response_code(200);
    echo json_encode(["result"=>$result, "status"=>1]);
    return;

} 
elseif ($stmt['outputStatus'] == 1400) {
    // set response code - 503 service unavailable
    http_response_code(503);
    echo json_encode( errorDiag($stmt['output']) );
    return;
}
else{
    // no subjects found will be here

    // set response code - 404 Not found
    http_response_code(404);
    echo json_encode(["message" => "Something went wrong. Not able to fetch subject."]);
}
  

