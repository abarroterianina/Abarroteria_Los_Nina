<?php
// ============================================================
// Abarrotería Los Nina — Panel de administración
// Productos, categorías, cupones, configuración, pedidos y cuenta.
// Todo pasa por sesión de admin + token CSRF.
// ============================================================

define('LOS_NINA_APP', true);
session_start();
require_once __DIR__ . '/../config/database.php';

cabeceras_seguridad(true);
requerir_admin();

$tabsValidas = ['productos', 'categorias', 'cupones', 'configuracion', 'pedidos', 'mensajes', 'cuenta'];

$tab = $_GET['tab'] ?? 'productos';
if (!in_array($tab, $tabsValidas, true)) {
    $tab = 'productos';
}
$destino = 'admin.php?tab=' . $tab;

// ------------------------------------------------------------
// Acciones (POST) — siempre con CSRF válido
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
                $stmt = $pdo->prepare('DELETE FROM productos WHERE id = :id');
                $stmt->execute([':id' => $id]);
                flash('success', 'Producto eliminado.');
                break;

            case 'categoria_crear':
                $nombre = trim((string) ($_POST['nombre'] ?? ''));
                if (strlen($nombre) < 3) {
                    throw new Exception('El nombre de la categoría es obligatorio.');
                }
                $pdo->prepare('INSERT INTO categorias (nombre) VALUES (:nombre)')->execute([':nombre' => $nombre]);
                flash('success', 'Categoría agregada: ' . $nombre);
                break;

            case 'categoria_renombrar':
                $id = (int) ($_POST['id'] ?? 0);
                $nombre = trim((string) ($_POST['nombre'] ?? ''));
                if ($id <= 0 || strlen($nombre) < 3) {
                    throw new Exception('Datos de categoría inválidos.');
                }
                $pdo->prepare('UPDATE categorias SET nombre = :nombre WHERE id = :id')
                    ->execute([':nombre' => $nombre, ':id' => $id]);
                flash('success', 'Categoría actualizada.');
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

            case 'config_actualizar':
                $permitidas = claves_configuracion();
                $valores = $_POST['config'] ?? [];
                if (!is_array($valores)) {
                    throw new Exception('Datos de configuración inválidos.');
                }
                $stmt = $pdo->prepare(
                    'INSERT INTO configuracion (clave, valor) VALUES (:clave, :valor)
                     ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
                );
                foreach ($permitidas as $clave => $meta) {
                    if (!array_key_exists($clave, $valores)) {
                        continue;
                    }
                    $stmt->execute([':clave' => $clave, ':valor' => trim((string) $valores[$clave])]);
                }
                flash('success', 'Configuración guardada. El sitio y el chatbot ya la usan.');
                break;

            case 'compra_estado':
            case 'reserva_estado':
                $id = (int) ($_POST['id'] ?? 0);
                $estado = (string) ($_POST['estado'] ?? '');
                $tabla = $accion === 'compra_estado' ? 'compras' : 'reservas';

                if ($accion === 'compra_estado') {
                    $validos = ['pendiente', 'completada', 'cancelada'];
                } else {
                    $validos = ['pendiente', 'confirmada', 'cancelada'];
                }

                if ($id <= 0 || !in_array($estado, $validos, true)) {
                    throw new Exception('Estado inválido.');
                }
                $pdo->prepare("UPDATE {$tabla} SET estado = :estado WHERE id = :id")
                    ->execute([':estado' => $estado, ':id' => $id]);
                flash('success', 'Estado actualizado.');
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
            flash('error', 'No se puede completar: el registro tiene ventas o reservas asociadas.');
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

/**
 * Claves editables de la tabla configuracion.
 * La descripción se muestra en el panel.
 */
function claves_configuracion(): array
{
    return [
        'negocio_nombre' => ['Nombre del negocio', 'text'],
        'negocio_eslogan' => ['Eslogan', 'text'],
        'ciudad' => ['Ciudad / zona de entrega', 'text'],
        'direccion' => ['Dirección de la tienda', 'text'],
        'telefono' => ['Teléfono de contacto', 'text'],
        'whatsapp' => ['WhatsApp (con código de país)', 'text'],
        'email_contacto' => ['Correo de contacto', 'text'],
        'horario' => ['Horario de atención', 'text'],
        'costo_delivery' => ['Costo de delivery', 'text'],
        'delivery_gratis_desde' => ['Delivery gratis desde', 'text'],
        'moneda_simbolo' => ['Símbolo de moneda', 'text'],
    ];
}

// ------------------------------------------------------------
// Datos para la vista
// ------------------------------------------------------------
$datosError = '';
$categorias = [];
$productos = [];
$cupones = [];
$configuracion = [];
$compras = [];
$comprasDetalle = [];
$reservas = [];
$mensajes = [];

