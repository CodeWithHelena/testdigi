<?php

$apiware = "userapi";

if($uaction == "getone" && $_SERVER['REQUEST_METHOD'] == 'POST'){

    include "./$apiware/getuser.php";
    return;

}
elseif($uaction == "getonebyemail" && $_SERVER['REQUEST_METHOD'] == 'POST'){

    include "./$apiware/getuserbyemail.php";
    return;

}
elseif($uaction == "getall"  && $_SERVER['REQUEST_METHOD'] == 'POST'){

    include "./$apiware/getusers.php";
    return;

}
elseif($uaction == "createone" && $_SERVER['REQUEST_METHOD'] == 'POST'){

    include "./$apiware/createuser.php";
    return;

}
elseif($uaction == "signup" && $_SERVER['REQUEST_METHOD'] == 'POST'){

    include "./$apiware/usersignup.php";
    return;

}
elseif($uaction == "createone_wp"  && $_SERVER['REQUEST_METHOD'] == 'POST'){

    include "./$apiware/createuser_wp.php";
    return;

}
elseif($uaction == "updateone" && ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'PATCH')){

    // Update own updatable info (firstname, lastname and password)
    include "./$apiware/updateuser.php";
    return;

}
elseif($uaction == "updateother" && ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'PATCH')){

    // Update other user data (ftirstname, lastname, password, role_id, active)
    include "./$apiware/updateuser_v2.php";
    return;

}
elseif($uaction == "deleteone" && $_SERVER['REQUEST_METHOD'] == 'DELETE'){

    include "./$apiware/deleteuser.php";
    return;


}elseif($uaction == "search" && $_SERVER['REQUEST_METHOD'] == 'POST'){
    
    include "./$apiware/searchuser.php";
    return;

}
elseif($uaction == "authcheck" && $_SERVER['REQUEST_METHOD'] == 'POST'){
    
    include "./$apiware/authcheck.php";
    return;

}
elseif($uaction == "login" && $_SERVER['REQUEST_METHOD'] == 'POST'){
    
    include "./$apiware/userlogin.php";
    return;

}
elseif($uaction == "logout" && $_SERVER['REQUEST_METHOD'] == 'POST'){
    
    include "./$apiware/userlogout.php";
    return;

}
elseif($uaction == "passchange"  && $_SERVER['REQUEST_METHOD'] == 'POST'){
    
    // Users to change thier own password passwordlessly
    include "./$apiware/changepassword.php";
    return;

}
elseif($uaction == "passchangeother"  && $_SERVER['REQUEST_METHOD'] == 'POST'){
    
    // Admins users to change thier the password of any user passwordlessly
    include "./$apiware/changepassword2.php";
    return;

}
elseif($uaction == "verifyemail"   && $_SERVER['REQUEST_METHOD'] == 'POST'){
    
    include "./$apiware/verifyuser.php";
    return;
}
elseif($uaction == "resetpass"   && $_SERVER['REQUEST_METHOD'] == 'POST'){
    
    include "./$apiware/resetpassword.php";
    return;

}
else{
    http_response_code(200);
    echo json_encode(["reqUrl" => "Requested URL doesnot exist", "res_uc"=>false]);
    return;
}

