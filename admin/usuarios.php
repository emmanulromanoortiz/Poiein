<?php
session_start();
if (!isset($_SESSION['nombre']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header("Location: ../index.php");
    exit();
}
include("../conexión.php");

// Lógica de filtrado
$filtro = $_GET['estado'] ?? 'todos';
$sql = "SELECT * FROM usuarios WHERE rol = 'creador'";
if ($filtro === 'activo') $sql .= " AND estado = 'activo'";
elseif ($filtro === 'pendiente') $sql .= " AND estado = 'pendiente'";
elseif ($filtro === 'rechazado') $sql .= " AND estado = 'rechazado'";

$lista_usuarios = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Poiein | Gestión de Usuarios</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="theme-black admin-body">

<header class="admin-header">
    <div class="logo-admin">✧ POIEIN <span>GESTIÓN</span></div>
    <div class="user-admin"><a href="dashboard.php">Volver al Panel</a></div>
</header>

<main class="admin-container">
    <h2>Listado de Creadores</h2>
    
    <!-- Filtros -->
    <div class="admin-filters">
        <a href="usuarios.php?estado=todos" class="btn-admin">Todos</a>
        <a href="usuarios.php?estado=activo" class="btn-admin btn-safe">Activos</a>
        <a href="usuarios.php?estado=pendiente" class="btn-admin btn-warning">Pendientes</a>
        <a href="usuarios.php?estado=rechazado" class="btn-admin btn-danger">Rechazados</a>
    </div>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($u = mysqli_fetch_assoc($lista_usuarios)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['nombre_completo']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <span class="badge <?php echo $u['estado']; ?>">
                            <?php echo ucfirst($u['estado']); ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        $estado = strtolower($u['estado']);
                        
                        // Si está PENDIENTE o RECHAZADO, mostrar botón Aprobar
                        if ($estado === 'pendiente' || $estado === 'rechazado'): 
                        ?>
                            <a href="procesar.php?accion=aprobar_creador&usuario_id=<?php echo $u['id']; ?>" class="btn-admin btn-aprobar">Aprobar</a>
                        <?php endif; ?>

                        <?php 
                        // Si está PENDIENTE o ACTIVO (o aprobado), mostrar botón Rechazar
                        if ($estado === 'pendiente' || $estado === 'activo'): 
                        ?>
                            <a href="procesar.php?accion=rechazar_creador&usuario_id=<?php echo $u['id']; ?>" class="btn-admin btn-danger">Rechazar</a>
                        <?php endif; ?>

                        <?php 
                        // Si está RECHAZADO, agregar opción de ELIMINAR definitivo de la BD
                        if ($estado === 'rechazado'): 
                        ?>
                            <a href="procesar.php?accion=eliminar_usuario&usuario_id=<?php echo $u['id']; ?>" class="btn-admin" style="background: #555; color: #fff;" onclick="return confirm('¿Seguro que deseas eliminar este usuario de la base de datos? Podrá volver a registrarse con este correo.');">Eliminar</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>