<?php
session_start();
header('Content-Type: application/json');

if (file_exists(__DIR__ . "/../conexion.php")) {
    include(__DIR__ . "/../conexion.php");
} elseif (file_exists(__DIR__ . "/../conexión.php")) {
    include(__DIR__ . "/../conexión.php");
}

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$usuario_id = intval($_SESSION['usuario_id']);
$comentario_id = isset($data['comentario_id']) ? intval($data['comentario_id']) : 0;
$tipo = isset($data['tipo']) ? mysqli_real_escape_string($conexion, $data['tipo']) : '';

if ($comentario_id <= 0 || !in_array($tipo, ['like', 'dislike'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
    exit;
}

// Verificar si el usuario ya reaccionó
$check = "SELECT id, tipo FROM reaccion_comentario WHERE comentario_id = '$comentario_id' AND usuario_id = '$usuario_id' LIMIT 1";
$res_check = mysqli_query($conexion, $check);

if ($res_check && mysqli_num_rows($res_check) > 0) {
    $reaccion_actual = mysqli_fetch_assoc($res_check);
    if ($reaccion_actual['tipo'] === $tipo) {
        // Si hace clic en la misma reacción, se quita (toggle)
        mysqli_query($conexion, "DELETE FROM reaccion_comentario WHERE id = '{$reaccion_actual['id']}'");
    } else {
        // Si cambia de Like a Dislike o viceversa
        mysqli_query($conexion, "UPDATE reaccion_comentario SET tipo = '$tipo' WHERE id = '{$reaccion_actual['id']}'");
    }
} else {
    // Insertar nueva reacción
    mysqli_query($conexion, "INSERT INTO reaccion_comentario (comentario_id, usuario_id, tipo) VALUES ('$comentario_id', '$usuario_id', '$tipo')");
}

// Obtener nuevos conteos
$res_likes = mysqli_query($conexion, "SELECT COUNT(*) as total FROM reaccion_comentario WHERE comentario_id = '$comentario_id' AND tipo = 'like'");
$likes = mysqli_fetch_assoc($res_likes)['total'];

$res_dislikes = mysqli_query($conexion, "SELECT COUNT(*) as total FROM reaccion_comentario WHERE comentario_id = '$comentario_id' AND tipo = 'dislike'");
$dislikes = mysqli_fetch_assoc($res_dislikes)['total'];

echo json_encode([
    'status' => 'success',
    'likes' => $likes,
    'dislikes' => $dislikes
]);