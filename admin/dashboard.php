<?php
session_start();

// 1. PROTECCIÓN ABSOLUTA: Si no es admin, lo expulsamos al index
if (!isset($_SESSION['nombre']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// 2. CONEXIÓN (Ajusta los puntos según la profundidad de tu carpeta)
include("../conexión.php");

// 3. CONSULTAS PARA LAS MÉTRICAS
// Total de productos
$res_prod = mysqli_query($conexion, "SELECT COUNT(*) as total FROM productos");
$total_productos = mysqli_fetch_assoc($res_prod)['total'];

// Total de reportes pendientes
$res_rep = mysqli_query($conexion, "SELECT COUNT(*) as total FROM reportes WHERE estado = 'pendiente'");
$total_reportes = mysqli_fetch_assoc($res_rep)['total'];

// 4. CONSULTA DE REPORTES ACTUALES (Renombramos r.id como reporte_id para evitar conflictos)
$query_reportes = "SELECT r.id AS reporte_id, r.producto_id, r.motivo, r.fecha_reporte, r.estado, p.nombre_item, p.imagen_producto 
                   FROM reportes r
                   JOIN productos p ON r.producto_id = p.id
                   WHERE r.estado = 'pendiente'
                   ORDER BY r.fecha_reporte DESC";
$lista_reportes = mysqli_query($conexion, $query_reportes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poiein | Panel de Control</title>
    <!-- Mantenemos tus estilos globales y agregamos los del admin -->
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="theme-black admin-body">

    <header class="admin-header">
        <div class="logo-admin">✧ POIEIN <span>ADMIN</span></div>
        <div class="user-admin">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?> | <a href="../index.php">Salir al Sitio</a></div>
    </header>

    <main class="admin-container">
        <!-- SECCIÓN 1: TARJETAS DE MÉTRICAS -->
        <section class="metrics-grid">
            <div class="metric-card">
                <h3>Productos Activos</h3>
                <p class="number"><?php echo $total_productos; ?></p>
            </div>
            <div class="metric-card alert-card">
                <h3>Reportes Pendientes</h3>
                <p class="number"><?php echo $total_reportes; ?></p>
            </div>
        </section>

        <!-- SECCIÓN 2: TABLA DE MONITOREO DE REPORTES -->
        <section class="reportes-seccion">
            <h2>Bandeja de Moderación y Denuncias</h2>
            <p class="sub">Monitorea, revisa la publicación y da de baja los legados que infrinjan las normas.</p>

            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Motivo del Reporte</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones de Moderación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($lista_reportes) > 0): ?>
                            <?php while($rep = mysqli_fetch_assoc($lista_reportes)): ?>
                                <tr>
                                    <td class="td-producto">
                                        <img src="/poiein/<?php echo str_replace('../', '', $rep['imagen_producto']); ?>" alt="Foto">
                                        <span><?php echo htmlspecialchars($rep['nombre_item']); ?> (ID: <?php echo $rep['producto_id']; ?>)</span>
                                    </td>
                                    <td><?php echo htmlspecialchars($rep['motivo']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($rep['fecha_reporte'])); ?></td>
                                    <td><span class="badge pendiente">Pendiente</span></td>
                                    <td class="acciones-td">
                                        <!-- Botón para ver la publicación del producto -->
                                        <a href="/poiein/detalle/detalleprod.php?id=<?php echo $rep['producto_id']; ?>" target="_blank" class="btn-admin" style="background: #222; color: #d4af37; border: 1px solid #d4af37;">Ver Publicación</a>
                                        
                                        <!-- Usamos reporte_id para el reporte y id para el producto -->
                                        <a href="procesar.php?accion=eliminar_prod&id=<?php echo $rep['producto_id']; ?>&reporte=<?php echo $rep['reporte_id']; ?>" class="btn-admin btn-danger" onclick="return confirm('¿Seguro que deseas ELIMINAR definitivamente este producto del catálogo?')">Dar de Baja</a>
                                        <a href="procesar.php?accion=ignorar&reporte=<?php echo $rep['reporte_id']; ?>" class="btn-admin btn-safe">Ignorar</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-data">No hay reportes pendientes</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SECCIÓN 3: SOLICITUDES DE CREADORES -->
        <?php
        $query_creadores = "SELECT * FROM usuarios WHERE rol = 'creador' AND estado = 'pendiente'";
        $resultado_creadores = mysqli_query($conexion, $query_creadores);
        ?>
        <section class="reportes-seccion" style="margin-top: 50px;">
            <h2>Solicitudes de Creadores</h2>
            <p class="sub">Revisa los nuevos creadores pendientes de aprobación.</p>

            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Creador</th>
                            <th>Producto Principal</th>
                            <th>Región</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultado_creadores && mysqli_num_rows($resultado_creadores) > 0): ?>
                            <?php while($c = mysqli_fetch_assoc($resultado_creadores)): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($c['nombre_completo']); ?></strong><br>
                                        <small style="color: #aaa;"><?php echo htmlspecialchars($c['email']); ?></small>
                                    </td>
                                    <td>
                                        <span style="color: #f39c12; font-weight: 600;"><?php echo htmlspecialchars($c['nombre_producto']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($c['region']); ?></td>
                                    <td class="acciones-td">
                                        <!-- Botón para abrir los detalles -->
                                        <button type="button" class="btn-admin btn-detalles" 
                                            onclick="mostrarDetalles(
                                                '<?php echo addslashes(htmlspecialchars($c['nombre_completo'])); ?>', 
                                                '<?php echo addslashes(htmlspecialchars($c['email'])); ?>', 
                                                '<?php echo addslashes(htmlspecialchars($c['nombre_producto'])); ?>', 
                                                '<?php echo addslashes(htmlspecialchars($c['region'])); ?>', 
                                                '<?php echo addslashes(nl2br(htmlspecialchars($c['biografia']))); ?>'
                                            )">
                                            Detalles
                                        </button>
                                        <a href="procesar.php?accion=aprobar_creador&usuario_id=<?php echo $c['id']; ?>" class="btn-admin btn-aprobar">Aprobar</a>
                                        <a href="procesar.php?accion=rechazar_creador&usuario_id=<?php echo $c['id']; ?>" class="btn-admin btn-danger" onclick="return confirm('¿Seguro que deseas rechazar a este creador?');">Rechazar</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="no-data">No hay solicitudes de creadores pendientes. ✧</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- MODAL DE DETALLES (Ventana Flotante) -->
        <div id="modalDetalles" class="modal-overlay">
            <div class="modal-content-admin">
                <h3>Detalles del Creador</h3>
                <p><strong>Nombre:</strong> <span id="modalNombre"></span></p>
                <p><strong>Correo:</strong> <span id="modalEmail"></span></p>
                <p><strong>Producto:</strong> <span id="modalProducto" class="modal-producto-highlight"></span></p>
                <p><strong>Región:</strong> <span id="modalRegion"></span></p>
                <p><strong>Biografía / Descripción:</strong></p>
                <div id="modalBio" class="modal-bio-box"></div>

                <div class="modal-footer" style="margin-top: 20px;">
                    <button type="button" class="btn-cerrar-modal" onclick="cerrarDetalles()">Cerrar</button>
                </div>
            </div>
        </div>

        <!-- SCRIPT PARA CONTROLAR EL MODAL -->
        <script>
        function mostrarDetalles(nombre, email, producto, region, biografia) {
            document.getElementById('modalNombre').innerText = nombre;
            document.getElementById('modalEmail').innerText = email;
            document.getElementById('modalProducto').innerText = producto;
            document.getElementById('modalRegion').innerText = region;
            document.getElementById('modalBio').innerHTML = biografia || 'Sin biografía proporcionada.';
            
            document.getElementById('modalDetalles').style.display = 'flex';
        }

        function cerrarDetalles() {
            document.getElementById('modalDetalles').style.display = 'none';
        }
        </script>
        
    </main>

    <!-- ACCESO RÁPIDO A GESTIÓN DE USUARIOS -->
    <section style="max-width: 1200px; margin: 0 auto 40px auto; padding: 0 20px;">
        <a href="usuarios.php" class="metric-card" style="text-decoration: none; display: flex; flex-direction: column; justify-content: center; align-items: center; border: 1px solid #d4af37; background: #1a1a1a; padding: 20px;">
            <h3 style="color: #d4af37;">Usuarios</h3>
            <p style="font-size: 14px; color: #fff; margin-top: 5px;">Ver Creadores y Estados</p>
        </a>
    </section>

</body>
</html>