<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id']) && !isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida.']);
    exit;
}

$usuario_id = $_SESSION['id'] ?? $_SESSION['usuario_id'];

if (file_exists("../conexión.php")) include_once("../conexión.php");
elseif (file_exists("../conexion.php")) include_once("../conexion.php");
else @include_once(__DIR__ . "/../conexión.php");

if (!isset($conexion)) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$carrito_id = isset($data['carrito_id']) ? intval($data['carrito_id']) : 0;
$cantidad = isset($data['cantidad']) ? intval($data['cantidad']) : 1;

if ($carrito_id <= 0 || $cantidad <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Parámetros inválidos.']);
    exit;
}

// Actualizar la cantidad asegurando que pertenezca al usuario en sesión
$query = "UPDATE carrito SET cantidad = $cantidad WHERE id = $carrito_id AND usuario_id = $usuario_id";
if (mysqli_query($conexion, $query)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar.']);
}