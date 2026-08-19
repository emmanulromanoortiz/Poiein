<header>
    <div class="logo">
        <a href="/poiein/index.php">POIEIN</a>
    </div>
    <link rel="stylesheet" href="/poiein/NAV/nav.css?v=2.2">
    <nav>
        <a href="/poiein/index.php">Inicio</a>
        <a href="/poiein/Explorar/Explorar.php">Explorar</a>
        <a href="/poiein/mis_prod/index.php">Mis Productos</a>
        <a href="/poiein/Comunidad/comunidad.php">Comunidad</a>

        <?php if(isset($_SESSION['nombre'])): 
            $u_id = $_SESSION['usuario_id'];
            $rol_actual = trim(strtolower($_SESSION['rol'] ?? ''));
            
            // 1. OBTENEMOS CONTADORES DE CARRITO Y FAVORITOS DE LA BD
            if (isset($conexion)) {
                mysqli_query($conexion, "DELETE FROM carrito WHERE fecha_agregado < NOW() - INTERVAL 30 DAY");
                
                $res_cart = mysqli_query($conexion, "SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id = '$u_id'");
                $cant_carrito = mysqli_fetch_assoc($res_cart)['total'] ?? 0;

                $res_fav = mysqli_query($conexion, "SELECT COUNT(*) as total FROM favoritos WHERE usuario_id = '$u_id'");
                $cant_favoritos = mysqli_fetch_assoc($res_fav)['total'] ?? 0;
            } else {
                $cant_carrito = 0;
                $cant_favoritos = 0;
            }
        ?>

            <!-- BOTÓN FAVORITOS (Corazón) -->
            <a href="/poiein/deseos/favoritos.php" class="nav-icon-link" title="Mis Favoritos">
               lista de deseos ♥
                <?php if($cant_favoritos > 0): ?>
                    <span class="badge-count"><?php echo $cant_favoritos; ?></span>
                <?php endif; ?>
            </a>

            <!-- BOTÓN CARRITO (🛒) -->
            <a href="/poiein/carrito/carrito.php" class="nav-icon-link cart-link" title="Mi Carrito">
                Carrito 🛒
                <?php if($cant_carrito > 0): ?>
                    <span class="badge-count badge-cart"><?php echo $cant_carrito; ?></span>
                <?php endif; ?>
            </a>

            <!-- CONDICIONAL DE ROLES -->
            <?php if ($rol_actual === 'admin'): ?>
                <a href="/poiein/admin/dashboard.php" class="btn-nav-admin">PANEL ADMIN ✧</a>
            <?php elseif ($rol_actual === 'creador'): ?>
                <a href="/poiein/Subir/subir_prod.php" class="btn-nav-creador" target="_blank">SUBIR PRODUCTO</a>
            <?php else: ?>
                <a href="/poiein/ofertas.php" class="btn btn-consumidor">OFERTAS</a>
            <?php endif; ?>

            <!-- MENÚ DEL AVATAR -->
            <div class="user-menu-container"> 
                <div class="user-avatar" id="avatarClick">
                    <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
                </div>
                
                <div class="dropdown-menu" id="dropdownMenu">
                    <div class="dropdown-header">Hola, <strong><?php echo explode(' ', $_SESSION['nombre'])[0]; ?></strong></div>
                    
                    <?php if ($rol_actual === 'admin'): ?>
                        <li><a href="/poiein/admin/dashboard.php" class="admin-link">Panel de Control</a></li>
                    <?php endif; ?>

                    <li><a href="/poiein/deseos/favoritos.php">♥ Mis Favoritos</a></li>
                    <li><a href="/poiein/carrito/carrito.php">🛒 Mi Carrito</a></li>
                    <li><a href="/poiein/perfil/perfil.php">Mi Perfil</a></li>
                    
                    <?php if ($rol_actual === 'creador'): ?>
                        <li><a href="/poiein/mis_prod/index.php">Mis Productos</a></li>
                    <?php endif; ?>

                    <a href="/poiein/logout.php" class="logout-link">Cerrar Sesión</a>
                </div>
            </div>

        <?php else: ?>
            <a href="#" class="login-link">Iniciar Sesión</a>
        <?php endif; ?>
    </nav>
</header>

<!-- MODAL DE INICIO DE SESIÓN -->
<div id="modalLogin" class="modal-overlay"> 
    <div class="modal-content">
        <button class="close-btn">&times;</button>
        <h2>Bienvenido de vuelta</h2>
        
        <form action="/poiein/login.php" method="POST" autocomplete="off">
            <div class="input-group">
                <label>Correo Electrónico</label>
                <input type="email" name="email" placeholder="tu@ejemplo.com" required autocomplete="off">
            </div>
            
            <div class="input-group">
                <label>Contraseña</label>
                <div class="password-wrapper">
                    <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="toggle-eye-btn" aria-label="Mostrar u Ocultar Contraseña">
                        <svg class="eye-svg" viewBox="0 0 100 60" width="26" height="18">
                            <path class="eye-shape" d="M0,30 Q50,-15 100,30 Q50,75 0,30 Z" fill="none" stroke="#d4af37" stroke-width="6" />
                            <circle class="eye-pupil" cx="50" cy="30" r="10" fill="#d4af37" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login-submit">Entrar</button>
            <div class="modal-footer">
                <span>¿No tienes cuenta? <a href="#" id="linkIrARegistro">Regístrate aquí</a></span>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE REGISTRO -->
