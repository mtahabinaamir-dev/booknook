<?php
// Login: bcrypt verify only, generic error, per email+IP throttle (5 failures locks the pair for 15 minutes), session id regenerated on success
require __DIR__ . '/lib.php';
if (user()) redirect('index.php');

function attempts_count($email, $ip) {
    try { $s = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND ip = ? AND tried_at > ?'); $s->execute([$email, $ip, time() - 900]); return (int)$s->fetchColumn(); }
    catch (PDOException $e) { return 0; }
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim(in_str($_POST, 'email'));
    $pass = in_str($_POST, 'password');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $s = db()->prepare('SELECT * FROM users WHERE email = ?');
    $s->execute([$email]);
    $u = $s->fetch();
    // A dummy hash keeps missing-account timing similar; the error message is identical either way
    $ok = attempts_count($email, $ip) < 5 && password_verify($pass, $u['password_hash'] ?? '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy');
    if ($ok && $u) {
        try { db()->prepare('DELETE FROM login_attempts WHERE email = ? AND ip = ?')->execute([$email, $ip]); } catch (PDOException $e) { error_log($e->getMessage()); }
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'role' => $u['role']];
        flash('Welcome back, ' . $u['name'] . '.');
        redirect(in_str($_GET, 'next') === 'checkout' ? 'checkout.php' : 'index.php');
    }
    try { db()->prepare('INSERT INTO login_attempts (email, ip, tried_at) VALUES (?, ?, ?)')->execute([$email, $ip, time()]); } catch (PDOException $e) { error_log($e->getMessage()); }
    $err = 'Invalid email or password.';
}

page_head('Login');
echo '<main class="container"><form method="post" class="form-card"><h1>Log in</h1>';
if ($err) echo '<div class="alert alert-error">' . e($err) . '</div>';
echo csrf_field();
echo '<div class="field"><label for="email">Email</label><input id="email" type="email" name="email" required value="' . e(in_str($_POST, 'email')) . '"></div>';
echo '<div class="field"><label for="password">Password</label><input id="password" type="password" name="password" required></div>';
echo '<button class="btn btn-block" type="submit">Log in</button>';
echo '<p class="author center">New here? <a href="register.php">Create an account</a></p></form></main>';
page_foot();
