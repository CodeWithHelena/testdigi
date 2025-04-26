<?php
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
$product = new product($conn);

// Get posted data
$data = json_decode(file_get_contents("php://input"));


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


// Make sure data is not empty
if (!empty($data->product_id)) {

    // Check if product exists
    $product->product_id = cleanData($data->product_id);

    $productStmt = $product->getProduct();

    if ($productStmt['outputStatus'] == 1200) {

        http_response_code(400);
        echo json_encode(errorDiag($productStmt['output']));
        return;

    }

    $product_to_update = $productStmt['output']->fetch(PDO::FETCH_ASSOC);

    if (!$product_to_update) {
        http_response_code(404);
        echo json_encode(["result"=>false, "message" => "Product not found.", "status" => 0]);
        return;
    }


    // Sanitize & set product property values
    $product->name = isset($data->name) ? strtolower(cleanData($data->name)) : $product_to_update['name'];  

    $product->description = isset($data->description) ? cleanScriptTags($data->description) : $product_to_update['description'];

    $product->image = isset($data->image) ? cleanData($data->image) : $product_to_update['image'];   

    $product->price = isset($data->price) ? cleanData($data->price) : $product_to_update['price'];   

    $product->category_id = isset($data->category_id) ? cleanData($data->category_id) : $product_to_update['category_id']; 

    $product->brand_name = isset($data->brand_name) ? strtolower(cleanData($data->brand_name)) : $product_to_update['brand_name'];    

    $product->sub_unit = isset($data->sub_unit) ? strtolower(cleanData($data->sub_unit)) : $product_to_update['sub_unit'];   

    $product->unit = isset($data->unit) ? strtolower(cleanData($data->unit)) : $product_to_update['unit'];   
    
    $product->quantity = isset($data->quantity) ? cleanData($data->quantity) : $product_to_update['quantity'];  
    
    $product->user_id = isset($data->user_id) ? cleanData($data->user_id) : $product_to_update['user_id']; 

    $product->user_type_id = isset($data->user_type_id) ? cleanData($data->user_type_id) : $product_to_update['user_type_id']; 


    // Create the assignment
    $newproduct = $product->updateProduct();


    if ($newproduct['outputStatus'] == 1000 && $newproduct['output'] == true) {

        http_response_code(201);
        echo json_encode(["message" => "product was created successfully", "status" => 1]);
        return;

    } elseif ($newproduct['outputStatus'] == 1100 && $newproduct['output'] == false) {

        http_response_code(201);
        echo json_encode(["message" => "product was NOT created. Please try again.", "status" => 0]);
        return;

    } elseif ($newproduct['outputStatus'] == 1200) {

        http_response_code(400);
        echo json_encode(errorDiag($newproduct['output']));
        return;

    } else {

        http_response_code(400);
        echo json_encode(["result"=>false, "message" => "Something went wrong", "status" => 2]);
        return;

    }
} else {

    http_response_code(400);
    echo json_encode(["result"=>false, "message" => "Unable to create product. Fill all fields.", "status" => 3]);
    return;

}
