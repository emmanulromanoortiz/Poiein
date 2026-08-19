<?php
session_start();
// Protegemos el panel: solo tu correo puede entrar
if (!isset($_SESSION['email']) || $_SESSION['email'] !== 'emmanuel@gmail.com') {
    header("Location: index.php");
    exit();
}
include("conexión.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración | Poiein</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container { padding: 40px; color: white; }
        table { width: 100%; border-collapse: collapse; background: #1a1a1a; }
        th, td { padding: 15px; border: 1px solid #333; text-align: left; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Panel de Control</h1>
        <p>Bienvenido, Administrador.</p>
        
        <h2>Reportes de productos</h2>
        <table>
            <tr>
                <th>ID Producto</th>
                <th>Motivo</th>
                <th>Acción</th>
            </tr>
            <!-- Aquí iría un bucle PHP que consulte tu tabla de reportes -->
            <tr>
                <td>12</td>
                <td>Contenido inapropiado</td>
                <td><button>Eliminar Producto</button></td>
            </tr>
        </table>
    </div>
</body>
</html>