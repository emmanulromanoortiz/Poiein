<?php

mysqli_report(MYSQLI_REPORT_OFF);

$host_A = "localhost"; 
$user_A = "root"; 
$pass_A = "1234"; 
$db     = "poiein_db";

$host_B = "192.168.1.158"; 
$user_B = "root"; 
$pass_B = "1234";    


$conexion = @mysqli_connect($host_A, $user_A, $pass_A, $db);


if (!$conexion) {

    $conexion = @mysqli_connect($host_B, $user_B, $pass_B, $db);    

    if (!$conexion) {
    die("Error de la Compu B: " . mysqli_connect_error());
}
}


mysqli_set_charset($conexion, "utf8");
?>