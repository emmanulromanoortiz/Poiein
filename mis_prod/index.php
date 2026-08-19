<?php
session_start();
// Conexión a la base de datos
include($_SERVER['DOCUMENT_ROOT'] . "/poiein/conexión.php"); 

// 1. Verificamos si el usuario ha iniciado sesión
$is_logged_in = isset($_SESSION['nombre']); 
$rol = $is_logged_in ? ($_SESSION['rol'] ?? 'consumidor') : null;
$mi_id = $_SESSION['usuario_id'] ?? null; // ID del usuario logueado

$resultado = null;
$resultado_encargos = null;

if ($is_logged_in && $mi_id) {
    if ($rol == 'creador') {
        // Consulta para Creador: Muestra sus productos subidos
        $stmt = $conexion->prepare("SELECT * FROM productos WHERE usuario_id = ? ORDER BY fecha_subida DESC");
        if ($stmt) {
            $stmt->bind_param("i", $mi_id);
            $stmt->execute();
            $resultado = $stmt->get_result();
        }
    } else {
        // Consulta para Consumidor: Muestra los productos que HA COMPRADO
        $query = "SELECT p.* 
                  FROM productos p
                  JOIN detalle_pedido dp ON p.id = dp.producto_id
                  JOIN pedidos ped ON dp.pedido_id = ped.id
                  WHERE ped.usuario_id = ? 
                  GROUP BY p.id 
                  ORDER BY MAX(ped.fecha) DESC";
                  
        $stmt = $conexion->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $mi_id);
            $stmt->execute();
            $resultado = $stmt->get_result();
        }

        // Consulta para Consumidor: Muestra sus encargos personalizados y el estatus del creador
        $query_encargos = "SELECT e.*, u.nombre_completo AS creador_nombre, u.nombre_producto AS creador_marca 
                           FROM encargos_personalizados e 
                           JOIN usuarios u ON e.creador_id = u.id 
                           WHERE e.usuario_id = ? 
                           ORDER BY e.fecha DESC";
        $stmt_encargos = $conexion->prepare($query_encargos);
        if ($stmt_encargos) {
            $stmt_encargos->bind_param("i", $mi_id);
            $stmt_encargos->execute();
            $resultado_encargos = $stmt_encargos->get_result();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Poiein - Mis Productos y Encargos</title>
    <link rel="stylesheet" href="mis_prod.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
</head>
<body>

    <?php include('../NAV/nav.php'); ?>

    <div class="container-productos">
        <?php if (!$is_logged_in): ?>
            <div class="restricted-view">
                <div class="restricted-icon">✧</div>
                <h1>Área Reservada</h1>
                <p>Inicia sesión para ver tus compras y productos.</p>
                <button class="btn-accion" onclick="openLoginModal()">Acceder a Poiein</button>
            </div>

        <?php else: ?>
            <header class="header-seccion">
                <?php if($rol == 'creador'): ?>
                    <h1>Mis Creaciones</h1>
                    <p>Gestiona los legados que has compartido con el mundo.</p>
                <?php else: ?>
                    <h1>Mis Compras</h1>
                    <p>Las obras y piezas que has adquirido en Poiein.</p>
                <?php endif; ?>
            </header>

            <div class="grid-productos">
                <?php 
                if ($resultado && $resultado->num_rows > 0): 
                    while($prod = $resultado->fetch_assoc()): 
                        $ruta_db = $prod['imagen_producto'] ?? ''; 
                        $ruta_limpia = str_replace('../', '', $ruta_db);
                        $prod_id = $prod['id'] ?? 0;
                ?>
                    <article class="card-legado">
                        <div class="img-container">
                            <img src="/poiein/<?php echo $ruta_limpia; ?>" alt="<?php echo htmlspecialchars($prod['nombre_item']); ?>">
                        </div>
                        <div class="info-producto">
                            <h3><?php echo htmlspecialchars($prod['nombre_item']); ?></h3>
                            <span class="price-tag">$<?php echo number_format($prod['precio'], 2); ?> USD</span>
                            
                            <div class="acciones-prod">
                                <?php if($rol == 'creador'): ?>
                                    <button class="btn-accion" onclick="window.location.href='/poiein/editar/edt_prod.php?id=<?php echo $prod_id; ?>'">
                                        Editar
                                    </button>
                                    <span class="btn-delete" style="cursor:pointer;" onclick="eliminarProducto(<?php echo $prod_id; ?>)">Eliminar</span>
                                <?php else: ?>
                                    <a href="/poiein/detalle/detalleprod.php?id=<?php echo $prod_id; ?>">
                                        <button class="btn-accion">Ver Detalles</button>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <p style="color: var(--oro); font-style: italic;">
                        <?php echo ($rol == 'creador') ? "Aún no has subido productos." : "Aún no has realizado ninguna compra."; ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- SECCIÓN EXCLUSIVA PARA CONSUMIDORES: ENCARGOS PERSONALIZADOS -->
            <?php if($rol != 'creador'): ?>
                <header class="header-seccion" style="margin-top: 50px; border-top: 1px solid #222; padding-top: 30px;">
                    <h1>Mis Solicitudes de Diseño Personalizado</h1>
                    <p>Estado de tus peticiones enviadas a los creadores.</p>
                </header>

                <div class="grid-productos">
                    <?php if ($resultado_encargos && $resultado_encargos->num_rows > 0): ?>
                        <?php while($encargo = $resultado_encargos->fetch_assoc()): ?>
                            <article class="card-legado">
                                <div class="img-container">
                                    <img src="/poiein/<?php echo htmlspecialchars($encargo['imagen_personalizada']); ?>" alt="Diseño personalizado">
                                </div>
                                <div class="info-producto">
                                    <h3>Creador: <?php echo htmlspecialchars(!empty($encargo['creador_marca']) ? $encargo['creador_marca'] : $encargo['creador_nombre']); ?></h3>
                                    <span style="font-size: 0.8rem; color: #888;">Fecha: <?php echo $encargo['fecha']; ?></span>
                                    
                                    <p style="margin-top: 10px; font-size: 0.9rem;">
                                        <strong>Estado:</strong> 
                                        <span style="text-transform: uppercase; font-weight: bold; color: 
                                            <?php 
                                                if($encargo['estado'] == 'aceptado') echo '#2ecc71';
                                                elseif($encargo['estado'] == 'rechazado') echo '#e74c3c';
                                                else echo '#d4af37'; 
                                            ?>;">
                                            <?php 
                                                if($encargo['estado'] == 'aceptado') echo '🟢 Aceptado';
                                                elseif($encargo['estado'] == 'rechazado') echo '🔴 Rechazado';
                                                else echo '🟡 Pendiente'; 
                                            ?>
                                        </span>
                                    </p>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: var(--oro); font-style: italic; grid-column: 1/-1;">
                            Aún no has enviado ningún encargo personalizado.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <script>
    function openLoginModal() {
        const modal = document.querySelector('.modal-overlay');
        if(modal) modal.classList.add('active');
    }

    function eliminarProducto(id) {
        if(confirm("¿Estás seguro de que deseas eliminar este producto?")) {
            fetch('/poiein/productos/eliminar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message || "No se pudo eliminar el producto.");
                }
            })
            .catch(err => console.error("Error:", err));
        }
    }
    </script>
</body>
</html>