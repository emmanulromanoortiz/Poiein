<?php
include(__DIR__ . "/../../conexión.php");
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_id'])) {
    $uid = $_SESSION['usuario_id'];
    
    // Limpiamos los links para evitar inyecciones SQL
    $spotify = mysqli_real_escape_string($conexion, $_POST['link_spotify']);
    $instagram = mysqli_real_escape_string($conexion, $_POST['link_instagram']);

    // Actualizamos la tabla usuarios
    $sql = "UPDATE usuarios SET 
            link_spotify = '$spotify', 
            link_instagram = '$instagram' 
            WHERE id = '$uid'";

    if (mysqli_query($conexion, $sql)) {
        header("Location: ../perfil.php?success=links");
    } else {
        echo "Error al actualizar redes: " . mysqli_error($conexion);
    }
}
exit();