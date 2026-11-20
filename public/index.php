<?php
define('LOS_NINA_APP', true);
session_start();
require_once __DIR__ . '/../config/database.php';

cabeceras_seguridad();

$productosDestacados = [];
try {
    $stmt = $pdo->query(
        'SELECT p.*, c.nombre AS categoria_nombre FROM productos p
         INNER JOIN categorias c ON p.categoria_id = c.id
         WHERE p.stock > 20
         ORDER BY p.stock DESC
         LIMIT 8'
    );
    $productosDestacados = $stmt->fetchAll();
} catch (Exception $e) {
    $productosDestacados = [];
}

$titulo = 'Abarrotería Los Nina — Tu tienda de barrio de confianza';
$descripcion = 'Abarrotes, lácteos, bebidas, limpieza y más con entrega en el barrio. Compra en línea y recibe asesoría por chat.';
$preloadHero = true;
$paginaActiva = 'inicio';
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/navbar.php';
?>

<main>
    <?php $mensajesFlash = flash_obtener(); ?>
    <?php if (!empty($mensajesFlash)): ?>
        <div class="container" style="padding-top: 1rem;">
            <?php foreach ($mensajesFlash as $mensaje): ?>
                <div class="toast <?= $mensaje['tipo'] === 'error' ? 'error' : 'success' ?> show toast-inline"
                     role="<?= $mensaje['tipo'] === 'error' ? 'alert' : 'status' ?>">
                    <?= e($mensaje['mensaje']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="hero" id="inicio" aria-labelledby="hero-title">
        <picture class="hero-bg">
            <!-- Móvil (≤600 px): recorte vertical 4:5 -->
            <source media="(max-width: 600px)" type="image/webp"
                    srcset="assets/img/hero/hero-mobile-828.webp">
            <source media="(max-width: 600px)"
                    srcset="assets/img/hero/hero-mobile-828.jpg">
            <!-- Tablet (601–1024 px): recorte 4:3 -->
            <source media="(max-width: 1024px)" type="image/webp"
                    srcset="assets/img/hero/hero-tablet-1280.webp">
            <source media="(max-width: 1024px)"
                    srcset="assets/img/hero/hero-tablet-1280.jpg">
            <!-- Escritorio (>1024 px): recorte 16:9 con dos densidades -->
            <source type="image/webp"
                    srcset="assets/img/hero/hero-desktop-1600.webp 1600w,
                            assets/img/hero/hero-desktop-2066.webp 2066w" sizes="100vw">
            <img src="assets/img/hero/hero-desktop-1600.jpg"
                 srcset="assets/img/hero/hero-desktop-1600.jpg 1600w,
                         assets/img/hero/hero-desktop-2066.jpg 2066w"
                 sizes="100vw"
                 alt="Estantes de la Abarrotería Los Nina con papel higiénico, limpieza y abarrotes"
                 width="2066" height="1162"
                 fetchpriority="high" decoding="async">
        </picture>

        <div class="hero-inner">
            <div class="hero-content">
                <span class="hero-eyebrow">Tu tienda de barrio</span>
                <h1 id="hero-title" class="hero-title">
                    <span class="hero-brand-kicker">Abarrotería</span>
                    <span class="hero-brand-name">Los Nina</span>
                </h1>
                <p class="hero-text">Todo lo que tu hogar necesita, en un solo lugar.</p>

                <div class="hero-actions">
                    <a href="inventario.php" class="btn btn-accent btn-lg">Ver productos</a>
                    <a href="<?= e(whatsapp_enlace()) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-lg">WhatsApp</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Productos destacados</h2>
            <p class="section-subtitle">Una selección de lo más pedido esta semana.</p>

            <?php if (empty($productosDestacados)): ?>
                <p class="text-muted text-center">Todavía no hay productos destacados.</p>
            <?php else: ?>
                <div class="grid-products">
                    <?php foreach ($productosDestacados as $p): ?>
                        <article class="product-card cat-<?= (int) $p['categoria_id'] ?>">
                            <div class="product-card-img" aria-hidden="true"><img src="<?= e($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" loading="lazy"></div>
                            <div class="product-card-body">
                                <span class="product-category"><?= htmlspecialchars($p['categoria_nombre']) ?></span>
                                <h3 class="product-name"><?= htmlspecialchars($p['nombre']) ?></h3>
                                <div class="product-price"><?= e(formato_moneda($p['precio'])) ?></div>
                                <div class="product-stock available">Disponible</div>
                                <div class="product-actions">
                                    <button class="btn btn-primary btn-add"
                                        data-id="<?= (int) $p['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                        data-imagen="<?= htmlspecialchars($p['imagen']) ?>"
                                        data-precio="<?= htmlspecialchars($p['precio']) ?>">
                                        Agregar al carrito
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="text-center" style="margin-top: 2rem;">
                <a href="inventario.php" class="btn btn-primary btn-lg">Ver inventario completo</a>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
