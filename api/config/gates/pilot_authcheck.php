<?php
$pilot = new Pilot($conn);

if (!authGateCheck($data)) {
    // set response code - 503 service unavailable
    http_response_code(401);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 30]);
    return;
}

// ===== Auth Gate Check =========================

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

// ===== Auth Gate Check 2 ends here ===============



// ===== Authorisation Gate Check ===============

if(isDriver($userData) || isPilot($userData)){
    http_response_code(401);
    // tell the user
    echo json_encode(["message" => "User Not Authorsied to carry out the action.","result" => false, "status" => 40]);
    return;
}

// ===== Authorisation Gate Check ends here ===============






    // ===== Authorisation Gate Check ===============

    // if(isDriver($userData)){
    //     http_response_code(401);
    //     echo json_encode(["message" => "User Not Authorsied to carry out the action.","result" => false, "status" => 40]);
    //     return;
    // }

    // if(isPilot($userData) && $user_to_delete['role_id'] > $xDriver){
    //     http_response_code(401);
    //     echo json_encode(["message" => "User Not Authorsied to carry out the action.","result" => false, "status" => 41]);
    //     return;
    // }

    // ===== Authorisation Gate Check ends here ===============

?>