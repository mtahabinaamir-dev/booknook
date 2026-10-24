<?php
// One-time setup: creates the database (sqlite) and the first admin account, then must be deleted
require __DIR__ . '/lib.php';

$msg = '';
try {
    if (db_driver() === 'sqlite') {
        // The sqlite file lives outside the web folder; still drop deny rules there as a backstop
        $dir = dirname($DB['sqlite']);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        if (!file_exists($dir . '/.htaccess')) file_put_contents($dir . '/.htaccess', "Require all denied\n");
        if (!file_exists($dir . '/index.html')) file_put_contents($dir . '/index.html', '');
        // A homepage visit creates an empty database file first, so gate on the books table, not the file
        $pdo = new PDO('sqlite:' . $DB['sqlite'], null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $hasBooks = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='books'")->fetchColumn() > 0;
        if (!$hasBooks) {
            foreach (explode(';', file_get_contents(__DIR__ . '/schema.sqlite.sql')) as $sql) {
                if (trim($sql) !== '') $pdo->exec($sql);
            }
        }
    }
    $pdo = db();
    // Login throttle table from the security contract; sqlite creates it here, MySQL may need the owner (app user has no CREATE right)
    $ddl = db_driver() === 'mysql'
        ? 'CREATE TABLE IF NOT EXISTS login_attempts (email VARCHAR(190) NOT NULL, ip VARCHAR(45) NOT NULL, tried_at INT NOT NULL, KEY idx_attempts_pair (email, ip)) ENGINE=InnoDB'
        : 'CREATE TABLE IF NOT EXISTS login_attempts (email TEXT NOT NULL, ip TEXT NOT NULL, tried_at INTEGER NOT NULL)';
    try { $pdo->exec($ddl); } catch (PDOException $e) { $msg = 'Run this SQL once as the database owner, then reload: ' . $ddl; }
    $hasAdmin = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;
} catch (PDOException $e) {
    error_log($e->getMessage());
    $hasAdmin = true;
    if (db_driver() === 'mysql') $msg = 'Database not ready. In phpMyAdmin, import schema.sql and create the app user from DATABASE.md, then reload this page.';
    else $msg = 'Database setup failed. Check the server error log.';
}

if (!$hasAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim(in_str($_POST, 'name'));
    $email = trim(in_str($_POST, 'email'));
    $pass = in_str($_POST, 'password');
    if (strlen($name) < 1 || strlen($name) > 100) $msg = 'Name must be 1-100 characters.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $msg = 'Enter a valid email address.';
    elseif (strlen($pass) < 8) $msg = 'Password must be at least 8 characters.';
    else {
        try {
            $s = db()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
            $s->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            $hasAdmin = true;
            $msg = 'Admin account created. Delete install.php now, then log in.';
        } catch (PDOException $e) { error_log($e->getMessage()); $msg = 'Could not create the admin account.'; }
    }
}

page_head('Install');
echo '<main class="container"><h1>BookNook setup</h1>';
if ($msg) echo '<div class="alert ' . ($hasAdmin ? 'alert-success' : 'alert-error') . '">' . e($msg) . '</div>';
if (!$hasAdmin) {
    echo '<form method="post" class="form-card">' . csrf_field();
    echo '<div class="field"><label for="name">Admin name</label><input id="name" name="name" required maxlength="100"></div>';
    echo '<div class="field"><label for="email">Admin email</label><input id="email" type="email" name="email" required></div>';
    echo '<div class="field"><label for="password">Admin password (min 8 chars)</label><input id="password" type="password" name="password" required></div>';
    echo '<button class="btn btn-block" type="submit">Create admin account</button></form>';
}
echo '</main>';
page_foot();
