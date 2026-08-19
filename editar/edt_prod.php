<?php
// /poiein/editar/edt_prod.php
session_start();

// Conexión a la base de datos
include($_SERVER['DOCUMENT_ROOT'] . "/poiein/conexión.php");

// 1. Obtener ID del producto desde la URL
$producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($producto_id <= 0) {
    header("Location: /poiein/mis_productos.php");
    exit;
}

// 2. Consultar datos actuales del producto
$query = "SELECT * FROM productos WHERE id = $producto_id";
$resultado = mysqli_query($conexion, $query);

if (!$resultado || mysqli_num_rows($resultado) == 0) {
    echo "<h2 style='color:#fff; text-align:center; margin-top:50px;'>El producto no existe o fue eliminado.</h2>";
    exit;
}

$producto = mysqli_fetch_assoc($resultado);

// Mapeo de datos con las nuevas columnas
$nombre_val      = $producto['nombre_item'] ?? '';
$precio_val      = $producto['precio'] ?? 0;
$descuento_val   = $producto['descuento'] ?? 0;
$disponible_val  = $producto['disponible'] ?? 1;
$categoria_val   = $producto['categoria'] ?? '';
$descripcion_val = $producto['descripcion'] ?? '';

// Ruta de imagen limpia
$ruta_db = $producto['imagen_producto'] ?? '';
$ruta_limpia = str_replace('../', '', $ruta_db);
$imagen_url = !empty($ruta_limpia) ? "/poiein/" . $ruta_limpia : '';

// 3. GUARDAR CAMBIOS EN LA BASE DE DATOS
$mensaje = "";
$error_msj = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = mysqli_real_escape_string($conexion, trim($_POST['nombre_item'] ?? ''));
    $precio      = floatval($_POST['precio'] ?? 0);
    $descuento   = intval($_POST['descuento'] ?? 0);
    $disponible  = intval($_POST['disponible'] ?? 1);
    $categoria   = mysqli_real_escape_string($conexion, trim($_POST['categoria'] ?? ''));
    $descripcion = mysqli_real_escape_string($conexion, trim($_POST['descripcion'] ?? ''));

    // Consulta con todas las columnas
    $update_query = "UPDATE productos SET 
                        nombre_item = '$nombre', 
                        precio = $precio, 
                        descuento = $descuento,
                        disponible = $disponible,
                        categoria = '$categoria',
                        descripcion = '$descripcion'
                    WHERE id = $producto_id";

    if (mysqli_query($conexion, $update_query)) {
        $mensaje = "¡Obra actualizada con éxito!";
        
        // Actualizar variables locales para refrescar la vista de inmediato
        $nombre_val      = $_POST['nombre_item'];
        $precio_val      = $precio;
        $descuento_val   = $descuento;
        $disponible_val  = $disponible;
        $categoria_val   = $_POST['categoria'];
        $descripcion_val = $_POST['descripcion'];
    } else {
        $error_msj = "Error al actualizar: " . mysqli_error($conexion);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pieza - POIEIN</title>
    <link rel="stylesheet" href="edt_prod.css?v=5.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <a href="/poiein/mis_prod/index.php" class="btn-top-back">
        ← Volver a Mis Productos
    </a>

    <div class="editor-layout">
        
        <!-- VISUALIZADOR DE LA IMAGEN DE LA OBRA -->
        <div class="editor-media-section">
            <div class="image-preview-box">
                <?php if (!empty($imagen_url)): ?>
                    <img id="img-preview" src="<?php echo $imagen_url; ?>" alt="Previsualización del producto">
                <?php else: ?>
                    <div class="no-image">✦ Sin imagen cargada</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- FORMULARIO DE EDICIÓN -->
        <div class="editor-form-section">
            <div class="form-header">
                <h2>Detalles del Producto</h2>
                <p>Edita los valores de tu obra expuesta.</p>
            </div>

            <?php if (!empty($mensaje)): ?>
                <div class="alert-exito"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_msj)): ?>
                <div class="alert-exito" style="border-color: #ff4444; color: #ff4444; background: rgba(255,68,68,0.1);"><?php echo $error_msj; ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="form-detalles">
                
                <div class="input-group">
                    <label for="nombre_item">Nombre del Producto</label>
                    <input type="text" id="nombre_item" name="nombre_item" class="input-field" value="<?php echo htmlspecialchars((string)$nombre_val); ?>" required placeholder="Título de la obra">
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label for="precio">Precio ($ USD)</label>
                        <input type="number" step="0.01" id="precio" name="precio" class="input-field" value="<?php echo $precio_val; ?>" required placeholder="0.00">
                    </div>

                    <div class="input-group">
                        <label for="descuento">Descuento (%)</label>
                        <input type="number" min="0" max="100" id="descuento" name="descuento" class="input-field" value="<?php echo $descuento_val; ?>" placeholder="0">
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label for="disponible">Estado del Producto</label>
                        <select id="disponible" name="disponible" class="input-field select-field">
                            <option value="1" <?php echo ($disponible_val == 1) ? 'selected' : ''; ?>>Disponible</option>
                            <option value="0" <?php echo ($disponible_val == 0) ? 'selected' : ''; ?>>Agotado / Desactivado</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="categoria">Categoría</label>
                        <input type="text" id="categoria" name="categoria" class="input-field" value="<?php echo htmlspecialchars((string)$categoria_val); ?>" placeholder="Ej: Pintura, Escultura...">
                    </div>
                </div>

                <div class="input-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="input-field textarea-field" rows="4" placeholder="Cuenta la historia de este producto..."><?php echo htmlspecialchars((string)$descripcion_val); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">GUARDAR CAMBIOS</button>

            </form>
        </div>

    </div>

</body>
</html>