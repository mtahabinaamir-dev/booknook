-- Bookstore schema -- SQLite translation of schema.sql (same tables, constraints and seed books)
CREATE TABLE users (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  name          TEXT NOT NULL,
  email         TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role          TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('user','admin')),
  created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE books (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  title       TEXT NOT NULL,
  author      TEXT NOT NULL,
  category    TEXT NOT NULL,
  price       NUMERIC NOT NULL CHECK (price >= 0),
  stock       INTEGER NOT NULL DEFAULT 0 CHECK (stock >= 0),
  description TEXT,
  created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_books_category ON books (category);
CREATE TABLE orders (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id    INTEGER NOT NULL REFERENCES users (id) ON DELETE RESTRICT,
  total      NUMERIC NOT NULL CHECK (total >= 0),
  status     TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','confirmed','shipped','delivered','cancelled')),
  name       TEXT NOT NULL,
  phone      TEXT NOT NULL,
  address    TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_orders_user ON orders (user_id);
CREATE TABLE order_items (
  id       INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL REFERENCES orders (id) ON DELETE CASCADE,
  book_id  INTEGER NOT NULL REFERENCES books (id) ON DELETE RESTRICT,
  qty      INTEGER NOT NULL CHECK (qty > 0),
  price    NUMERIC NOT NULL CHECK (price >= 0)
);
CREATE INDEX idx_items_order ON order_items (order_id);
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
