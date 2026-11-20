<?php
// ============================================================
// Abarrotería Los Nina — Panel de administración
// Inicio (solicitudes y stock), productos, cupones y cuenta.
// ============================================================

define('LOS_NINA_APP', true);
session_start();
require_once __DIR__ . '/../config/database.php';

cabeceras_seguridad(true);
requerir_admin();

$tabsValidas = ['inicio', 'productos', 'cupones', 'cuenta', 'bd'];

$tab = $_GET['tab'] ?? 'inicio';
if (!in_array($tab, $tabsValidas, true)) {
    $tab = 'inicio';
}
$destino = 'admin.php?tab=' . $tab;

// ------------------------------------------------------------
// Acciones (POST)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar()) {
        flash('error', 'La sesión expiró o el formulario no es válido. Intenta de nuevo.');
        header('Location: ' . $destino);
        exit;
    }

    $accion = (string) ($_POST['accion'] ?? '');

    try {
        switch ($accion) {
            case 'producto_crear':
            case 'producto_actualizar':
                $nombre = trim((string) ($_POST['nombre'] ?? ''));
                $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
                $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
                $precio = monto_desde_input($_POST['precio'] ?? '0');
                $stock = max(0, (int) ($_POST['stock'] ?? 0));
                $imagen = trim((string) ($_POST['imagen'] ?? ''));

                if (strlen($nombre) < 2) {
                    throw new Exception('El nombre del producto es obligatorio.');
                }
                if ($categoriaId <= 0) {
                    throw new Exception('Selecciona una categoría válida.');
                }
                if ($precio <= 0) {
                    throw new Exception('El precio debe ser mayor a cero.');
                }

                if ($accion === 'producto_crear') {
                    $pdo->prepare(
                        'INSERT INTO productos (nombre, categoria_id, descripcion, precio, stock, imagen)
                         VALUES (:nombre, :cat, :desc, :precio, :stock, :imagen)'
                    )->execute([
                        ':nombre' => $nombre,
                        ':cat' => $categoriaId,
                        ':desc' => $descripcion,
                        ':precio' => $precio,
                        ':stock' => $stock,
                        ':imagen' => $imagen,
                    ]);
                    flash('success', 'Producto agregado: ' . $nombre);
                } else {
                    $id = (int) ($_POST['id'] ?? 0);
                    if ($id <= 0) {
                        throw new Exception('Producto inválido.');
                    }
                    $pdo->prepare(
                        'UPDATE productos
                         SET nombre = :nombre, categoria_id = :cat, descripcion = :desc,
                             precio = :precio, stock = :stock, imagen = :imagen
                         WHERE id = :id'
                    )->execute([
                        ':nombre' => $nombre,
                        ':cat' => $categoriaId,
                        ':desc' => $descripcion,
                        ':precio' => $precio,
                        ':stock' => $stock,
                        ':imagen' => $imagen,
                        ':id' => $id,
                    ]);
                    flash('success', 'Producto actualizado: ' . $nombre);
                }
                break;

            case 'producto_eliminar':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Producto inválido.');
                }
                $pdo->prepare('DELETE FROM productos WHERE id = :id')->execute([':id' => $id]);
                flash('success', 'Producto eliminado.');
                break;

            case 'cupon_crear':
            case 'cupon_actualizar':
                $codigo = strtoupper(trim((string) ($_POST['codigo'] ?? '')));
                $codigoOriginal = strtoupper(trim((string) ($_POST['codigo_original'] ?? $codigo)));
                $porcentaje = (int) ($_POST['porcentaje_descuento'] ?? 0);
                $minimo = monto_desde_input($_POST['minimo_compra'] ?? '0');
                $activo = isset($_POST['activo']) ? 1 : 0;
                $expira = trim((string) ($_POST['expira_en'] ?? ''));

                if ($codigo === '' || !preg_match('/^[A-Z0-9_-]{3,50}$/', $codigo)) {
                    throw new Exception('El código debe tener entre 3 y 50 caracteres (letras, números, guion o guion bajo).');
                }
                if ($porcentaje < 1 || $porcentaje > 90) {
                    throw new Exception('El porcentaje de descuento debe estar entre 1 y 90.');
                }
                if ($expira !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expira)) {
                    throw new Exception('La fecha de expiración no es válida.');
                }

                if ($accion === 'cupon_crear') {
                    $pdo->prepare(
                        'INSERT INTO cupones (codigo, porcentaje_descuento, minimo_compra, activo, expira_en)
                         VALUES (:codigo, :pct, :minimo, :activo, :expira)'
                    )->execute([
                        ':codigo' => $codigo,
                        ':pct' => $porcentaje,
                        ':minimo' => $minimo,
                        ':activo' => $activo,
                        ':expira' => $expira !== '' ? $expira : null,
                    ]);
                    flash('success', 'Cupón creado: ' . $codigo);
                } else {
                    $pdo->prepare(
                        'UPDATE cupones
                         SET codigo = :codigo, porcentaje_descuento = :pct, minimo_compra = :minimo,
                             activo = :activo, expira_en = :expira
                         WHERE codigo = :original'
                    )->execute([
                        ':codigo' => $codigo,
                        ':pct' => $porcentaje,
                        ':minimo' => $minimo,
                        ':activo' => $activo,
                        ':expira' => $expira !== '' ? $expira : null,
                        ':original' => $codigoOriginal,
                    ]);
                    flash('success', 'Cupón actualizado: ' . $codigo);
                }
                break;

            case 'cupon_eliminar':
                $codigo = strtoupper(trim((string) ($_POST['codigo'] ?? '')));
                if ($codigo === '') {
                    throw new Exception('Cupón inválido.');
                }
                $pdo->prepare('DELETE FROM cupones WHERE codigo = :codigo')->execute([':codigo' => $codigo]);
                flash('success', 'Cupón eliminado.');
                break;

            case 'compra_estado':
                $id = (int) ($_POST['id'] ?? 0);
                $estado = (string) ($_POST['estado'] ?? '');
                $validos = ['pendiente', 'completada', 'cancelada'];
                if ($id <= 0 || !in_array($estado, $validos, true)) {
                    throw new Exception('Estado inválido.');
                }

                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare('SELECT id, estado FROM compras WHERE id = :id FOR UPDATE');
                    $stmt->execute([':id' => $id]);
                    $compra = $stmt->fetch();
                    if (!$compra) {
                        throw new Exception('Pedido no encontrado.');
                    }

                    $actual = (string) $compra['estado'];
                    $mensajeExito = 'El pedido #' . $id . ' ya tiene ese estado.';

                    if ($actual !== $estado) {
                        $det = $pdo->prepare(
                            'SELECT d.producto_id, d.cantidad, p.nombre
                             FROM compra_detalle d
                             INNER JOIN productos p ON p.id = d.producto_id
                             WHERE d.compra_id = :id'
                        );
                        $det->execute([':id' => $id]);
                        $items = $det->fetchAll();
                        $cambios = [];

                        if ($actual !== 'completada' && $estado === 'completada') {
                            $chk = $pdo->prepare('SELECT nombre, stock FROM productos WHERE id = :id FOR UPDATE');
                            foreach ($items as $it) {
                                $chk->execute([':id' => (int) $it['producto_id']]);
                                $p = $chk->fetch();
                                $stockActual = $p ? (int) $p['stock'] : 0;
                                $cant = (int) $it['cantidad'];
                                $nombreProd = $p['nombre'] ?? ($it['nombre'] ?? 'Producto');
                                if ($stockActual < $cant) {
                                    throw new Exception('Stock insuficiente para "' . $nombreProd . '". No se pudo concretar el pedido.');
                                }
                                $cambios[] = $nombreProd . ' (-' . $cant . ')';
                            }
                            $upd = $pdo->prepare('UPDATE productos SET stock = stock - :cant WHERE id = :id');
                            foreach ($items as $it) {
                                $upd->execute([':cant' => (int) $it['cantidad'], ':id' => (int) $it['producto_id']]);
                            }
                            $mensajeExito = 'Pedido #' . $id . ' concretado. Stock descontado: ' . (!empty($cambios) ? implode(', ', $cambios) : 'sin ítems') . '.';
                        } elseif ($actual === 'completada' && $estado !== 'completada') {
                            $upd = $pdo->prepare('UPDATE productos SET stock = stock + :cant WHERE id = :id');
                            foreach ($items as $it) {
                                $cant = (int) $it['cantidad'];
                                $upd->execute([':cant' => $cant, ':id' => (int) $it['producto_id']]);
                                $nombreProd = $it['nombre'] ?? 'Producto';
                                $cambios[] = $nombreProd . ' (+' . $cant . ')';
                            }
                            $etiquetaDestino = $estado === 'cancelada' ? 'no concretada' : 'pendiente';
                            $mensajeExito = 'Pedido #' . $id . ' marcado como ' . $etiquetaDestino . '. Stock repuesto: ' . (!empty($cambios) ? implode(', ', $cambios) : 'sin ítems') . '.';
                        } else {
                            $etiquetaDestino = $estado === 'cancelada' ? 'no concretada' : ($estado === 'pendiente' ? 'pendiente' : $estado);
                            $mensajeExito = 'Pedido #' . $id . ' actualizado a ' . $etiquetaDestino . '.';
                        }

                        $pdo->prepare('UPDATE compras SET estado = :estado WHERE id = :id')
                            ->execute([':estado' => $estado, ':id' => $id]);
                    }

                    $pdo->commit();
                    flash('success', $mensajeExito);
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $e;
                }
                break;

            case 'password_actualizar':
                $actual = (string) ($_POST['password_actual'] ?? '');
                $nueva = (string) ($_POST['password_nueva'] ?? '');
                $repetir = (string) ($_POST['password_repetir'] ?? '');

                if (strlen($nueva) < 8) {
                    throw new Exception('La nueva contraseña debe tener al menos 8 caracteres.');
                }
                if ($nueva !== $repetir) {
                    throw new Exception('La nueva contraseña y su confirmación no coinciden.');
                }

                $stmt = $pdo->prepare('SELECT password_hash FROM usuarios WHERE id = :id');
                $stmt->execute([':id' => (int) ($_SESSION['usuario_id'] ?? 0)]);
                $fila = $stmt->fetch();
                if (!$fila || !password_verify($actual, $fila['password_hash'])) {
                    throw new Exception('La contraseña actual no es correcta.');
                }

                $pdo->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id')
                    ->execute([
                        ':hash' => password_hash($nueva, PASSWORD_DEFAULT),
                        ':id' => (int) $_SESSION['usuario_id'],
                    ]);
                flash('success', 'Contraseña actualizada.');
                break;

            default:
                throw new Exception('Acción no reconocida.');
        }
    } catch (PDOException $e) {
        $codigo = (int) ($e->errorInfo[1] ?? 0);
        if ($codigo === 1451 || $codigo === 1452) {
            flash('error', 'No se puede completar: el registro tiene datos asociados.');
        } elseif ($codigo === 1062) {
            flash('error', 'Ese valor ya existe. Debe ser único.');
        } else {
            flash('error', 'Error de base de datos: ' . $e->getMessage());
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }

    header('Location: ' . $destino);
    exit;
}

