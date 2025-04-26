<?php

$apiware = "tokenapi";

if($uaction == "getone" && ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'POST')) {

    include "./$apiware/gettoken.php";
    return;

}
elseif($uaction == "getall"  && ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'POST')){

    include "./$apiware/gettokens.php";
    return;

}
elseif($uaction == "createone" && $_SERVER['REQUEST_METHOD'] == 'POST'){

    include "./$apiware/createtoken.php";
    return;

}
elseif($uaction == "createmany" && $_SERVER['REQUEST_METHOD'] == 'POST'){

    include "./$apiware/createtoken2.php";
    return;

}
elseif($uaction == "deleteone" && $_SERVER['REQUEST_METHOD'] == 'DELETE'){

    include "./$apiware/deletetoken.php";
    return;


}elseif($uaction == "search" && $_SERVER['REQUEST_METHOD'] == 'POST'){
    
    include "./$apiware/searchtoken.php";
    return;

}
else{
    http_response_code(200);
    echo json_encode(["reqUrl" => "Requested URL doesnot exist", "res_tok"=>false]);
    return;
}

