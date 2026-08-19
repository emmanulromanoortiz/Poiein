<?php
session_start();
include($_SERVER['DOCUMENT_ROOT'] . "/poiein/conexión.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'creador') {
    header("Location: /poiein/Login/login.php");
    exit();
}

if (isset($_POST['encargo_id']) && isset($_POST['accion'])) {
    $encargo_id = intval($_POST['encargo_id']);
    $accion = $_POST['accion']; // Puede ser 'aceptado' o 'rechazado'
    $mi_id = $_SESSION['usuario_id'];

    // Validar que la acción sea segura
    if ($accion === 'aceptado' || $accion === 'rechazado') {
        // Nos aseguramos de que el encargo pertenezca realmente a este creador
        $stmt = $conexion->prepare("UPDATE encargos_personalizados SET estado = ? WHERE id = ? AND creador_id = ?");
        if ($stmt) {
            $stmt->bind_param("sii", $accion, $encargo_id, $mi_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

header("Location: /poiein/perfil/perfil.php");
exit();
?>