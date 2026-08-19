<?php
session_start();
include("conexión.php");

echo "<h2>--- DIAGNÓSTICO DE SESIÓN EN POIEIN ---</h2>";

if (isset($_SESSION['usuario_id'])) {
    echo "ID de usuario en sesión actual: <b>" . $_SESSION['usuario_id'] . "</b><br>";
    echo "Nombre en sesión actual: <b>" . $_SESSION['nombre'] . "</b><br>";
    echo "Rol en sesión actual: <b style='color:red; font-size:20px;'>" . ($_SESSION['rol'] ?? 'NO TIENE ROL ASIGNADO') . "</b><br>";
    
    // Vamos a ir a buscar REALMENTE qué dice la Base de Datos ahora mismo para ese ID
    $id = $_SESSION['usuario_id'];
    $sql = "SELECT id, email, rol FROM usuarios WHERE id = '$id'";
    $res = mysqli_query($conexion, $sql);
    $user_db = mysqli_fetch_assoc($res);
    
    echo "<hr>";
    echo "<h3>Lo que dice la Base de Datos REALMENTE para tu ID:</h3>";
    if ($user_db) {
        echo "Email en BD: <b>" . $user_db['email'] . "</b><br>";
        echo "Rol en BD: <b style='color:green; font-size:20px;'> " . $user_db['rol'] . "</b><br>";
    } else {
        echo "Error: Tu ID de sesión no existe en la tabla de usuarios.";
    }
} else {
    echo "No has iniciado sesión. No hay ninguna sesión activa en este navegador.";
}
?>