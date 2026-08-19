<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("conexión.php"); // Cambia a "conexion.php" si tu archivo no lleva tilde

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre_completo'] ?? '');
    $email = mysqli_real_escape_string($conexion, $_POST['email'] ?? '');
    $pass_raw = $_POST['password'] ?? '';
    $rol = mysqli_real_escape_string($conexion, $_POST['rol'] ?? 'consumidor');
    $region = mysqli_real_escape_string($conexion, $_POST['region'] ?? 'CENTRO');

    if (empty($nombre) || empty($email) || empty($pass_raw)) {
        echo "<script>alert('Por favor, completa todos los campos obligatorios.'); window.history.back();</script>";
        exit();
    }

    $check_query = "SELECT id FROM usuarios WHERE email = '$email'";
    $check_result = mysqli_query($conexion, $check_query);
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('El correo electrónico ya está registrado.'); window.history.back();</script>";
        exit();
    }

    $pass = password_hash($pass_raw, PASSWORD_DEFAULT);

    // Variables para creador
    $nombre_producto = '';
    $biografia = '';
    $estado = 'aprobado'; // Por defecto para consumidor

    if ($rol === 'creador') {
        $estado = 'pendiente';
        $nombre_producto = mysqli_real_escape_string($conexion, $_POST['nombre_producto'] ?? '');
        $biografia = mysqli_real_escape_string($conexion, $_POST['biografia'] ?? '');
    }

    // Inserción en la base de datos sin foto_ine
    $sql = "INSERT INTO usuarios (nombre_completo, nombre_producto, email, password, rol, estado, region, biografia) 
            VALUES ('$nombre', '$nombre_producto', '$email', '$pass', '$rol', '$estado', '$region', '$biografia')";

    if (mysqli_query($conexion, $sql)) {
        if ($rol === 'creador') {
            echo "<script>
                alert('¡Gracias por registrarte! Tu solicitud ha sido enviada. Un administrador la revisará próximamente.');
                window.location.href = 'index.php';
            </script>";
            exit();
        } else {
            $_SESSION['usuario_id'] = mysqli_insert_id($conexion); 
            $_SESSION['nombre'] = $nombre;
            $_SESSION['rol'] = $rol;
            $_SESSION['region'] = $region;

            echo "<script>
                alert('¡Bienvenido/a a Poiein, " . htmlspecialchars($nombre) . "!');
                window.location.href = 'index.php';
            </script>";
            exit();
        }
    } else {
        echo "<script>alert('Error en la base de datos: " . addslashes(mysqli_error($conexion)) . "'); window.history.back();</script>";
        exit();
    }
}
?>