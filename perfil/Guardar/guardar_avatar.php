<?php
session_start();

// 1. Incluir conexión con verificación de tildes
if (file_exists(__DIR__ . "/../../conexion.php")) {
    include(__DIR__ . "/../../conexion.php");
} elseif (file_exists(__DIR__ . "/../../conexión.php")) {
    include(__DIR__ . "/../../conexión.php");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil']) && isset($_SESSION['usuario_id'])) {
    $uid = $_SESSION['usuario_id'];
    $file = $_FILES['foto_perfil'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Extensiones permitidas por seguridad
        $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($ext, $permitidas)) {
            // Nombre para la base de datos (relativo a la carpeta /poiein/)
            $nombre_db = "perfil/user_" . $uid . "." . $ext;
            
            // Ruta física donde se guardará la imagen realmente (sube un nivel desde /Guardar/ hacia /perfil/)
            $ruta_fisica = __DIR__ . "/../user_" . $uid . "." . $ext;

            if (move_uploaded_file($file['tmp_name'], $ruta_fisica)) {
                // Guardar la nueva ruta en la BD
                $query = "UPDATE usuarios SET foto_perfil = '$nombre_db' WHERE id = '$uid'";
                mysqli_query($conexion, $query);
            }
        }
    }
}

// 2. Regresar correctamente a perfil.php (un nivel arriba de /Guardar/)
header("Location: ../perfil.php");
exit();