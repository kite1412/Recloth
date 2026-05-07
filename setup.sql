-- Create database
CREATE DATABASE IF NOT EXISTS recloth;

-- Use database
USE recloth;

-- USERS
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  email VARCHAR(255) UNIQUE,
  password VARCHAR(255),
  address TEXT NULL,
  role VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CATEGORIES
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- PRODUCTS
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  description TEXT,
  gender ENUM('pria', 'wanita') NULL,
  condition_status VARCHAR(100) NULL,
  size_label VARCHAR(50) NULL,
  production_year YEAR NULL,
  material VARCHAR(120) NULL,
  image VARCHAR(255) NULL,
  price DECIMAL(10,2),
  stock INT,
  discount_percent INT DEFAULT 0,
  category_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE CASCADE
);

-- PRODUCT IMAGES (MULTI FOTO)
CREATE TABLE product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  image_url VARCHAR(255) NOT NULL,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE
);

-- CARTS
CREATE TABLE carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
);

-- CART ITEMS
CREATE TABLE cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cart_id INT,
  product_id INT,
  quantity INT,
  FOREIGN KEY (cart_id) REFERENCES carts(id)
    ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE
);

-- ORDERS
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  total_price DECIMAL(10,2),
  status VARCHAR(50),
  payment_method VARCHAR(100),
  payment_address VARCHAR(1000),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
);

-- ORDER ITEMS
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT,
  product_id INT,
  quantity INT,
  price DECIMAL(10,2),
  FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE
);

-- PAYMENTS
CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT,
  method VARCHAR(100),
  amount DECIMAL(10,2),
  status VARCHAR(50),
  paid_at TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE
);

-- DEFAULT ADMIN
INSERT INTO users (name, email, password, role)
VALUES ('Admin', 'admin@recloth.com', 'admin123', 'admin');

-- DEFAULT CATEGORIES FOR RECLOTH
INSERT INTO categories (name) VALUES
('kaos'),
('kemeja'),
('hoodie'),
('sweater'),
('celana'),
('rok'),
('jaket'),
('cardigan'),
('blazer'),
('topi'),
('sepatu'),
('ikat pinggang'),
('aksesoris');

-- DUMMY PRODUCT FOR DETAIL PAGE TEST
INSERT INTO products (
  name,
  description,
  gender,
  condition_status,
  size_label,
  production_year,
  material,
  image,
  price,
  stock,
  discount_percent,
  category_id
)
SELECT
  'Kemeja Coklat Vintage',
  'Kemeja warna coklat bernuansa vintage dengan potongan regular fit. Nyaman dipakai harian, cocok untuk gaya kasual maupun semi formal.',
  'pria',
  'Sangat Baik',
  'L',
  2021,
  'Katun Twill',
  'https://dummyimage.com/900x1100/8b5e3c/ffffff&text=Kemeja+Coklat+Utama',
  149000,
  6,
  25,
  c.id
FROM categories c
WHERE c.name = 'kemeja'
  AND NOT EXISTS (
    SELECT 1
    FROM products p
    WHERE p.name = 'Kemeja Coklat Vintage'
  )
LIMIT 1;

INSERT INTO product_images (product_id, image_url, sort_order)
SELECT
  p.id,
  'https://dummyimage.com/900x1100/7a4f31/ffffff&text=Kemeja+Coklat+Depan',
  1
FROM products p
WHERE p.name = 'Kemeja Coklat Vintage'
  AND NOT EXISTS (
    SELECT 1
    FROM product_images pi
    WHERE pi.product_id = p.id
      AND pi.sort_order = 1
  );

INSERT INTO product_images (product_id, image_url, sort_order)
SELECT
  p.id,
  'https://dummyimage.com/900x1100/6b442a/ffffff&text=Kemeja+Coklat+Belakang',
  2
FROM products p
WHERE p.name = 'Kemeja Coklat Vintage'
  AND NOT EXISTS (
    SELECT 1
    FROM product_images pi
    WHERE pi.product_id = p.id
      AND pi.sort_order = 2
  );

INSERT INTO product_images (product_id, image_url, sort_order)
SELECT
  p.id,
  'https://dummyimage.com/900x1100/5a3823/ffffff&text=Kemeja+Coklat+Detail+Bahan',
  3
FROM products p
WHERE p.name = 'Kemeja Coklat Vintage'
  AND NOT EXISTS (
    SELECT 1
    FROM product_images pi
    WHERE pi.product_id = p.id
      AND pi.sort_order = 3
  );