<?php
include(__DIR__ . "/../conexión.php"); 
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /poiein/Login/login.php"); 
    exit();
}

$mi_id = $_SESSION['usuario_id'];
$query = "SELECT * FROM usuarios WHERE id = '$mi_id'";
$resultado = mysqli_query($conexion, $query);
$usuario = mysqli_fetch_assoc($resultado);

// Lógica de nombres profesional
if ($usuario['rol'] === 'creador') {
    $nombre_principal = !empty($usuario['nombre_producto']) ? $usuario['nombre_producto'] : $usuario['nombre_completo'];
} else {
    $nombre_principal = $usuario['nombre_completo'];
}

$saludo_bienvenida = "Hola, " . $usuario['nombre_completo'];

// Rutas de imágenes
$avatar = !empty($usuario['foto_perfil']) ? "/poiein/" . $usuario['foto_perfil'] . "?v=" . time() : "/poiein/perfil/default.png";
$portada = !empty($usuario['foto_portada']) 
    ? "/poiein/" . $usuario['foto_portada'] . "?v=" . time() 
    : "/poiein/perfil/portada/default_bg.jpg";

$acepta_custom = $usuario['acepta_personalizados'] ?? 0;

// Consulta para obtener los encargos recibidos si es creador
$encargos_resultado = null;
if ($usuario['rol'] === 'creador') {
    $query_encargos = "SELECT e.*, u.nombre_completo AS cliente_nombre 
                       FROM encargos_personalizados e 
                       JOIN usuarios u ON e.usuario_id = u.id 
                       WHERE e.creador_id = '$mi_id' 
                       ORDER BY e.fecha DESC";
    $encargos_resultado = mysqli_query($conexion, $query_encargos);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil | Poiein</title>
    <link rel="stylesheet" href="perfil.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../NAV/nav.css">
</head>
<body>

    <?php include '../NAV/nav.php'; ?>

    <div class="contenedor-perfil">
        <!-- PORTADA -->
        <header class="portada-seccion" style="background-image: url('<?php echo $portada; ?>');">
            <form action="Guardar/guardar_portada.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="foto_portada" id="portadaIn" class="input-oculto" onchange="this.form.submit()">
                <label for="portadaIn" class="btn-editar-portada">✎ Editar Portada</label>
            </form>
        </header>

        <div class="bloque-usuario">
            <!-- AVATAR / FOTO DE PERFIL -->
            <div class="avatar-area">
                <img src="<?php echo $avatar; ?>" class="img-avatar">
                <form action="Guardar/guardar_avatar.php" method="POST" enctype="multipart/form-data">
                    <input type="file" name="foto_perfil" id="avatarIn" class="input-oculto" onchange="this.form.submit()">
                    <label for="avatarIn" class="btn-editar-avatar">✎</label>
                </form>
            </div>

            <div class="info-detalles">
                <span class="saludo-personal"><?php echo $saludo_bienvenida; ?></span>
                <h1 class="marca-titulo"><?php echo htmlspecialchars($nombre_principal); ?> ✦</h1>
                
                <div class="redes-lista">
                    <?php if(!empty($usuario['link_spotify'])): ?>
                        <span class="pildora spot">Spotify</span>
                    <?php endif; ?>
                    <?php if(!empty($usuario['link_instagram'])): ?>
                        <span class="pildora insta">Instagram</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <section class="paneles-informativos">

            <!-- PANEL DE ENCARGOS / DISEÑOS PERSONALIZADOS -->
            <div class="tarjeta-profesional">
                <h3 class="titulo-dorado">✨ Encargos Especiales</h3>
                <p class="txt-subtitulo">
                    Permite que los clientes te soliciten obras y diseños únicos a medida desde tu perfil visible.
                </p>
                <form action="Guardar/guardar_personalizados.php" method="POST">
                    <div class="switch-container">
                        <span class="txt-estado-custom">
                            <?php echo ($acepta_custom == 1) ? '🟢 Diseños Activos' : '🔴 Diseños Inactivos'; ?>
                        </span>
                        <label class="switch">
                            <input type="checkbox" name="acepta_personalizados" value="1" <?php echo ($acepta_custom == 1) ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span class="slider"></span>
                        </label>
                    </div>
                </form>

                <?php if ($usuario['rol'] === 'creador'): ?>
                    <h3 class="espacio-top" style="color: #d4af37; margin-top: 25px;">Solicitudes Recibidas</h3>
                    <div class="grid-encargos">
                        <?php if ($encargos_resultado && mysqli_num_rows($encargos_resultado) > 0): ?>
                            <?php while($encargo = mysqli_fetch_assoc($encargos_resultado)): ?>
                                <div class="card-encargo">
                                    <img src="/poiein/<?php echo htmlspecialchars($encargo['imagen_personalizada']); ?>" alt="Diseño cliente">
                                    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($encargo['cliente_nombre']); ?></p>
                                    <p><strong>Fecha:</strong> <?php echo $encargo['fecha']; ?></p>
                                    <p style="margin-bottom: 10px;">
                                        <strong>Estado:</strong> 
                                        <span style="text-transform: uppercase; color: 
                                            <?php 
                                                if($encargo['estado'] == 'aceptado') echo '#2ecc71';
                                                elseif($encargo['estado'] == 'rechazado') echo '#e74c3c';
                                                else echo '#d4af37'; 
                                            ?>;">
                                            <?php echo $encargo['estado']; ?>
                                        </span>
                                    </p>

                                    <!-- Botones de Acción (Aceptar / Rechazar) -->
                                    <?php if ($encargo['estado'] === 'pendiente'): ?>
                                        <div style="display: flex; gap: 8px;">
                                            <form action="/poiein/personalizado/actualizar_estado.php" method="POST" style="flex: 1;">
                                                <input type="hidden" name="encargo_id" value="<?php echo $encargo['id']; ?>">
                                                <input type="hidden" name="accion" value="aceptado">
                                                <button type="submit" style="width: 100%; background: #2ecc71; color: #000; border: none; padding: 6px; border-radius: 4px; font-weight: bold; cursor: pointer;">Aceptar</button>
                                            </form>
                                            
                                            <form action="/poiein/personalizado/actualizar_estado.php" method="POST" style="flex: 1;">
                                                <input type="hidden" name="encargo_id" value="<?php echo $encargo['id']; ?>">
                                                <input type="hidden" name="accion" value="rechazado">
                                                <button type="submit" style="width: 100%; background: #e74c3c; color: #fff; border: none; padding: 6px; border-radius: 4px; font-weight: bold; cursor: pointer;">Rechazar</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color: #666; font-size: 0.85rem; grid-column: 1/-1;">Aún no tienes encargos personalizados pendientes.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <h3 class="espacio-top" style="margin-top: 25px;">Configuración y Privacidad</h3>
                <ul>
                    <li>Editar datos de cuenta</li>
                    <li>Seguridad y acceso</li>
                    <li>Preferencias de notificaciones</li>
                </ul>
            </div>

            <div class="tarjeta-profesional">
                <h3>Soporte Técnico</h3>
                <ul>
                    <li>Centro de ayuda Poiein</li>
                    <li>Reportar un problema</li>
                    <li>Términos y condiciones</li>
                    <li class="txt-rojo"><a href="/poiein/logout.php" class="link-logout">Cerrar Sesión</a></li>
                </ul>
                
                <h3>Redes Sociales</h3>
                <form action="Guardar/guardar_enlaces.php" method="POST">
                    <div class="input-red-box">
                        <label class="lbl-spotify">Enlace de Spotify</label>
                        <input type="url" name="link_spotify" 
                               value="<?php echo htmlspecialchars($usuario['link_spotify'] ?? ''); ?>" 
                               placeholder="https://open.spotify.com/..." 
                               class="input-red">
                    </div>

                    <div class="input-red-box">
                        <label class="lbl-instagram">Enlace de Instagram</label>
                        <input type="url" name="link_instagram" 
                               value="<?php echo htmlspecialchars($usuario['link_instagram'] ?? ''); ?>" 
                               placeholder="https://instagram.com/..." 
                               class="input-red">
                    </div>

                    <button type="submit" class="btn-actualizar-redes">
                        ACTUALIZAR REDES
                    </button>
                </form>   
            </div>
        </section>
    </div>

</body>
</html>