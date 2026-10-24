# BookNook

A small online bookstore in plain PHP 8 with PDO — no framework. Browse and search the catalog, manage a session cart, check out with cash on delivery, and run the shop from a small admin page.

## Run on XAMPP (MySQL)

1. Copy this folder into `htdocs` (e.g. `htdocs/bookstore`).
2. In phpMyAdmin, import `schema.sql` (creates the `bookstore` database, tables and 10 seed books).
3. Create the app database user and grant only what it needs:
   `CREATE USER 'bookstore_app'@'localhost' IDENTIFIED BY 'your-password';`
   `GRANT SELECT, INSERT, UPDATE, DELETE ON bookstore.* TO 'bookstore_app'@'localhost';`
4. In `config.php` set `driver` to `mysql` and fill in the password from step 3.
5. Open `http://localhost/bookstore/install.php` once, create the admin account, then **delete `install.php`**. If it shows a `login_attempts` SQL line, run that once in phpMyAdmin too.
6. Open `http://localhost/bookstore/` and shop. Admin logs in with the account from step 5 and uses the **Admin** link.

## Run locally for a quick test (SQLite, no server setup)

1. In `config.php` keep `driver` set to `sqlite`.
2. From this folder: `php -S localhost:8000`
3. Open `http://localhost:8000/install.php` once — it creates the SQLite database from `schema.sqlite.sql` in a `booknook-data` folder **next to** (outside) this project folder, and lets you create the admin account. Delete `install.php` afterwards.
4. Open `http://localhost:8000/`.

## Going live

- The SQLite database file lives **outside** the web folder by default (`booknook-data/` next to the project folder), so no web server can serve it directly — this does not depend on any server config. The installer also drops a deny rule into that folder as a backstop.
- Under Apache/XAMPP the bundled `.htaccess` additionally blocks direct web access to `*.sqlite`, `*.sql` schemas, `*.md` docs and `config.php` wherever they sit.
- On any other server, apply the equivalent rules for the schemas, docs and `config.php`.
- `php -S` applies no access rules at all — it is for local development only, never for a public deployment.

## Deploy on Render (free)

1. Push this repo to GitHub (config.php stays on the sqlite driver with an empty password).
2. In Render: New → Web Service → connect the repo → Environment: Docker → plan: Free → Create.
3. When it is live, open `https://<your-service>.onrender.com/install.php` once: it seeds the books and lets you create the admin account.
4. Free-tier notes: the service sleeps after 15 minutes of inactivity (first load after sleep is slow), and the demo database is wiped whenever the service restarts, spins down, or redeploys — just re-run install.php then. That is fine for a demo: the schema files in the repo rebuild it fresh every time.

## Notes

- All queries are prepared statements; passwords are bcrypt-hashed; every POST form carries a CSRF token. See `DATABASE.md` for the full contract and `schema.sql` for the data model.
- Prices are stored as `DECIMAL(10,2)` and re-read from the database at checkout; the cart never carries trusted prices.
