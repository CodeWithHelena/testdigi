<?php
header("Access-Control-Allow-Origin: *" );
header("Content-Type: application/json");
header("Accept: application/json");
header("Access-Control-Allow-Headers:" . $configx['dbconnx']['ALLOWED_HEADERS']);
header("Access-Control-Allow-Methods:" . $configx["dbconnx"]["GET_METHOD"]);


// initialize object
$db = new Database($configx);
$conn = $db->getConnection();

// Check connection
if ($conn == null) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed.", "status" => 4]);
    return;
}

$product = new Product($conn);

// read product id will be here
$product_id = $param_id;
// $product_stmt = null;

// Update product_id param if it exists
// if (!empty($_GET['product_id'])) {
//     $product_id = $_GET['product_id'];
// } else {

//     // set response code - 404 Not found
//     http_response_code(404);
//     // tell the product no products found
//     echo json_encode(["message" => "Plaese provide a valid product ID."]);
//     return;
// }


if ((empty($product_id) || $product_id == null || !is_numeric($product_id) || trim($product_id) == '')) {
    // No valid product id provided

    // set response code - 404 Not found
    http_response_code(404);
    // tell the product no products found
    echo json_encode(["message" => "Plaese provide a valid product ID.", "status" => 3]);
    return;
}


// Call method depending on which parameter is provided
    // query products
    $product->product_id = $product_id;

    $product_stmt = $product->getProduct();

// check if more than 0 record found
if ($product_stmt["outputStatus"] == 1000) {
     
    $result = $product_stmt["output"]->fetch(PDO::FETCH_ASSOC);
   

    if (!$result) {
        // set response code - 200 OK
        http_response_code(404);
        // show products data in json format
        echo json_encode(["result"=>false, "message" => "No product found with this ID:$product_id", "status"=>0]);
        return;
    }

  
    // set response code - 200 OK
    http_response_code(200);
    echo json_encode(["result"=>$result, "message"=>"success", "status"=>1]);
    return;
} 
elseif ($product_stmt['outputStatus'] == 1200) {
    // no products found will be here
    echo json_encode(errorDiag($product_stmt['output']));
    return;
}
else{
    // no products found will be here

    // set response code - 404 Not found
    http_response_code(404);
    echo json_encode(["result"=>false, "message" => "Something went wrong. Not able to fetch product.", "status"=>2]);
}