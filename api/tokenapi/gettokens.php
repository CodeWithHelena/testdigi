<?php
/*
1) GET ALL TOKENS
API: 
/api/token/getall

PARAMS:
Nil

RESPONSE:
success: json of token arrays
failure: josn of message
*/
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

$user = new User($conn);

$product = new Token($conn);

$data = json_decode(file_get_contents("php://input"));


/*

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


*/


$stmt = $product->getAllTokens();

// check if more than 0 record found
if ($stmt["outputStatus"] == 1000) {
     
    $result = $stmt["output"]->fetchAll(PDO::FETCH_ASSOC);

    // var_dump($result);
    // return;
    
    if (count($result) == 0 || !$result) {
        // set response code - 200 OK
        http_response_code(203);
        echo json_encode(["result"=>false, "message" => "No tokens found.", "status"=>0]);
        return;
    }

  
    // set response code - 200 OK
    http_response_code(200);
    // show products data in json format
    echo json_encode(["result"=>$result, "message"=>"success", "status"=>1]);
    return;
} 
elseif ($stmt['outputStatus'] == 1400) {
    // no products found will be here
      http_response_code(203);
      echo json_encode(errorDiag($stmt['output']));
    return;
}
else{
    // no products found will be here
    http_response_code(203);
    echo json_encode(["result"=>false, "message" => "Something went wrong. Not able to fetch token.", "status" =>2]);
}