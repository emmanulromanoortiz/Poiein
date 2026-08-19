<?php
session_start();

// Conexión
if (file_exists(__DIR__ . "/../conexion.php")) {
    include(__DIR__ . "/../conexion.php");
} elseif (file_exists(__DIR__ . "/../conexión.php")) {
    include(__DIR__ . "/../conexión.php");
}

// Validar inicio de sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /poiein/login.php");
    exit;
}

$usuario_id = intval($_SESSION['usuario_id']);

// Consulta para obtener los productos guardados en favoritos
// Aseguramos p.id AS real_producto_id para evitar confusiones de nombres
$query = "SELECT f.id AS favorito_id, f.producto_id, f.fecha_agregado, 
                 p.id AS real_producto_id, p.nombre_item, p.precio, p.imagen_producto,
                 u.nombre_completo, u.nombre_producto 
          FROM favoritos f
          INNER JOIN productos p ON f.producto_id = p.id
          LEFT JOIN usuarios u ON p.usuario_id = u.id
          WHERE f.usuario_id = '$usuario_id'
          ORDER BY f.fecha_agregado DESC";

$resultado = mysqli_query($conexion, $query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Lista de Deseos | Poiein</title>
    
    <link rel="stylesheet" href="../NAV/nav.css">
    <link rel="stylesheet" href="../style.css">
    
    <style>
        body {
            background-color: #0b0512;
            color: #fff;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .container-deseos {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .titulo-deseos {
            font-size: 28px;
            color: #d4af37;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .grid-deseos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        .card-deseo {
            background: #140b21;
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-deseo:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.2);
        }
        .img-deseo {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }
        .info-deseo {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .nombre-deseo {
            font-size: 18px;
            color: #fff;
            margin-bottom: 5px;
            text-decoration: none;
            font-weight: 600;
        }
        .nombre-deseo:hover {
            color: #d4af37;
        }
        .autor-deseo {
            font-size: 13px;
            color: #9a8c9e;
            margin-bottom: 12px;
        }
        .precio-deseo {
            font-size: 22px;
            color: #d4af37;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .acciones-deseo {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn-deseo-carrito {
            background: #d4af37;
            color: #0d0614;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }
        .btn-deseo-carrito:hover {
            background: #e6c247;
        }
        .btn-eliminar-deseo {
            background: transparent;
            border: 1px solid rgba(231, 76, 60, 0.4);
            color: #e74c3c;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }
        .btn-eliminar-deseo:hover {
            background: rgba(231, 76, 60, 0.15);
            border-color: #e74c3c;
        }
        .vacio-box {
            text-align: center;
            padding: 60px 20px;
            background: #140b21;
            border-radius: 16px;
            border: 1px dashed rgba(212, 175, 55, 0.3);
        }

        /* --- ESTILOS DEL MODAL POIEIN --- */
        .modal-poiein-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 99999;
        }
        .modal-poiein-card {
            background: #180d28;
            border: 1px solid #d4af37;
            border-radius: 16px;
            padding: 30px;
            width: 90%;
            max-width: 380px;
            text-align: center;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }
        .modal-poiein-close {
            position: absolute;
            top: 10px;
            right: 15px;
            background: transparent;
            border: none;
            color: #888;
            font-size: 22px;
            cursor: pointer;
        }
        .modal-poiein-icono {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .modal-poiein-titulo {
            color: #d4af37;
            font-size: 20px;
            margin-bottom: 10px;
        }
        .modal-poiein-mensaje {
            color: #ccc;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .btn-poiein-modal {
            background: #d4af37;
            color: #000;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body class="theme-black">

    <?php 
    if (file_exists(__DIR__ . '/../NAV/nav.php')) {
        include __DIR__ . '/../NAV/nav.php'; 
    }
    ?>

    <main class="container-deseos">
        <h1 class="titulo-deseos">💖 Mi Lista de Deseos</h1>

        <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
            <div class="grid-deseos">
                <?php while ($row = mysqli_fetch_assoc($resultado)): 
                    // Asignamos explícitamente el ID del producto
                    $prod_id = $row['real_producto_id'] ?: $row['producto_id'];
                ?>
                    <div class="card-deseo" id="item-deseo-<?php echo $prod_id; ?>">
                        <a href="/poiein/PRODUCTOS/detalleprod.php?id=<?php echo $prod_id; ?>">
                            <img src="/poiein/<?php echo $row['imagen_producto']; ?>" alt="<?php echo htmlspecialchars($row['nombre_item']); ?>" class="img-deseo">
                        </a>
                        <div class="info-deseo">
                            <a href="/poiein/PRODUCTOS/detalleprod.php?id=<?php echo $prod_id; ?>" class="nombre-deseo">
                                <?php echo htmlspecialchars($row['nombre_item']); ?>
                            </a>
                            <span class="autor-deseo">
                                Creado por: <?php echo htmlspecialchars($row['nombre_producto'] ?: $row['nombre_completo'] ?: 'Artista'); ?>
                            </span>
                            <div class="precio-deseo">$<?php echo number_format($row['precio'], 2); ?></div>

                            <div class="acciones-deseo">
                                <button type="button" class="btn-deseo-carrito" onclick="agregarAlCarrito(<?php echo $prod_id; ?>)">
                                    🛒 Mover al Carrito
                                </button>
                                <button type="button" class="btn-eliminar-deseo" onclick="eliminarDeDeseos(<?php echo $prod_id; ?>)">
                                    🗑️ Quitar de Deseos
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="vacio-box">
                <h3 style="color: #d4af37; margin-bottom: 10px;">Tu lista de deseos está vacía</h3>
                <p>Explora nuestras obras y guarda tus favoritas presionando el botón de corazón.</p>
                <a href="/poiein/index.php" style="color: #d4af37; text-decoration: underline; margin-top: 15px; display: inline-block;">Descubrir Productos</a>
            </div>
        <?php endif; ?>
    </main>

    <!-- MODAL POIEIN (NOTIFICACIONES CENTRADAS) -->
    <div id="poiein-modal" class="modal-poiein-overlay">
        <div class="modal-poiein-card">
            <button class="modal-poiein-close" onclick="cerrarModalPoiein()">&times;</button>
            <div class="modal-poiein-icono" id="poiein-modal-icono">✨</div>
            <h3 class="modal-poiein-titulo" id="poiein-modal-titulo">Atención</h3>
            <p class="modal-poiein-mensaje" id="poiein-modal-mensaje">Mensaje de notificación...</p>
            <div class="modal-poiein-acciones" id="poiein-modal-acciones">
                <button class="btn-poiein-modal" onclick="cerrarModalPoiein()">Aceptar</button>
            </div>
        </div>
    </div>

    <script>
    function mostrarModalPoiein(titulo, mensaje, icono = '✨') {
        document.getElementById('poiein-modal-titulo').innerText = titulo;
        document.getElementById('poiein-modal-mensaje').innerText = mensaje;
        document.getElementById('poiein-modal-icono').innerText = icono;
        document.getElementById('poiein-modal').style.display = 'flex';
    }

    function cerrarModalPoiein() {
        document.getElementById('poiein-modal').style.display = 'none';
    }

    // 1. Mover de lista de deseos al Carrito
    function agregarAlCarrito(productoId) {
        if (!productoId) {
            mostrarModalPoiein("Error", "ID de producto inválido.", "⚠️");
            return;
        }

        fetch('/poiein/carrito/agregar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ producto_id: productoId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                mostrarModalPoiein("¡Añadido al Carrito!", data.message, "🛒");
            } else {
                mostrarModalPoiein("Atención", data.message, "⚠️");
            }
        })
        .catch(err => console.error("Error en carrito:", err));
    }

    // 2. Quitar pieza de la lista de deseos
    function eliminarDeDeseos(productoId) {
        if (!productoId) {
            mostrarModalPoiein("Error", "ID de producto inválido.", "⚠️");
            return;
        }

        fetch('/poiein/deseos/eliminar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ producto_id: productoId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const card = document.getElementById('item-deseo-' + productoId);
                if (card) {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        card.remove();
                        if (document.querySelectorAll('.card-deseo').length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
                mostrarModalPoiein("Removido", data.message, "🗑️");
            } else {
                mostrarModalPoiein("Error", data.message, "⚠️");
            }
        })
        .catch(err => console.error("Error al eliminar:", err));
    }
    </script>
</body>
</html>