<?php
session_start();

// Incluir conexión con verificación de tildes
if (file_exists(__DIR__ . "/../../conexion.php")) {
    include(__DIR__ . "/../../conexion.php");
} elseif (file_exists(__DIR__ . "/../../conexión.php")) {
    include(__DIR__ . "/../../conexión.php");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_portada']) && isset($_SESSION['usuario_id'])) {
    $uid = $_SESSION['usuario_id'];
    $file = $_FILES['foto_portada'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($ext, $permitidas)) {
            // Nombre para la base de datos (relativo a la carpeta web /poiein/)
            $nombre_db = "perfil/portada/portada_" . $uid . "." . $ext;
            
            // Ruta física: Sube de Guardar/ a perfil/ y entra a portada/
            $ruta_fisica = __DIR__ . "/../portada/portada_" . $uid . "." . $ext;

            if (move_uploaded_file($file['tmp_name'], $ruta_fisica)) {
                // Actualizar BD
                $query = "UPDATE usuarios SET foto_portada = '$nombre_db' WHERE id = '$uid'";
                mysqli_query($conexion, $query);
            }
        }
    }
}

// Redirigir de regreso al perfil
header("Location: ../perfil.php");
exit();