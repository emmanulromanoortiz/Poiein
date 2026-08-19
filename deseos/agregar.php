<?php
session_start();
header('Content-Type: application/json');

// Conexión con verificación de archivo
if (file_exists(__DIR__ . "/../conexion.php")) {
    include(__DIR__ . "/../conexion.php");
} elseif (file_exists(__DIR__ . "/../conexión.php")) {
    include(__DIR__ . "/../conexión.php");
}

// 1. Verificar si el usuario inició sesión
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Debes iniciar sesión para añadir productos a tu lista de deseos.'
    ]);
    exit;
}

// 2. Obtener datos de la solicitud JSON
$input = json_decode(file_get_contents('php://input'), true);
$usuario_id = intval($_SESSION['usuario_id']);
$producto_id = isset($input['producto_id']) ? intval($input['producto_id']) : 0;

if ($producto_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Producto no válido.'
    ]);
    exit;
}

// 3. Verificar si el producto ya está en la lista de favoritos
$check_query = "SELECT id FROM favoritos WHERE usuario_id = '$usuario_id' AND producto_id = '$producto_id' LIMIT 1";
$check_res = mysqli_query($conexion, $check_query);

if ($check_res && mysqli_num_rows($check_res) > 0) {
    // Si ya existe, lo eliminamos (efecto toggle / quitar de deseos)
    $delete_query = "DELETE FROM favoritos WHERE usuario_id = '$usuario_id' AND producto_id = '$producto_id'";
    if (mysqli_query($conexion, $delete_query)) {
        echo json_encode([
            'status' => 'removed',
            'message' => 'La pieza fue removida de tu lista de deseos.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se pudo quitar de la lista de deseos.'
        ]);
    }
} else {
    // Si no existe, lo agregamos
    $insert_query = "INSERT INTO favoritos (usuario_id, producto_id, fecha_agregado) VALUES ('$usuario_id', '$producto_id', NOW())";
    if (mysqli_query($conexion, $insert_query)) {
        echo json_encode([
            'status' => 'added',
            'message' => '¡Pieza guardada en tu lista de deseos!'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al guardar en la lista de deseos.'
        ]);
    }
}
?>