<div id="modalRegistro" class="modal-overlay">
    <div class="modal-content">
        <button class="close-btn">&times;</button>
        <h2>Crea tu Legado</h2>
        <form action="/poiein/registro.php" method="POST" autocomplete="off">
            <div class="input-group">
                <label>Tipo de Perfil</label>
                <select name="rol" id="rolSelect" required class="modal-select">
                    <option value="consumidor">Consumidor (Explorar y comprar)</option>
                    <option value="creador">Creador (Mostrar mis productos)</option>
                </select>
            </div>

            <div class="input-group">
                <label>Región / Nodo</label>
                <select name="region" required class="modal-select">
                    <option value="CENTRO">Nodo Centro</option>
                    <option value="NORTE">Nodo Norte</option>
                    <option value="SUR">Nodo Sur</option>
                </select>
            </div>

            <div class="input-group">
                <label>Nombre Completo</label>
                <input type="text" name="nombre_completo" placeholder="Tu nombre" required autocomplete="off">
            </div>
            
            <div class="input-group">
                <label>Correo Electrónico</label>
                <input type="email" name="email" placeholder="ejemplo: correo@dominio.com" required autocomplete="off">
            </div>

            <!-- CAMPOS EXCLUSIVOS DE CREADOR -->
            <div id="camposCreador" style="display: none;">
                <div class="input-group">
                    <label>Nombre de Marca o Producto</label>
                    <input type="text" name="nombre_producto" id="inputProducto" placeholder="Ej: Arte Inmortal">
                </div>
                <div class="input-group">
                    <label>Biografía</label>
                    <textarea name="biografia" id="inputBio" placeholder="Tu historia..." class="modal-textarea"></textarea>
                </div>
            </div>

            <div class="input-group">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="Mínimo 8 caracteres" required autocomplete="new-password">
            </div>
            
            <button type="submit" class="btn-login-submit">Registrarme</button>
            <div class="modal-footer">
                <span>¿Ya tienes cuenta? <a href="#" id="linkIrALogin">Inicia sesión aquí</a></span>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalLogin = document.getElementById('modalLogin');
    const modalRegistro = document.getElementById('modalRegistro');
    
    const toggleModal = (modal, action) => {
        action === 'open' ? modal.classList.add('active') : modal.classList.remove('active');
    };

    document.querySelector('.login-link')?.addEventListener('click', (e) => {
        e.preventDefault();
        toggleModal(modalLogin, 'open');
    });

    document.getElementById('linkIrARegistro').onclick = (e) => {
        e.preventDefault();
        toggleModal(modalLogin, 'close');
        toggleModal(modalRegistro, 'open');
    };

    document.getElementById('linkIrALogin').onclick = (e) => {
        e.preventDefault();
        toggleModal(modalRegistro, 'close');
        toggleModal(modalLogin, 'open');
    };

    document.querySelectorAll('.close-btn').forEach(btn => {
        btn.onclick = () => {
            toggleModal(modalLogin, 'close');
            toggleModal(modalRegistro, 'close');
        };
    });

    const rolSelect = document.getElementById('rolSelect');
    const camposCreador = document.getElementById('camposCreador');

    if (rolSelect) {
        rolSelect.addEventListener('change', () => {
            if (rolSelect.value === 'creador') {
                camposCreador.style.display = 'block';
            } else {
                camposCreador.style.display = 'none';
            }
        });
    }

    const avatar = document.getElementById('avatarClick');
    const menu = document.getElementById('dropdownMenu');
    if (avatar) {
        avatar.onclick = (e) => {
            e.stopPropagation();
            menu.classList.toggle('show');
        };
    }

    window.onclick = (e) => {
        if (e.target.classList.contains('modal-overlay')) {
            toggleModal(modalLogin, 'close');
            toggleModal(modalRegistro, 'close');
        }
        if (menu && !menu.contains(e.target) && e.target !== avatar) menu.classList.remove('show');
    };
});

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('mousemove', (e) => {
        const eyes = document.querySelectorAll('.eye-svg');
        
        eyes.forEach(eye => {
            const pupil = eye.querySelector('.eye-pupil');
            if (!pupil) return;

            const rect = eye.getBoundingClientRect();
            const eyeX = rect.left + rect.width / 2;
            const eyeY = rect.top + rect.height / 2;

            const angle = Math.atan2(e.clientY - eyeY, e.clientX - eyeX);
            const distance = Math.min(12, Math.hypot(e.clientX - eyeX, e.clientY - eyeY) / 10);

            const pupilX = Math.cos(angle) * distance;
            const pupilY = Math.sin(angle) * distance;

            pupil.style.transform = `translate(${pupilX}px, ${pupilY}px)`;
        });
    });

    document.querySelectorAll('.toggle-eye-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const container = btn.closest('.password-wrapper');
            const input = container.querySelector('input');
            const eyeShape = btn.querySelector('.eye-shape');
            const pupil = btn.querySelector('.eye-pupil');

            if (input.type === 'password') {
                input.type = 'text';
                eyeShape.setAttribute('stroke', '#ffffff');
                pupil.setAttribute('fill', '#ffffff');
            } else {
                input.type = 'password';
                eyeShape.setAttribute('stroke', '#d4af37');
                pupil.setAttribute('fill', '#d4af37');
            }
        });
    });
});
</script>