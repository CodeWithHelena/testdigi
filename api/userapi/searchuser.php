<?php
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
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed.", "status" => 7]);
    return;
}

$user = new User($conn);

// get admin_no of user to be edited
$data = json_decode(file_get_contents("php://input"));

// Check for valid admin_no
if (empty($data->searchstring) || $data->searchstring == null || trim($data->searchstring) == '') {
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "Please provide what you are searching for", "status" => 2]);
    return;
}

// Check for valid new password
if (empty($data->searchcolumn) || $data->searchcolumn == null || trim($data->searchcolumn) == '') {
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "Please provide a valid table column name to search in", "status" => 3]);
    return;
}


// ===== Auth Gate Check =========================

if (!authGateCheck($data)) {
    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 30]);
    return;
}

$pilot = new Pilot($conn);

$pilot->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($pilot->user_id) || !isset($pilot->user_id) || is_null($pilot->user_id) || !authGateCheck($data)) {
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
 if (!$pilotData || empty($pilotData) || !authGateCheck2($pilotData, $data)) {
    // set response code - 203 not found
    http_response_code(203);
    echo json_encode(["message" => "User Not Authenticated.", "status" => 0]);
    return;
}


// ===== Auth Gate Check ends here ===============



// ===== Authorisation Gate Check =========================


// ===== Authorisation Gate Check ends here ===============


$searchString = cleanData($data->searchstring);
$searchColumn = cleanData($data->searchcolumn);

// Get the user whose details are to be updated 
$search_stmt = $user->searchUser($searchString, $searchColumn);

// var_dump($search_stmt);
// return;

if ($search_stmt['outputStatus'] == 1000) {

    $search_result = $search_stmt['output']->fetchAll(PDO::FETCH_ASSOC);

    if (count($search_result) == 0) {
        // set response code -
        http_response_code(203);

        // tell the user
        echo json_encode(["message" => "No user found for this search word : $searchString", "status" => 0]);
        return;
    }

    $result = [];

    // Remove the password keys
    foreach ($search_result as $res){
        unset($res['password']);
        unset($res['user_code']);

        array_push($result, $res);
    };

    // Obscure pass

    // set response code - 200 ok
    http_response_code(200);
    echo json_encode(["message" => "Success","result"=>$result, "status" => 1]);
    return;

} elseif ($search_stmt['outputStatus'] == 1400) {

    // set response code - 200 ok
    http_response_code(200);
    echo json_encode(errorDiag($search_stmt['output']));
    return;
    
} else {

    // set response code - 203 service unavailable
    http_response_code(203);
    echo json_encode(["message" => "No user found for this search item", "status" => 200]);
}
