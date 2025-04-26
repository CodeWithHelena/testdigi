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
    echo json_encode(["message" => "Database connection failed.", "status" => 2]);
    return;
}

// http_response_code(500);
// echo json_encode(["message" => "Not authorised.", "status" => 403]);
// return;

$pilot = new Pilot($conn);

// get posted data
$data = json_decode(file_get_contents("php://input"));


// Check for valid pilot email
if (empty($data->email) || $data->email == null || trim($data->email) == "" || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    // set response code - 403 forbidden
    http_response_code(403);
    // tell the pilot
    echo json_encode(["message" => "Please provide your valid email", "status" => 3]);
    return;
}

// Check for valid pilot email
if (empty($data->firstname) || $data->firstname == null || trim(cleanData($data->firstname)) == "" || strlen($data->firstname) < 3) {
    // set response code - 403 forbidden
    http_response_code(403);
    // tell the pilot
    echo json_encode(["message" => "Please provide your valid firstname", "status" => 4]);
    return;
}

// Check for valid pilot lastname
if (empty($data->lastname) || $data->lastname == null || trim(cleanData($data->lastname)) == "" || strlen($data->lastname) < 3) {
    // set response code - 403 forbidden
    http_response_code(403);
    echo json_encode(["message" => "Please provide your valid lastname", "status" => 5]);
    return;
}

// Check for valid pilot lastname
if (empty($data->phone) || $data->phone == null || trim(cleanData($data->phone)) == "") {
    // set response code - 403 forbidden
    http_response_code(403);
    echo json_encode(["message" => "Please provide your valid phone number", "status" => 5]);
    return;
}

// Check for valid pilot email
if (empty($data->password) || $data->password == null || cleanData($data->password) == "") {
    // set response code - 403 forbidden
    http_response_code(403);
    echo json_encode(["message" => "Please provide your valid password", "status" => 6]);
    return;
}

// Check for valid pilot email
if (strlen($data->password) < 12) {
    // set response code - 403 forbidden
    http_response_code(403);
    echo json_encode(["message" => "Your password must at least 12 characters long", "status" => 7]);
    return;
}

 // Check for valid pilot email
 if (strpbrk($data->firstname, "<>&") || strpbrk($data->lastname, "<>&") || strpbrk($data->email, "<>&") || strpbrk($data->password, "<>&")) {
    // set response code - 403 forbidden
    http_response_code(403);
    echo json_encode(["message" => "Your firstname, lastname, email or password can not conatin <, >, or &", "status" => 7]);
    return;
}

// Sanitize data
$data->email = cleanData($data->email);
$data->firstname = cleanData($data->firstname);
$data->lastname = cleanData($data->lastname);
$data->phone = cleanData($data->phone);
$data->password = cleanData($data->password);


// make sure data is not empty
if ($data->firstname != "" && $data->email != "" && $data->lastname != "" && $data->phone != "" && $data->password != "") {

    // Assign pilot email for search
    $pilot->email = $data->email;

    // Check if the pilot email already exists
    $pilot_stmt = $pilot->getUserByEmail();

    if ($pilot_stmt['outputStatus'] == 1000) {
        
        $pilotExits = $pilot_stmt['output']->fetch(PDO::FETCH_ASSOC);

        if($pilotExits) {

            // set response code - 200 ok
            http_response_code(400);
            echo json_encode(["message" => "email already exists. Please choose another one.", "status" => 0]);
            return;

        }


        // Sanitize & set pilot property values
        $pilot->email = $data->email;
        $pilot->firstname = $data->firstname;
        $pilot->lastname = $data->lastname;
        $pilot->phone = $data->phone;
        $pilot->password = $data->password;

        // create the pilot
        $new_pilot = $pilot->createUser();

        // var_dump($new_pilot);
        // return;

        if ($new_pilot['outputStatus'] == 1000 || $new_pilot['output'] == true) {

            // Send new pilot password ro email
            $to = $data->email;
            $subject = 'New pilot Registration';
            $message = 'We are delighted to have you onboard our Akanta community.' . "\r\n" . 'Your password is: ' . $data->password;
            $headers = 'From: noreply@akanta.com'. "\r\n" . 'Reply-To: noreply@akanta.com'. "\r\n" . 'X-Mailer: PHP/'. phpversion();

            $mailSent = false;

            // Only send mail if not on localhost
            $whitelist = ['localhost', '127.0.0.1', '::1'];
            if (!in_array($_SERVER['SERVER_NAME'], $whitelist)) {
                $mailSent = mail($to, $subject, $message, $headers);
                 
                http_response_code(201);
                echo json_encode(["message" => "New pilot was created successfully and your password has been sent to your email.","mailsent"=>$mailSent, "status" => 1]);
                return;

            }

            // set response code - 201 created
            http_response_code(201);
            echo json_encode(["message" => "New pilot was created successfully and your password is $data->password","mailsent"=>$mailSent, "status" => 1]);
            return;
            
        }
        elseif ($new_pilot['outputStatus'] == 1200 || $new_pilot['output'] == false) {

            // set response code - 201 created
            http_response_code(201);
            echo json_encode(["message" => "New pilot creation FAILED.Try again.", "status" => 1]);
            return;
            
        }
        elseif ($new_pilot['outputStatus'] == 1400) {

             // set response code - 503 service unavailable
             http_response_code(503);
             echo json_encode( errorDiag($new_stmt['output']) );
             return;
        }
        else {
            // set response code - 200 ok
            http_response_code(400);

            // tell the pilot
            echo json_encode(["message" => "Something went wrong. Please try again", "status" => 3]);
            return;
        }

    }elseif ($pilot_stmt['outputStatus'] == 1200) {

         // set response code - 503 service unavailable
        http_response_code(503);
        // tell the pilot
        echo json_encode( errorDiag($pilot_stmt['output']) );
        return;
    }
    else {
        // set response code - 200 ok
        http_response_code(400);
        // tell the pilot
        echo json_encode(["message" => "Network issues. Try again.", "status" => 4]);
        return;
    }

} else {

    // set response code - 400 bad request
    http_response_code(400);
    echo json_encode(["message" => "Unable to create pilot. Fill all fields.", "status" => 5]);
    return;

}


