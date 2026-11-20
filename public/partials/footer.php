<?php
// Partial protegido: solo se incluye desde páginas de la app.
if (!defined('LOS_NINA_APP')) {
    http_response_code(404);
    exit;
}
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <picture>
                    <source type="image/webp" srcset="assets/img/logo/logo-160.webp">
                    <img src="assets/img/logo/logo-160.png" width="67" height="46" alt="Abarrotería Los Nina" loading="lazy" decoding="async">
                </picture>
                <div>
                    <strong><?= e(config_get('negocio_nombre', 'Abarrotería Los Nina')) ?></strong>
                    <span><?= e(config_get('negocio_eslogan', 'Tu tienda de barrio de confianza')) ?></span>
                </div>
            </div>

            <div class="footer-col">
                <h4>Tienda</h4>
                <a href="inventario.php">Inventario</a>
                <a href="carrito.php">Carrito</a>
                <a href="nosotros.php">Nosotros</a>
                <a href="<?= e(whatsapp_enlace()) ?>" target="_blank" rel="noopener">WhatsApp</a>
            </div>

            <div class="footer-col">
                <h4>Atención</h4>
                <p><?= e(config_get('horario', 'Lun a Sáb · 8:00 – 21:00')) ?></p>
                <?php if (config_get('direccion') !== ''): ?>
                    <p><?= e(config_get('direccion')) ?></p>
                <?php endif; ?>
                <?php if (config_get('ciudad') !== ''): ?>
                    <p><?= e(config_get('ciudad')) ?></p>
                <?php endif; ?>
                <?php if (config_get('telefono') !== ''): ?>
                    <p><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', config_get('telefono'))) ?>">📞 <?= e(config_get('telefono')) ?></a></p>
                <?php endif; ?>
                <p>Consulta productos y precios 💬</p>
            </div>
        </div>

        <div class="footer-bottom">
            © <?= date('Y') ?> Abarrotería Los Nina — Hecho con cariño para el barrio.
        </div>
    </div>
</footer>

<button id="chat-fab" class="chat-fab" aria-label="Abrir asistente de chat">💬</button>
<div id="chat-panel" class="chat-panel" role="dialog" aria-modal="false" aria-label="Asistente Los Nina">
    <div class="chat-header">
        <h3><span class="chat-avatar" aria-hidden="true">🛒</span> Asistente Los Nina</h3>
        <button id="chat-close" class="chat-close" aria-label="Cerrar chat">✕</button>
    </div>
    <div id="chat-body" class="chat-body" aria-live="polite" aria-relevant="additions"></div>
    <div class="chat-footer">
        <input id="chat-input" class="input" type="text" placeholder="Escribe tu mensaje…" aria-label="Mensaje para el asistente">
        <button id="chat-send" class="btn btn-primary" aria-label="Enviar mensaje">➤</button>
    </div>
</div>

<div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="false"></div>

<script src="assets/js/main.js"></script>
<script src="assets/js/chatbot-widget.js"></script>
</body>
</html>
