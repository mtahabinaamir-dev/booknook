# Bookstore — Database & Security Contract

The PHP developer follows this contract exactly. Schema: `schema.sql` (MySQL 8 / MariaDB, InnoDB, utf8mb4). Production runs on XAMPP (MySQL/MariaDB); automated tests run on SQLite via the same PDO code.

## PDO configuration (single shared connection, e.g. `db.php`)

```php
$pdo = new PDO($dsn, $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // fail loudly, never silently
    PDO::ATTR_EMULATE_PREPARES   => false,                  // real server-side prepares
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
// MySQL DSN must carry the charset: mysql:host=127.0.0.1;dbname=bookstore;charset=utf8mb4
```

- Credentials live outside the web root / in a config file that is never committed or echoed.
- No query may expose raw PDO exception messages to the browser; log them, show a generic error.

## Rule #1: prepared statements for EVERY query

No exceptions — not for "safe" values, not for IDs, not for search terms. Zero string interpolation, concatenation, or `sprintf` into SQL. Values go in as bound parameters only. `LIMIT`/`OFFSET` values are cast to `int` in PHP and bound with `PDO::PARAM_INT`. Column/table names never come from user input (sort order uses a fixed allow-list map in code).

```php
// BAD — injectable, never do this
$pdo->query("SELECT * FROM books WHERE title LIKE '%$q%'");

// GOOD — same query, value bound as a parameter
$stmt = $pdo->prepare('SELECT * FROM books WHERE title LIKE :q');
$stmt->execute(['q' => '%' . $q . '%']);
```

## Passwords

- Store only `password_hash($pw, PASSWORD_DEFAULT)` (bcrypt) output in `users.password_hash`. Never store, log, email, or display plaintext passwords.
- Verify logins with `password_verify($pw, $hash)` only. Never compare passwords in SQL.
- Minimum password length: 8 characters (validate at registration).
- Login throttling: after 5 failed attempts for an account/IP, delay or lock further attempts (e.g. 15 minutes); return the same generic "invalid email or password" message whether the email exists or not (no account enumeration).

## Sessions

- Session cookie: `HttpOnly` on, `SameSite=Lax`, `Secure` on whenever the site is served over HTTPS. Set via `session_cookie_set` params before `session_start()`.
- Call `session_regenerate_id(true)` immediately after successful login (and after any privilege change) to prevent session fixation.
- Admin gate: every admin page/action checks `$_SESSION['user']['role'] === 'admin'` server-side before doing anything; hide-links-in-UI is not a control.
- Cart lives in the session only; prices are always re-read from the `books` table at checkout, never trusted from the session or the request.

## CSRF

- Every POST form (login, register, cart, checkout, all admin actions) carries a per-session CSRF token in a hidden field; the handler compares it with `hash_equals($_SESSION['csrf'], $_POST['csrf'])` and rejects on mismatch. No state-changing GET requests.

## Output escaping (stored XSS)

- Every value printed into HTML goes through `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` — including book titles, authors, descriptions, user names and addresses coming back out of the database. Data is stored raw; escaping happens at output, every time, no exceptions for "admin-only" fields (admins can be phished too, and titles are user-visible).

## Least-privilege DB user

The app connects as a dedicated user, never as `root`:

```sql
CREATE USER 'bookstore_app'@'localhost' IDENTIFIED BY '<strong-random-password>';
GRANT SELECT, INSERT, UPDATE, DELETE ON bookstore.* TO 'bookstore_app'@'localhost';
```

No `FILE`, `SUPER`, `CREATE`, `DROP`, `GRANT`, or access to any other database. Schema changes are applied manually by the owner, not by the app account.

## Input validation contract (validate server-side, before any query)

| Field | Rule |
|---|---|
| name | string, 1–100 chars, trimmed |
| email | `filter_var(..., FILTER_VALIDATE_EMAIL)`, ≤190 chars, unique (DB constraint is the backstop) |
| password | ≥8 chars |
| book title / author | string, 1–255 / 1–150 chars |
| category | string, 1–80 chars |
| price | numeric, 0 ≤ price ≤ 99999999.99, stored as DECIMAL(10,2) — never float math for money |
| stock | integer, 0 ≤ stock (DB CHECK enforces ≥ 0) |
| qty (cart/order) | integer, 1–99 per line |
| phone | string, 7–20 chars, digits/spaces/`+`/`-` only |
| address | string, 10–500 chars |
| id parameters | positive integers (`filter_var(..., FILTER_VALIDATE_INT, min_range=1)`); non-numeric → 404/redirect, never a query |

## Checkout & stock (transaction — all or nothing)

Checkout runs in ONE transaction:

1. `beginTransaction()`
2. For each cart line: `SELECT price, stock FROM books WHERE id = ? FOR UPDATE` (row lock stops two buyers racing for the last copy).
3. Re-check `stock >= qty` inside the transaction; if any line fails, `rollBack()` and show "not enough stock" — never place a partial order.
4. Decrement with a guarded update: `UPDATE books SET stock = stock - ? WHERE id = ? AND stock >= ?` and confirm 1 row changed.
5. Compute the order total from the prices just read from the DB (snapshot price into `order_items.price`).
6. Insert `orders` + `order_items`, then `commit()`.

Client-submitted prices/totals are ignored entirely. Orders are created with status `pending` (cash on delivery — no payment data is ever collected or stored).

## SQLite (tests) ↔ MySQL portability rules

Tests run the same app queries against SQLite, so application SQL must stay portable:

- Use only standard SQL in app code: `SELECT/INSERT/UPDATE/DELETE` with `?` or `:name` placeholders, `LIKE`, `ORDER BY`, `LIMIT ? OFFSET ?` (supported by both).
- Avoid MySQL-only syntax in app queries: no `NOW()` (set `created_at` by DB default in both), no `ON DUPLICATE KEY UPDATE`, no `INSERT IGNORE`, no backtick quoting, no `ENUM` logic in SQL (validate role/status values in PHP; SQLite stores them as TEXT).
- `SELECT ... FOR UPDATE` is MySQL-only: in the SQLite test path, skip the lock clause (single-connection tests have no race); keep the guarded `UPDATE ... AND stock >= ?` as the real protection, it is portable.
- Types map as: `INT UNSIGNED AUTO_INCREMENT` → `INTEGER PRIMARY KEY AUTOINCREMENT`, `DECIMAL` → kept as text/numeric (SQLite NUMERIC affinity — compare prices as strings/cents in tests), `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` and `CHECK` constraints work in both. The SQLite test schema is a direct translation of `schema.sql` kept in the test folder, not a second source of truth for the design.
