# Despliegue — Abarrotería Los Nina (TiDB Cloud + Koyeb)

## A. Base de datos (TiDB Cloud — gratuito, 5 GB)
1. Crear cuenta en https://tidbcloud.com (login con GitHub, sin tarjeta).
2. Crear un cluster **Serverless** (capa free).
3. Ir a "Connect" → elegir "General" → copiar el **Connection String** (formato `mysql://user:pass@gateway...:4000/dbname?ssl-mode=REQUIRED`).
   - Anotar también usuario, password y host si se piden por separado.
4. Abrir el **SQL Editor** web de TiDB Cloud, seleccionar la base del sitio y pegar TODO el contenido de `sql/schema.sql` → Run.
   (Esto crea las tablas y los 60 productos en bolivianos. TiDB es MySQL-compatible, el schema funciona tal cual.)
   - TiDB Cloud Serverless **no permite crear bases de datos** desde el SQL Editor: usá la que ya trae el cluster.

## B. Código (GitHub)
5. Crear un repositorio en GitHub y subir todo el proyecto (incluye Dockerfile y .dockerignore).

## C. App (Koyeb — gratuito, no se duerme)
6. Crear cuenta en https://app.koyeb.com (login con GitHub).
7. "Create Service" → "Web service" → "Deploy from a container / GitHub".
   - Conectar el repo. Koyeb detecta el `Dockerfile`.
8. En la configuración del servicio:
   - **Port = 80** (HTTP).
   - Añadir **Environment variables**: `DATABASE_URL` (del paso A) y `NVIDIA_API_KEY`.
9. "Deploy". En 2-3 min se publica en una URL `https://<app>-<user>.koyeb.app` con HTTPS automático.

## D. Pruebas
10. Abrir la URL → debe cargar la portada con los 60 productos y precios en **Bs**.
11. Panel de administración: `https://<tu-app>.koyeb.app/login.php`
    - Usuario: `admin@gmail.com`
    - Contraseña inicial: `Losnina123` → cambiarla en **Mi cuenta**.
    - El login **no está enlazado** desde el sitio público: solo se entra escribiendo la URL.
12. Probar carrito (cupón `BIENVENIDO10`), compra (ver que baja el stock en TiDB) y chatbot (responde vía NVIDIA).

## E. Actualizaciones
13. Cada `git push` al repo → Koyeb redeploya automáticamente (igual que Vercel).

## F. Migrar una base que ya está en producción

Si el sitio ya está desplegado con los datos antiguos (precios en soles y productos peruanos), **no hay que borrar nada ni volver a crear tablas**:

1. Abrir el SQL Editor de TiDB Cloud y seleccionar la misma base que usa `DATABASE_URL`.
2. Pegar TODO el contenido de `sql/actualizacion_bolivia.sql` → Run.
3. Revisar las consultas de verificación del final del archivo (60 productos, 3 cupones, 2 tablas nuevas).

La migración es idempotente y actualiza por `id`:

- Reescribe nombre, categoría, descripción, precio y emoji de los 60 productos con marcas bolivianas y precios en **Bs**.
- Preserva el stock de los productos que ya tienen ventas registradas (ids 9, 28 y 45).
- Ajusta los cupones a bolivianos (`COMPRA20` pasa a compra mínima de `Bs 100`).
- Crea las tablas `configuracion` e `intentos_login` si no existen.
- Carga la configuración base del negocio sin sobrescribir valores ya editados desde el panel.
- Crea o actualiza el administrador `admin@gmail.com` y desactiva el rol del email anterior.

## G. Configuración del negocio sin redeployar

Los datos que aparecen en el sitio y en el chatbot se guardan en la tabla `configuracion` y se editan desde
`admin.php?tab=configuracion`: nombre, eslogan, ciudad, dirección, teléfono, WhatsApp, correo, horario,
costo de delivery, delivery gratis desde y símbolo de moneda.

Se leen en cada visita, así que **no hace falta volver a desplegar** para cambiarlos.

## H. Notas técnicas

- `docker/php.ini` desactiva `display_errors` en producción, endurece las cookies de sesión y carga
  `config/prepend.php`, que marca la cookie como `Secure` cuando la conexión es HTTPS.
- `public/.htaccess` bloquea el listado de directorios y la descarga de `.sql`, `.md`, `.ini` y similares.
- `public/robots.txt` mantiene fuera de los buscadores el login, el panel y los endpoints internos.
- El chatbot requiere conexión a internet para llamar a NVIDIA NIM. Plan gratuito: 500 requests/mes;
  si se agota, el bot responde con un mensaje de error controlado.
- No hay Composer, npm ni frameworks: solo PHP 8 y PDO.
