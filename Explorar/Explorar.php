<?php
session_start();

// Intentamos incluir la conexión de forma segura
if (file_exists("../conexión.php")) {
    include("../conexión.php");
} elseif (file_exists("../conexion.php")) {
    include("../conexion.php");
} else {
    @include(__DIR__ . "/../conexión.php");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poiein_Explorar</title>
    
    <link rel="stylesheet" href="../NAV/nav.css">
    <link rel="stylesheet" href="Explorar.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include '../NAV/nav.php'; ?>

    <main>
        <!-- HERO SECTION -->
        <div class="hero-section" style="text-align: center;">
            <h1>Explora creaciones únicas</h1>
            <p>Encuentra piezas hechas a mano con alma y propósito</p> 
        </div>

        <!-- BARRA DE BÚSQUEDA -->
        <!-- BARRA DE BÚSQUEDA CON DESPLEGABLE DINÁMICO -->
<div class="search-container">
    <div class="search-bar">
        <input type="text" id="input-busqueda" placeholder="¿Qué estás buscando explorar?" autocomplete="off">
        <button class="btn-search" id="btn-buscar">BUSCAR</button>
    </div>
    
    <!-- Contenedor desplegable flotante de resultados -->
    <div id="resultados-busqueda" class="search-results-dropdown" style="display: none;"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('input-busqueda');
    const dropdown = document.getElementById('resultados-busqueda');

    let timeoutId = null;

    input.addEventListener('input', () => {
        clearTimeout(timeoutId);
        const query = input.value.trim();

        if (query.length < 2) {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
            return;
        }

        // Retraso de 300ms para no saturar el servidor mientras escribe
        timeoutId = setTimeout(() => {
            fetch(`buscar.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    let html = '';

                    // Sección Creadores
                    if (data.creadores.length > 0) {
                        html += `<div class="search-section-title">✧ CREADORES</div>`;
                        data.creadores.forEach(c => {
                            html += `
                                <a href="/poiein/perfil/perfil_creador.php?id=${c.id}" class="search-item">
                                    <div class="search-img-box">
                                        ${c.foto ? `<img src="${c.foto}">` : '<div class="no-img"></div>'}
                                    </div>
                                    <div class="search-info">
                                        <strong>${c.nombre}</strong>
                                        <span>${c.disciplina}</span>
                                    </div>
                                </a>
                            `;
                        });
                    }

                    // Sección Productos
                    if (data.productos.length > 0) {
                        html += `<div class="search-section-title">✦ OBRAS Y PIEZAS</div>`;
                        data.productos.forEach(p => {
                            html += `
                                <a href="/poiein/detalle/detalleprod.php?id=${p.id}" class="search-item">
                                    <div class="search-img-box">
                                        ${p.imagen ? `<img src="${p.imagen}">` : '<div class="no-img"></div>'}
                                    </div>
                                    <div class="search-info">
                                        <strong>${p.nombre}</strong>
                                        <span class="precio-item">$${p.precio}</span>
                                    </div>
                                </a>
                            `;
                        });
                    }

                    if (data.creadores.length === 0 && data.productos.length === 0) {
                        html = `<div class="search-no-results">No se encontraron coincidencias para "${query}"</div>`;
                    }

                    dropdown.innerHTML = html;
                    dropdown.style.display = 'block';
                })
                .catch(err => console.error("Error en búsqueda:", err));
        }, 300);
    });

    // Ocultar desplegable si da clic fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-container')) {
            dropdown.style.display = 'none';
        }
    });
});
</script>
        
        <!-- SECCIÓN CREADORES ESTILO ACORDEÓN COMPACTO -->
        <section class="creadores-destacados-container">
            <div class="creadores-header">
                <span class="badge-bienvenida">✧ MENTES CREATIVAS ✧</span>
                <h2>Creadores en la <span class="gold-text">Comunidad</span></h2>
            </div>

            <div class="creadores-accordion">
                <?php
                if (isset($conexion)) {
                    // Consulta con los nombres exactos de tus columnas
                    $query_creadores = "SELECT * FROM usuarios WHERE rol = 'creador' ORDER BY id DESC LIMIT 5";
                    $res_creadores = mysqli_query($conexion, $query_creadores);

                    // Si aún no hay usuarios con rol 'creador', muestra los últimos registrados
                    if (!$res_creadores || mysqli_num_rows($res_creadores) == 0) {
                        $res_creadores = mysqli_query($conexion, "SELECT * FROM usuarios ORDER BY id DESC LIMIT 5");
                    }

                    if ($res_creadores && mysqli_num_rows($res_creadores) > 0) {
                        while ($creador = mysqli_fetch_assoc($res_creadores)):
                            // Variables mapeadas EXACTAMENTE a tu tabla MySQL
                            $nombre_real = !empty($creador['nombre_completo']) ? $creador['nombre_completo'] : 'Artista Poiein';
                            $disciplina = !empty($creador['nombre_producto']) ? $creador['nombre_producto'] : 'Creador';
                            
                            // Foto de perfil
                            $foto_db = $creador['foto_perfil'] ?? '';
                            $foto = !empty($foto_db) ? "/poiein/" . str_replace('../', '', $foto_db) : "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=300";
                ?>
                    <!-- TARJETA ÚNICA CLICKEABLE -->
                    <a href="/poiein/perfil/perfil_creador.php?id=<?php echo $creador['id']; ?>" class="accordion-item-link">
                        <div class="accordion-item" style="background-image: url('<?php echo htmlspecialchars($foto); ?>');">
                            <div class="accordion-overlay">
                                <span class="creador-tag">✧ <?php echo htmlspecialchars($disciplina); ?></span>
                                <h3><?php echo htmlspecialchars($nombre_real); ?></h3>
                                <span class="btn-creador">Ver Perfil</span>
                            </div>
                        </div>
                    </a>
                <?php 
                        endwhile;
                    } else {
                        echo "<p class='no-results'>Aún no hay creadores registrados.</p>";
                    }
                }
                ?>
            </div>
        </section>

        <!-- SECCIÓN DE PRODUCTOS DESTACADOS -->
        <section class="destacados">
            <h2 style="text-align: center; color: #fff; margin-bottom: 20px;">Obras y Piezas</h2>
            <div class="productos-grid">
                <?php
                if (isset($conexion)) {
                    $resultado = mysqli_query($conexion, "SELECT * FROM productos ORDER BY id DESC");

                    if ($resultado && mysqli_num_rows($resultado) > 0) {
                        while($producto = mysqli_fetch_assoc($resultado)): 
                            $ruta_limpia = str_replace('../', '', $producto['imagen_producto'] ?? '');
                ?>
                    <article class="producto-card">
                        <img src="/poiein/<?php echo $ruta_limpia; ?>" alt="<?php echo htmlspecialchars($producto['nombre_item'] ?? 'Producto'); ?>">
                        <h3><?php echo htmlspecialchars($producto['nombre_item'] ?? 'Sin nombre'); ?></h3>
                        <span class="precio">$<?php echo number_format($producto['precio'] ?? 0, 2); ?></span>
                        <a href="/poiein/detalle/detalleprod.php?id=<?php echo $producto['id']; ?>">
                            <button class="btn-card">Ver Detalles</button>
                        </a>
                    </article>
                <?php 
                        endwhile; 
                    } else {
                        echo "<p class='no-results'>Aún no hay productos disponibles.</p>";
                    }
                }
                ?>
            </div>
        </section>
    </main>

</body>
</html>