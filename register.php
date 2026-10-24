<?php
// Register: validates per contract, bcrypt hash, duplicate email handled gracefully
require __DIR__ . '/lib.php';
if (user()) redirect('index.php');

$err = '';
$old = ['name' => '', 'email' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = ['name' => trim(in_str($_POST, 'name')), 'email' => trim(in_str($_POST, 'email'))];
    $pass = in_str($_POST, 'password');
    if (strlen($old['name']) < 1 || strlen($old['name']) > 100) $err = 'Name must be 1-100 characters.';
    elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 190) $err = 'Enter a valid email address.';
    elseif (strlen($pass) < 8) $err = 'Password must be at least 8 characters.';
    else {
        try {
            db()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')")
                ->execute([$old['name'], $old['email'], password_hash($pass, PASSWORD_DEFAULT)]);
            flash('Account created. Please log in.');
            redirect('login.php');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            // Duplicate email gets a neutral message so the form never confirms an account exists
            $err = $e->getCode() === '23000' ? 'Could not create the account. If you already have an account, please log in instead.' : 'Something went wrong. Please try again.';
        }
    }
}

page_head('Register');
echo '<main class="container"><form method="post" class="form-card"><h1>Create an account</h1>';
if ($err) echo '<div class="alert alert-error">' . e($err) . '</div>';
echo csrf_field();
echo '<div class="field"><label for="name">Full name</label><input id="name" name="name" required maxlength="100" value="' . e($old['name']) . '"></div>';
echo '<div class="field"><label for="email">Email</label><input id="email" type="email" name="email" required value="' . e($old['email']) . '"></div>';
echo '<div class="field"><label for="password">Password (min 8 chars)</label><input id="password" type="password" name="password" required></div>';
echo '<button class="btn btn-block" type="submit">Register</button>';
echo '<p class="author center">Already have an account? <a href="login.php">Log in</a></p></form></main>';
page_foot();