try {
    $categorias = $pdo->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetchAll();
    $productos = $pdo->query(
        'SELECT p.*, c.nombre AS categoria_nombre
         FROM productos p
         INNER JOIN categorias c ON p.categoria_id = c.id
         ORDER BY p.nombre'
    )->fetchAll();
    $cupones = $pdo->query('SELECT * FROM cupones ORDER BY codigo')->fetchAll();

    try {
        foreach ($pdo->query('SELECT clave, valor FROM configuracion') as $fila) {
            $configuracion[$fila['clave']] = $fila['valor'];
        }
    } catch (Throwable $e) {
        $configuracion = [];
    }

    $compras = $pdo->query('SELECT * FROM compras ORDER BY fecha DESC LIMIT 50')->fetchAll();
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

    $reservas = $pdo->query(
        'SELECT r.*, p.nombre AS producto_nombre
         FROM reservas r
         INNER JOIN productos p ON p.id = r.producto_id
         ORDER BY r.fecha_reserva DESC
         LIMIT 50'
    )->fetchAll();

    $mensajes = $pdo->query('SELECT * FROM mensajes_contacto ORDER BY fecha DESC LIMIT 50')->fetchAll();
} catch (Throwable $e) {
    // Si la base falla, mostramos un aviso legible en lugar de una página en blanco.
    error_log('[admin] Error al cargar datos del panel: ' . $e->getMessage());
    $datosError = 'No se pudieron cargar los datos del panel. Revisa la conexión con la base de datos e intenta de nuevo.';
}

