<?php
ini_set('display_errors', 0);
session_start();

// 1. Conexión a la base de datos
$conexion = null;
$rutas_conexion = [
    __DIR__ . "/../conexión.php",
    __DIR__ . "/../conexion.php",
    $_SERVER['DOCUMENT_ROOT'] . "/poiein/conexión.php",
    $_SERVER['DOCUMENT_ROOT'] . "/poiein/conexion.php"
];

foreach ($rutas_conexion as $ruta) {
    if (file_exists($ruta)) {
        include_once($ruta);
        break;
    }
}

if (!isset($conexion) || !$conexion) {
    die("<p style='color:#fff; text-align:center; padding:50px;'>Error al conectar con la base de datos.</p>");
}

// 2. Obtener y validar el ID
$id_creador = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_creador <= 0) {
    die("<p style='color:#fff; text-align:center; padding:50px;'>No se especificó un ID de creador válido.</p>");
}

$creador = null;
$productos_creador = [];

// 3. Consulta del Creador
$query_user = "SELECT * FROM usuarios WHERE id = '$id_creador' LIMIT 1";
$res_user = mysqli_query($conexion, $query_user);

if ($res_user && mysqli_num_rows($res_user) > 0) {
    $creador = mysqli_fetch_assoc($res_user);
} else {
    die("<p style='color:#fff; text-align:center; padding:50px;'>El creador no existe en la base de datos.</p>");
}

// 4. Consulta de Productos del Creador
$query_prod = "SELECT * FROM productos WHERE usuario_id = '$id_creador' ORDER BY id DESC";
$res_prod = mysqli_query($conexion, $query_prod);

if ($res_prod && mysqli_num_rows($res_prod) > 0) {
    while ($row = mysqli_fetch_assoc($res_prod)) {
        $productos_creador[] = $row;
    }
}

// 5. Configurar nombres e imágenes
$nombre_tienda = !empty($creador['nombre_producto']) ? $creador['nombre_producto'] : (!empty($creador['nombre_completo']) ? $creador['nombre_completo'] : 'Creador');
$nombre_usuario = !empty($creador['nombre_completo']) ? $creador['nombre_completo'] : $nombre_tienda;

// Rutas limpias
$foto_perfil = !empty($creador['foto_perfil']) ? '/poiein/' . str_replace('../', '', $creador['foto_perfil']) : '/poiein/perfiles/perfil/default.png';
$foto_portada = !empty($creador['foto_portada']) ? '/poiein/' . str_replace('../', '', $creador['foto_portada']) : ''; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($nombre_tienda); ?> | Poiein</title>
    
    <!-- Estilos generales y barra de navegación -->
    <link rel="stylesheet" href="../NAV/nav.css">
    <link rel="stylesheet" href="../style.css">
    
    <!-- Tu archivo CSS en la misma carpeta -->
    <link rel="stylesheet" href="perfil_creador.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php 
    if (file_exists(__DIR__ . '/../NAV/nav.php')) {
        include __DIR__ . '/../NAV/nav.php'; 
    }
    ?>

    <main class="perfil-publico-wrapper">
        
        <!-- BANNER PORTADA -->
        <div class="banner-header" <?php echo !empty($foto_portada) ? "style=\"background-image: url('".htmlspecialchars($foto_portada)."');\"" : ""; ?>></div>

        <!-- INFO DEL CREADOR -->
        <div class="creador-info-card">
            <div class="avatar-box">
                <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="<?php echo htmlspecialchars($nombre_tienda); ?>">
            </div>

            <div class="creador-detalles">
                <span class="saludo-tag">Hola, <?php echo htmlspecialchars($nombre_usuario); ?></span>
                <h1 class="nombre-tienda-titulo"><?php echo htmlspecialchars($nombre_tienda); ?> <span>✦</span></h1>
                <?php if(!empty($creador['biografia'])): ?>
                    <p class="bio-texto"><?php echo htmlspecialchars($creador['biografia']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- BANNER DE ENCARGOS / DISEÑO PERSONALIZADO (SI ESTÁ ACTIVO) -->
        <?php if (!empty($creador['acepta_personalizados']) && $creador['acepta_personalizados'] == 1): ?>
            <div class="banner-personalizado">
                <div class="banner-content">
                    <span class="badge-sparkle">✦ ENCARGOS ABIERTOS</span>
                    <h2>¿Buscas algo especial?</h2>
                    <p>Prueba la opción de <strong>diseño personalizado</strong> de <span class="destaque-artista"><?php echo htmlspecialchars($nombre_tienda); ?></span>.</p>
                </div>
               <a href="/poiein/personalizado/personalizado.php?artista_id=<?php echo $creador['id']; ?>" class="btn-solicitar">
    Solicitar Diseño
</a>
            </div>
        <?php endif; ?>

        <!-- OBRAS / PRODUCTOS -->
        <section class="seccion-obras-header">
            <h2>Colección & Obras</h2>

            <?php if (!empty($productos_creador)): ?>
                <div class="grid-productos">
                    <?php foreach ($productos_creador as $producto): ?>
                        <?php $ruta_limpia = str_replace('../', '', $producto['imagen_producto']); ?>
                        <a href="../detalle/detalleprod.php?id=<?php echo $producto['id']; ?>" class="card-producto">
                            <div class="card-img-box">
                                <img src="/poiein/<?php echo $ruta_limpia; ?>" alt="<?php echo htmlspecialchars($producto['nombre_item']); ?>">
                            </div>
                            <h3><?php echo htmlspecialchars($producto['nombre_item']); ?></h3>
                            <span class="precio-tag">$<?php echo number_format($producto['precio'], 2); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="sin-publicaciones">Este creador aún no tiene publicaciones disponibles.</p>
            <?php endif; ?>
        </section>

    </main>

</body>
</html>