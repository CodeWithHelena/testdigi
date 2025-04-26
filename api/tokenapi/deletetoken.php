<?php
// required headers
header("Access-Control-Allow-Origin: *" );
header("Content-Type: application/json");
header("Accept: application/json");
header("Access-Control-Allow-Headers:" . $configx['dbconnx']['ALLOWED_HEADERS']);
header("Access-Control-Allow-Methods: " . $configx["dbconnx"]["DEL_METHOD"]);

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
$package = new Package($conn);


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

// ===== Authorisation Gate Check ===============

if(isDriver($userData)){
    http_response_code(401);
    echo json_encode(["message" => "You are Not Authorsied to delete package. Please contact your supervisor.","result" => false, "status" => 40]);
    return;
}

// ===== Authorisation Gate Check ends here ===============


// Check for valid package_id
if (empty($data->package_id) || !is_numeric($data->package_id) || intval($data->package_id) <= 0) {
    // set response code - 403 forbidden
    http_response_code(403);
    // tell the user
    echo json_encode(["message" => "Please provide a valid package id", "status" => 2]);
    return;
}

// set package_id property of package to be edited
$package->package_id = cleanData($data->package_id);

// Retrieve the package details
$package_stmt = $package->getPackage();

// Handle package retrieval errors
if ($package_stmt['outputStatus'] == 1000) {
        
    $package_to_delete = $package_stmt['output']->fetch(PDO::FETCH_ASSOC);
        

    // If package does not exist
    if (!$package_to_delete || empty($package_to_delete)) {
        // set response code - 404 not found
        http_response_code(404);
        // tell the package
        echo json_encode(["message" => "No package found with this ID.", "status" => 0]);
        return;
    }


        // update the package
        $deleteStatus = $package->deletePackage();
        
    // Check delete status
    if ($deleteStatus['outputStatus'] == 1000 && $deleteStatus['output'] == true) {
            
        // set response code - 200 ok
        http_response_code(200);
        // tell the package
        echo json_encode(["message" => "Package was deleted successfully.", "status" => 1]);
        return;
            
    } 
    elseif ($deleteStatus['outputStatus'] == 1200 && $deleteStatus['output'] == false) {

        // set response code - 201 created
        http_response_code(201);
        // tell the package
        // echo json_encode(array("message" => "package was created. Please check your email for your verification link","mailSent"=>$mailSent));
        echo json_encode(["message" => "package was NOT deleted. Please try again.", "status" => 0]);
        return;
   }
    elseif ($deleteStatus['outputStatus'] == 1400) {
        // set response code - 503 service unavailable
        http_response_code(503);
        echo json_encode(errorDiag($deleteStatus['output']));
        return;
            
    } else {
            
        // set response code - 503 service unavailable
        http_response_code(503);
        // tell the package
        echo json_encode(["message" => "Unable to delete package. Please try again.", "status" => 5]);
        return;
            
    }
} elseif ($package_stmt['outputStatus'] == 1200) {
        
    // set response code - 503 service unavailable
    http_response_code(503);
    echo json_encode(errorDiag($package_stmt['output']));
    return;
        
} else {
        
    // set response code - 503 service unavailable
    http_response_code(503);
    // tell the package
    echo json_encode(["message" => "Unable to delete package. Please try again.", "status" => 6]);
    return;
        
}
