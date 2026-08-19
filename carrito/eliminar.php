<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 1. Validar autenticación
if (!isset($_SESSION['id']) && !isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit;
}

$usuario_id = $_SESSION['id'] ?? $_SESSION['usuario_id'];

// 2. Incluir conexión
if (file_exists("../conexión.php")) include_once("../conexión.php");
elseif (file_exists("../conexion.php")) include_once("../conexion.php");
elseif (file_exists("conexión.php")) include_once("conexión.php");
elseif (file_exists("conexion.php")) include_once("conexion.php");
else @include_once(__DIR__ . "/../conexión.php");

if (!isset($conexion) || !$conexion) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// 3. Obtener el cuerpo de la petición JSON sent desde fetch
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

$carrito_id = isset($data['carrito_id']) ? intval($data['carrito_id']) : 0;

if ($carrito_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de carrito no válido']);
    exit;
}

// 4. Ejecutar DELETE validando que pertenezca al usuario en sesión
$query = "DELETE FROM carrito WHERE id = $carrito_id AND usuario_id = $usuario_id";

if (mysqli_query($conexion, $query)) {
    if (mysqli_affected_rows($conexion) > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Ítem eliminado correctamente']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'El ítem no existe o no te pertenece']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar la consulta: ' . mysqli_error($conexion)]);
}
?>