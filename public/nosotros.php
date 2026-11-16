<?php
define('LOS_NINA_APP', true);
session_start();
require_once __DIR__ . '/../config/database.php';

cabeceras_seguridad();

$titulo = 'Nosotros — Abarrotería Los Nina';
$descripcion = 'Conoce la historia y los valores de Abarrotería Los Nina, tu tienda de barrio de confianza.';
$paginaActiva = 'nosotros';
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/navbar.php';
?>

<main>
    <section class="section">
        <div class="container narrow-wrap">
            <div class="page-header">
                <h1>¿Quiénes somos?</h1>
                <p>Una tienda de barrio que crece contigo.</p>
            </div>

            <div class="card prose">
                <p>Abarrotería Los Nina nació como una tienda de barrio con un solo objetivo: <strong>llevar lo mejor de la abarrotería al alcance de tu puerta</strong>.</p>
                <p>Somos una familia apasionada por los productos frescos y de calidad. Trabajamos cada día para tener los mejores abarrotes, lácteos, bebidas y accesorios al alcance de todos.</p>
                <p>En Los Nina creemos en la confianza, el trato cercano y la calidad de lo que vendemos. ¡Bienvenidos a la familia!</p>
            </div>

            <div class="values-grid">
                <div class="value-item">
                    <h3>🤝 Trato cercano</h3>
                    <p>Te conocemos por tu nombre y te atendemos como en casa.</p>
                </div>
                <div class="value-item">
                    <h3>🌿 Productos frescos</h3>
                    <p>Seleccionamos lo mejor cada día para tu mesa.</p>
                </div>
                <div class="value-item">
                    <h3>🚚 Cerca de ti</h3>
                    <p>Pedidos simples y entrega en el barrio, sin complicaciones.</p>
                </div>
            </div>

            <?php
            $datosTienda = [
                'Dirección' => config_get('direccion'),
                'Ciudad' => config_get('ciudad'),
                'Horario' => config_get('horario'),
                'Teléfono' => config_get('telefono'),
                'Delivery' => config_get('costo_delivery'),
            ];
            $datosTienda = array_filter($datosTienda, static function ($valor) {
                return trim((string) $valor) !== '';
            });
            ?>
            <?php if (!empty($datosTienda)): ?>
                <div class="card" style="margin-top: 2rem;">
                    <h3>📌 Visítanos</h3>
                    <ul style="list-style: none; margin-top: 0.75rem;">
                        <?php foreach ($datosTienda as $etiqueta => $valor): ?>
                            <li style="margin-bottom: 0.35rem;">
                                <strong><?= e($etiqueta) ?>:</strong> <?= e($valor) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Mapa de ubicación -->
            <div class="card" style="margin-top: 2rem;">
                <h3 style="margin-bottom: 1rem;">📍 Nuestra ubicación</h3>
                <div class="map-embed">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d568.6526063523161!2d-68.20648618992506!3d-16.504703594982765!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMTbCsDMwJzE3LjAiUyA2OMKwMTInMjMuMCJX!5e0!3m2!1ses!2sbo!4v1794802050293!5m2!1ses!2sbo"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            title="Ubicación de Abarrotería Los Nina"></iframe>
                </div>
            </div>

        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
