<?php
session_start();

if (file_exists(__DIR__ . "/../conexion.php")) {
    include(__DIR__ . "/../conexion.php");
} elseif (file_exists(__DIR__ . "/../conexión.php")) {
    include(__DIR__ . "/../conexión.php");
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<p class='mensaje-error'>No se especificó ningún producto.</p>");
}

$id_producto = intval($_GET['id']);

// 1. Datos del producto (Consulta preparada)
$stmt = $conexion->prepare("SELECT p.*, u.nombre_completo, u.nombre_producto, u.foto_perfil AS foto_creador 
                            FROM productos p 
                            LEFT JOIN usuarios u ON p.usuario_id = u.id 
                            WHERE p.id = ? LIMIT 1");
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado && $resultado->num_rows > 0) {
    $prod = $resultado->fetch_assoc();
} else {
    die("<p class='mensaje-error'>El producto no existe.</p>");
}

$id_user_actual = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0;

// 2. Comprobar favoritos
$es_favorito = false;
if ($id_user_actual > 0) {
    $stmt_fav = $conexion->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND producto_id = ? LIMIT 1");
    $stmt_fav->bind_param("ii", $id_user_actual, $prod['id']);
    $stmt_fav->execute();
    if ($stmt_fav->get_result()->num_rows > 0) {
        $es_favorito = true;
    }
}

// 3. Procesar publicación o respuesta
$mensaje_resena = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_publicar_opinion'])) {
    if ($id_user_actual <= 0) {
        $mensaje_resena = "login_requerido";
    } else {
        $estrellas = isset($_POST['estrellas']) ? intval($_POST['estrellas']) : 5;
        $comentario = trim($_POST['comentario']);
        $parent_id = (!empty($_POST['parent_id'])) ? intval($_POST['parent_id']) : null;

        if (!empty($comentario)) {
            $stmt_ins = $conexion->prepare("INSERT INTO reseñas (producto_id, usuario_id, parent_id, estrellas, comentario) VALUES (?, ?, ?, ?, ?)");
            $stmt_ins->bind_param("iiiis", $prod['id'], $id_user_actual, $parent_id, $estrellas, $comentario);
            
            if ($stmt_ins->execute()) {
                header("Location: detalleprod.php?id=" . $prod['id']);
                exit;
            }
        }
    }
}

// 4. Promedio de estrellas
$stmt_prom = $conexion->prepare("SELECT AVG(estrellas) AS promedio, COUNT(*) AS total FROM reseñas WHERE producto_id = ? AND parent_id IS NULL");
$stmt_prom->bind_param("i", $prod['id']);
$stmt_prom->execute();
$datos_prom = $stmt_prom->get_result()->fetch_assoc();

$promedio_real = $datos_prom['promedio'] ? number_format($datos_prom['promedio'], 1) : "0.0";
$total_votos = $datos_prom['total'] ?? 0;

