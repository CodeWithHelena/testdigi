<?php
// include 'config.ini';
$configx = parse_ini_file('configuration.ini', true);

include 'database.php';
include 'authgate.php';


// Models
include './models/User.php';
include './models/Pilot.php';
include './models/Token.php';




// include '../models/Mail.php';


