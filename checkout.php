<?php
session_start();
include("conexión.php");

$u_id = $_SESSION['usuario_id'] ?? 0;

if ($u_id == 0) {
    header("Location: /poiein/index.php");
    exit();
}

// Obtener el total del carrito del usuario
$res_cart = mysqli_query($conexion, "SELECT SUM(p.precio * c.cantidad) as total FROM carrito c JOIN productos p ON c.producto_id = p.id WHERE c.usuario_id = '$u_id'");
$data = mysqli_fetch_assoc($res_cart);
$total_a_pagar = $data['total'] ?? 0;

if ($total_a_pagar <= 0) {
    echo "<script>alert('Tu carrito está vacío'); window.location.href='carrito/carrito.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Finalizar Pedido - Poiein</title>
    <style>
        body { background: #121212; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .checkout-box { background: #1e1e1e; padding: 30px; border-radius: 10px; width: 400px; box-shadow: 0 4px 10px rgba(0,0,0,0.5); }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; color: #d4af37; }
        .input-group input, .input-group select { width: 100%; padding: 10px; background: #2a2a2a; border: 1px solid #444; color: #fff; border-radius: 5px; }
        .btn-pagar { width: 100%; background: #d4af37; color: #121212; border: none; padding: 12px; font-weight: bold; cursor: pointer; border-radius: 5px; font-size: 16px; }
        .btn-pagar:hover { background: #b8972f; }
    </style>
</head>
<body>
    <div class="checkout-box">
        <h2>Detalles del Pedido</h2>
        <p>Total a pagar: <strong>$<?php echo number_format($total_a_pagar, 2); ?> MXN</strong></p>
        
        <form action="procesar_pedido_local.php" method="POST">
            <div class="input-group">
                <label>Dirección de Envío</label>
                <input type="text" name="direccion" placeholder="Calle, número, ciudad..." required>
            </div>

            <div class="input-group">
                <label>Método de Pago</label>
                <select name="metodo_pago" required>
                    <option value="transferencia">Transferencia Bancaria</option>
                    <option value="efectivo">Pago en Efectivo (Contra entrega)</option>
                    <option value="tarjeta_manual">Tarjeta de Crédito/Débito (Registro)</option>
                </select>
            </div>

            <button type="submit" class="btn-pagar">Confirmar Pedido</button>
        </form>
    </div>
</body>
</html>