$flashMensajes = flash_obtener();

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
                    'productos' => 'Productos',
                    'categorias' => 'Categorías',
                    'cupones' => 'Cupones',
                    'pedidos' => 'Pedidos y reservas',
                    'mensajes' => 'Mensajes',
                    'configuracion' => 'Configuración',
                    'cuenta' => 'Mi cuenta',
                ];
                ?>
                <?php foreach ($etiquetas as $clave => $texto): ?>
                    <a href="admin.php?tab=<?= e($clave) ?>"
                       class="<?= $tab === $clave ? 'is-active' : '' ?>"
                       <?= $tab === $clave ? 'aria-current="page"' : '' ?>><?= e($texto) ?></a>
                <?php endforeach; ?>
            </nav>

            <?php if ($tab === 'productos'): ?>
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
                    <p class="admin-note">Editá cualquier campo y presioná Guardar en esa fila. El precio acepta coma o punto.</p>

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
                                    <?php
                                    $pid = (int) $p['id'];
                                    $formId = 'prod-' . $pid;
                                    $stock = (int) $p['stock'];
                                    ?>
                                    <tr>
                                        <td class="cell-emoji">
                                            <input class="input" type="text" form="<?= e($formId) ?>" name="imagen"
                                                   value="<?= e($p['imagen']) ?>" placeholder="/productos-img/" aria-label="Imagen de <?= e($p['nombre']) ?>">
                                        </td>
                                        <td class="cell-nombre">
                                            <input class="input" type="text" form="<?= e($formId) ?>" name="nombre"
                                                   value="<?= e($p['nombre']) ?>" maxlength="100" required aria-label="Nombre de <?= e($p['nombre']) ?>">
                                        </td>
                                        <td>
                                            <select class="input" form="<?= e($formId) ?>" name="categoria_id" aria-label="Categoría de <?= e($p['nombre']) ?>">
                                                <?php foreach ($categorias as $c): ?>
                                                    <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $p['categoria_id'] ? 'selected' : '' ?>>
                                                        <?= e($c['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td style="min-width: 16rem">
                                            <input class="input" type="text" form="<?= e($formId) ?>" name="descripcion"
                                                   value="<?= e($p['descripcion']) ?>" maxlength="255" aria-label="Descripción de <?= e($p['nombre']) ?>">
                                        </td>
                                        <td>
                                            <input class="input" type="text" inputmode="decimal" form="<?= e($formId) ?>" name="precio"
                                                   value="<?= e(number_format((float) $p['precio'], 2, '.', '')) ?>" required aria-label="Precio de <?= e($p['nombre']) ?>">
                                        </td>
                                        <td>
                                            <input class="input" type="number" min="0" form="<?= e($formId) ?>" name="stock"
                                                   value="<?= $stock ?>" required aria-label="Stock de <?= e($p['nombre']) ?>">
                                            <?php if ($stock <= 0): ?>
                                                <span class="badge badge-out">Agotado</span>
                                            <?php elseif ($stock < 5): ?>
                                                <span class="badge badge-low">Bajo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="cell-actions">
                                            <button type="submit" form="<?= e($formId) ?>" name="accion" value="producto_actualizar" class="btn btn-primary btn-sm">Guardar</button>
                                            <button type="submit" form="<?= e($formId) ?>" name="accion" value="producto_eliminar" class="btn btn-outline btn-sm"
                                                    onclick="return confirm('¿Eliminar <?= e($p['nombre']) ?>? Esta acción no se puede deshacer.');">Eliminar</button>
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

            <?php elseif ($tab === 'categorias'): ?>
                <div class="admin-card">
                    <h2>Agregar categoría</h2>
                    <form method="POST" action="admin.php?tab=categorias" class="form-grid-3">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="accion" value="categoria_crear">
                        <div>
                            <label for="categoria_nueva">Nombre</label>
                            <input class="input" type="text" id="categoria_nueva" name="nombre" maxlength="50" required>
                        </div>
                        <div style="align-self: end">
                            <button type="submit" class="btn btn-primary">Agregar</button>
                        </div>
                    </form>
                </div>

                <div class="admin-card">
                    <h2>Categorías (<?= count($categorias) ?>)</h2>
                    <div class="table-wrap">
                        <table class="data">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Productos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorias as $c): ?>
                                    <?php
                                    $cid = (int) $c['id'];
                                    $total = 0;
                                    foreach ($productos as $p) {
                                        if ((int) $p['categoria_id'] === $cid) {
                                            $total++;
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?= $cid ?></td>
                                        <td>
                                            <input class="input" type="text" form="cat-<?= $cid ?>" name="nombre"
                                                   value="<?= e($c['nombre']) ?>" maxlength="50" required aria-label="Nombre de la categoría <?= e($c['nombre']) ?>">
                                        </td>
                                        <td><span class="badge badge-muted"><?= $total ?></span></td>
                                        <td class="cell-actions">
                                            <button type="submit" form="cat-<?= $cid ?>" name="accion" value="categoria_renombrar" class="btn btn-primary btn-sm">Guardar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php foreach ($categorias as $c): ?>
                        <?php $cid = (int) $c['id']; ?>
                        <form id="cat-<?= $cid ?>" method="POST" action="admin.php?tab=categorias">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= $cid ?>">
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
                                        <td>
                                            <input class="input" type="text" form="<?= e($formId) ?>" name="codigo"
                                                   value="<?= e($c['codigo']) ?>" maxlength="50" required aria-label="Código de cupón">
                                        </td>
                                        <td>
                                            <input class="input" type="number" min="1" max="90" form="<?= e($formId) ?>" name="porcentaje_descuento"
                                                   value="<?= (int) $c['porcentaje_descuento'] ?>" required aria-label="Porcentaje de <?= e($c['codigo']) ?>">
                                        </td>
                                        <td>
                                            <input class="input" type="text" inputmode="decimal" form="<?= e($formId) ?>" name="minimo_compra"
                                                   value="<?= e(number_format((float) $c['minimo_compra'], 2, '.', '')) ?>" aria-label="Mínimo de <?= e($c['codigo']) ?>">
                                        </td>
                                        <td>
                                            <input type="checkbox" form="<?= e($formId) ?>" name="activo" <?= (int) $c['activo'] === 1 ? 'checked' : '' ?> aria-label="Activo <?= e($c['codigo']) ?>">
                                        </td>
                                        <td>
                                            <input class="input" type="date" form="<?= e($formId) ?>" name="expira_en"
                                                   value="<?= e($c['expira_en'] ?? '') ?>" aria-label="Expiración de <?= e($c['codigo']) ?>">
                                        </td>
                                        <td class="cell-actions">
                                            <button type="submit" form="<?= e($formId) ?>" name="accion" value="cupon_actualizar" class="btn btn-primary btn-sm">Guardar</button>
                                            <button type="submit" form="<?= e($formId) ?>" name="accion" value="cupon_eliminar" class="btn btn-outline btn-sm"
                                                    onclick="return confirm('¿Eliminar el cupón <?= e($c['codigo']) ?>?');">Eliminar</button>
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

            <?php elseif ($tab === 'pedidos'): ?>
                <div class="admin-card">
                    <h2>Últimas compras (<?= count($compras) ?>)</h2>
                    <p class="admin-note">Los montos están en <?= e(simbolo_moneda()) ?>.</p>

                    <?php if (empty($compras)): ?>
                        <p class="text-muted">Todavía no hay compras registradas.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="data">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Fecha</th>
                                        <th>Subtotal</th>
                                        <th>Descuento</th>
                                        <th>Cupón</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($compras as $c): ?>
                                        <?php
                                        $cid = (int) $c['id'];
                                        $estados = ['pendiente', 'completada', 'cancelada'];
                                        ?>
                                        <tr>
                                            <td><?= $cid ?></td>
                                            <td><?= e(date('d/m/Y H:i', strtotime($c['fecha']))) ?></td>
                                            <td><?= e(formato_moneda((float) $c['total'] + (float) $c['descuento_aplicado'])) ?></td>
                                            <td><?= e(formato_moneda($c['descuento_aplicado'])) ?></td>
                                            <td><?= $c['cupon_usado'] ? e($c['cupon_usado']) : '<span class="badge badge-muted">—</span>' ?></td>
                                            <td><strong><?= e(formato_moneda($c['total'])) ?></strong></td>
                                            <td>
                                                <form method="POST" action="admin.php?tab=pedidos" class="admin-inline-form">
                                                    <?= csrf_campo() ?>
                                                    <input type="hidden" name="accion" value="compra_estado">
                                                    <input type="hidden" name="id" value="<?= $cid ?>">
                                                    <select class="input" name="estado" aria-label="Estado de la compra <?= $cid ?>">
                                                        <?php foreach ($estados as $estado): ?>
                                                            <option value="<?= e($estado) ?>" <?= $c['estado'] === $estado ? 'selected' : '' ?>><?= e(ucfirst($estado)) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-primary btn-sm">OK</button>
                                                </form>
                                            </td>
                                            <td>
                                                <?php if (!empty($comprasDetalle[$cid])): ?>
                                                    <ul class="admin-note" style="list-style: none">
                                                        <?php foreach ($comprasDetalle[$cid] as $d): ?>
                                                            <li><?= (int) $d['cantidad'] ?> × <?= e($d['nombre']) ?> · <?= e(formato_moneda($d['subtotal'])) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <span class="badge badge-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="admin-card">
                    <h2>Reservas del chatbot (<?= count($reservas) ?>)</h2>
                    <?php if (empty($reservas)): ?>
                        <p class="text-muted">Todavía no hay reservas.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="data">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Teléfono</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservas as $r): ?>
                                        <?php $rid = (int) $r['id']; ?>
                                        <tr>
                                            <td><?= $rid ?></td>
                                            <td><?= e(date('d/m/Y H:i', strtotime($r['fecha_reserva']))) ?></td>
                                            <td><?= e($r['nombre_cliente']) ?></td>
                                            <td><?= e($r['telefono']) ?></td>
                                            <td><?= e($r['producto_nombre']) ?></td>
                                            <td><?= (int) $r['cantidad'] ?></td>
                                            <td>
                                                <form method="POST" action="admin.php?tab=pedidos" class="admin-inline-form">
                                                    <?= csrf_campo() ?>
                                                    <input type="hidden" name="accion" value="reserva_estado">
                                                    <input type="hidden" name="id" value="<?= $rid ?>">
                                                    <select class="input" name="estado" aria-label="Estado de la reserva <?= $rid ?>">
                                                        <?php foreach (['pendiente', 'confirmada', 'cancelada'] as $estado): ?>
                                                            <option value="<?= e($estado) ?>" <?= $r['estado'] === $estado ? 'selected' : '' ?>><?= e(ucfirst($estado)) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-primary btn-sm">OK</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($tab === 'mensajes'): ?>
                <div class="admin-card">
                    <h2>Mensajes de contacto (<?= count($mensajes) ?>)</h2>
                    <?php if (empty($mensajes)): ?>
                        <p class="text-muted">No hay mensajes todavía.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="data">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Mensaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mensajes as $m): ?>
                                        <tr>
                                            <td><?= e(date('d/m/Y H:i', strtotime($m['fecha']))) ?></td>
                                            <td><?= e($m['nombre']) ?></td>
                                            <td><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></td>
                                            <td style="min-width: 20rem"><?= nl2br(e($m['mensaje'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($tab === 'configuracion'): ?>
                <div class="admin-card">
                    <h2>Datos del negocio</h2>
                    <p class="admin-note">
                        Estos datos se usan en el pie de página, en la página de contacto y en el prompt del chatbot.
                        No hace falta redeployar: se leen de la base de datos en cada visita.
                    </p>
                    <form method="POST" action="admin.php?tab=configuracion">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="accion" value="config_actualizar">
                        <div class="form-grid-3">
                            <?php foreach (claves_configuracion() as $clave => $meta): ?>
                                <div>
                                    <label for="cfg_<?= e($clave) ?>"><?= e($meta[0]) ?></label>
                                    <input class="input"
                                           type="<?= $meta[1] === 'email' ? 'email' : 'text' ?>"
                                           id="cfg_<?= e($clave) ?>"
                                           name="config[<?= e($clave) ?>]"
                                           value="<?= e($configuracion[$clave] ?? '') ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar configuración</button>
                    </form>
                </div>

            <?php elseif ($tab === 'cuenta'): ?>
                <div class="admin-card">
                    <h2>Cambiar contraseña</h2>
                    <p class="admin-note">Usá una contraseña larga (mínimo 8 caracteres). Es la única llave del panel.</p>
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
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
