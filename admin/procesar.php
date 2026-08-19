<?php
session_start();

// 1. PROTECCIÓN ABSOLUTA: Si no es admin, lo expulsamos al index
if (!isset($_SESSION['nombre']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// 2. CONEXIÓN COMPATIBLE
if (file_exists(__DIR__ . "/../conexion.php")) {
    include(__DIR__ . "/../conexion.php");
} elseif (file_exists(__DIR__ . "/../conexión.php")) {
    include(__DIR__ . "/../conexión.php");
}

$accion = $_GET['accion'] ?? '';
$id_reporte = intval($_GET['reporte'] ?? 0);
$id_producto = intval($_GET['id'] ?? 0);
$id_usuario = intval($_GET['usuario_id'] ?? 0);

// Detectar de qué página viene para regresarlo al mismo lugar
$redirect = "dashboard.php";
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'usuarios.php') !== false) {
    $redirect = "usuarios.php";
}

if ($accion === 'eliminar_prod' && $id_producto > 0) {
    // 1. Borrar el producto
    mysqli_query($conexion, "DELETE FROM productos WHERE id = '$id_producto'");
    
    // 2. Marcar el reporte como resuelto
    if ($id_reporte > 0) {
        mysqli_query($conexion, "UPDATE reportes SET estado = 'resuelto' WHERE id = '$id_reporte'");
    }
} 
elseif ($accion === 'ignorar') {
    // Depuración estricta para asegurarnos de que entra aquí
    if ($id_reporte > 0) {
        $update = "UPDATE reportes SET estado = 'ignorado' WHERE id = $id_reporte";
        $resultado = mysqli_query($conexion, $update);
        
        // Si quieres ver si hubo error de MySQL al ignorar, descomenta la siguiente línea:
        // if (!$resultado) { die("Error SQL: " . mysqli_error($conexion)); }
    }
}
elseif ($accion === 'aprobar_creador' && $id_usuario > 0) {
    mysqli_query($conexion, "UPDATE usuarios SET estado = 'activo' WHERE id = '$id_usuario'");
}
elseif ($accion === 'rechazar_creador' && $id_usuario > 0) {
    mysqli_query($conexion, "UPDATE usuarios SET estado = 'rechazado' WHERE id = '$id_usuario'");
}
elseif ($accion === 'eliminar_usuario' && $id_usuario > 0) {
    // Borrar de la base de datos directamente
    mysqli_query($conexion, "DELETE FROM usuarios WHERE id = '$id_usuario'");
}

// Redirección dinámica inteligente
header("Location: " . $redirect);
exit();
?>