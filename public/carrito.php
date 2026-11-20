<?php
define('LOS_NINA_APP', true);
session_start();
require_once __DIR__ . '/../config/database.php';

cabeceras_seguridad();

$titulo = 'Tu carrito — Abarrotería Los Nina';
$descripcion = 'Revisa tu carrito, aplica cupones y finaliza tu compra en Abarrotería Los Nina.';
$paginaActiva = 'carrito';
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/navbar.php';
?>

<main>
    <section class="section">
        <div class="container">
            <div class="page-header">
                <h1>Tu carrito</h1>
                <p>Revisa tus productos, aplica un cupón y confirma tu pedido.</p>
            </div>

            <div class="cart-layout">
                <div>
                    <div id="cart-items" class="cart-items"></div>

                    <div id="cart-empty" class="empty-state" hidden>
                        <div class="empty-state-icon" aria-hidden="true">🛒</div>
                        <p>Tu carrito está vacío</p>
                        <a href="inventario.php" class="btn btn-primary">Ir a comprar</a>
                    </div>
                </div>

                <aside class="cart-summary" aria-label="Resumen de compra">
                    <h3>Resumen de compra</h3>
                    <div class="cart-summary-row">
                        <span>Subtotal</span>
                        <span id="subtotal"><?= e(formato_moneda(0)) ?></span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Descuento</span>
                        <span id="descuento">-<?= e(formato_moneda(0)) ?></span>
                    </div>
                    <div class="cart-summary-row total">
                        <span>Total final</span>
                        <span id="total-final"><?= e(formato_moneda(0)) ?></span>
                    </div>

                    <hr>

                    <form id="coupon-form">
                        <label for="coupon-input">Código de cupón</label>
                        <div class="coupon-row">
                            <input class="input" type="text" id="coupon-input" placeholder="BIENVENIDO10" autocomplete="off">
                            <button type="submit" id="coupon-btn" class="btn btn-primary">Aplicar</button>
                        </div>
                        <p id="coupon-applied-info" class="text-muted" style="margin-top: 0.5rem; font-size: 0.9rem;"></p>
                    </form>

                    <hr>

                    <form id="finalizar-form">
                        <div style="margin-bottom: 1rem;">
                            <label for="telefono-cliente">Número de celular (WhatsApp)</label>
                            <input class="input" type="tel" id="telefono-cliente" name="telefono" inputmode="tel" autocomplete="tel" placeholder="Ej: 71234567" required>
                            <p class="text-muted" style="margin-top: 0.35rem; font-size: 0.85rem;">Ingresa tu número boliviano de 8 dígitos (comienza con 6 o 7) para coordinar tu pedido.</p>
                        </div>
                        <button type="submit" id="finalizar-btn" class="btn btn-accent btn-lg btn-block">Enviar solicitud de compra</button>
                    </form>
                </aside>
            </div>
        </div>
    </section>
</main>

<div id="purchase-success" class="purchase-modal" hidden>
    <div class="purchase-modal-backdrop" data-close-purchase></div>
    <div class="purchase-card"
         role="dialog"
         aria-modal="true"
         aria-labelledby="purchase-success-title"
         aria-describedby="purchase-success-desc">
        <span class="purchase-icon" aria-hidden="true">✓</span>
        <h2 id="purchase-success-title" class="purchase-title">¡Solicitud recibida!</h2>
        <p id="purchase-success-desc" class="purchase-desc">
            Registramos tu pedido. Nos comunicaremos contigo por WhatsApp al número indicado para coordinar el pago y la entrega.
        </p>

        <dl class="purchase-details">
            <div class="purchase-detail">
                <dt>Solicitud</dt>
                <dd id="purchase-order">—</dd>
            </div>
            <div class="purchase-detail">
                <dt>Total</dt>
                <dd id="purchase-total">—</dd>
            </div>
            <div class="purchase-detail">
                <dt>Teléfono</dt>
                <dd id="purchase-phone">—</dd>
            </div>
        </dl>

        <p id="purchase-discount" class="purchase-note" hidden></p>

        <div class="purchase-actions">
            <a href="inventario.php" class="btn btn-primary btn-lg">Seguir comprando</a>
            <a href="index.php" class="btn btn-outline btn-lg">Volver al inicio</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
