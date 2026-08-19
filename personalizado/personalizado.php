<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /poiein/Login/login.php");
    exit();
}
$artista_id = isset($_GET['artista_id']) ? intval($_GET['artista_id']) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Laboratorio de Diseño | Poiein</title>
    <link rel="stylesheet" href="/poiein/NAV/nav.css">
    <link rel="stylesheet" href="personalizado.css">
    <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.4.0/model-viewer.min.js"></script>
</head>
<body>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/poiein/NAV/nav.php'; ?>

    <div class="contenedor-personalizador">
        <div>
            <!-- Añadido crossorigin="anonymous" para permitir texturas locales y remotas -->
            <model-viewer id="visor3d" src="plain_mug.glb" crossorigin="anonymous" camera-controls auto-rotate shadow-intensity="1"></model-viewer>
        </div>

        <div class="panel-controles">
            <h1>Personaliza tu Objeto</h1>
            <p>Sube tu diseño. Al enviar, la solicitud llegará directamente al creador seleccionado.</p>

            <!-- El formulario envía el archivo y el ID del artista -->
            <form action="procesar_encargo.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="artista_id" value="<?php echo $artista_id; ?>">

                <label for="inputReal" class="input-file-custom">
                    📁 <span>Seleccionar Imagen o Estampado</span>
                </label>
                <input type="file" name="imagen_usuario" id="inputReal" accept="image/*" style="display: none;" required>

                <div id="nombreArchivo" class="info-archivo"></div>

                <button type="submit" class="btn-enviar-pedido">ENVIAR SOLICITUD AL CREADOR</button>
            </form>
        </div>
    </div>

<script>
    const inputReal = document.getElementById('inputReal');
    const txtNombre = document.getElementById('nombreArchivo');
    const visor3d = document.getElementById('visor3d');
    
    inputReal.addEventListener('change', async (e) => {
        const archivo = e.target.files[0];
        if (archivo) {
            txtNombre.textContent = "Archivo listo: " + archivo.name;

            // 1. Crear URL temporal del archivo del cliente
            const urlImagen = URL.createObjectURL(archivo);

            // 2. Asegurar que model-viewer esté completamente cargado
            await visor3d.updateComplete;

            try {
                // 3. Verificar que el modelo y sus materiales existan
                if (visor3d.model && visor3d.model.materials.length > 0) {
                    
                    // 4. Seleccionar el primer material de la taza
                    const material = visor3d.model.materials[0];

                    // 5. Crear la textura dentro del visor
                    const textura = await visor3d.createTexture(urlImagen);

                    // 6. Asignar la textura al material base
                    material.pbrMetallicRoughness.baseColorTexture.setTexture(textura);

                    console.log("¡Textura aplicada correctamente en la vista del cliente!");
                } else {
                    console.error("El modelo 3D aún no tiene materiales cargados.");
                }
            } catch (error) {
                console.error("Error al procesar la imagen en el modelo 3D:", error);
            }
        }
    });
</script>
</body>
</html>