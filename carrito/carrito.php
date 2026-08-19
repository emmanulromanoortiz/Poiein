<?php
session_start();

// 1. Validar si el usuario está autenticado
if (!isset($_SESSION['id']) && !isset($_SESSION['usuario_id'])) {
    header("Location: /poiein/login.php");
    exit;
}

$usuario_id = $_SESSION['id'] ?? $_SESSION['usuario_id'];

// 2. Conexión a la base de datos
if (file_exists("../conexión.php")) include_once("../conexión.php");
elseif (file_exists("../conexion.php")) include_once("../conexion.php");
else @include_once(__DIR__ . "/../conexión.php");

// 3. Consulta para obtener los productos en el carrito del usuario
$query = "SELECT c.id AS carrito_id, c.cantidad, p.id AS producto_id, p.nombre_item, p.precio, p.imagen_producto, u.nombre_completo AS autor
          FROM carrito c
          INNER JOIN productos p ON c.producto_id = p.id
          LEFT JOIN usuarios u ON p.usuario_id = u.id
          WHERE c.usuario_id = $usuario_id
          ORDER BY c.fecha_agregado DESC";

$resultado = mysqli_query($conexion, $query);

$items = [];
$subtotal_general = 0;
$total_piezas = 0;

if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($row = mysqli_fetch_assoc($resultado)) {
        $subtotal_item = $row['precio'] * $row['cantidad'];
        $subtotal_general += $subtotal_item;
        $total_piezas += $row['cantidad'];
        
        $row['subtotal_item'] = $subtotal_item;
        $items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras — POIEIN</title>
    <!-- Fuentes Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS del Nav y Carrito -->
    <link rel="stylesheet" href="/poiein/NAV/nav.css?v=1.7">
    <link rel="stylesheet" href="carrito.css">
</head>
<body>

    <!-- NAV BAR -->
    <?php 
    if (file_exists("../NAV/nav.php")) include_once("../NAV/nav.php");
    elseif (file_exists("NAV/nav.php")) include_once("NAV/nav.php");
    ?>

    <div class="carrito-container">
        
        <!-- ENCABEZADO -->
        <div class="carrito-header">
            <h1 class="titulo-galeria">
                <span>Tu Galería Personal</span>
                <span class="badget-count" id="badge-piezas"><?php echo $total_piezas; ?> pieza(s)</span>
            </h1>
            <p class="subtitulo-poiein">Revisa tus selecciones de obras únicas antes de adquirir.</p>
        </div>

        <?php if (empty($items)): ?>
            <!-- ESTADO VACÍO -->
            <div class="carrito-vacio-box">
                <div class="animacion-vacio">✨ 📜 ✨</div>
                <h2>Tu carrito está vacío</h2>
                <p>Aún no has agregado obras o piezas de nuestros creadores a tu colección.</p>
                <a href="/poiein/Explorar/Explorar.php" class="btn-explorar">Explorar Obras</a>
            </div>
        <?php else: ?>
            <!-- GRID CON PRODUCTOS Y RESUMEN -->
            <div class="carrito-grid">
                
                <!-- LISTA DE PRODUCTOS -->
                <div class="lista-productos">
                    <?php 
                    $delay = 0;
                    foreach ($items as $item): 
                        $delay += 0.1;
                        $img_path = !empty($item['imagen_producto']) ? '/poiein/' . str_replace('../', '', $item['imagen_producto']) : '';
                    ?>
                        <div class="item-card" id="item-card-<?php echo $item['carrito_id']; ?>" style="--delay: <?php echo $delay; ?>s;">
                            <!-- Imagen -->
                            <div class="item-img-box">
                                <?php if ($img_path): ?>
                                    <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($item['nombre_item']); ?>">
                                <?php else: ?>
                                    <div style="width:100%; height:100%; background:#25003e;"></div>
                                <?php endif; ?>
                            </div>

                            <!-- Información -->
                            <div>
                                <span class="autor-tag"><?php echo htmlspecialchars($item['autor'] ?? 'Creador Poiein'); ?></span>
                                <h3 class="item-titulo"><?php echo htmlspecialchars($item['nombre_item']); ?></h3>
                                <p class="item-precio-unitario">$<?php echo number_format($item['precio'], 2); ?> c/u</p>
                            </div>

                            <!-- Control de Cantidad -->
                            <div class="item-cantidad-box">
                                <button class="btn-qty" onclick="cambiarCantidad(<?php echo $item['carrito_id']; ?>, -1)">-</button>
                                <span class="qty-num" id="qty-<?php echo $item['carrito_id']; ?>"><?php echo $item['cantidad']; ?></span>
                                <button class="btn-qty" onclick="cambiarCantidad(<?php echo $item['carrito_id']; ?>, 1)">+</button>
                            </div>

                            <!-- Subtotal del Ítem -->
                            <div class="item-total-col">
                                <span class="lbl-subtotal-item">Subtotal</span>
                                <span class="item-precio-total" id="subtotal-item-<?php echo $item['carrito_id']; ?>" data-precio="<?php echo $item['precio']; ?>">
                                    $<?php echo number_format($item['subtotal_item'], 2); ?>
                                </span>
                            </div>

                            <!-- Botón Eliminar -->
                            <button class="btn-eliminar" onclick="confirmarEliminarItem(<?php echo $item['carrito_id']; ?>)" title="Eliminar ítem">
                                🗑️
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- RESUMEN DE COMPRA -->
                <aside class="resumen-compra">
                    <div class="card-resumen">
                        <h2 class="resumen-titulo">Resumen de Colección</h2>
                        <div class="separador-oro"></div>

                        <div class="resumen-linea">
                            <span>Subtotal</span>
                            <span id="resumen-subtotal">$<?php echo number_format($subtotal_general, 2); ?></span>
                        </div>

                        <div class="resumen-linea">
                            <span>Envío / Transferencia</span>
                            <span style="color: #2ece72; font-weight: 600;">A convenir</span>
                        </div>

                        <!-- Cupón -->
                        <div class="resumen-linea cupon-linea">
                            <input type="text" placeholder="Código de artesano" class="input-cupon">
                            <button class="btn-cupon">Aplicar</button>
                        </div>

                        <div class="separador-oro"></div>

                        <!-- Total Final -->
                        <div class="resumen-linea linea-total">
                            <span>Total</span>
                            <span class="precio-gold" id="resumen-total">$<?php echo number_format($subtotal_general, 2); ?></span>
                        </div>

                        <!-- Botón de Pago -->
                        <button class="btn-checkout" onclick="procederPago()">
                            <span>PROCEDER AL PAGO</span>
                            <span class="btn-flecha">→</span>
                        </button>

                        <div class="garantia-poiein">
                            🔒 Compras protegidas por Poiein
                            <div class="metodos-pago">CARD • TRANSF • CASH</div>
                        </div>
                    </div>
                </aside>

            </div>
        <?php endif; ?>

    </div>

    <!-- MODAL PERSONALIZADA DE CONFIRMACIÓN -->
<div id="modal-confirmar-eliminar" class="modal-poiein-overlay">
    <div class="modal-poiein-card">
        <button type="button" class="modal-poiein-close" onclick="cerrarModalConfirmacion()">&times;</button>
        <div class="modal-poiein-icono">🗑️</div>
        <h3 class="modal-poiein-titulo">Quitar Pieza</h3>
        <p class="modal-poiein-mensaje">¿Deseas quitar esta pieza de tu colección?</p>
        <div class="modal-poiein-acciones">
            <button type="button" class="btn-cancelar-modal" onclick="cerrarModalConfirmacion()">Cancelar</button>
            <button type="button" id="btn-ejecutar-eliminar" class="btn-eliminar-modal" onclick="ejecutarEliminacion()">Quitar</button>
        </div>
    </div>
</div>

    <!-- SCRIPT DE INTERACCIÓN DINÁMICA -->
   <script>
let carritoIdAEliminar = null;

// 1. Abrir Modal agregando la clase CSS .is-visible
function confirmarEliminarItem(carritoId) {
    console.log("1. Se hizo clic en la basura del ítem ID:", carritoId);
    carritoIdAEliminar = carritoId;
    
    const modal = document.getElementById('modal-confirmar-eliminar');
    if (modal) {
        modal.classList.add('is-visible');
        modal.style.display = 'flex'; // <--- FORZAR APARICIÓN DIRECTA
        console.log("2. Modal forzada a mostrarse.");
    } else {
        alert("ERROR: No se encontró el contenedor #modal-confirmar-eliminar en el HTML.");
    }
}

function cerrarModalConfirmacion() {
    const modal = document.getElementById('modal-confirmar-eliminar');
    if (modal) {
        modal.classList.remove('is-visible');
        modal.style.display = 'none'; // <--- OCULTAR AL CERRAR
    }
    carritoIdAEliminar = null;
}

// 3. Ejecutar Eliminación
function ejecutarEliminacion() {
    if (!carritoIdAEliminar) {
        alert("ERROR: No se ha seleccionado ningún ítem.");
        return;
    }

    const idAEnviar = carritoIdAEliminar;
    cerrarModalConfirmacion();

    fetch('/poiein/carrito/eliminar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ carrito_id: parseInt(idAEnviar) })
    })
    .then(res => {
        if (!res.ok) {
            throw new Error("HTTP " + res.status + " - No se encontró eliminar.php");
        }
        return res.json();
    })
    .then(data => {
        if (data.status === 'success') {
            const card = document.getElementById(`item-card-${idAEnviar}`);
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                card.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    card.remove();
                    recalcularTotales();
                }, 300);
            } else {
                window.location.reload();
            }
        } else {
            alert("Error: " + (data.message || "No se pudo eliminar el producto."));
        }
    })
    .catch(err => {
        console.error("Fetch Error:", err);
        alert("Error de red: " + err.message);
    });
}

