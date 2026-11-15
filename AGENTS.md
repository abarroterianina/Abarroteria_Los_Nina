# AGENT.md — Abarrotería Los Nina

## 🎯 CONTEXTO GENERAL DEL PROYECTO

Construir el sitio web completo de una abarrotería llamada **"Los Nina"**. Es una tienda de barrio con venta online básica que incluye: catálogo de productos, carrito de compras, chatbot inteligente (con NVIDIA NIM API) para reservas y consultas, sistema de descuentos con cupones, registro de ventas con transacciones y control de stock. El sitio debe funcionar en **hosting gratuito moderno (TiDB Cloud + Koyeb)** con Apache, PHP 8 y MySQL compatible.

**Objetivo**: Entregar un proyecto 100% funcional, listo para desplegar copiando archivos al hosting, sin dependencias externas ni procesos de build.

---

## 🛠️ STACK TECNOLÓGICO (ESTRICTO — NO MODIFICAR)

| Capa | Tecnología | Versión | Notas |
|------|-----------|---------|-------|
| Backend | PHP puro (sin frameworks) | 8.x | Sesiones nativas, PDO |
| Base de datos | MySQL / MariaDB (TiDB Cloud compatible) | 5.7+ | vía PDO con prepared statements |
| Frontend | HTML5 + CSS puro | — | Responsive, mobile-first |
| Interactividad | JavaScript vanilla | ES6+ | Sin librerías, con fetch() |
| Servidor web | Apache | 2.4+ | con `.htaccess` |
| Chatbot IA | NVIDIA NIM API | Nemotron 3.5 Super VL 120B | cURL nativo de PHP |
| Preprocesador CSS | SCSS (solo en desarrollo) | — | Compilar a CSS antes de entregar |

### 🚫 PROHIBIDO
- Frameworks PHP (Laravel, Symfony, CodeIgniter)
- Frameworks JS (React, Vue, Angular, Svelte)
- TypeScript
- Node.js
- Composer / dependencias de PHP (Guzzle, etc.)
- npm / dependencias de JS (jQuery, Bootstrap, Tailwind, etc.)
- APIs de IA externas distintas a NVIDIA NIM
- Imágenes locales (ej. logo en public/assets/img/)

---

## 📁 ESTRUCTURA DE ARCHIVOS

```
abarroteria-los-nina/
├── public/
│   ├── index.php              # Portada (carta de presentación)
│   ├── nosotros.php           # Quiénes somos
│   ├── contacto.php           # Formulario → guarda en BD
│   ├── inventario.php         # Catálogo + carrito + login admin
│   ├── carrito.php            # Vista detallada del carrito
│   ├── compra-venta.php       # Procesa pedido (transacción PDO)
│   ├── chatbot.php            # Endpoint JSON con NVIDIA NIM API
│   ├── login.php              # Autenticación admin
│   ├── logout.php             # Cierra sesión
│   └── assets/
│       ├── css/
│       │   └── style.css      # CSS ya compilado (NO .scss sueltos)
│       └── js/
│           ├── main.js        # Carrito, UI, validaciones, fetch
│           └── chatbot-widget.js  # Widget flotante del bot
├── config/
│   └── database.php           # Conexión PDO + constante NVIDIA_API_KEY
├── sql/
│   └── schema.sql             # CREATE TABLEs + 60 INSERTs + datos semilla
├── .htaccess                  # Seguridad básica y rewrites
├── Dockerfile                 # Para Koyeb (php:8.2-apache)
├── .dockerignore              # Excluye docs y .git del build
└── README.md                  # Instrucciones de despliegue (TiDB Cloud + Koyeb)
```

---

## 🗄️ BASE DE DATOS (schema.sql)

Generar un único archivo `schema.sql` que al importarse cree todo. Incluir:

### Tablas