// 5. Carga Optimizada de Comentarios y Respuestas (1 sola consulta)
$stmt_com = $conexion->prepare("SELECT r.*, u.nombre_completo, u.nombre_producto,
                               (SELECT COUNT(*) FROM reaccion_comentario WHERE comentario_id = r.id AND tipo = 'like') AS likes,
                               (SELECT COUNT(*) FROM reaccion_comentario WHERE comentario_id = r.id AND tipo = 'dislike') AS dislikes
                               FROM reseñas r 
                               JOIN usuarios u ON r.usuario_id = u.id 
                               WHERE r.producto_id = ? 
                               ORDER BY r.fecha ASC");
$stmt_com->bind_param("i", $prod['id']);
$stmt_com->execute();
$res_comentarios = $stmt_com->get_result();

$comentarios_principales = [];
$respuestas_map = [];

while ($row = $res_comentarios->fetch_assoc()) {
    if ($row['parent_id'] === null) {
        $comentarios_principales[] = $row;
    } else {
        $respuestas_map[$row['parent_id']][] = $row;
    }
}
usort($comentarios_principales, fn($a, $b) => strtotime($b['fecha']) - strtotime($a['fecha']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($prod['nombre_item']); ?> | Poiein</title>
    
    <link rel="stylesheet" href="../NAV/nav.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="detalleprod.css?v=4.0"> 
</head>
<body class="theme-black">

    <?php 
    if (file_exists(__DIR__ . '/../NAV/nav.php')) {
        include __DIR__ . '/../NAV/nav.php'; 
    }
    ?>

    <main class="container-detalle">
        <!-- COLUMNA GALERÍA -->
        <section class="columna-galeria">
            <div class="galeria-seccion">
                <div class="miniaturas">
                    <div class="mini-foto activa">
                        <img src="/poiein/<?php echo $prod['imagen_producto']; ?>" alt="Miniatura">
                    </div>
                </div>
                <div class="imagen-principal">
                    <img src="/poiein/<?php echo $prod['imagen_producto']; ?>" alt="<?php echo htmlspecialchars($prod['nombre_item']); ?>">
                </div>
            </div>

            <?php if(!empty($prod['usuario_id'])): ?>
                <a href="/poiein/perfil/perfil_creador.php?id=<?php echo $prod['usuario_id']; ?>" class="tarjeta-creador-link">
                    <img src="<?php echo !empty($prod['foto_creador']) ? '/poiein/' . str_replace('../', '', $prod['foto_creador']) : '/poiein/img/default_avatar.jpg'; ?>" class="avatar-creador-mini" alt="Foto Creador">
                    <div class="info-creador-mini">
                        <span class="lbl-creador">Creado por</span>
                        <span class="nombre-creador-txt">
                            <?php echo htmlspecialchars($prod['nombre_producto'] ?: $prod['nombre_completo']); ?> ✦
                        </span>
                    </div>
                </a>
            <?php endif; ?>
        </section>

        <!-- COLUMNA INFO -->
        <section class="info-seccion">
            <span class="estado-ventas">Nuevo | Publicado recientemente</span>
            <h1 class="producto-titulo"><?php echo htmlspecialchars($prod['nombre_item']); ?></h1>

            <div class="precio-contenedor">
                <span class="precio-entero">$<?php echo number_format($prod['precio'], 2); ?></span>
            </div>
            <p class="iva-aviso">IVA incluido</p>

            <div class="descripcion-contenedor">
                <h3>Descripción</h3>
                <p><?php echo nl2br(htmlspecialchars($prod['descripcion'])); ?></p>
            </div>
        </section>

        <!-- COLUMNA COMPRA -->
        <section class="compra-seccion">
            <div class="tarjeta-compra">
                <p class="envio-info">Disponible para envío</p>
                <p class="stock-disponible">Stock disponible</p>
                
                <button class="btn-comprar" type="button" onclick="comprarAhora(<?php echo $prod['id']; ?>)">Comprar ahora</button>
                <button class="btn-carrito" type="button" onclick="agregarAlCarrito(<?php echo $prod['id']; ?>)">🛒 Agregar al carrito</button>
                
                <button class="btn-deseos <?php echo $es_favorito ? 'en-favoritos' : ''; ?>" id="btn-deseos-prod" type="button" onclick="toggleDeseos(<?php echo $prod['id']; ?>)">
                    <svg class="icono-corazon-shadcn" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $es_favorito ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                        <path class="heart-path" d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572" />
                    </svg>
                    <span id="txt-btn-deseos"><?php echo $es_favorito ? 'En Lista de Deseos' : 'Añadir a Lista de Deseos'; ?></span>
                </button>
                
                <button class="btn-reportar-link" type="button" onclick="abrirModalReporte('producto', <?php echo $prod['id']; ?>)">
                    🚩 Reportar esta publicación
                </button>

                <a href="../index.php" class="btn-volver-link">Volver al inicio</a>
            </div>
        </section>
    </main>

    <!-- RESEÑAS Y COMENTARIOS -->
    <section class="contenedor-resenas">
        <h2 class="titulo-resenas">Opiniones y Calificaciones</h2>

        <div class="grid-resenas">
            <div class="resumen-estrellas">
                <div class="promedio-num"><?php echo $promedio_real; ?></div>
                <div class="estrellas-rating">
                    <?php 
                        $rating_redondeado = round((float)$promedio_real);
                        for($i = 1; $i <= 5; $i++) { echo ($i <= $rating_redondeado) ? "★" : "☆"; }
                    ?>
                </div>
                <p class="total-votos"><?php echo $total_votos; ?> opinión(es)</p>
            </div>

            <div class="box-comentar">
                <h3>Deja tu opinión</h3>
                <form action="detalleprod.php?id=<?php echo $prod['id']; ?>" method="POST" class="form-comentario">
                    <div class="selector-estrellas">
                        <label>Tu calificación:</label>
                        <div class="rating-stars">
                            <input type="radio" id="star5" name="estrellas" value="5" required><label for="star5">★</label>
                            <input type="radio" id="star4" name="estrellas" value="4"><label for="star4">★</label>
                            <input type="radio" id="star3" name="estrellas" value="3"><label for="star3">★</label>
                            <input type="radio" id="star2" name="estrellas" value="2"><label for="star2">★</label>
                            <input type="radio" id="star1" name="estrellas" value="1"><label for="star1">★</label>
                        </div>
                    </div>
                    <div class="input-group">
                        <textarea name="comentario" placeholder="¿Qué te pareció este producto u obra de arte?" class="textarea-comentario" required></textarea>
                    </div>
                    <button type="submit" name="btn_publicar_opinion" class="btn-publicar-comentario">Publicar Opinión</button>
                </form>
            </div>
        </div>

        <div class="lista-comentarios">
            <h3>Comentarios recientes</h3>

            <?php if(!empty($comentarios_principales)): ?>
                <?php foreach($comentarios_principales as $coment): ?>
                    <div class="tarjeta-comentario" id="comentario-<?php echo $coment['id']; ?>">
                        <div class="header-comentario">
                            <span class="autor-comentario">
                                <?php echo htmlspecialchars($coment['nombre_producto'] ?: $coment['nombre_completo']); ?> ✦
                            </span>
                            <span class="estrellas-comentario">
                                <?php for($s = 1; $s <= 5; $s++) { echo ($s <= $coment['estrellas']) ? "★" : "☆"; } ?>
                            </span>

                            <div class="opciones-comentario">
                                <button class="btn-tres-puntos" type="button" onclick="toggleMenuComentario(event, <?php echo $coment['id']; ?>)">⋮</button>
                                <div class="dropdown-menu-comentario" id="menu-coment-<?php echo $coment['id']; ?>">
                                    <button class="item-dropdown" type="button" onclick="abrirModalReporte('comentario', <?php echo $coment['id']; ?>)">
                                        🚩 Reportar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p class="texto-comentario"><?php echo nl2br(htmlspecialchars($coment['comentario'])); ?></p>
                        
                        <div class="acciones-comentario">
                            <button class="btn-accion-coment" type="button" onclick="reaccionarComentario(<?php echo $coment['id']; ?>, 'like')">
                                👍 <span id="like-count-<?php echo $coment['id']; ?>"><?php echo $coment['likes']; ?></span>
                            </button>
                            <button class="btn-accion-coment" type="button" onclick="reaccionarComentario(<?php echo $coment['id']; ?>, 'dislike')">
                                👎 <span id="dislike-count-<?php echo $coment['id']; ?>"><?php echo $coment['dislikes']; ?></span>
                            </button>
                            <button class="btn-accion-coment" type="button" onclick="toggleFormRespuesta(<?php echo $coment['id']; ?>)">
                                💬 Responder
                            </button>
                            <span class="fecha-comentario" style="margin-left:auto;"><?php echo date('d/m/Y - H:i', strtotime($coment['fecha'])); ?> hs</span>
                        </div>

                        <!-- FORMULARIO DE RESPUESTA -->
                        <div class="box-respuesta-form" id="form-respuesta-<?php echo $coment['id']; ?>">
                            <form action="detalleprod.php?id=<?php echo $prod['id']; ?>" method="POST">
                                <input type="hidden" name="parent_id" value="<?php echo $coment['id']; ?>">
                                <textarea name="comentario" placeholder="Escribe tu respuesta..." class="textarea-comentario" style="min-height: 70px;" required></textarea>
                                <div class="acciones-form-respuesta">
                                    <button type="submit" name="btn_publicar_opinion" class="btn-publicar-comentario" style="font-size:0.8rem; padding: 8px 16px;">Responder</button>
                                    <button type="button" class="btn-cancelar-respuesta" onclick="toggleFormRespuesta(<?php echo $coment['id']; ?>)">CANCELAR</button>
                                </div>
                            </form>
                        </div>

                        <!-- RESPUESTAS ANIDADAS -->
                        <?php if(isset($respuestas_map[$coment['id']])): ?>
                            <div class="lista-respuestas">
                                <?php foreach($respuestas_map[$coment['id']] as $resp): ?>
                                    <div class="tarjeta-respuesta" id="comentario-<?php echo $resp['id']; ?>">
                                        <div class="header-comentario">
                                            <span class="autor-comentario" style="font-size: 0.85rem;">
                                                <?php echo htmlspecialchars($resp['nombre_producto'] ?: $resp['nombre_completo']); ?> ✦
                                            </span>

                                            <div class="opciones-comentario">
                                                <button class="btn-tres-puntos" type="button" onclick="toggleMenuComentario(event, <?php echo $resp['id']; ?>)">⋮</button>
                                                <div class="dropdown-menu-comentario" id="menu-coment-<?php echo $resp['id']; ?>">
                                                    <button class="item-dropdown" type="button" onclick="abrirModalReporte('comentario', <?php echo $resp['id']; ?>)">
                                                        🚩 Reportar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <p class="texto-comentario" style="font-size: 0.9rem; margin-top: 4px;"><?php echo nl2br(htmlspecialchars($resp['comentario'])); ?></p>
                                        
                                        <div class="acciones-comentario" style="margin-top: 8px;">
                                            <button class="btn-accion-coment" type="button" onclick="reaccionarComentario(<?php echo $resp['id']; ?>, 'like')">
                                                👍 <span id="like-count-<?php echo $resp['id']; ?>"><?php echo $resp['likes']; ?></span>
                                            </button>
                                            <button class="btn-accion-coment" type="button" onclick="reaccionarComentario(<?php echo $resp['id']; ?>, 'dislike')">
                                                👎 <span id="dislike-count-<?php echo $resp['id']; ?>"><?php echo $resp['dislikes']; ?></span>
                                            </button>
                                            <span class="fecha-comentario" style="margin-left:auto; font-size: 0.75rem;"><?php echo date('d/m/Y - H:i', strtotime($resp['fecha'])); ?> hs</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #9a8c9e; font-style: italic; margin-top: 15px;">Aún no hay opiniones.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- MODAL REPORTE -->
    <div id="modal-reporte-unificado" class="modal-reporte-overlay" style="display: none;">
        <div class="modal-reporte-card">
            <h3 id="reporte-titulo">Reportar</h3>
            <p id="reporte-subtitulo" style="font-size: 0.85rem; color: #a1a1aa; margin-top: 4px;"></p>
            
            <form id="form-reporte-dinamico" onsubmit="enviarReporte(event)">
                <input type="hidden" id="rep-tipo" value="">
                <input type="hidden" id="rep-elemento-id" value="">

                <label style="font-size: 0.85rem; margin-top: 10px; display: block;">Motivo:</label>
                <select id="rep-motivo" required style="width: 100%; padding: 8px; margin-top: 4px; background: #1f1f23; color: #fff; border: 1px solid #3f3f46; border-radius: 6px;">
                    <option value="">Selecciona una opción...</option>
                    <option value="Contenido inapropiado o explícito">Contenido inapropiado o explícito</option>
                    <option value="Spam o fraude">Spam o fraude</option>
                    <option value="Lenguaje ofensivo o acoso">Lenguaje ofensivo o acoso</option>
                    <option value="Violación de derechos de autor">Violación de derechos de autor</option>
                    <option value="Otro">Otro motivo</option>
                </select>

                <label style="font-size: 0.85rem; display: block; margin-top: 10px;">Detalles (opcional):</label>
                <textarea id="rep-detalles" rows="3" placeholder="Describe el problema..." style="width: 100%; padding: 8px; margin-top: 4px; background: #1f1f23; color: #fff; border: 1px solid #3f3f46; border-radius: 6px;"></textarea>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 15px;">
                    <button type="button" class="btn-cancelar-respuesta" onclick="cerrarModalReporte()">Cancelar</button>
                    <button type="submit" class="btn-publicar-comentario" style="font-size:0.8rem;">Enviar Reporte</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL POIEIN (NOTIFICACIONES) -->
    <div id="poiein-modal" class="modal-poiein-overlay" style="display: none;">
        <div class="modal-poiein-card">
            <button class="modal-poiein-close" onclick="cerrarModalPoiein()">&times;</button>
            <div class="modal-poiein-icono" id="poiein-modal-icono">✨</div>
            <h3 class="modal-poiein-titulo" id="poiein-modal-titulo">Atención</h3>
            <p class="modal-poiein-mensaje" id="poiein-modal-mensaje">Mensaje...</p>
            <div class="modal-poiein-acciones" id="poiein-modal-acciones">
                <button class="btn-poiein-modal" onclick="cerrarModalPoiein()">Aceptar</button>
            </div>
        </div>
    </div>

    <script>
        const usuarioLogueado = <?php echo ($id_user_actual > 0) ? 'true' : 'false'; ?>;

        // --- FUNCIONALIDAD DE COMPRA Y CARRITO ---
        function agregarAlCarrito(productoId, redireccionar = false) {
            if (!usuarioLogueado) {
                mostrarModalPoiein("Sesión Requerida", "Debes iniciar sesión para agregar productos al carrito.", "🔒", true);
                return;
            }

            fetch('/poiein/carrito/agregar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ producto_id: productoId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const cartBadge = document.querySelector('.cart-badge, .badge-carrito');
                    if (cartBadge && data.cart_count !== undefined) {
                        cartBadge.innerText = data.cart_count;
                    }

                    if (redireccionar) {
                        window.location.href = '/poiein/carrito/carrito.php';
                    } else {
                        mostrarModalPoiein("¡Añadido!", data.message || "Pieza añadida a tu carrito.", "🛒");
                    }
                } else {
                    mostrarModalPoiein("Aviso", data.message || "No se pudo agregar el producto.", "⚠️");
                }
            })
            .catch(error => {
                console.error('Error al agregar al carrito:', error);
                mostrarModalPoiein("Error", "Ocurrió un problema de red al conectar con el servidor.", "❌");
            });
        }

        function comprarAhora(productoId) {
            if (!usuarioLogueado) {
                mostrarModalPoiein("Sesión Requerida", "Debes iniciar sesión para comprar.", "🔒", true);
                return;
            }

            // Agregamos al carrito primero y redirigimos directo al checkout local
            fetch('/poiein/carrito/agregar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ producto_id: productoId, cantidad: 1 })
            })
            .then(res => res.json())
            .then(data => {
                // Redirige de inmediato al apartado de pago que creamos (checkout.php)
                window.location.href = '/poiein/checkout.php';
            })
            .catch(err => {
                console.error("Error en comprarAhora:", err);
                window.location.href = '/poiein/checkout.php';
            });
        }

        function toggleDeseos(productoId) {
            if (!usuarioLogueado) {
                mostrarModalPoiein("Sesión Requerida", "Debes iniciar sesión para tu lista de deseos.", "🔒", true);
                return;
            }

            const btn = document.getElementById('btn-deseos-prod');
            const txtBtn = document.getElementById('txt-btn-deseos');
            const esFavorito = btn.classList.contains('en-favoritos');

            const url = esFavorito ? '/poiein/deseos/eliminar.php' : '/poiein/deseos/agregar.php';

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ producto_id: productoId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    if (esFavorito) {
                        btn.classList.remove('en-favoritos');
                        if (txtBtn) txtBtn.innerText = 'Añadir a Lista de Deseos';
                        mostrarModalPoiein("Removido", data.message || "Quitado de tu lista de deseos.", "💔");
                    } else {
                        btn.classList.add('en-favoritos');
                        if (txtBtn) txtBtn.innerText = 'En Lista de Deseos';
                        mostrarModalPoiein("Guardado", data.message || "Añadido a tu lista de deseos.", "❤️");
                    }
                } else {
                    mostrarModalPoiein("Aviso", data.message || "No se pudo actualizar tu lista de deseos.", "⚠️");
                }
            })
            .catch(error => {
                console.error('Error al actualizar deseos:', error);
                mostrarModalPoiein("Error", "Ocurrió un problema de red al conectar con el servidor.", "❌");
            });
        }

        <?php if ($mensaje_resena === "login_requerido"): ?>
            document.addEventListener('DOMContentLoaded', function() {
                mostrarModalPoiein("Sesión Requerida", "Debes iniciar sesión para calificar o responder.", "🔒", true);
            });
        <?php endif; ?>

        function toggleMenuComentario(e, comentId) {
            e.stopPropagation();
            document.querySelectorAll('.dropdown-menu-comentario').forEach(m => {
                if (m.id !== 'menu-coment-' + comentId) m.classList.remove('show');
            });
            const menu = document.getElementById('menu-coment-' + comentId);
            if (menu) menu.classList.toggle('show');
        }

        document.addEventListener('click', function() {
            document.querySelectorAll('.dropdown-menu-comentario').forEach(m => m.classList.remove('show'));
        });

        function toggleFormRespuesta(comentId) {
            if (!usuarioLogueado) {
                mostrarModalPoiein("Sesión Requerida", "Inicia sesión para responder.", "🔒", true);
                return;
            }
            const form = document.getElementById('form-respuesta-' + comentId);
            if (form) form.classList.toggle('show');
        }

        function reaccionarComentario(comentId, tipo) {
            if (!usuarioLogueado) {
                mostrarModalPoiein("Sesión Requerida", "Debes iniciar sesión para reaccionar.", "🔒", true);
                return;
            }

            fetch('reacc_coment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comentario_id: comentId, tipo: tipo })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('like-count-' + comentId).innerText = data.likes;
                    document.getElementById('dislike-count-' + comentId).innerText = data.dislikes;
                } else {
                    mostrarModalPoiein("Aviso", data.message, "⚠️");
                }
            })
            .catch(err => {
                console.error("Error al reaccionar:", err);
            });
        }

        function abrirModalReporte(tipo, elementoId) {
            if (!usuarioLogueado) {
                mostrarModalPoiein("Sesión Requerida", "Debes iniciar sesión para reportar.", "🔒", true);
                return;
            }
            document.getElementById('rep-tipo').value = tipo;
            document.getElementById('rep-elemento-id').value = elementoId;
            document.getElementById('rep-motivo').value = "";
            document.getElementById('rep-detalles').value = "";
            document.getElementById('modal-reporte-unificado').style.display = 'flex';
        }

        function cerrarModalReporte() { 
            document.getElementById('modal-reporte-unificado').style.display = 'none'; 
        }

        function enviarReporte(e) {
            e.preventDefault();
            
            const datos = {
                tipo: document.getElementById('rep-tipo').value,
                elemento_id: document.getElementById('rep-elemento-id').value,
                motivo: document.getElementById('rep-motivo').value,
                detalles: document.getElementById('rep-detalles').value
            };

            fetch('/poiein/detalle/procesar_reporte.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            })
            .then(res => res.json())
            .then(data => {
                cerrarModalReporte();
                mostrarModalPoiein(
                    data.status === 'success' ? "Reporte Enviado" : "Aviso", 
                    data.message || data.mensaje, 
                    data.status === 'success' ? "🚩" : "⚠️"
                );
            })
            .catch(err => {
                console.error("Error al enviar reporte:", err);
                cerrarModalReporte();
                mostrarModalPoiein("Error", "Ocurrió un fallo en la conexión con el servidor.", "❌");
            });
        }

        function mostrarModalPoiein(titulo, mensaje, icono = '✨', esLogin = false) {
            document.getElementById('poiein-modal-titulo').innerText = titulo;
            document.getElementById('poiein-modal-mensaje').innerText = mensaje;
            document.getElementById('poiein-modal-icono').innerText = icono;
            const acciones = document.getElementById('poiein-modal-acciones');

            if (esLogin) {
                acciones.innerHTML = `
                    <button class="btn-cancelar-respuesta" onclick="cerrarModalPoiein()">Cancelar</button>
                    <button type="button" class="btn-publicar-comentario" onclick="cerrarModalPoiein(); const m = document.querySelector('.modal-overlay'); if(m) m.classList.add('active');">Iniciar Sesión</button>
                `;
            } else {
                acciones.innerHTML = `<button class="btn-publicar-comentario" onclick="cerrarModalPoiein()">Aceptar</button>`;
            }
            document.getElementById('poiein-modal').style.display = 'flex';
        }

        function cerrarModalPoiein() { 
            document.getElementById('poiein-modal').style.display = 'none'; 
        }
    </script>
</body>
</html>