<?php

include './config/autoloader.php';

// // required headers
// header("Access-Control-Allow-Origin:" . $configx["dbconnx"]["ORIGIN"]);
// header("Content-Type:" . $configx["dbconnx"]["CONTENT_TYPE"]);
// header("Access-Control-Max-Age:" . $configx["dbconnx"]["MAX_AGE"]);
// header("Access-Control-Allow-Headers:" . $configx["dbconnx"]["ALLOWED_HEADERS"]);

// $reqUrl_1 = str_replace("//", "", $reqUrl);
$reqUrlArr = explode("/", $reqUrl);

// http_response_code(200);
// echo json_encode(["reqUrl" => $_SERVER['HTTP_HOST'], "res"=>false]);
// return;

/*
$udomain = $reqUrlArr[0];
$uapibox = $reqUrlArr[1] ?? NULL;
$uresource = $reqUrlArr[2] ?? NULL;
$uaction = $reqUrlArr[3] ?? NULL;
$param_id = $reqUrlArr[4] ?? NULL;
*/

// if ($_SERVER['HTTP_HOST'] == "localhost") {
    $udomain = $reqUrlArr[1];
    $uapibox = $reqUrlArr[2] ?? NULL;
    $uresource = $reqUrlArr[3] ?? NULL;
    $uaction = $reqUrlArr[4] ?? NULL;
    $param_id = $reqUrlArr[5] ?? NULL;
// }

$coreUrl = "$udomain/$uapibox/$uresource/$uaction";


// http_response_code(200);
// echo json_encode(["reqUrl" => $reqUrl,"reqUrlArr" => $reqUrlArr,"coreUrl" => $coreUrl,"udomain"=>$udomain,"upibox"=>$uapibox, "uresource"=>$uresource,"uaction"=>$uaction,"param_id"=>$param_id, "res"=>false]);
// return;

// if($coreUrl == "$udomain/" ){
if($uresource == "user"){
    include "./controller/usercontroller.php";
    return;
}
elseif($uresource == "pilot"){
    include "./controller/pilotcontroller.php";
    return;
}
elseif($uresource == "token"){
    include "./controller/tokencontroller.php";
    return;
}
else{
    http_response_code(200);
    echo json_encode(["reqUrl" => "Requested URL doesnot exist", "res_cl"=>false]);
    return;
}