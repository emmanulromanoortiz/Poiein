<?php
// 1. Iniciamos la sesión antes de CUALQUIER otra línea de código o include
session_start();
include("conexión.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpiamos los datos de entrada
    $email = mysqli_real_escape_string($conexion, trim($_POST['email']));
    $password = $_POST['password'];

    // 2. Buscamos al usuario por email de forma segura
    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $resultado = mysqli_query($conexion, $sql);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $usuario = mysqli_fetch_assoc($resultado);

        // 3. Verificamos la contraseña encriptada
        if (password_verify($password, $usuario['password'])) {
            
            // --- VALIDACIÓN DE ESTADO PARA CREADORES ---
            if ($usuario['rol'] === 'creador') {
                if ($usuario['estado'] === 'pendiente') {
                    echo "<script>alert('Tu cuenta de creador se encuentra pendiente de aprobación por el administrador.'); window.location.href='index.php';</script>";
                    exit();
                }
                if ($usuario['estado'] === 'rechazado') {
                    echo "<script>alert('Lo sentimos, tu solicitud de creador ha sido rechazada por el administrador.'); window.location.href='index.php';</script>";
                    exit();
                }
            }
            // ------------------------------------------

            // ¡ÉXITO! Almacenamos los datos en la sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre_completo'];
            $_SESSION['rol'] = $usuario['rol']; 
            $_SESSION['region'] = $usuario['region'] ?? 'CENTRO';

            // Redirigimos de inmediato
            header("Location: index.php");
            exit();
        }
    }
    
    // Si algo falla, alerta única
    echo "<script>alert('Correo o contraseña incorrectos'); window.location.href='index.php';</script>";
    exit();
}
?>