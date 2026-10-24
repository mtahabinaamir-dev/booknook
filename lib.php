<?php
// Shared bootstrap: DB connection, session, helpers and page layout used by every page
require __DIR__ . '/config.php';

function db() {
    global $DB;
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    if ($DB['driver'] === 'mysql') {
        $pdo = new PDO('mysql:host=' . $DB['host'] . ';dbname=' . $DB['name'] . ';charset=utf8mb4', $DB['user'], $DB['pass'], $opts);
    } else {
        $pdo = new PDO('sqlite:' . $DB['sqlite'], null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}

function db_driver() { global $DB; return $DB['driver']; }

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
// Input helper: the value only when it really is a string, so array-typed params behave as empty instead of crashing
function in_str($arr, $key) { $v = $arr[$key] ?? ''; return is_string($v) ? $v : ''; }
function money($p) { $f = (float)$p; return '$' . number_format($f, 2); }
function redirect($url) { header('Location: ' . $url); exit; }
function user() { return $_SESSION['user'] ?? null; }
function require_login() { if (!user()) redirect('login.php'); }
function require_admin() { if (!user()) redirect('login.php'); if (user()['role'] !== 'admin') { http_response_code(403); exit('Admins only.'); } }

function csrf() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_check() { if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], in_str($_POST, 'csrf'))) { http_response_code(403); exit('Invalid form token. Please go back and try again.'); } }

function flash($msg, $type = 'success') { $_SESSION['flash'] = [$msg, $type]; }

function badge($stock) {
    if ($stock <= 0) return '<span class="badge badge-out">Out of stock</span>';
    if ($stock <= 5) return '<span class="badge badge-low">Low stock</span>';
    return '<span class="badge">In stock</span>';
}

function cover($b, $cls = '') {
    $c = 'c' . ((($b['id'] - 1) % 6) + 1);
    $ch = function_exists('mb_substr') ? mb_substr($b['title'], 0, 1) : substr($b['title'], 0, 1);
    return '<div class="cover ' . $c . ' ' . $cls . '" aria-hidden="true">' . e($ch) . '</div>';
}

function page_head($title) {
    $u = user();
    $count = array_sum($_SESSION['cart'] ?? []);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . ' — BookNook</title><meta name="description" content="BookNook — a small online bookstore. Browse books, manage your cart and check out with cash on delivery."><link rel="stylesheet" href="style.css"></head><body>';
    echo '<header class="site-header"><div class="container header-in">';
    echo '<a class="logo" href="index.php">Book<span>Nook</span>.</a>';
    echo '<form class="search" action="index.php" method="get"><input type="search" name="q" placeholder="Search by title or author" aria-label="Search books" value="' . e(in_str($_GET, 'q')) . '"><button class="btn" type="submit">Search</button></form>';
    echo '<nav class="main-nav"><a href="index.php">Home</a><a href="cart.php">Cart' . ($count ? ' (' . $count . ')' : '') . '</a>';
    if ($u) {
        echo '<a href="orders.php">My Orders</a>';
        if ($u['role'] === 'admin') echo '<a href="admin.php">Admin</a>';
        echo '<form method="post" action="logout.php" class="logout-form">' . csrf_field() . '<button type="submit" class="nav-btn">Logout</button></form>';
    } else {
        echo '<a href="login.php">Login</a><a href="register.php">Register</a>';
    }
    echo '</nav></div></header>';
    if (!empty($_SESSION['flash'])) {
        [$m, $t] = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="container"><div class="alert alert-' . e($t) . '">' . e($m) . '</div></div>';
    }
}

function page_foot() {
    echo '<footer class="site-footer"><div class="container footer-in"><span>BookNook — a small online bookstore.</span><span>Built with PHP &amp; MySQL</span></div></footer></body></html>';
}
