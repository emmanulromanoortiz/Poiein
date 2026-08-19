<?php
session_start();
header('Content-Type: application/json');

if (file_exists(__DIR__ . "/../conexion.php")) {
    include(__DIR__ . "/../conexion.php");
} elseif (file_exists(__DIR__ . "/../conexión.php")) {
    include(__DIR__ . "/../conexión.php");
}

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$usuario_id = intval($_SESSION['usuario_id']);
$producto_id = isset($input['producto_id']) ? intval($input['producto_id']) : 0;

if ($producto_id > 0) {
    $delete_query = "DELETE FROM favoritos WHERE usuario_id = '$usuario_id' AND producto_id = '$producto_id'";
    if (mysqli_query($conexion, $delete_query)) {
        echo json_encode(['status' => 'success', 'message' => 'La pieza fue removida de tus favoritos.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos al eliminar.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID de producto no válido.']);
}
?>