// 4. Recalcular Totales en pantalla
function recalcularTotales() {
    const cards = document.querySelectorAll('.item-card');
    if (cards.length === 0) {
        window.location.reload();
        return;
    }

    let nuevoSubtotalGeneral = 0;
    let piezasTotales = 0;

    cards.forEach(card => {
        const qtySpan = card.querySelector('.qty-num');
        const subtotalSpan = card.querySelector('.item-precio-total');
        
        if (qtySpan && subtotalSpan) {
            const qty = parseInt(qtySpan.innerText) || 0;
            const precioUnitario = parseFloat(subtotalSpan.getAttribute('data-precio')) || 0;
            
            const nuevoSubtotalItem = qty * precioUnitario;
            subtotalSpan.innerText = `$${nuevoSubtotalItem.toFixed(2)}`;

            nuevoSubtotalGeneral += nuevoSubtotalItem;
            piezasTotales += qty;
        }
    });

    const subtotalElem = document.getElementById('resumen-subtotal');
    const totalElem = document.getElementById('resumen-total');
    const badgeElem = document.getElementById('badge-piezas');

    if (subtotalElem) subtotalElem.innerText = `$${nuevoSubtotalGeneral.toFixed(2)}`;
    if (totalElem) totalElem.innerText = `$${nuevoSubtotalGeneral.toFixed(2)}`;
    if (badgeElem) badgeElem.innerText = `${piezasTotales} pieza(s)`;

    const cartBadgeNav = document.getElementById('cart-badge');
    if (cartBadgeNav) cartBadgeNav.innerText = piezasTotales;
}
</script>
</body>
</html>