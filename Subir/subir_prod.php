<?php
// 1. Conexión a la base de datos
include($_SERVER['DOCUMENT_ROOT'] . "/poiein/conexión.php"); 
session_start();

// 2. Control de acceso
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'creador') {
    header("Location: /poiein/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editor de Articulos | Poiein</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="subir_prod.css?v=<?php echo time(); ?>">
</head>
<body class="theme-black">

<div class="editor-layout">
    <aside class="sidebar-styles">
        <div class="trigger-zone">🎨</div>
        <div class="drawer-content">
            <h4>Color de Espacio</h4>
            <div class="color-grid">
                <div class="dot black" onclick="setTheme('black')"></div>
                <div class="dot purple" onclick="setTheme('purple')"></div>
                <div class="dot pink" onclick="setTheme('pink')"></div>
                <div class="dot blue" onclick="setTheme('blue')"></div>
            </div>
        </div>
    </aside>

    <main class="canvas-center">
        <div class="preview-box" id="dropZone">
            <img id="mainPreview" src="#" alt="Preview" style="display:none; max-width: 100%; max-height: 100%;">
            <div id="canvas-text">✦ Arrastra o selecciona tu obra</div>
        </div>
    </main>

    <aside class="sidebar-fields">
        <div class="panel-header">
            <h3>Detalles del Producto</h3>
        </div>
        
        <form action="/poiein/Guardar/guardar_prod.php" method="POST" enctype="multipart/form-data">
            <div class="input-field">
                <label>Nombre del Producto</label>
                <input type="text" name="nombre" placeholder="Título de la obra" required>
            </div>

            <div class="input-field">
                <label>Precio</label>
                <input type="number" name="precio" step="0.01" placeholder="0.00">
            </div>

            <div class="input-field">
                <label>Archivo de Imagen</label>
                <div class="custom-file-upload">
                    <input type="file" name="imagen_url" id="imgInput" accept="image/*" required style="display: none;">
                    
                    <label for="imgInput" class="btn-custom-file">
                        SELECCIONAR PRODUCTO
                    </label>
                    
                    <p id="file-name-display">Sin archivo seleccionado</p>
                </div>
            </div>

            <div class="input-field">
                <label>Descripción</label>
                <textarea name="descripcion" placeholder="Cuenta la historia de este producto..."></textarea>
            </div>

            <button type="submit" class="btn-main">PUBLICAR AHORA</button>
        </form>
    </aside>
</div>

<script>
    function setTheme(color) {
        document.body.className = 'theme-' + color;
    }

    // Previsualización instantánea y actualización de nombre de archivo
    document.getElementById('imgInput').onchange = function(e) {
        const fileDisplay = document.getElementById('file-name-display');
        const preview = document.getElementById('mainPreview');
        const canvasText = document.getElementById('canvas-text');
        const [file] = e.target.files;

        if (file) {
            fileDisplay.innerText = "Archivo: " + file.name;
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
            canvasText.style.display = 'none';
        }
    }
</script>
</body>
</html>