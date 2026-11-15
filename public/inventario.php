<?php
define('LOS_NINA_APP', true);
session_start();
require_once __DIR__ . '/../config/database.php';

cabeceras_seguridad();

$categorias = $pdo->query('SELECT id, nombre FROM categorias ORDER BY id')->fetchAll();
$productos = $pdo->query(
    'SELECT p.*, c.nombre AS categoria_nombre FROM productos p
     INNER JOIN categorias c ON p.categoria_id = c.id
     ORDER BY p.id'
)->fetchAll();

$categoriaId = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Respuesta AJAX para el buscador en tiempo real
if (isset($_GET['ajax'])) {
    $sql = 'SELECT p.*, c.nombre AS categoria_nombre FROM productos p
            INNER JOIN categorias c ON p.categoria_id = c.id
            WHERE 1=1';
    $condiciones = [];
    $parametros = [];
    if ($categoriaId > 0) {
        $condiciones[] = 'p.categoria_id = :cat';
        $parametros[':cat'] = $categoriaId;
    }
    if ($q !== '') {
        $condiciones[] = '(p.nombre LIKE :q OR p.descripcion LIKE :q)';
        $parametros[':q'] = '%' . $q . '%';
    }
    if (!empty($condiciones)) {
        $sql .= ' AND ' . implode(' AND ', $condiciones);
    }
    $sql .= ' ORDER BY p.id';

    $stmt = $pdo->prepare($sql);
    foreach ($parametros as $clave => $valor) {
        $tipo = ($clave === ':q') ? PDO::PARAM_STR : PDO::PARAM_INT;
        $stmt->bindValue($clave, $valor, $tipo);
    }
    $stmt->execute();
    echo json_encode($stmt->fetchAll());
    exit;
}

$titulo = 'Inventario — Abarrotería Los Nina';
$descripcion = 'Catálogo completo de productos de Abarrotería Los Nina con stock actualizado.';
$paginaActiva = 'inventario';
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/navbar.php';
?>

<main>
    <section class="section">
        <div class="container">
            <div class="page-header">
                <h1>Inventario</h1>
                <p>Explora por categoría o busca por nombre. Agrega al carrito en un clic.</p>
            </div>

            <?php if (es_admin()): ?>
                <div class="text-center" style="margin-bottom: 1.5rem;">
                    <a href="admin.php" class="btn btn-ghost btn-sm">Abrir panel de administración</a>
                </div>
            <?php endif; ?>

            <div class="inventory-toolbar">
                <div class="category-filters" id="cat-buttons" role="group" aria-label="Filtrar por categoría">
                    <button class="btn btn-outline cat-filter active" data-cat="0">Todas</button>
                    <?php foreach ($categorias as $c): ?>
                        <button class="btn btn-outline cat-filter" data-cat="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></button>
                    <?php endforeach; ?>
                </div>

                <div class="search-field">
                    <label for="search-input" class="sr-only">Buscar producto</label>
                    <input type="text" id="search-input" class="input" placeholder="Buscar producto…" autocomplete="off">
                </div>
            </div>

            <div class="grid-products" id="products-grid" aria-live="polite">
                <?php foreach ($productos as $p): ?>
                    <?php
                    $stock = (int) $p['stock'];
                    $stockClass = $stock > 5 ? 'available' : ($stock > 0 ? 'low' : 'out');
                    $stockText = $stock > 5 ? 'Disponible' : ($stock > 0 ? '¡Últimas ' . $stock . ' unidades!' : 'Agotado');
                    ?>
                    <article class="product-card cat-<?= (int) $p['categoria_id'] ?>">
                        <div class="product-card-img" aria-hidden="true"><img src="<?= e($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" loading="lazy"></div>
                        <div class="product-card-body">
                            <span class="product-category"><?= htmlspecialchars($p['categoria_nombre']) ?></span>
                            <h3 class="product-name"><?= htmlspecialchars($p['nombre']) ?></h3>
                            <div class="product-price"><?= e(formato_moneda($p['precio'])) ?></div>
                            <div class="product-stock <?= $stockClass ?>"><?= $stockText ?></div>
                            <div class="product-actions">
                                <button class="btn btn-primary btn-add"
                                    data-id="<?= (int) $p['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                    data-imagen="<?= htmlspecialchars($p['imagen']) ?>"
                                    data-precio="<?= htmlspecialchars($p['precio']) ?>"
                                    <?= $stock <= 0 ? 'disabled' : '' ?>>
                                    <?= $stock <= 0 ? 'Agotado' : 'Agregar al carrito' ?>
                                </button>
                                <?php if ($stock > 0): ?>
                                    <button class="btn btn-accent btn-reservar"
                                        data-id="<?= (int) $p['id'] ?>"
                                        data-cantidad="1"
                                        data-nombre="<?= htmlspecialchars($p['nombre']) ?>">
                                        Reservar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
