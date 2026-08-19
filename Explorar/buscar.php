<?php
header('Content-Type: application/json');
session_start();

if (file_exists("../conexión.php")) {
    include("../conexión.php");
} elseif (file_exists("../conexion.php")) {
    include("../conexion.php");
} else {
    @include(__DIR__ . "/../conexión.php");
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($q) || !isset($conexion)) {
    echo json_encode(['creadores' => [], 'productos' => []]);
    exit;
}


$q_escaped = mysqli_real_escape_string($conexion, $q);

// 1. Buscar Creadores
$query_creadores = "SELECT id, nombre_completo, nombre_producto, foto_perfil 
                    FROM usuarios 
                    WHERE (nombre_completo LIKE '%$q_escaped%' OR nombre_producto LIKE '%$q_escaped%') 
                    LIMIT 4";
$res_creadores = mysqli_query($conexion, $query_creadores);
$creadores = [];

if ($res_creadores) {
    while ($row = mysqli_fetch_assoc($res_creadores)) {
        $foto = !empty($row['foto_perfil']) ? '/poiein/' . str_replace('../', '', $row['foto_perfil']) : '';
        $creadores[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre_completo'] ?: 'Creador',
            'disciplina' => $row['nombre_producto'] ?: 'Artista',
            'foto' => $foto
        ];
    }
}

// 2. Buscar Productos (por nombre del item)
$query_productos = "SELECT id, nombre_item, precio, imagen_producto 
                    FROM productos 
                    WHERE nombre_item LIKE '%$q_escaped%' 
                    LIMIT 6";
$res_productos = mysqli_query($conexion, $query_productos);
$productos = [];

if ($res_productos) {
    while ($row = mysqli_fetch_assoc($res_productos)) {
        $img = !empty($row['imagen_producto']) ? '/poiein/' . str_replace('../', '', $row['imagen_producto']) : '';
        $productos[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre_item'],
            'precio' => number_format($row['precio'], 2),
            'imagen' => $img
        ];
    }
}

// Devolver resultados en JSON
echo json_encode([
    'creadores' => $creadores,
    'productos' => $productos
]);