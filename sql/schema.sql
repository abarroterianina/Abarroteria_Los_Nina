-- ============================================================
-- Abarrotería Los Nina — Esquema de Base de Datos
-- Moneda: bolivianos (Bs). Precios y cupones en Bs.
--
-- IMPORTANTE (TiDB Cloud Serverless):
--   No se puede crear la base de datos desde el SQL Editor.
--   Selecciona la base existente (por ejemplo `github_sample`)
--   antes de pegar este archivo.
-- ============================================================

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
  imagen VARCHAR(255) NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_productos_nombre (nombre),
  FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Compras
CREATE TABLE compras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  total DECIMAL(10,2) NOT NULL,
  descuento_aplicado DECIMAL(10,2) DEFAULT 0,
  cupon_usado VARCHAR(50) NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('pendiente', 'completada', 'cancelada') DEFAULT 'completada',
  KEY idx_compras_fecha (fecha),
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
  KEY idx_reservas_fecha (fecha_reserva),
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

-- Configuración editable del negocio (clave/valor)
CREATE TABLE configuracion (
  clave VARCHAR(50) PRIMARY KEY,
  valor TEXT NOT NULL,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Intentos de login (límite de fuerza bruta)
CREATE TABLE intentos_login (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  email VARCHAR(100) NOT NULL,
  exito BOOLEAN NOT NULL DEFAULT FALSE,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_intentos_ip_fecha (ip, fecha)
);

-- ============================================================
-- Datos semilla (moneda: bolivianos)
-- ============================================================

-- Categorías (8)
INSERT INTO categorias (nombre) VALUES
('Abarrotes'),
('Lácteos'),
('Bebidas'),
('Limpieza'),
('Higiene'),
('Snacks'),
('Enlatados'),
('Panadería');

-- Usuario admin (contraseña inicial: NinaBolivia#2026 — cámbiala desde el panel)
INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES
('Admin Los Nina', 'admin@losnina.com', '$2y$10$dIi/6qQ0Luj/zhM4E66P5erHA7VzHNYc4kB2AVwJaE.CpvNEdNrea', 'admin');

-- Cupones (montos en Bs)
INSERT INTO cupones (codigo, porcentaje_descuento, minimo_compra, activo, expira_en) VALUES
('BIENVENIDO10', 10, 0, TRUE, NULL),
('COMPRA20', 20, 100, TRUE, NULL),
('NINA5', 5, 0, TRUE, DATE_ADD(CURDATE(), INTERVAL 30 DAY));

-- Configuración del negocio (completar desde el panel admin)
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
('delivery_gratis_desde', '');

-- Productos (60) — marcas y precios de referencia en bolivianos
INSERT INTO productos (nombre, categoria_id, descripcion, precio, stock, imagen) VALUES
-- Abarrotes (8)
('Arroz Grano de Oro 5kg', 1, 'Arroz blanco de grano largo, bolsa familiar de 5 kg', 45.00, 40, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771495/o5ppzczbmqgbusidjhrw.webp'),
('Aceite Fino 1L', 1, 'Aceite vegetal comestible, botella de 1 litro', 14.50, 55, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771495/ory0bpworaunwzmkydwu.webp'),
('Azúcar Blanca 1kg', 1, 'Azúcar refinada, bolsa de 1 kg', 8.00, 70, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771496/hvgxh20ocotdsxfkwgv6.webp'),
('Sal Emsal 1kg', 1, 'Sal yodada para cocina, bolsa de 1 kg', 3.50, 85, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771498/rdw8bgenqmmugu6cxokd.webp'),
('Fideos Corbatas 500g', 1, 'Fideos de trigo para sopa y guisos', 6.00, 90, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771497/bbhfdotprqao7notu2cs.webp'),
('Harina de Trigo 1kg', 1, 'Harina de trigo todo uso, bolsa de 1 kg', 7.50, 70, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771497/jnrfxfjcanlfw4tgjqcv.webp'),
('Lentejas 1kg', 1, 'Lentejas secas seleccionadas, bolsa de 1 kg', 12.00, 55, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771498/jktqm5njivrlcphtd68l.webp'),
('Arvejas Secas 1kg', 1, 'Arvejas secas partidas, bolsa de 1 kg', 10.00, 50, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771496/bavuldj4j6zxuqcjirvh.webp'),

-- Lácteos (8)
('Leche Pil Entera 1L', 2, 'Leche entera UHT, botella de 1 litro', 8.00, 90, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771511/icj0t9vobzkba7anedax.webp'),
('Leche Pil en Polvo 380g', 2, 'Leche en polvo entera, bolsa de 380 g', 15.00, 45, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771512/oiyslpgxa8err607tjks.webp'),
('Yogur Pil 1L', 2, 'Yogur natural cremoso, frasco de 1 litro', 12.50, 40, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771513/w5crdulehk0x8baggq8g.webp'),
('Queso Fresco Menonita 500g', 2, 'Queso fresco artesanal de Santa Cruz', 22.00, 25, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771513/qf5mtrrpzu0od06pvuqo.webp'),
('Mantequilla Pil 200g', 2, 'Mantequilla con sal, tableta de 200 g', 14.00, 30, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771512/fmnxs0youqy8gq167lrw.webp'),
('Dulce de Leche Pil 500g', 2, 'Dulce de leche repostero, frasco de 500 g', 16.00, 35, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771511/ycm9lgft6cpi2micif85.webp'),
('Crema de Leche Pil 200ml', 2, 'Crema espesa para cocinar y repostería', 11.00, 28, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771510/c3tmhkyusntyzsplajxc.webp'),
('Queso Crema 200g', 2, 'Queso crema untable, pote de 200 g', 13.50, 4, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771512/mr3zrq0b1ceqmk0fgdsq.webp'),

-- Bebidas (7)
('Agua Fátima 2L', 3, 'Agua mineral sin gas, botella de 2 litros', 5.50, 80, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771498/ncp5k0stj6o3n4mlblkv.webp'),
('Coca-Cola 2L', 3, 'Gaseosa Coca-Cola, botella de 2 litros', 13.00, 60, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771500/t3crv1bbn591cpsooze0.webp'),
('Cerveza Paceña 330ml', 3, 'Cerveza rubia, botella de 330 ml', 10.00, 48, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771499/glywx6pwvzwfjmntk7rq.webp'),
('Cerveza Taquiña 330ml', 3, 'Cerveza cochabambina, botella de 330 ml', 10.00, 44, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771500/xrpdkxk15dj0td3gfmbk.webp'),
('Café Copacabana 250g', 3, 'Café molido de los Yungas, bolsa de 250 g', 18.00, 35, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771499/qhypi9prmwvzlim47so1.webp'),
('Jugo Del Valle 1L', 3, 'Néctar de fruta, botella de 1 litro', 12.00, 45, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771501/swpioge04xtcxfs6c1oq.webp'),
('Fanta 2L', 3, 'Gaseosa sabor naranja, botella de 2 litros', 12.00, 42, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771500/tkyokmv8aax27ku8baka.webp'),

-- Limpieza (7)
('Detergente Ariel 1kg', 4, 'Detergente en polvo para ropa, bolsa de 1 kg', 20.00, 55, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771508/ozxajeeg8o4mihgqlten.webp'),
('Cloro Clorox 1L', 4, 'Cloro desinfectante, botella de 1 litro', 9.00, 70, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771507/btd2g7lqzwgvzco4rvso.webp'),
('Lavavajillas Magistral 500ml', 4, 'Lavavajillas líquido con aroma limón', 12.00, 40, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771509/tezhud5e4bzlkermpwbc.webp'),
('Jabón de Lavar 3 und', 4, 'Jabón en barra para ropa, pack de 3', 9.00, 65, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771508/gu6ye77ufjszqmqrogxn.webp'),
('Papel Higiénico Scott 4 rollos', 4, 'Papel higiénico doble hoja, pack de 4', 12.00, 85, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771509/yiw6dsznkomtsnrzrgmj.webp'),
('Desinfectante Magistral 900ml', 4, 'Desinfectante multiusos, botella de 900 ml', 13.00, 50, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771508/qtslfhp9rbjyq9tayasi.webp'),
('Suavizante Downy 750ml', 4, 'Suavizante de ropa, botella de 750 ml', 15.00, 45, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771510/atdesoeph12pizskkfhj.webp'),

-- Higiene (7)
('Shampoo Sedal 340ml', 5, 'Shampoo para cabello normal, 340 ml', 18.00, 60, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771506/mccsgfmm66h3mgaubv5r.webp'),
('Cepillo Dental Colgate 2 und', 5, 'Cepillos de dientes suaves, pack de 2', 12.00, 70, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771505/mlzkrdp3ut4zeoyutgkh.webp'),
('Desodorante Rexona 150ml', 5, 'Desodorante en aerosol, 150 ml', 20.00, 50, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771506/n6bvicfdvqu7m8sp0dyx.webp'),
('Toallitas Húmedas 80 und', 5, 'Toallitas húmedas para bebé, 80 unidades', 14.00, 40, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771507/binr9skblbwvdhertzsr.webp'),
('Pañales Huggies 30 und', 5, 'Pañales desechables talla M, pack de 30', 55.00, 3, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771506/duk9nyq7kfra6ioeuxcs.webp'),
('Alcohol 70% 500ml', 5, 'Alcohol etílico desinfectante, 500 ml', 10.00, 65, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771505/idpjxeo9s0cei9ekatn2.webp'),

-- Snacks (8)
('Papas Fritas Lays 150g', 6, 'Papas fritas sabor natural, bolsa de 150 g', 15.00, 60, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771520/uqjdir9ucn8cbzla2ate.webp'),
('Chicharrón Boliviano 250g', 6, 'Chicharrón de cerdo crocante, 250 g', 18.00, 35, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771518/lfuzbiuda08hrcz3kjfw.webp'),
('Maní Salado 400g', 6, 'Maní tostado y salado, bolsa de 400 g', 10.00, 55, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771519/wf2teg3iyauygg1izzll.webp'),
('Chocolate Taboada 100g', 6, 'Chocolate boliviano relleno, tableta de 100 g', 12.00, 70, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771518/harszxvnyjrkhmsnvwe3.webp'),
('Galleta de Agua 300g', 6, 'Galletas de agua, paquete de 300 g', 8.00, 80, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771518/qg6pxkptmnbxh9kunjc3.webp'),
('Palomitas de Maíz 100g', 6, 'Maíz pisingallo para microondas', 7.00, 65, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771520/pqegaufojppnpdkkfkbp.webp'),
('Alfajor Boliviano 6 und', 6, 'Alfajores de dulce de leche, pack de 6', 12.00, 45, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771517/bpzlnizs8v1ftquml1gh.webp'),
('Mix Frutos Secos 500g', 6, 'Mezcla de nueces, pasas y almendras', 25.00, 30, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771519/aaugmtmny5nmdak5kzgk.webp'),

-- Enlatados (7)
('Atún en Aceite 170g', 7, 'Atún en aceite vegetal, lata de 170 g', 12.00, 50, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771501/vyyhbuksh7imsysgvrgx.webp'),
('Sardinas en Tomate 425g', 7, 'Sardinas en salsa de tomate, lata de 425 g', 15.00, 40, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771503/j6zprgcjwzofwr2jnnay.webp'),
('Frijoles Enlatados 400g', 7, 'Frijoles cocidos, lata de 400 g', 9.00, 60, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771502/kpbl9qih4dnk6x4cjjef.webp'),
('Tomate Pelado 400g', 7, 'Tomates pelados enteros, lata de 400 g', 8.50, 55, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771504/xsait1zpmyupkznurkgo.webp'),
('Maíz Dulce 400g', 7, 'Granos de maíz dulce, lata de 400 g', 9.00, 50, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771503/x9nf8bzp383lwglacjw4.webp'),
('Duraznos en Almíbar 820g', 7, 'Duraznos en almíbar, lata de 820 g', 18.00, 30, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771502/yr5brbcnijy1y97vcne5.webp'),
('Leche Condensada Pil 397g', 7, 'Leche condensada azucarada, 397 g', 16.00, 35, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771502/xpzfoesry0ytfxqzyxbf.webp'),

-- Panadería (8)
('Pan de Molde Integral 500g', 8, 'Pan de molde integral, 500 g', 12.00, 40, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771515/qre88jbwhvvrqvhrz0ti.webp'),
('Pan Francés 6 und', 8, 'Pan francés crocante, pack de 6', 6.00, 60, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771516/nvy1ivtdo5nt7zvv7ctc.webp'),
('Cuñapé 6 und', 8, 'Cuñapé de queso, pack de 6', 15.00, 20, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771514/h10yz1qcn7a84s0sz9ma.webp'),
('Pan Dulce 500g', 8, 'Pan dulce artesanal, 500 g', 10.00, 35, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771516/sqpm6slxrypq3o02298h.webp'),
('Torta de Chocolate 450g', 8, 'Torta de chocolate para compartir', 35.00, 12, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771517/crgom4t7cl5jfdncuqfe.webp'),
('Bizcocho de Vainilla 300g', 8, 'Bizcocho esponjoso de vainilla', 18.00, 15, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771514/rlwektndejeeffcw0vsu.webp'),
('Pan de Ajo 400g', 8, 'Pan de ajo con mantequilla', 13.00, 3, 'https://res.cloudinary.com/bupbgq1u/image/upload/v1794771515/yssajpu8fzo0oskipus2.webp');
