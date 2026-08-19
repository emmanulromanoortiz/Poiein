<?php
session_start();
include($_SERVER['DOCUMENT_ROOT'] . "/poiein/conexión.php");

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /poiein/Login/login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$creador_id = isset($_POST['artista_id']) ? intval($_POST['artista_id']) : 0;

// Validar que se haya subido un archivo
if (isset($_FILES['imagen_usuario']) && $_FILES['imagen_usuario']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['imagen_usuario'];
    $nombre_tmp = $archivo['tmp_name'];
    $nombre_original = basename($archivo['name']);
    
    // Carpeta donde se guardarán los diseños personalizados
    $carpeta_destino = $_SERVER['DOCUMENT_ROOT'] . "/poiein/uploads/encargos/";
    
    // Si la carpeta no existe, la creamos
    if (!file_exists($carpeta_destino)) {
        mkdir($carpeta_destino, 0777, true);
    }

    // Renombrar el archivo para evitar duplicados
    $nombre_unico = uniqid("custom_") . "_" . $nombre_original;
    $ruta_final = $carpeta_destino . $nombre_unico;
    $ruta_db = "uploads/encargos/" . $nombre_unico;

    if (move_uploaded_file($nombre_tmp, $ruta_final)) {
        // Guardar el registro en la base de datos
        $stmt = $conexion->prepare("INSERT INTO encargos_personalizados (usuario_id, creador_id, imagen_personalizada) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iis", $usuario_id, $creador_id, $ruta_db);
            if ($stmt->execute()) {
                // Redirigir a una página de éxito o de confirmación
                echo "<script>alert('¡Solicitud enviada con éxito al creador!'); window.location.href='/poiein/mis_prod/index.php';</script>";
                exit();
            } else {
                echo "Error al registrar en la base de datos.";
            }
        }
    } else {
        echo "Error al mover el archivo al servidor.";
    }
} else {
    echo "No se ha seleccionado ninguna imagen válida.";
}
?>