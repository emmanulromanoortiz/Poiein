<?php
session_start(); 
// Incluimos la conexión al inicio de la página
@include("conexión.php"); 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poiein</title>
    
    <link rel="stylesheet" href="poiein/NAV/nav.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include 'NAV/nav.php'; ?>
    
    <!-- BIENVENIDA -->
    <?php if (!isset($_SESSION['nombre'])): ?>
        <div id="intro-overlay" class="intro-overlay">
            <div class="intro-content">
                <h1 class="intro-logo">POIEIN</h1>
                <p class="intro-subtext">Del griego <i>Poíēsis</i>: Crear, hacer emerger el arte.</p>
                <div class="intro-line"></div>
            </div>
        </div>

        <!-- GALERÍA EXPANDIBLE -->
        <?php
        $resultado_hero = false;
        if (isset($conexion)) {
            $query_hero = "SELECT id, nombre_item, descripcion, imagen_producto, categoria FROM productos ORDER BY id DESC LIMIT 5";
            $resultado_hero = mysqli_query($conexion, $query_hero);
        }
        ?>

        <section class="gallery-hero-container">
            <div class="gallery-title-wrapper">
                <span class="badge-bienvenida">✧ PIEZAS DESTACADAS ✧</span>
                <h2>Donde la Creación <span class="gold-text">Trasciende</span></h2>
                <p class="hero-subtitle">Descubre las últimas obras agregadas por nuestros creadores.</p>
            </div>

            <div class="accordion-gallery">
                <?php 
                if ($resultado_hero && mysqli_num_rows($resultado_hero) > 0): 
                    $i = 0;
                    while ($producto = mysqli_fetch_assoc($resultado_hero)): 
                        $activeClass = ($i === 0) ? 'active' : '';
                        
                        $ruta = $producto['imagen_producto'] ?? '';
                        $ruta_limpia = str_replace('../', '', $ruta);
                        $imagenUrl = !empty($ruta_limpia) ? "/poiein/" . $ruta_limpia : "img/default.jpg";
                        
                        $i++;
                ?>
                    <div class="gallery-card <?php echo $activeClass; ?>" style="--bg-img: url('<?php echo htmlspecialchars($imagenUrl); ?>');">
                        <div class="card-overlay"></div>
                        <div class="card-content">
                            <span class="card-tag">✧ <?php echo htmlspecialchars($producto['categoria'] ?? 'Obra Auténtica'); ?></span>
                            <h3><?php echo htmlspecialchars($producto['nombre_item']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($producto['descripcion'] ?? '', 0, 70)) . '...'; ?></p>
                            <a href="detalle/detalleprod.php?id=<?php echo $producto['id']; ?>" class="btn-card">Ver Pieza</a>
                        </div>
                    </div>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <div class="no-products-msg">
                        <p style="color: #d4af37; text-align: center; width: 100%;">Aún no hay piezas destacadas.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
    
    <!--  COMUNIDAD  -->
    <div class="banner-comunidad" id="bannerComunidad">
        <!-- CERRAR  -->
        <button type="button" class="btn-cerrar-banner" onclick="cerrarBanner()" aria-label="Cerrar banner">
            &times;
        </button>

        <div class="banner-contenido">
            <span class="tag-comunidad">✦ COMUNIDAD POIEIN ✦</span>
            <h2 class="banner-titulo">Conecta con el espíritu de otros <span>Creadores</span></h2>
            <p class="banner-descripcion">
                Comparte tus procesos de creación, descubre historias de arte auténtico y forma parte de un espacio exclusivo hecho por y para artistas.
            </p>
            <a href="/poiein/Comunidad/comunidad.php" class="btn-unirme" onclick="irAComunidad()">Unirme a la Comunidad →</a>
        </div>

        <div class="banner-icono">
            🏛️
        </div>
    </div>

    <!-- PRINCIPAL -->
    <main>
        <div class="hero-section">
            <h1>Descubre productos artesanales únicos</h1>
            <p>Una plataforma para creadores</p> 
        </div>

       <!-- BARRA DE BÚSQUEDA CON DESPLEGABLE EN INDEX -->
