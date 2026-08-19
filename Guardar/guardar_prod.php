<?php
include(__DIR__ . "/../conexión.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    
    // Captura de datos
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    $precio = mysqli_real_escape_string($conexion, $_POST['precio']);

    // Procesar imagen
    if (isset($_FILES['imagen_url']) && $_FILES['imagen_url']['error'] === 0) {
        $nombre_img = time() . "_" . $_FILES['imagen_url']['name'];
        // Cambia tu $ruta_destino por esta línea exacta:
         $ruta_destino = dirname(__DIR__) . "/uploads/" . $nombre_img;
        $ruta_db = "uploads/" . $nombre_img; // Ruta limpia para la BD

        if (move_uploaded_file($_FILES['imagen_url']['tmp_name'], $ruta_destino)) {
            
            // Insert sin el campo categoria (ajusta si tu BD requiere valor obligatorio)
            $sql = "INSERT INTO productos (usuario_id, nombre_item, descripcion, precio, imagen_producto) 
                    VALUES ('$usuario_id', '$nombre', '$descripcion', '$precio', '$ruta_db')";

            if (mysqli_query($conexion, $sql)) {
                echo "<script>alert('¡Producto publicado!'); window.location.href = '../index.php';</script>";
            } else {
                echo "Error en BD: " . mysqli_error($conexion);
            }
        } else {
            echo "Error al subir la imagen.";
        }
    } else {
        echo "Error: No se seleccionó imagen.";
    }
}
?>