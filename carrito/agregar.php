<?php
session_start();
header('Content-Type: application/json');

// 1. Validar inicio de sesión
if (!isset($_SESSION['id']) && !isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Debes iniciar sesión para añadir productos a tu carrito.'
    ]);
    exit;
}

$usuario_id = $_SESSION['id'] ?? $_SESSION['usuario_id'];

// 2. Conexión a la Base de Datos
if (file_exists("../conexión.php")) include_once("../conexión.php");
elseif (file_exists("../conexion.php")) include_once("../conexion.php");
else @include_once(__DIR__ . "/../conexión.php");

if (!isset($conexion)) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// 3. Obtener el producto_id enviado vía JSON
$data = json_decode(file_get_contents('php://input'), true);
$producto_id = isset($data['producto_id']) ? intval($data['producto_id']) : 0;

if ($producto_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Producto no válido.']);
    exit;
}

// 4. Verificar si la pieza ya fue añadida por este usuario
$check = mysqli_query($conexion, "SELECT id, cantidad FROM carrito WHERE usuario_id = $usuario_id AND producto_id = $producto_id");

if ($check && mysqli_num_rows($check) > 0) {
    // Si ya existe en su carrito, sumamos +1 a la cantidad
    $fila = mysqli_fetch_assoc($check);
    $nueva_cant = $fila['cantidad'] + 1;
    mysqli_query($conexion, "UPDATE carrito SET cantidad = $nueva_cant WHERE id = {$fila['id']}");
} else {
    // Si es nueva, la insertamos con cantidad 1
    mysqli_query($conexion, "INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES ($usuario_id, $producto_id, 1)");
}

// 5. Contar el total de piezas acumuladas para actualizar el badge del Nav
$res_count = mysqli_query($conexion, "SELECT SUM(cantidad) AS total FROM carrito WHERE usuario_id = $usuario_id");
$count_row = mysqli_fetch_assoc($res_count);
$total_cart = $count_row['total'] ? intval($count_row['total']) : 0;

// Devolver respuesta exitosa a JavaScript
echo json_encode([
    'status' => 'success',
    'message' => '¡Pieza añadida a tu carrito!',
    'cart_count' => $total_cart
]);