<div class="search-container">
    <div class="search-bar">
        <input type="text" id="input-busqueda-index" placeholder="¿Qué estás buscando explorar?" autocomplete="off">
        <button class="btn-search" id="btn-buscar-index">BUSCAR</button>
    </div>
    
    <!-- Contenedor  -->
    <div id="resultados-busqueda-index" class="search-results-dropdown" style="display: none;"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('input-busqueda-index');
    const dropdown = document.getElementById('resultados-busqueda-index');

    let timeoutId = null;

    input.addEventListener('input', () => {
        clearTimeout(timeoutId);
        const query = input.value.trim();

        if (query.length < 2) {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
            return;
        }

        
        timeoutId = setTimeout(() => {
            fetch(`/poiein/Explorar/buscar.php?q=${encodeURIComponent(query)}`)
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

    // clic fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-container')) {
            dropdown.style.display = 'none';
        }
    });
});
</script>

        <section class="destacados">
            <h2 style="text-align: center; color: #fff; margin-bottom: 20px;">Destacados</h2>
            <div class="productos-grid">
                <?php
                if (isset($conexion)) {
                    $resultado = mysqli_query($conexion, "SELECT * FROM productos ORDER BY fecha_subida DESC");
                    
                    if ($resultado && mysqli_num_rows($resultado) > 0) {
                        while($producto = mysqli_fetch_assoc($resultado)): 
                            $ruta = $producto['imagen_producto'];
                            $ruta_limpia = str_replace('../', '', $ruta);
                ?>
                    <article class="producto-card">
                        <div class="image-container">
                            <img src="/poiein/<?php echo $ruta_limpia; ?>" alt="Imagen de <?php echo htmlspecialchars($producto['nombre_item']); ?>">
                        </div>
                        <h3><?php echo htmlspecialchars($producto['nombre_item']); ?></h3>
                        <span class="precio">$<?php echo number_format($producto['precio'], 2); ?></span>
                        <a href="detalle/detalleprod.php?id=<?php echo $producto['id']; ?>" class="btn-card">Ver Detalles</a>
                    </article>
                <?php 
                        endwhile; 
                    } else {
                        echo "<p class='no-results'>Aún no hay legados publicados. ¡Sé el primero!</p>";
                    }
                }
                ?>
            </div>
        </section>
    </main>

    <script>
        window.addEventListener('storage', function(event) {
            if (event.key === 'logout_event') {
                window.location.href = "index.php";
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            // Animación Splash
            const introOverlay = document.getElementById('intro-overlay');
            setTimeout(() => {
                if(introOverlay) introOverlay.classList.add('hidden');
            }, 1800);

            // Fun Acordeón
            const cards = document.querySelectorAll('.gallery-card');
            cards.forEach(card => {
                card.addEventListener('click', (e) => {
                    if (e.target.classList.contains('btn-card')) return;
                    cards.forEach(c => c.classList.remove('active'));
                    card.classList.add('active');
                });
            });

            //  banner fue cerrado o interactuado durante ESTA sesión
            const banner = document.getElementById('bannerComunidad');
            if (sessionStorage.getItem('poiein_banner_visto') === 'true' && banner) {
                banner.style.display = 'none';
            }
        });

        //  cerrar banner manualmente con la X
        function cerrarBanner() {
            const banner = document.getElementById('bannerComunidad');
            if (banner) {
                banner.classList.add('banner-oculto');
                
                // Guardamos en la memoria de la sesión
                sessionStorage.setItem('poiein_banner_visto', 'true');
                
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 400);
            }
        }

        function irAComunidad() {
            sessionStorage.setItem('poiein_banner_visto', 'true');
        }
    </script>
</body>
</html>