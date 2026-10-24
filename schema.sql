-- Bookstore schema -- MySQL 8 / MariaDB 10.4+, InnoDB, utf8mb4
-- Run once at install: mysql -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS bookstore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bookstore;

-- App users; role gates the admin area, email is the login identifier
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- Catalog; stock is decremented at checkout and must never go negative
CREATE TABLE books (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title       VARCHAR(255) NOT NULL,
  author      VARCHAR(150) NOT NULL,
  category    VARCHAR(80)  NOT NULL,
  price       DECIMAL(10,2) NOT NULL,
  stock       INT NOT NULL DEFAULT 0,
  description TEXT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_books_title (title),
  KEY idx_books_category (category),
  CONSTRAINT chk_books_price CHECK (price >= 0),
  CONSTRAINT chk_books_stock CHECK (stock >= 0)
) ENGINE=InnoDB;

-- Orders are financial records: RESTRICT stops a user delete from erasing order history
CREATE TABLE orders (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  total      DECIMAL(10,2) NOT NULL,
  status     ENUM('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  name       VARCHAR(100) NOT NULL,
  phone      VARCHAR(20)  NOT NULL,
  address    VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_orders_user (user_id),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_orders_total CHECK (total >= 0)
) ENGINE=InnoDB;

-- Items die with their order (CASCADE) but a book that was ever ordered cannot be deleted (RESTRICT); price is a snapshot so later price edits never rewrite past orders
CREATE TABLE order_items (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  book_id  INT UNSIGNED NOT NULL,
  qty      INT NOT NULL,
  price    DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_items_order (order_id),
  KEY idx_items_book (book_id),
  CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_items_book FOREIGN KEY (book_id) REFERENCES books (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_items_qty CHECK (qty > 0),
  CONSTRAINT chk_items_price CHECK (price >= 0)
) ENGINE=InnoDB;

-- SEED: admin account -- do NOT uncomment with a real password here; at install time generate the hash and insert once, e.g. in PHP: password_hash('CHANGE_ME', PASSWORD_DEFAULT)
-- INSERT INTO users (name, email, password_hash, role) VALUES ('Admin', 'admin@example.com', '<bcrypt-hash-generated-at-install>', 'admin');

-- SEED: sample catalog (prices in PKR)
INSERT INTO books (title, author, category, price, stock, description) VALUES
('The Hobbit', 'J.R.R. Tolkien', 'Fantasy', 14.99, 25, 'Bilbo Baggins is swept from his comfortable hobbit-hole into a quest for dragon-guarded treasure.'),
('Pride and Prejudice', 'Jane Austen', 'Classic', 9.99, 30, 'Elizabeth Bennet spars with the proud Mr Darcy in Austen''s sharp comedy of manners and marriage.'),
('A Brief History of Time', 'Stephen Hawking', 'Science', 16.99, 18, 'Hawking explores black holes, the Big Bang and the nature of time for the general reader.'),
('Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', 'History', 18.99, 22, 'How an unremarkable ape came to rule the planet, from the Cognitive Revolution to the age of algorithms.'),
('The Alchemist', 'Paulo Coelho', 'Fiction', 12.99, 40, 'Santiago, an Andalusian shepherd, journeys to Egypt in search of treasure and his Personal Legend.'),
('Atomic Habits', 'James Clear', 'Self-Help', 17.49, 35, 'Tiny changes, remarkable results: a practical framework for building good habits and breaking bad ones.'),
('Harry Potter and the Philosopher''s Stone', 'J.K. Rowling', 'Fantasy', 13.99, 28, 'A boy who thought he was ordinary discovers he is a wizard and starts at Hogwarts School of Witchcraft and Wizardry.'),
('The Diary of a Young Girl', 'Anne Frank', 'History', 11.99, 15, 'Anne Frank''s diary written in hiding in Amsterdam during the Second World War.'),
('Clean Code', 'Robert C. Martin', 'Technology', 34.99, 12, 'A handbook of agile software craftsmanship: writing code that is readable, testable and easy to change.'),
('Shah Jo Risalo', 'Shah Abdul Latif Bhittai (trans. Elsa Kazi)', 'Poetry', 8.99, 20, 'Selected surs from the great Sindhi Sufi poet, in Elsa Kazi''s classic English rendering.');
