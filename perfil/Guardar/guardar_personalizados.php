<?php
include(__DIR__ . "/../../conexión.php");
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /poiein/Login/login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$acepta_personalizados = isset($_POST['acepta_personalizados']) ? 1 : 0;

$query = "UPDATE usuarios SET acepta_personalizados = $acepta_personalizados WHERE id = '$usuario_id'";
mysqli_query($conexion, $query);

header("Location: /poiein/perfil/perfil.php");
exit();
?>