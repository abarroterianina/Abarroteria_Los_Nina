<?php
// ============================================================
// Abarrotería Los Nina — Helpers compartidos
// Escapes, moneda, configuración editable, CSRF y sesión admin.
// Se carga automáticamente desde config/database.php.
// ============================================================

/**
 * Escapa texto para HTML. Atajo de htmlspecialchars con UTF-8 y comillas.
 */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Lee un valor de la tabla configuracion (clave/valor) con cache por request.
 * Si la tabla no existe o la clave no está, devuelve el valor por defecto.
 */
function config_get(string $clave, string $porDefecto = ''): string
{
    global $pdo;
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                $filas = $pdo->query('SELECT clave, valor FROM configuracion')->fetchAll();
                foreach ($filas as $fila) {
                    $cache[$fila['clave']] = (string) $fila['valor'];
                }
            } catch (Throwable $e) {
                // La tabla aún no existe (migración pendiente): seguimos con defaults.
                $cache = [];
            }
        }
    }

    if (array_key_exists($clave, $cache) && trim($cache[$clave]) !== '') {
        return $cache[$clave];
    }

    return $porDefecto;
}

/**
 * Símbolo de moneda del negocio. Configurable desde el panel admin.
 */
function simbolo_moneda(): string
{
    return config_get('moneda_simbolo', 'Bs');
}

/**
 * Formatea un monto: "Bs 1,234.50".
 * Punto decimal y coma de miles para que coincida con main.js.
 */
function formato_moneda($valor): string
{
    return simbolo_moneda() . ' ' . number_format((float) $valor, 2, '.', ',');
}

/**
 * Normaliza un monto escrito por el usuario (acepta "12,50" y "12.50").
 */
function monto_desde_input($valor): float
{
    $texto = trim((string) $valor);
    $texto = str_replace([' ', 'Bs', 'bs'], '', $texto);

    // Si hay coma y no hay punto, la coma es separador decimal.
    if (strpos($texto, ',') !== false && strpos($texto, '.') === false) {
        $texto = str_replace(',', '.', $texto);
    } else {
        // Si hay ambos, el último separador manda; asumimos formato 1.234,56.
        if (strpos($texto, ',') !== false) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        }
    }

    return max(0.0, (float) $texto);
}

/**
 * Token CSRF de la sesión (se genera en el primer uso).
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Input oculto listo para insertar dentro de un <form>.
 */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Valida el token CSRF enviado por POST.
 */
function csrf_validar(): bool
{
    $enviado = $_POST['csrf_token'] ?? '';
    $esperado = $_SESSION['csrf_token'] ?? '';

    return is_string($enviado) && $enviado !== '' && $esperado !== '' && hash_equals($esperado, $enviado);
}

/**
 * ¿La sesión actual es de un administrador?
 */
function es_admin(): bool
{
    return ($_SESSION['rol'] ?? '') === 'admin';
}

/**
 * Exige sesión de administrador. Corta la ejecución si no la hay.
 */
function requerir_admin(): void
{
    if (!es_admin()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Guarda un mensaje flash para mostrarlo después de un redirect (PRG).
 */
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/**
 * Devuelve y limpia los mensajes flash pendientes.
 */
function flash_obtener(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return is_array($mensajes) ? $mensajes : [];
}

/**
 * Cabeceras de seguridad comunes. Debe llamarse antes de imprimir HTML.
 */
function cabeceras_seguridad(bool $noIndex = false): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');

    if ($noIndex) {
        header('X-Robots-Tag: noindex, nofollow');
    }
}

/**
 * Devuelve solo dígitos del número de WhatsApp configurado.
 * Quita espacios, guiones, paréntesis y el signo '+'.
 * Default: +591 64049678 (Bolivia).
 */
function whatsapp_numero(): string
{
    $raw = config_get('whatsapp', '+591 64049678');
    return preg_replace('/[^0-9]/', '', $raw);
}

/**
 * Genera el enlace wa.me con mensaje precargado.
 * $mensaje opcional; si se omite usa un default amigable.
 */
function whatsapp_enlace(?string $mensaje = null): string
{
    $numero = whatsapp_numero();
    $texto = $mensaje ?? 'Hola, quiero hacer un pedido en Abarrotería Los Nina.';
    return 'https://wa.me/' . $numero . '?text=' . rawurlencode($texto);
}

/**
 * Normaliza y valida un celular boliviano (8 dígitos, inicia en 6 o 7).
 * Acepta el número local; si se incluye prefijo 591 lo ignora.
 * Devuelve el número con prefijo 591 para WhatsApp o null si es inválido.
 */
function normalizar_telefono_bolivia($valor): ?string
{
    $digitos = preg_replace('/[^0-9]/', '', trim((string) $valor));
    if (is_string($digitos) && strpos($digitos, '591') === 0 && strlen($digitos) === 11) {
        $digitos = substr($digitos, 3);
    }
    if (!is_string($digitos) || !preg_match('/^[67]\d{7}$/', $digitos)) {
        return null;
    }
    return '591' . $digitos;
}

/**
 * Enlace wa.me para un número de cliente (ya normalizado o local de 8 dígitos).
 */
function whatsapp_enlace_cliente(string $telefono, string $mensaje = ''): string
{
    $numero = preg_replace('/[^0-9]/', '', $telefono);
    if (preg_match('/^[67]\d{7}$/', $numero)) {
        $numero = '591' . $numero;
    }
    $url = 'https://wa.me/' . $numero;
    if ($mensaje !== '') {
        $url .= '?text=' . rawurlencode($mensaje);
    }
    return $url;
}
