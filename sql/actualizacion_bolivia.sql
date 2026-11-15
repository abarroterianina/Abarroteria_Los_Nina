-- ============================================================
-- Abarrotería Los Nina — Actualización a datos y moneda de Bolivia
--
-- Migración IDEMPOTENTE para una base que ya está en producción.
-- No borra filas ni recrea tablas: actualiza por id y asegura el usuario administrador.
--
-- Cómo aplicarla:
--   1. TiDB Cloud → SQL Editor → selecciona la base del sitio
--      (la que usa DATABASE_URL; hoy: github_sample).
--   2. Pega TODO este archivo y ejecuta.
--   3. Revisa el resultado de las consultas de verificación del final.
--
-- Notas:
--   - Los precios están en bolivianos (Bs).
--   - Los productos se actualizan por id. Los ids NO cambian porque
--     compra_detalle y reservas los referencian con clave foránea.
--   - El stock de los productos con ventas registradas (9, 28 y 45)
--     se deja como está; el resto toma el stock del catálogo nuevo.
-- ============================================================

-- ------------------------------------------------------------
-- 1. Tablas nuevas que el código ya espera
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS configuracion (
  clave VARCHAR(50) PRIMARY KEY,
  valor TEXT NOT NULL,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS intentos_login (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  email VARCHAR(100) NOT NULL,
  exito BOOLEAN NOT NULL DEFAULT FALSE,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_intentos_ip_fecha (ip, fecha)
);

-- ------------------------------------------------------------
-- 2. Catálogo de productos (60) — precios en Bs
-- ------------------------------------------------------------

  UPDATE productos SET nombre = 'Arroz Grano de Oro 5kg', categoria_id = 1, descripcion = 'Arroz blanco de grano largo, bolsa familiar de 5 kg', precio = 45.00, stock = 40, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771495/o5ppzczbmqgbusidjhrw.webp' WHERE id = 1;
  UPDATE productos SET nombre = 'Aceite Fino 1L', categoria_id = 1, descripcion = 'Aceite vegetal comestible, botella de 1 litro', precio = 14.50, stock = 55, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771495/ory0bpworaunwzmkydwu.webp' WHERE id = 2;
  UPDATE productos SET nombre = 'Azúcar Blanca 1kg', categoria_id = 1, descripcion = 'Azúcar refinada, bolsa de 1 kg', precio = 8.00, stock = 70, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771496/hvgxh20ocotdsxfkwgv6.webp' WHERE id = 3;
  UPDATE productos SET nombre = 'Sal Emsal 1kg', categoria_id = 1, descripcion = 'Sal yodada para cocina, bolsa de 1 kg', precio = 3.50, stock = 85, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771498/rdw8bgenqmmugu6cxokd.webp' WHERE id = 4;
  UPDATE productos SET nombre = 'Fideos Corbatas 500g', categoria_id = 1, descripcion = 'Fideos de trigo para sopa y guisos', precio = 6.00, stock = 90, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771497/bbhfdotprqao7notu2cs.webp' WHERE id = 5;
  UPDATE productos SET nombre = 'Harina de Trigo 1kg', categoria_id = 1, descripcion = 'Harina de trigo todo uso, bolsa de 1 kg', precio = 7.50, stock = 70, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771497/jnrfxfjcanlfw4tgjqcv.webp' WHERE id = 6;
  UPDATE productos SET nombre = 'Lentejas 1kg', categoria_id = 1, descripcion = 'Lentejas secas seleccionadas, bolsa de 1 kg', precio = 12.00, stock = 55, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771498/jktqm5njivrlcphtd68l.webp' WHERE id = 7;
  UPDATE productos SET nombre = 'Arvejas Secas 1kg', categoria_id = 1, descripcion = 'Arvejas secas partidas, bolsa de 1 kg', precio = 10.00, stock = 50, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771496/bavuldj4j6zxuqcjirvh.webp' WHERE id = 8;
  UPDATE productos SET nombre = 'Leche Pil Entera 1L', categoria_id = 2, descripcion = 'Leche entera UHT, botella de 1 litro', precio = 8.00, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771511/icj0t9vobzkba7anedax.webp' WHERE id = 9;
  UPDATE productos SET nombre = 'Leche Pil en Polvo 380g', categoria_id = 2, descripcion = 'Leche en polvo entera, bolsa de 380 g', precio = 15.00, stock = 45, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771512/oiyslpgxa8err607tjks.webp' WHERE id = 10;
  UPDATE productos SET nombre = 'Yogur Pil 1L', categoria_id = 2, descripcion = 'Yogur natural cremoso, frasco de 1 litro', precio = 12.50, stock = 40, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771513/w5crdulehk0x8baggq8g.webp' WHERE id = 11;
  UPDATE productos SET nombre = 'Queso Fresco Menonita 500g', categoria_id = 2, descripcion = 'Queso fresco artesanal de Santa Cruz', precio = 22.00, stock = 25, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771513/qf5mtrrpzu0od06pvuqo.webp' WHERE id = 12;
  UPDATE productos SET nombre = 'Mantequilla Pil 200g', categoria_id = 2, descripcion = 'Mantequilla con sal, tableta de 200 g', precio = 14.00, stock = 30, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771512/fmnxs0youqy8gq167lrw.webp' WHERE id = 13;
  UPDATE productos SET nombre = 'Dulce de Leche Pil 500g', categoria_id = 2, descripcion = 'Dulce de leche repostero, frasco de 500 g', precio = 16.00, stock = 35, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771511/ycm9lgft6cpi2micif85.webp' WHERE id = 14;
  UPDATE productos SET nombre = 'Crema de Leche Pil 200ml', categoria_id = 2, descripcion = 'Crema espesa para cocinar y repostería', precio = 11.00, stock = 28, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771510/c3tmhkyusntyzsplajxc.webp' WHERE id = 15;
  UPDATE productos SET nombre = 'Queso Crema 200g', categoria_id = 2, descripcion = 'Queso crema untable, pote de 200 g', precio = 13.50, stock = 4, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771512/mr3zrq0b1ceqmk0fgdsq.webp' WHERE id = 16;
  UPDATE productos SET nombre = 'Agua Fátima 2L', categoria_id = 3, descripcion = 'Agua mineral sin gas, botella de 2 litros', precio = 5.50, stock = 80, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771498/ncp5k0stj6o3n4mlblkv.webp' WHERE id = 17;
  UPDATE productos SET nombre = 'Coca-Cola 2L', categoria_id = 3, descripcion = 'Gaseosa Coca-Cola, botella de 2 litros', precio = 13.00, stock = 60, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771500/t3crv1bbn591cpsooze0.webp' WHERE id = 18;
  UPDATE productos SET nombre = 'Cerveza Paceña 330ml', categoria_id = 3, descripcion = 'Cerveza rubia, botella de 330 ml', precio = 10.00, stock = 48, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771499/glywx6pwvzwfjmntk7rq.webp' WHERE id = 19;
  UPDATE productos SET nombre = 'Cerveza Taquiña 330ml', categoria_id = 3, descripcion = 'Cerveza cochabambina, botella de 330 ml', precio = 10.00, stock = 44, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771500/xrpdkxk15dj0td3gfmbk.webp' WHERE id = 20;
  UPDATE productos SET nombre = 'Café Copacabana 250g', categoria_id = 3, descripcion = 'Café molido de los Yungas, bolsa de 250 g', precio = 18.00, stock = 35, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771499/qhypi9prmwvzlim47so1.webp' WHERE id = 21;
  UPDATE productos SET nombre = 'Jugo Del Valle 1L', categoria_id = 3, descripcion = 'Néctar de fruta, botella de 1 litro', precio = 12.00, stock = 45, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771501/swpioge04xtcxfs6c1oq.webp' WHERE id = 22;
  UPDATE productos SET nombre = 'Fanta 2L', categoria_id = 3, descripcion = 'Gaseosa sabor naranja, botella de 2 litros', precio = 12.00, stock = 42, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771500/tkyokmv8aax27ku8baka.webp' WHERE id = 23;
  UPDATE productos SET nombre = 'Detergente Ariel 1kg', categoria_id = 4, descripcion = 'Detergente en polvo para ropa, bolsa de 1 kg', precio = 20.00, stock = 55, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771508/ozxajeeg8o4mihgqlten.webp' WHERE id = 24;
  UPDATE productos SET nombre = 'Cloro Clorox 1L', categoria_id = 4, descripcion = 'Cloro desinfectante, botella de 1 litro', precio = 9.00, stock = 70, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771507/btd2g7lqzwgvzco4rvso.webp' WHERE id = 25;
  UPDATE productos SET nombre = 'Lavavajillas Magistral 500ml', categoria_id = 4, descripcion = 'Lavavajillas líquido con aroma limón', precio = 12.00, stock = 40, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771509/tezhud5e4bzlkermpwbc.webp' WHERE id = 26;
  UPDATE productos SET nombre = 'Jabón de Lavar 3 und', categoria_id = 4, descripcion = 'Jabón en barra para ropa, pack de 3', precio = 9.00, stock = 65, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771508/gu6ye77ufjszqmqrogxn.webp' WHERE id = 27;
  UPDATE productos SET nombre = 'Papel Higiénico Scott 4 rollos', categoria_id = 4, descripcion = 'Papel higiénico doble hoja, pack de 4', precio = 12.00, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771509/yiw6dsznkomtsnrzrgmj.webp' WHERE id = 28;
  UPDATE productos SET nombre = 'Desinfectante Magistral 900ml', categoria_id = 4, descripcion = 'Desinfectante multiusos, botella de 900 ml', precio = 13.00, stock = 50, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771508/qtslfhp9rbjyq9tayasi.webp' WHERE id = 29;
  UPDATE productos SET nombre = 'Suavizante Downy 750ml', categoria_id = 4, descripcion = 'Suavizante de ropa, botella de 750 ml', precio = 15.00, stock = 45, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771510/atdesoeph12pizskkfhj.webp' WHERE id = 30;
  UPDATE productos SET nombre = 'Shampoo Sedal 340ml', categoria_id = 5, descripcion = 'Shampoo para cabello normal, 340 ml', precio = 18.00, stock = 60, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771506/mccsgfmm66h3mgaubv5r.webp' WHERE id = 31;
  UPDATE productos SET nombre = 'Cepillo Dental Colgate 2 und', categoria_id = 5, descripcion = 'Cepillos de dientes suaves, pack de 2', precio = 12.00, stock = 70, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771505/mlzkrdp3ut4zeoyutgkh.webp' WHERE id = 33;
  UPDATE productos SET nombre = 'Desodorante Rexona 150ml', categoria_id = 5, descripcion = 'Desodorante en aerosol, 150 ml', precio = 20.00, stock = 50, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771506/n6bvicfdvqu7m8sp0dyx.webp' WHERE id = 34;
  UPDATE productos SET nombre = 'Toallitas Húmedas 80 und', categoria_id = 5, descripcion = 'Toallitas húmedas para bebé, 80 unidades', precio = 14.00, stock = 40, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771507/binr9skblbwvdhertzsr.webp' WHERE id = 35;
  UPDATE productos SET nombre = 'Pañales Huggies 30 und', categoria_id = 5, descripcion = 'Pañales desechables talla M, pack de 30', precio = 55.00, stock = 3, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771506/duk9nyq7kfra6ioeuxcs.webp' WHERE id = 36;
  UPDATE productos SET nombre = 'Alcohol 70% 500ml', categoria_id = 5, descripcion = 'Alcohol etílico desinfectante, 500 ml', precio = 10.00, stock = 65, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771505/idpjxeo9s0cei9ekatn2.webp' WHERE id = 37;
  UPDATE productos SET nombre = 'Papas Fritas Lays 150g', categoria_id = 6, descripcion = 'Papas fritas sabor natural, bolsa de 150 g', precio = 15.00, stock = 60, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771520/uqjdir9ucn8cbzla2ate.webp' WHERE id = 38;
  UPDATE productos SET nombre = 'Chicharrón Boliviano 250g', categoria_id = 6, descripcion = 'Chicharrón de cerdo crocante, 250 g', precio = 18.00, stock = 35, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771518/lfuzbiuda08hrcz3kjfw.webp' WHERE id = 39;
  UPDATE productos SET nombre = 'Maní Salado 400g', categoria_id = 6, descripcion = 'Maní tostado y salado, bolsa de 400 g', precio = 10.00, stock = 55, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771519/wf2teg3iyauygg1izzll.webp' WHERE id = 40;
  UPDATE productos SET nombre = 'Chocolate Taboada 100g', categoria_id = 6, descripcion = 'Chocolate boliviano relleno, tableta de 100 g', precio = 12.00, stock = 70, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771518/harszxvnyjrkhmsnvwe3.webp' WHERE id = 41;
  UPDATE productos SET nombre = 'Galleta de Agua 300g', categoria_id = 6, descripcion = 'Galletas de agua, paquete de 300 g', precio = 8.00, stock = 80, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771518/qg6pxkptmnbxh9kunjc3.webp' WHERE id = 42;
  UPDATE productos SET nombre = 'Palomitas de Maíz 100g', categoria_id = 6, descripcion = 'Maíz pisingallo para microondas', precio = 7.00, stock = 65, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771520/pqegaufojppnpdkkfkbp.webp' WHERE id = 43;
  UPDATE productos SET nombre = 'Alfajor Boliviano 6 und', categoria_id = 6, descripcion = 'Alfajores de dulce de leche, pack de 6', precio = 12.00, stock = 45, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771517/bpzlnizs8v1ftquml1gh.webp' WHERE id = 44;
  UPDATE productos SET nombre = 'Mix Frutos Secos 500g', categoria_id = 6, descripcion = 'Mezcla de nueces, pasas y almendras', precio = 25.00, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771519/aaugmtmny5nmdak5kzgk.webp' WHERE id = 45;
  UPDATE productos SET nombre = 'Atún en Aceite 170g', categoria_id = 7, descripcion = 'Atún en aceite vegetal, lata de 170 g', precio = 12.00, stock = 50, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771501/vyyhbuksh7imsysgvrgx.webp' WHERE id = 46;
  UPDATE productos SET nombre = 'Sardinas en Tomate 425g', categoria_id = 7, descripcion = 'Sardinas en salsa de tomate, lata de 425 g', precio = 15.00, stock = 40, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771503/j6zprgcjwzofwr2jnnay.webp' WHERE id = 47;
  UPDATE productos SET nombre = 'Frijoles Enlatados 400g', categoria_id = 7, descripcion = 'Frijoles cocidos, lata de 400 g', precio = 9.00, stock = 60, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771502/kpbl9qih4dnk6x4cjjef.webp' WHERE id = 48;
  UPDATE productos SET nombre = 'Tomate Pelado 400g', categoria_id = 7, descripcion = 'Tomates pelados enteros, lata de 400 g', precio = 8.50, stock = 55, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771504/xsait1zpmyupkznurkgo.webp' WHERE id = 49;
  UPDATE productos SET nombre = 'Maíz Dulce 400g', categoria_id = 7, descripcion = 'Granos de maíz dulce, lata de 400 g', precio = 9.00, stock = 50, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771503/x9nf8bzp383lwglacjw4.webp' WHERE id = 50;
  UPDATE productos SET nombre = 'Duraznos en Almíbar 820g', categoria_id = 7, descripcion = 'Duraznos en almíbar, lata de 820 g', precio = 18.00, stock = 30, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771502/yr5brbcnijy1y97vcne5.webp' WHERE id = 51;
  UPDATE productos SET nombre = 'Leche Condensada Pil 397g', categoria_id = 7, descripcion = 'Leche condensada azucarada, 397 g', precio = 16.00, stock = 35, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771502/xpzfoesry0ytfxqzyxbf.webp' WHERE id = 52;
  UPDATE productos SET nombre = 'Pan de Molde Integral 500g', categoria_id = 8, descripcion = 'Pan de molde integral, 500 g', precio = 12.00, stock = 40, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771515/qre88jbwhvvrqvhrz0ti.webp' WHERE id = 53;
  UPDATE productos SET nombre = 'Pan Francés 6 und', categoria_id = 8, descripcion = 'Pan francés crocante, pack de 6', precio = 6.00, stock = 60, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771516/nvy1ivtdo5nt7zvv7ctc.webp' WHERE id = 54;
  UPDATE productos SET nombre = 'Empanada Salteña 6 und', categoria_id = 8, descripcion = 'Salteñas de carne, pack de 6', precio = 18.00, stock = 24, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771515/qre88jbwhvvrqvhrz0ti.webp' WHERE id = 55;
  UPDATE productos SET nombre = 'Cuñapé 6 und', categoria_id = 8, descripcion = 'Cuñapé de queso, pack de 6', precio = 15.00, stock = 20, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771514/h10yz1qcn7a84s0sz9ma.webp' WHERE id = 56;
  UPDATE productos SET nombre = 'Pan Dulce 500g', categoria_id = 8, descripcion = 'Pan dulce artesanal, 500 g', precio = 10.00, stock = 35, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771516/sqpm6slxrypq3o02298h.webp' WHERE id = 57;
  UPDATE productos SET nombre = 'Torta de Chocolate 450g', categoria_id = 8, descripcion = 'Torta de chocolate para compartir', precio = 35.00, stock = 12, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771517/crgom4t7cl5jfdncuqfe.webp' WHERE id = 58;
  UPDATE productos SET nombre = 'Bizcocho de Vainilla 300g', categoria_id = 8, descripcion = 'Bizcocho esponjoso de vainilla', precio = 18.00, stock = 15, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771514/rlwektndejeeffcw0vsu.webp' WHERE id = 59;
  UPDATE productos SET nombre = 'Pan de Ajo 400g', categoria_id = 8, descripcion = 'Pan de ajo con mantequilla', precio = 13.00, stock = 3, imagen = 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771515/yssajpu8fzo0oskipus2.webp' WHERE id = 60;

-- ------------------------------------------------------------
-- 3. Cupones en Bs
-- ------------------------------------------------------------

UPDATE cupones SET activo = TRUE, minimo_compra = 0 WHERE codigo = 'BIENVENIDO10';
UPDATE cupones SET activo = TRUE, porcentaje_descuento = 20, minimo_compra = 100 WHERE codigo = 'COMPRA20';
UPDATE cupones SET activo = TRUE, porcentaje_descuento = 5, minimo_compra = 0,
  expira_en = DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE codigo = 'NINA5';

-- ------------------------------------------------------------
-- 4. Usuario administrador
--    Email: admin@gmail.com
--    Contraseña inicial: Losnina123
--    Cámbiala desde el panel: admin.php?tab=cuenta
-- ------------------------------------------------------------

INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES
('Admin Los Nina', 'admin@gmail.com', '$2y$10$2AkvFs/t05Gny8WE/aUKWeVBPjibUROM.C7dYY0rphniJ38lNDZZS', 'admin')
ON DUPLICATE KEY UPDATE
  password_hash = VALUES(password_hash),
  rol = 'admin';

UPDATE usuarios
SET rol = 'cliente'
WHERE email = 'admin@losnina.com';

-- ------------------------------------------------------------
-- 5. Configuración del negocio
--    Solo inserta las claves que falten: si ya existe un valor
--    cargado desde el panel, NO se sobrescribe.
-- ------------------------------------------------------------

INSERT INTO configuracion (clave, valor) VALUES
  ('negocio_nombre', 'Abarrotería Los Nina'),
  ('negocio_eslogan', 'Tu tienda de barrio de confianza'),
  ('horario', 'Lun a Sáb · 8:00 – 21:00'),
  ('moneda_simbolo', 'Bs'),
  ('ciudad', ''),
  ('direccion', ''),
  ('telefono', ''),
  ('whatsapp', ''),
  ('email_contacto', ''),
  ('costo_delivery', ''),
  ('delivery_gratis_desde', '')
ON DUPLICATE KEY UPDATE valor = configuracion.valor;

-- ------------------------------------------------------------
-- 6. Verificación
-- ------------------------------------------------------------

-- Debe devolver 8, 8, 7, 7, 7, 8, 7, 8 (una fila por categoría)
SELECT c.id, c.nombre, COUNT(p.id) AS productos
FROM categorias c
LEFT JOIN productos p ON p.categoria_id = c.id
GROUP BY c.id, c.nombre
ORDER BY c.id;

-- Debe devolver 60
SELECT COUNT(*) AS total_productos FROM productos;

-- Muestra los productos con stock bajo (< 5) para probar "¡Últimas unidades!"
SELECT id, nombre, stock FROM productos WHERE stock < 5 ORDER BY id;

-- Debe devolver 3 cupones activos con montos en Bs
SELECT codigo, porcentaje_descuento, minimo_compra, activo, expira_en
FROM cupones ORDER BY codigo;

-- Debe devolver 2 tablas nuevas
SELECT table_name FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN ('configuracion', 'intentos_login')
ORDER BY table_name;

-- Debe devolver el administrador con rol admin
SELECT id, nombre, email, rol
FROM usuarios
WHERE email = 'admin@gmail.com';