```sql
-- Categorías de productos
CREATE TABLE categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
);

-- Usuarios (admin y clientes)
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('admin', 'cliente') DEFAULT 'cliente',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Productos
CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  categoria_id INT NOT NULL,
  descripcion TEXT,
  precio DECIMAL(10,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  emoji VARCHAR(10) DEFAULT '📦',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Compras
CREATE TABLE compras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT,
  total DECIMAL(10,2) NOT NULL,
  descuento_aplicado DECIMAL(10,2) DEFAULT 0,
  cupon_usado VARCHAR(50),
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('pendiente', 'completada', 'cancelada') DEFAULT 'completada',
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Detalle de compras
CREATE TABLE compra_detalle (
  id INT AUTO_INCREMENT PRIMARY KEY,
  compra_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Reservas (hechas vía chatbot)
CREATE TABLE reservas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_cliente VARCHAR(100) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  fecha_reserva TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('pendiente', 'confirmada', 'cancelada') DEFAULT 'pendiente',
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Cupones de descuento
CREATE TABLE cupones (
  codigo VARCHAR(50) PRIMARY KEY,
  porcentaje_descuento INT NOT NULL,
  minimo_compra DECIMAL(10,2) DEFAULT 0,
  activo BOOLEAN DEFAULT TRUE,
  expira_en DATE
);

-- Mensajes de contacto
CREATE TABLE mensajes_contacto (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  mensaje TEXT NOT NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Datos semilla

**Categorías** (8):
- Abarrotes, Lácteos, Bebidas, Limpieza, Higiene, Snacks, Enlatados, Panadería

**Usuario admin** (1):
- email: `admin@losnina.com`
- password: `admin123` (hasheado con `password_hash()`)
- rol: `admin`

**Productos** (60, distribuidos en las 8 categorías, ~7-8 por categoría):
- Precios realistas en moneda local (S/ soles peruanos o $MX pesos mexicanos)
- Stock inicial entre 10 y 100 unidades
- Emojis representativos (🍚 arroz, 🥛 leche, 🧴 shampoo, etc.)
- Ejemplos: Arroz extra 5kg (S/ 22.90), Leche Gloria 1L (S/ 4.50), Aceite Primor 1L (S/ 8.90), etc.

**Cupones** (3):
- `BIENVENIDO10` → 10% descuento, sin mínimo
- `COMPRA20` → 20% descuento, mínimo S/ 50
- `NINA5` → 5% descuento, sin mínimo, expira en 30 días

---

## ⚙️ FUNCIONALIDADES DETALLADAS

### 5.1 Portada (`index.php`)
- Hero con nombre "Abarrotería Los Nina" y eslogan tipo "Tu tienda de barrio de confianza".
- Sección de productos destacados (6 productos con stock > 20).
- Enlaces claros a: Inventario, Nosotros, Contacto.
- Diseño atractivo tipo tienda de barrio moderna.

### 5.2 Inventario (`inventario.php`)
- Listado de los 60 productos en grid responsive (cards con emoji, nombre, precio, stock, botón "Agregar al carrito").
- Filtro por categoría (botones o select).
- Buscador por nombre (JS, sin recargar).
- Indicador visual "¡Últimas unidades!" si stock < 5.
- Botón "Modo Admin" que lleva a `login.php`.
- **Vista admin** (tras login): tabla editable con stock y precios, botón para agregar/editar/eliminar productos.

### 5.3 Carrito (`carrito.php` + `main.js`)
- Productos agregados desde el inventario (persistencia en `localStorage`).
- Suma de totales en tiempo real (JS, sin recargar).
- Modificar cantidades, eliminar items.
- Mostrar subtotal, descuento aplicado (si hay cupón), total final.
- Campo para ingresar código de cupón (valida vía `fetch` a `chatbot.php`).
- Botón "Finalizar compra" → envía a `compra-venta.php`.

### 5.4 Compra-venta (`compra-venta.php`)
- Recibe el carrito por POST.
- **Transacción PDO** (BEGIN/COMMIT/ROLLBACK):
  1. INSERT en `compras` (con total y descuento).
  2. INSERT en `compra_detalle` por cada producto.
  3. UPDATE `productos` SET stock = stock - cantidad (verificar stock suficiente antes).
- Si algo falla, rollback y mensaje de error.
- Si todo OK, limpiar carrito y mostrar confirmación.

### 5.5 Chatbot con NVIDIA NIM API (`chatbot.php` + `chatbot-widget.js`)

**Backend (`chatbot.php`)**:
- Recibe `{ mensaje: "..." }` vía POST.
- Usa **cURL nativo de PHP** (NO GuzzleHTTP ni librerías externas).
- Configuración:
  - URL: `https://integrate.api.nvidia.com/v1/chat/completions`
  - Modelo: `nvidia/nemotron-3.5-super-vl-120b-a12b`
  - API Key: constante `NVIDIA_API_KEY` en `config/database.php`
- System prompt: "Eres el asistente de Abarrotería Los Nina. Ayudas con reservas, descuentos y consultas de stock. Responde en español, de forma amigable y concisa. Si el usuario quiere reservar un producto, responde confirmando y pide nombre y teléfono. Si pregunta por descuentos, menciona los cupones disponibles."
- El bot consulta la BD para:
  - Verificar stock de productos mencionados.
  - Validar cupones de descuento.
  - Guardar reservas en tabla `reservas`.
- Manejo de errores: si la API falla, responder "Estoy teniendo problemas para conectarme. Intenta de nuevo en unos momentos."
- Responde JSON: `{ respuesta: "..." }`

**Frontend (`chatbot-widget.js`)**:
- Widget flotante en esquina inferior derecha (icono de burbuja 💬, se expande al hacer clic).
- Interfaz de chat simple (mensajes del usuario y respuestas del bot).
- Input de texto + botón enviar.
- Animación de "escribiendo..." mientras espera respuesta.
- Persistencia de conversación en sesión (opcional).

### 5.6 Descuentos
- Aplicables desde el chatbot (cupón válido) o desde el carrito (campo "Código de cupón").
- Validar: cupón activo, no expirado, total >= mínimo_compra.
- Mostrar descuento aplicado en el total.

