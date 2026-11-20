<?php
// ============================================================
// Abarrotería Los Nina — Procesa Pedido (Transacción PDO)
// ============================================================

session_start();
require_once __DIR__ . '/../config/database.php';

cabeceras_seguridad();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
$cupon = isset($data['cupon']) ? strtoupper(trim($data['cupon'])) : '';
$telefonoRaw = isset($data['telefono']) ? (string) $data['telefono'] : '';

$telefono = normalizar_telefono_bolivia($telefonoRaw);
if ($telefono === null) {
    echo json_encode(['exito' => false, 'mensaje' => 'Ingresa un número de celular boliviano válido: 8 dígitos que comiencen con 6 o 7.']);
    exit;
}

if (empty($items)) {
    echo json_encode(['exito' => false, 'mensaje' => 'El carrito está vacío']);
    exit;
}

// Validación básica de la forma del carrito antes de abrir la transacción.
foreach ($items as $item) {
    if (!isset($item['id'], $item['cantidad'])) {
        echo json_encode(['exito' => false, 'mensaje' => 'Carrito con datos inválidos']);
        exit;
    }
}

$pdo->beginTransaction();
try {
    $lineas = [];
    $subtotal = 0.0;

    // Precios SIEMPRE desde la base de datos (nunca desde el cliente).
    foreach ($items as $item) {
        $pid = (int) $item['id'];
        $cantidad = (int) $item['cantidad'];
        if ($cantidad <= 0) {
            continue;
        }

        $stmt = $pdo->prepare('SELECT nombre, precio, stock FROM productos WHERE id = :id');
        $stmt->execute([':id' => $pid]);
        $prod = $stmt->fetch();

        if (!$prod) {
            throw new Exception('Producto no encontrado');
        }
        if ((int) $prod['stock'] < $cantidad) {
            throw new Exception('Stock insuficiente para ' . $prod['nombre']);
        }

        $precio = (float) $prod['precio'];
        $sub = round($precio * $cantidad, 2);
        $subtotal += $sub;
        $lineas[] = [
            'id' => $pid,
            'cantidad' => $cantidad,
            'subtotal' => $sub,
        ];
    }

    if (empty($lineas)) {
        throw new Exception('El carrito está vacío');
    }

    // Validar cupón contra el subtotal real del servidor.
    $descuento = 0.0;
    $cuponUsado = null;
    if ($cupon !== '') {
        $stmt = $pdo->prepare(
            'SELECT codigo, porcentaje_descuento, minimo_compra, activo, expira_en FROM cupones WHERE codigo = :codigo'
        );
        $stmt->execute([':codigo' => $cupon]);
        $c = $stmt->fetch();
        $hoy = date('Y-m-d');
        if (
            $c
            && (int) $c['activo'] === 1
            && ($c['expira_en'] === null || $c['expira_en'] >= $hoy)
            && $subtotal >= (float) $c['minimo_compra']
        ) {
            $descuento = round($subtotal * ((int) $c['porcentaje_descuento'] / 100), 2);
            $cuponUsado = $c['codigo'];
        }
    }

    $total = round($subtotal - $descuento, 2);

    // Registrar solicitud como pendiente (el stock se descuenta al concretar en el panel admin).
    $insertCompra = $pdo->prepare(
        'INSERT INTO compras (usuario_id, telefono_cliente, total, descuento_aplicado, cupon_usado, estado) VALUES (:uid, :tel, :total, :desc, :cupon, :estado)'
    );
    $insertCompra->execute([
        ':uid' => $_SESSION['usuario_id'] ?? null,
        ':tel' => $telefono,
        ':total' => $total,
        ':desc' => $descuento,
        ':cupon' => $cuponUsado,
        ':estado' => 'pendiente',
    ]);
    $compraId = (int) $pdo->lastInsertId();

    $insDetalle = $pdo->prepare(
        'INSERT INTO compra_detalle (compra_id, producto_id, cantidad, subtotal) VALUES (:cid, :pid, :cant, :sub)'
    );

    foreach ($lineas as $linea) {
        $insDetalle->execute([
            ':cid' => $compraId,
            ':pid' => $linea['id'],
            ':cant' => $linea['cantidad'],
            ':sub' => $linea['subtotal'],
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'exito' => true,
        'compra_id' => $compraId,
        'subtotal' => $subtotal,
        'descuento' => $descuento,
        'total' => $total,
        'estado' => 'pendiente',
        'telefono' => $telefono,
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // El detalle técnico va al log, nunca al cliente.
    error_log('[compra-venta] ' . $e->getMessage());
    echo json_encode(['exito' => false, 'mensaje' => 'No se pudo enviar tu solicitud. Revisa el stock e intenta de nuevo.']);
}
