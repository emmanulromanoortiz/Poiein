<?php
session_start();
include("conexión.php");

$u_id = $_SESSION['usuario_id'] ?? 0;
$direccion = mysqli_real_escape_string($conexion, $_POST['direccion'] ?? '');
$metodo_pago = mysqli_real_escape_string($conexion, $_POST['metodo_pago'] ?? '');

if ($u_id == 0 || empty($direccion)) {
    echo "<script>alert('Faltan datos por completar.'); window.history.back();</script>";
    exit();
}

// 1. Obtener los productos del carrito para calcular el total y guardarlos en detalle_pedido
$res_cart = mysqli_query($conexion, "SELECT c.producto_id, c.cantidad, p.precio FROM carrito c JOIN productos p ON c.producto_id = p.id WHERE c.usuario_id = '$u_id'");

$total_a_pagar = 0;
$productos_carrito = [];
while ($row = mysqli_fetch_assoc($res_cart)) {
    $total_a_pagar += ($row['precio'] * $row['cantidad']);
    $productos_carrito[] = $row;
}

if ($total_a_pagar <= 0 || empty($productos_carrito)) {
    header("Location: carrito/carrito.php");
    exit();
}

// 2. Insertar el pedido principal en la tabla 'pedidos'
$sql_pedido = "INSERT INTO pedidos (usuario_id, total, direccion, metodo_pago, estado) VALUES ('$u_id', '$total_a_pagar', '$direccion', '$metodo_pago', 'completado')";

if (mysqli_query($conexion, $sql_pedido)) {
    // Obtener el ID del pedido que se acaba de crear
    $pedido_id = mysqli_insert_id($conexion);

    // 3. Insertar cada producto del carrito en la tabla 'detalle_pedido'
    foreach ($productos_carrito as $prod) {
        $prod_id = $prod['producto_id'];
        $cantidad = $prod['cantidad'];
        $precio = $prod['precio'];

        $sql_detalle = "INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio) VALUES ('$pedido_id', '$prod_id', '$cantidad', '$precio')";
        mysqli_query($conexion, $sql_detalle);
    }

    // 4. Vaciar el carrito del usuario
    mysqli_query($conexion, "DELETE FROM carrito WHERE usuario_id = '$u_id'");

    echo "<script>
        alert('¡Pedido realizado con éxito!');
        window.location.href = 'mis_prod/index.php';
    </script>";
} else {
    echo "<script>alert('Error al procesar el pedido: " . mysqli_error($conexion) . "'); window.history.back();</script>";
}
?>