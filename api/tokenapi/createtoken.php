<?php
/*
1) CREATE A TOKEN
API: 
/api/token/createone

PARAMS:
created_by: (integer | required) id of user creating the token

RESPONSE:
json of success or failure message
*/
header("Access-Control-Allow-Origin: *" );
header("Content-Type: application/json");
header("Accept: application/json");
header("Access-Control-Allow-Methods: post");
header("Access-Control-Allow-Headers:" . $configx['dbconnx']['ALLOWED_HEADERS']);

// Initialize database connection
$db = new Database($configx);
$conn = $db->getConnection();

// Check connection
if ($conn == null) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed.", "status" => 7]);
    return;
}

$user = new User($conn);
$token = new Token($conn);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

/*
// ===== Auth Gate Check =========================
if (!authGateCheck($data)) {
    http_response_code(401);
    echo json_encode(["message" => "User Not authenticated.", "result" => false, "status" => 30]);
    return;
}

// Session check
$user->user_id = cleanData(scrubUserCode2($data));

if (!is_numeric($user->user_id)) {
    http_response_code(401);
    echo json_encode(["message" => "User Not authenticated.", "result" => false, "status" => 31]);
    return;
}

$userStmt = $user->getUser();
$userData = $userStmt['output']->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    http_response_code(401);
    echo json_encode(["message" => "User Not authenticated.", "result" => false, "status" => 32]);
    return;
}

// ===== Auth Gate Check 2 =====================

if (!authGateCheck2($userData, $data)) {
    http_response_code(401);
    echo json_encode(["message" => "User Not authenticated.", "result" => false, "status" => 33]);
    return;
} 

// ======== ======================= ================
*/

// Make sure data is not empty
if (!empty($data->created_by) || !empty($data->no_of_toks)) {

    // Sanitize & set token property values
    $token->created_by = cleanData($data->created_by);   

    // Create the assignment
    $newtoken = $token->createToken2($data->no_of_toks);

    if ($newtoken['outputStatus'] == 1000 && $newtoken['output'] == true) {

        http_response_code(201);
        echo json_encode(["result"=>true, "message" => "token was created successfully", "status" => 1]);
        return;

    } elseif ($newtoken['outputStatus'] == 1100 && $newtoken['output'] == false) {

        http_response_code(203);
        echo json_encode(["result"=>false,"message" => "token was NOT created. Please try again.", "status" => 0]);
        return;

    } elseif ($newtoken['outputStatus'] == 1200) {

        http_response_code(203);
        echo json_encode(errorDiag($newtoken['output']));
        return;

    } else {

        http_response_code(203);
        echo json_encode(["result"=>false, "message" => "Something went wrong", "status" => 2]);
        return;

    }
} else {

    http_response_code(203);
    echo json_encode(["result"=>false, "message" => "Unable to create token. Fill all fields.", "status" => 3]);
    return;

}
