<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 1. Validar Sesión de Usuario
if (!isset($_SESSION['id']) && !isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Debes iniciar sesión para enviar un reporte.'
    ]);
    exit;
}

$usuario_reporta_id = $_SESSION['id'] ?? $_SESSION['usuario_id'];

// 2. Incluir Conexión
if (file_exists(__DIR__ . "/../conexion.php")) {
    include_once(__DIR__ . "/../conexion.php");
} elseif (file_exists(__DIR__ . "/../conexión.php")) {
    include_once(__DIR__ . "/../conexión.php");
}

if (!isset($conexion) || !$conexion) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de conexión con la base de datos.'
    ]);
    exit;
}

// 3. Obtener Datos JSON de JavaScript
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se recibieron datos válidos.'
    ]);
    exit;
}

// Capturamos el ID del producto (o comentario)
$producto_id = isset($data['elemento_id']) ? intval($data['elemento_id']) : 0;
$motivo      = isset($data['motivo']) ? trim($data['motivo']) : '';
$detalles    = isset($data['detalles']) ? trim($data['detalles']) : '';
$estado      = 'pendiente'; // Estado por defecto para el panel de admin

if ($producto_id <= 0 || empty($motivo)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Por favor, selecciona un motivo para el reporte.'
    ]);
    exit;
}

// 4. Insertar usando los nombres EXACTOS de tus columnas de MySQL
$stmt = $conexion->prepare("INSERT INTO reportes (producto_id, usuario_reporta_id, motivo, detalles, estado, fecha_reporte) VALUES (?, ?, ?, ?, ?, NOW())");

if ($stmt) {
    $stmt->bind_param("iisss", $producto_id, $usuario_reporta_id, $motivo, $detalles, $estado);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => '¡Reporte recibido! Será revisado por un administrador.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al guardar reporte: ' . $stmt->error
        ]);
    }
    $stmt->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la consulta: ' . $conexion->error
    ]);
}
?>