### 5.7 Contacto (`contacto.php`)
- Formulario: nombre, email, mensaje.
- Al enviar: INSERT en `mensajes_contacto` + mensaje de éxito.
- Validación JS (campos no vacíos, email válido).

### 5.8 Login admin (`login.php`)
- Formulario email + password.
- Validar con `password_verify`.
- Guardar sesión `$_SESSION['usuario_id']` y `$_SESSION['rol']`.
- Redirigir a inventario.php en modo admin si rol = 'admin'.
- `logout.php` destruye la sesión.


## 🔒 SEGURIDAD BÁSICA

- Todas las consultas SQL con **PDO prepared statements** (nunca concatenar variables).
- Contraseñas con `password_hash()` / `password_verify()`.
- Validar y sanitizar inputs del usuario (`htmlspecialchars`, `filter_var`).
- Sesiones con `session_regenerate_id(true)` tras login.
- `.htaccess`: denegar acceso directo a `/config/` y `/sql/`.
- API Key de NVIDIA guardada en `config/database.php` (no en código público).

---

## 📋 CRITERIOS DE ACEPTACIÓN (CHECKLIST FINAL)

El proyecto se considera completo cuando:
- [ ] Al importar `schema.sql` se crean todas las tablas y los 60 productos aparecen en el inventario.
- [ ] El carrito suma totales en tiempo real sin recargar la página.
- [ ] Al finalizar compra, el stock se descuenta correctamente en la BD.
- [ ] El chatbot responde a consultas de stock, reservas y cupones usando NVIDIA NIM API.
- [ ] Un cupón válido aplica el descuento en el total.
- [ ] El login admin funciona y permite ver/editar productos.
- [ ] El formulario de contacto guarda mensajes en la BD.
- [ ] El sitio es responsive (se ve bien en móvil y desktop).
- [ ] No hay errores PHP visibles en producción (`display_errors = Off`).
- [ ] No hay dependencias externas (ni Composer, ni npm).

---

## 🚀 INSTRUCCIONES DE DESPLIEGUE (para README.md)

Generar un README con estos pasos exactos (ver `DEPLOYMENT.md` para la estrategia completa moderna):


# Despliegue — Abarrotería Los Nina (TiDB Cloud + Koyeb)

## A. Base de datos (TiDB Cloud — gratuito, 5 GB)
1. Crear cuenta en https://tidbcloud.com (login con GitHub, sin tarjeta).
2. Crear un cluster **Serverless** (capa free).
3. Ir a "Connect" → elegir "General" → copiar el **Connection String** (formato `mysql://user:pass@gateway...:4000/dbname?ssl-mode=REQUIRED`).
4. Abrir el **SQL Editor** web de TiDB Cloud y pegar TODO el contenido de `sql/schema.sql` → Run.

## B. Código (GitHub)
5. Subir el proyecto a un repositorio en GitHub (incluye Dockerfile y .dockerignore).

## Notas
- El chatbot requiere conexión a internet para llamar a la API de NVIDIA.
- Plan gratuito de NVIDIA: 500 requests/mes.
- Si superas el límite, el bot mostrará mensaje de error elegante.


---

## ⚠️ RESTRICCIONES FINALES

- **NO preguntar pasos intermedios**. Generar todo el código de una vez.
- **NO usar dependencias externas** (ni composer, ni npm).
- **NO incluir archivos .scss sueltos**. Entregar solo `style.css` compilado y funcional.
- **NO usar imágenes externas**. Para los productos, usar emojis o placeholders CSS. Excepcion: el logo local del proyecto (public/assets/img/).
- **TODO el código en español** (variables, comentarios, mensajes al usuario).
- **El chatbot DEBE usar cURL nativo de PHP** para llamar a NVIDIA NIM API. NO usar GuzzleHTTP ni librerías externas.
- **Las transacciones de compra DEBEN usar PDO con BEGIN/COMMIT/ROLLBACK**.

---

## 🤖 INSTRUCCIÓN FINAL PARA EL AGENTE

**Lee este archivo completo y ejecuta todas las instrucciones. Genera el proyecto completo de una vez, sin preguntar pasos intermedios. Entrega todos los archivos listos para deploy en Koyeb con TiDB Cloud.**
```

---

## 📌 Cómo usar este archivo

1. **Guarda este contenido** como `AGENT.md` en la raíz de tu proyecto (o en la carpeta donde OpenCode pueda leerlo).

2. **En OpenCode**, inicia la conversación pegando:
   ```
   Lee el archivo AGENT.md y ejecuta todas las instrucciones. Genera el proyecto completo de una vez, sin preguntar pasos intermedios.
   ```

3. El agente debería devolverte todos los archivos listos. Si se corta por longitud, pídele que continúe desde donde quedó.

---

## ✅ Checklist antes de ejecutar

- [ ] Tienes cuenta en **TiDB Cloud** (tidbcloud.com) y **Koyeb** (app.koyeb.com)
- [ ] Tienes API Key de **NVIDIA NIM** (build.nvidia.com) — plan gratuito con 500 requests/mes