// ------------------------------------------------------------
// Datos para la vista
// ------------------------------------------------------------
$datosError = '';
$categorias = [];
$productos = [];
$cupones = [];
$compras = [];
$comprasDetalle = [];
$tablasBd = [];

try {
    $categorias = $pdo->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetchAll();
    $productos = $pdo->query(
        'SELECT p.*, c.nombre AS categoria_nombre
         FROM productos p
         INNER JOIN categorias c ON p.categoria_id = c.id
         ORDER BY p.nombre'
    )->fetchAll();
    $cupones = $pdo->query('SELECT * FROM cupones ORDER BY codigo')->fetchAll();

    $compras = $pdo->query("SELECT id, telefono_cliente, total, descuento_aplicado, cupon_usado, fecha, estado FROM compras ORDER BY CASE WHEN estado = 'pendiente' THEN 0 ELSE 1 END, fecha DESC LIMIT 50")->fetchAll();
    if (!empty($compras)) {
        $ids = array_map('intval', array_column($compras, 'id'));
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare(
            "SELECT d.compra_id, d.cantidad, d.subtotal, p.nombre
             FROM compra_detalle d
             INNER JOIN productos p ON p.id = d.producto_id
             WHERE d.compra_id IN ({$marcadores})
             ORDER BY d.id"
        );
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $fila) {
            $comprasDetalle[(int) $fila['compra_id']][] = $fila;
        }
    }

    if ($tab === 'bd') {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $limite = 500;
        $nombres = [];
        if ($driver === 'sqlite') {
            $nombres = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $stmtT = $pdo->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = ? ORDER BY table_name');
            $stmtT->execute(['BASE TABLE']);
            $nombres = $stmtT->fetchAll(PDO::FETCH_COLUMN);
        }
        foreach ($nombres as $tname) {
            if (!is_string($tname) || !preg_match('/^[A-Za-z0-9_]+$/', $tname)) {
                continue;
            }
            $quoted = '`' . str_replace('`', '``', $tname) . '`';
            $total = (int) $pdo->query("SELECT COUNT(*) AS c FROM {$quoted}")->fetchColumn();
            $filas = $pdo->query("SELECT * FROM {$quoted} LIMIT {$limite}")->fetchAll(PDO::FETCH_ASSOC);
            $columnas = [];
            if (!empty($filas)) {
                $columnas = array_keys($filas[0]);
            } else {
                if ($driver === 'sqlite') {
                    $colsStmt = $pdo->query("PRAGMA table_info({$quoted})");
                    foreach ($colsStmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
                        $columnas[] = $c['name'];
                    }
                } else {
                    $colsStmt = $pdo->query("DESCRIBE {$quoted}");
                    foreach ($colsStmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
                        $columnas[] = $c['Field'];
                    }
                }
            }
            $tablasBd[] = [
                'nombre' => $tname,
                'columnas' => $columnas,
                'filas' => $filas,
                'total' => $total,
                'mostradas' => count($filas),
                'limitado' => $total > $limite,
            ];
        }
    }
} catch (Throwable $e) {
    error_log('[admin] Error al cargar datos del panel: ' . $e->getMessage());
    $datosError = 'No se pudieron cargar los datos del panel. Revisa la conexión con la base de datos e intenta de nuevo.';
}

