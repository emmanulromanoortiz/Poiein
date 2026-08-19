<?php
include("conexión.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre_artesania']);
    $precio = $_POST['precio'];
    $usuario_id = $_SESSION['usuario_id']; // Viene de tu login

    // Configuración de la imagen
    $directorio = "uploads/";
    $nombre_archivo = time() . "_" . basename($_FILES["foto"]["name"]);
    $ruta_final = $directorio . $nombre_archivo;

    // Movemos el archivo a la carpeta uploads que ya tienes
    if (move_uploaded_file($_FILES["foto"]["tmp_name"], $ruta_final)) {
        // Insertamos en la tabla de productos
        $sql = "INSERT INTO productos (nombre, precio, imagen_url, usuario_id) 
                VALUES ('$nombre', '$precio', '$ruta_final', '$usuario_id')";

        if (mysqli_query($conexion, $sql)) {
            echo "<script>alert('¡Obra publicada con éxito!'); window.location.href='index.php';</script>";
        }
    } else {
        echo "Error al subir la imagen.";
    }
}
?>