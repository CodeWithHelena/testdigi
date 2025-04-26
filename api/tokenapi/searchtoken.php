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

// User gate will be implemented here
$user = new User($conn);

$product = new Product($conn);

// get admin_no of user to be edited
$data = json_decode(file_get_contents("php://input"));

 // ===== Auth Gate Check =========================
 if (!authGateCheck($data)) {
    // set response code - 503 service unavailable
    http_response_code(401);
    // tell the user
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 30]);
    return;
}

// Session check
$user->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($user->user_id)) {
     // set response code - 503 service unavailable
     http_response_code(401);
     // tell the user
     echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
     return;
}

$userStmt = $user->getUser();
$userData = $userStmt['output']->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    // set response code - 503 service unavailable
    http_response_code(401);
    // tell the user
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 32]);
    return;
}

// ===== Auth Gate Check 2  =====================

if(!authGateCheck2($userData, $data)){
    // set response code - 503 service unavailable
    http_response_code(401);
    // tell the user
    echo json_encode(["message" => "User Not authentificated.","result" => false, "status" => 31]);
    return;
}

// ===== Auth Gate Check ends here ===============



// Check for valid admin_no
if (empty($data->searchstring) || $data->searchstring == null || $data->searchstring == ' ') {
    // set response code - 503 service unavailable
    http_response_code(403);
    echo json_encode(["message" => "Please provide what you are searching for", "status" => 2]);
    return;
}

// Check for valid new password
if (empty($data->searchcolumn) || $data->searchcolumn == null || $data->searchcolumn == ' ') {
    // set response code - 503 service unavailable
    http_response_code(403);
    echo json_encode(["message" => "Please provide a valid table column name to search in", "status" => 3]);
    return;
}

$searchString = cleanData($data->searchstring);
$searchColumn = cleanData($data->searchcolumn);

// Get the product whose details are to be updated 
$search_stmt = $product->searchproduct($searchString, $searchColumn);


if ($search_stmt['outputStatus'] == 1000) {

    $search_result = $search_stmt['output']->fetchAll(PDO::FETCH_ASSOC);

    if (count($search_result) == 0) {
        // set response code -
        http_response_code(404);

        // tell the product
        echo json_encode(["message" => "No product found for this search word : $searchString", "status" => 0]);
        return;
    }

    // set response code - 200 ok
    http_response_code(200);
    echo json_encode(["message" => "Success","result"=>$search_result, "status" => 1]);
    return;

} elseif ($search_stmt['outputStatus'] == 1200) {
    echo json_encode(errorDiag($search_stmt['output']));
    return;
    
} else {

    // set response code - 503 service unavailable
    http_response_code(503);
    echo json_encode(["message" => "No product found for this search item", "status" => 200]);
}