$flashMensajes = flash_obtener();

$etiquetasEstado = [
    'pendiente' => 'Pendiente',
    'completada' => 'Concretada',
    'cancelada' => 'No concretada',
];

$titulo = 'Panel de administración — Abarrotería Los Nina';
$descripcion = 'Panel interno de administración.';
$paginaActiva = 'admin';
$noindex = true;
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/navbar.php';
?>

<main>
    <section class="section admin-panel">
        <div class="container">
            <div class="admin-topbar">
                <div>
                    <h1>Panel de administración</h1>
                    <p class="admin-note">Sesión de <?= e(config_get('negocio_nombre', 'Abarrotería Los Nina')) ?></p>
                </div>
                <form method="POST" action="logout.php" class="admin-inline-form">
                    <?= csrf_campo() ?>
                    <button type="submit" class="btn btn-outline btn-sm">Cerrar sesión</button>
                </form>
            </div>

            <?php foreach ($flashMensajes as $mensaje): ?>
                <div class="toast <?= $mensaje['tipo'] === 'error' ? 'error' : 'success' ?> show toast-inline"
                     role="<?= $mensaje['tipo'] === 'error' ? 'alert' : 'status' ?>">
                    <?= e($mensaje['mensaje']) ?>
                </div>
            <?php endforeach; ?>

            <?php if ($datosError !== ''): ?>
                <div class="toast error show toast-inline" role="alert"><?= e($datosError) ?></div>
            <?php endif; ?>

            <nav class="admin-tabs" aria-label="Secciones del panel">
                <?php
                $etiquetas = [
                    'inicio' => 'Inicio',
                    'productos' => 'Productos',
                    'cupones' => 'Cupones',
                    'cuenta' => 'Mi cuenta',
                    'bd' => 'Base de datos',
                ];
                ?>
                <?php foreach ($etiquetas as $clave => $texto): ?>
                    <a href="admin.php?tab=<?= e($clave) ?>"
                       class="<?= $tab === $clave ? 'is-active' : '' ?>"
                       <?= $tab === $clave ? 'aria-current="page"' : '' ?>><?= e($texto) ?></a>
                <?php endforeach; ?>
            </nav>

            <?php if ($tab === 'inicio'): ?>
                <div class="admin-card">
                    <h2>Solicitudes de compra (<?= count($compras) ?>)</h2>
                    <p class="admin-note">Contacta al cliente por WhatsApp para coordinar pago/QR. Al marcar <strong>Concretada</strong> se descuenta el stock; si reviertes a Pendiente o No concretada se repone.</p>

                    <?php if (empty($compras)): ?>
                        <p class="text-muted">Todavía no hay solicitudes de compra.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="data" style="min-width: 980px">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Fecha</th>
                                        <th>Teléfono</th>
                                        <th>Pedido</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($compras as $c): ?>
                                        <?php
                                        $cid = (int) $c['id'];
                                        $tel = (string) ($c['telefono_cliente'] ?? '');
                                        $telMostrar = $tel !== '' ? preg_replace('/^591/', '', $tel) : '';
                                        $waMsg = 'Hola, te escribo de ' . config_get('negocio_nombre', 'Abarrotería Los Nina') . ' por tu solicitud #' . $cid . '.';
                                        ?>
                                        <tr>
                                            <td><?= $cid ?></td>
                                            <td><?= e(date('d/m/Y H:i', strtotime($c['fecha']))) ?></td>
                                            <td>
                                                <?php if ($tel !== ''): ?>
                                                    <a href="<?= e(whatsapp_enlace_cliente($tel, $waMsg)) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">WhatsApp <?= e($telMostrar) ?></a>
                                                <?php else: ?>
                                                    <span class="badge badge-muted">Sin teléfono</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="min-width: 18rem">
                                                <?php if (!empty($comprasDetalle[$cid])): ?>
                                                    <ul class="admin-note" style="list-style: none; margin: 0;">
                                                        <?php foreach ($comprasDetalle[$cid] as $d): ?>
                                                            <li><?= (int) $d['cantidad'] ?> × <?= e($d['nombre']) ?> · <?= e(formato_moneda($d['subtotal'])) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <span class="badge badge-muted">Sin detalle</span>
                                                <?php endif; ?>
                                                <?php if (!empty($c['cupon_usado'])): ?>
                                                    <div class="admin-note" style="margin-top: 0.25rem;">Cupón: <?= e($c['cupon_usado']) ?> (desc. <?= e(formato_moneda($c['descuento_aplicado'])) ?>)</div>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?= e(formato_moneda($c['total'])) ?></strong></td>
                                            <td>
                                                <form method="POST" action="admin.php?tab=inicio" class="admin-inline-form">
                                                    <?= csrf_campo() ?>
                                                    <input type="hidden" name="accion" value="compra_estado">
                                                    <input type="hidden" name="id" value="<?= $cid ?>">
                                                    <select class="input" name="estado" aria-label="Estado de la solicitud <?= $cid ?>">
                                                        <?php foreach ($etiquetasEstado as $valor => $label): ?>
                                                            <option value="<?= e($valor) ?>" <?= $c['estado'] === $valor ? 'selected' : '' ?>><?= e($label) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="admin-card">
                    <h2>Resumen de stock (<?= count($productos) ?> productos)</h2>
                    <p class="admin-note">Vista de solo lectura (orden alfabético). Al concretar o revertir una solicitud el stock se actualiza automáticamente.</p>
                    <div style="margin: 0.75rem 0; max-width: 28rem;">
                        <label for="stock-search">Buscar en stock</label>
                        <input class="input" type="text" id="stock-search" placeholder="Buscar por producto o categoría…" autocomplete="off">
                    </div>
                    <div class="table-wrap">
                        <table class="data" id="stock-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody id="stock-tbody">
                                <?php foreach ($productos as $p): ?>
                                    <?php $stock = (int) $p['stock']; ?>
                                    <tr>
                                        <td><?= e($p['nombre']) ?></td>
                                        <td><?= e($p['categoria_nombre']) ?></td>
                                        <td><?= e(formato_moneda($p['precio'])) ?></td>
                                        <td>
                                            <strong><?= $stock ?></strong>
                                            <?php if ($stock <= 0): ?>
                                                <span class="badge badge-out">Agotado</span>
                                            <?php elseif ($stock < 5): ?>
                                                <span class="badge badge-low">Bajo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <script>
                (function () {
                    var input = document.getElementById('stock-search');
                    var tbody = document.getElementById('stock-tbody');
                    if (!input || !tbody) return;
                    input.addEventListener('input', function () {
                        var q = (input.value || '').toLowerCase().trim();
                        var rows = tbody.querySelectorAll('tr');
                        rows.forEach(function (tr) {
                            var text = (tr.textContent || '').toLowerCase();
                            tr.style.display = q === '' || text.indexOf(q) !== -1 ? '' : 'none';
                        });
                    });
                })();
                </script>

            <?php elseif ($tab === 'productos'): ?>
                <div class="admin-card">
                    <h2>Agregar producto</h2>
                    <form method="POST" action="admin.php?tab=productos">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="accion" value="producto_crear">
                        <div class="form-grid-3">
                            <div>
                                <label for="nuevo_nombre">Nombre</label>
                                <input class="input" type="text" id="nuevo_nombre" name="nombre" maxlength="100" required>
                            </div>
                            <div>
                                <label for="nuevo_categoria">Categoría</label>
                                <select class="input" id="nuevo_categoria" name="categoria_id" required>
                                    <?php foreach ($categorias as $c): ?>
                                        <option value="<?= (int) $c['id'] ?>"><?= e($c['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="nuevo_precio">Precio (<?= e(simbolo_moneda()) ?>)</label>
                                <input class="input" type="text" inputmode="decimal" id="nuevo_precio" name="precio" placeholder="12.50" required>
                            </div>
                            <div>
                                <label for="nuevo_stock">Stock</label>
                                <input class="input" type="number" min="0" id="nuevo_stock" name="stock" value="0" required>
                            </div>
                            <div>
                                <label for="nuevo_imagen">Imagen</label>
                                <input class="input" type="text" id="nuevo_imagen" name="imagen" value="" placeholder="/productos-img/">
                            </div>
                            <div class="admin-full">
                                <label for="nuevo_descripcion">Descripción</label>
                                <input class="input" type="text" id="nuevo_descripcion" name="descripcion" maxlength="255">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Agregar producto</button>
                    </form>
                </div>

                <div class="admin-card">
                    <h2>Productos (<?= count($productos) ?>)</h2>
                    <p class="admin-note">Edita los campos y guarda por fila. El stock también puede ajustarse aquí; los pedidos concretados lo modifican automáticamente.</p>
                    <div class="table-wrap">
                        <table class="data" style="min-width: 1040px">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Descripción</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $p): ?>
                                    <?php $pid = (int) $p['id']; $formId = 'prod-' . $pid; $stock = (int) $p['stock']; ?>
                                    <tr>
                                        <td class="cell-emoji">
                                            <input class="input" type="text" form="<?= e($formId) ?>" name="imagen" value="<?= e($p['imagen']) ?>" placeholder="/productos-img/" aria-label="Imagen de <?= e($p['nombre']) ?>">
                                        </td>
                                        <td class="cell-nombre">
                                            <input class="input" type="text" form="<?= e($formId) ?>" name="nombre" value="<?= e($p['nombre']) ?>" maxlength="100" required aria-label="Nombre de <?= e($p['nombre']) ?>">
                                        </td>
                                        <td>
                                            <select class="input" form="<?= e($formId) ?>" name="categoria_id" aria-label="Categoría de <?= e($p['nombre']) ?>">
                                                <?php foreach ($categorias as $c): ?>
                                                    <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $p['categoria_id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td style="min-width: 16rem">
                                            <input class="input" type="text" form="<?= e($formId) ?>" name="descripcion" value="<?= e($p['descripcion']) ?>" maxlength="255" aria-label="Descripción de <?= e($p['nombre']) ?>">
                                        </td>
                                        <td>
                                            <input class="input" type="text" inputmode="decimal" form="<?= e($formId) ?>" name="precio" value="<?= e(number_format((float) $p['precio'], 2, '.', '')) ?>" required aria-label="Precio de <?= e($p['nombre']) ?>">
                                        </td>
                                        <td>
                                            <input class="input" type="number" min="0" form="<?= e($formId) ?>" name="stock" value="<?= $stock ?>" required aria-label="Stock de <?= e($p['nombre']) ?>">
                                            <?php if ($stock <= 0): ?>
                                                <span class="badge badge-out">Agotado</span>
                                            <?php elseif ($stock < 5): ?>
                                                <span class="badge badge-low">Bajo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="cell-actions">
                                            <button type="submit" form="<?= e($formId) ?>" name="accion" value="producto_actualizar" class="btn btn-primary btn-sm">Guardar</button>
                                            <button type="submit" form="<?= e($formId) ?>" name="accion" value="producto_eliminar" class="btn btn-outline btn-sm" onclick="return confirm('¿Eliminar <?= e($p['nombre']) ?>? Esta acción no se puede deshacer.');">Eliminar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php foreach ($productos as $p): ?>
                        <?php $pid = (int) $p['id']; ?>
                        <form id="prod-<?= $pid ?>" method="POST" action="admin.php?tab=productos">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= $pid ?>">
                        </form>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($tab === 'cupones'): ?>
                <div class="admin-card">
                    <h2>Crear cupón</h2>
                    <form method="POST" action="admin.php?tab=cupones">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="accion" value="cupon_crear">
                        <div class="form-grid-3">
                            <div>
                                <label for="cupon_codigo">Código</label>
                                <input class="input" type="text" id="cupon_codigo" name="codigo" maxlength="50" placeholder="VERANO15" required>
                            </div>
                            <div>
                                <label for="cupon_pct">Descuento (%)</label>
                                <input class="input" type="number" min="1" max="90" id="cupon_pct" name="porcentaje_descuento" required>
                            </div>
                            <div>
                                <label for="cupon_minimo">Mínimo de compra (<?= e(simbolo_moneda()) ?>)</label>
                                <input class="input" type="text" inputmode="decimal" id="cupon_minimo" name="minimo_compra" value="0">
                            </div>
                            <div>
                                <label for="cupon_expira">Expira en</label>
                                <input class="input" type="date" id="cupon_expira" name="expira_en">
                            </div>
                            <div style="align-self: end">
                                <label style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="checkbox" name="activo" checked> Activo
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Crear cupón</button>
                    </form>
                </div>

                <div class="admin-card">
                    <h2>Cupones (<?= count($cupones) ?>)</h2>
                    <div class="table-wrap">
                        <table class="data">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>%</th>
                                    <th>Mínimo</th>
                                    <th>Activo</th>
                                    <th>Expira</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cupones as $c): ?>
                                    <?php $formId = 'cupon-' . preg_replace('/[^A-Za-z0-9_-]/', '', $c['codigo']); ?>
                                    <tr>
                                        <td><input class="input" type="text" form="<?= e($formId) ?>" name="codigo" value="<?= e($c['codigo']) ?>" maxlength="50" required></td>
                                        <td><input class="input" type="number" min="1" max="90" form="<?= e($formId) ?>" name="porcentaje_descuento" value="<?= (int) $c['porcentaje_descuento'] ?>" required></td>
                                        <td><input class="input" type="text" inputmode="decimal" form="<?= e($formId) ?>" name="minimo_compra" value="<?= e(number_format((float) $c['minimo_compra'], 2, '.', '')) ?>"></td>
                                        <td><input type="checkbox" form="<?= e($formId) ?>" name="activo" <?= (int) $c['activo'] === 1 ? 'checked' : '' ?>></td>
                                        <td><input class="input" type="date" form="<?= e($formId) ?>" name="expira_en" value="<?= e($c['expira_en'] ?? '') ?>"></td>
                                        <td class="cell-actions">
                                            <button type="submit" form="<?= e($formId) ?>" name="accion" value="cupon_actualizar" class="btn btn-primary btn-sm">Guardar</button>
                                            <button type="submit" form="<?= e($formId) ?>" name="accion" value="cupon_eliminar" class="btn btn-outline btn-sm" onclick="return confirm('¿Eliminar el cupón <?= e($c['codigo']) ?>?');">Eliminar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php foreach ($cupones as $c): ?>
                        <?php $formId = 'cupon-' . preg_replace('/[^A-Za-z0-9_-]/', '', $c['codigo']); ?>
                        <form id="<?= e($formId) ?>" method="POST" action="admin.php?tab=cupones">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="codigo_original" value="<?= e($c['codigo']) ?>">
                        </form>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($tab === 'cuenta'): ?>
                <div class="admin-card">
                    <h2>Cambiar contraseña</h2>
                    <p class="admin-note">Usa una contraseña larga (mínimo 8 caracteres). Es la única llave del panel.</p>
                    <form method="POST" action="admin.php?tab=cuenta" class="form-grid-3">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="accion" value="password_actualizar">
                        <div>
                            <label for="password_actual">Contraseña actual</label>
                            <input class="input" type="password" id="password_actual" name="password_actual" autocomplete="current-password" required>
                        </div>
                        <div>
                            <label for="password_nueva">Nueva contraseña</label>
                            <input class="input" type="password" id="password_nueva" name="password_nueva" autocomplete="new-password" minlength="8" required>
                        </div>
                        <div>
                            <label for="password_repetir">Repetir nueva contraseña</label>
                            <input class="input" type="password" id="password_repetir" name="password_repetir" autocomplete="new-password" minlength="8" required>
                        </div>
                        <div style="align-self: end">
                            <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
                        </div>
                    </form>
                </div>

            <?php elseif ($tab === 'bd'): ?>
                <div class="admin-card">
                    <h2>Base de datos (<?= count($tablasBd) ?> tablas)</h2>
                    <p class="admin-note">Vista de solo lectura de los datos reales. Se consultan todas las tablas al entrar a esta pestaña.</p>
                </div>

                <?php if (empty($tablasBd)): ?>
                    <div class="admin-card">
                        <p class="text-muted">No se encontraron tablas o no se pudieron cargar.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tablasBd as $tbd): ?>
                        <div class="admin-card">
                            <h3>Tabla: <code><?= e($tbd['nombre']) ?></code> <span class="badge badge-muted"><?= (int) $tbd['total'] ?> filas</span></h3>
                            <?php if ($tbd['limitado']): ?>
                                <p class="admin-note">Mostrando <?= (int) $tbd['mostradas'] ?> de <?= (int) $tbd['total'] ?> filas.</p>
                            <?php endif; ?>

                            <?php if (empty($tbd['columnas'])): ?>
                                <p class="text-muted">Sin columnas.</p>
                            <?php else: ?>
                                <div class="table-wrap">
                                    <table class="data">
                                        <thead>
                                            <tr>
                                                <?php foreach ($tbd['columnas'] as $col): ?>
                                                    <th><?= e($col) ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($tbd['filas'])): ?>
                                                <tr>
                                                    <td colspan="<?= count($tbd['columnas']) ?>" class="text-muted">Tabla vacía.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($tbd['filas'] as $fila): ?>
                                                    <tr>
                                                        <?php foreach ($tbd['columnas'] as $col): ?>
                                                            <?php $val = $fila[$col] ?? null; ?>
                                                            <td>
                                                                <?php if ($col === 'password_hash' && $val !== null): ?>
                                                                    <span class="badge badge-muted">oculto</span>
                                                                <?php elseif ($val === null): ?>
                                                                    <span class="badge badge-muted">NULL</span>
                                                                <?php else: ?>
                                                                    <?= e((string) $val) ?>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($tab !== 'bd'): ?>
                <div class="admin-card" style="text-align: center; margin-top: 1rem;">
                    <a href="admin.php?tab=bd" class="btn btn-outline">Ver datos de las tablas (BD)</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
