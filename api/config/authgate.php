<?php
$xPilot = 0;
$yPilot = 1;
$zPilot = 10;


function cleanData($data){
    return htmlspecialchars(strip_tags(trim($data)));
}

//Remove only script tags
function cleanScriptTags($data){

    if (is_string($data)) {
        $data2 = $data.replace("</script>", '');

        return $data2.replace("<script>", '');
    }
}

function scrubUcode($ucode){
    return $ucode->user_code;
}

function authGateCheck($udata) {
    if (!isset($udata->ak_lsc) || empty($udata->ak_lsc) || $udata->ak_lsc == null || trim($udata->ak_lsc) == '' || !isset($udata->ak_ssc) || empty($udata->ak_ssc) || $udata->ak_ssc == null || trim($udata->ak_ssc) == '' || !isset($udata->ak_usc) || empty($udata->ak_usc) || $udata->ak_usc == null || trim($udata->ak_usc) == '') {

        return false;
    }
    return true;
}


function authGateCheck2($dbdata, $supplied_data){
    if ($dbdata['user_code'] != scrubUserCode($supplied_data)) {
      return false;
    }
    return true;
}

function scrubUserCode($ucode){
    return substr($ucode->ak_usc, 108);
}

function scrubUserCode2($ucode){
    return intval(substr($ucode->ak_usc, 126, strlen($ucode->ak_usc)-144));
}

function prepareUserCode($ucode){
    return substr(md5(time()), 0, 18) . substr(md5(time()), 0, 18) . substr(md5(time()), 0, 18) . substr(md5(time()), 0, 18) . substr(md5(time()), 0, 18) . substr(md5(time()), 0, 18) . $ucode;
}

function prepareUserCode2(){
    return hash('sha512',microtime()) ;
}

function prepareUserCode3(){
    return hash('sha256',microtime()) ;
}

function isPilot3($uData){
    return $uData['role_id'] == 10 ? true : false; 
}

function isPilot2($uData){
    return $uData['role_id'] == 1 ? true : false; 
}

function isPilot1($uData){
    return $uData['role_id'] == 0 ? true : false; 
}


function errorDiag($err){
    if (stripos($err, 'duplicate')) {
        return ["result"=>false, "message" => "FAILED: Entity already exists.", "status" => 50];
    }
    elseif (stripos($err, 'Invalid parameter')) {
        return ["result"=>false, "message" => "Internal query parameter error.", "status" => 51];
    }
    elseif (stripos($err, 'column') || stripos($err, 'unknown')) {
        return["result"=>false, "message" => "Internal or external: A column name is wrong.", "status" => 52];
    }
    elseif (stripos($err, 'SQL syntax')) {
        return ["result"=>false, "message" => "Internal : Issue with SQL query syntax.", "status" => 53];
    }
    elseif (stripos($err, 'base table')) {
        return ["result"=>false, "message" => "Internal : Table name does not exist.", "status" => 54];
    }
    else{
        return ["result"=>false, "message" =>"Error", "error"=>$err, "status" => 55];
    }

}