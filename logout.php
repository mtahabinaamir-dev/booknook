<?php
// Logout: POST only with CSRF, destroys the session
require __DIR__ . '/lib.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION = [];
    session_destroy();
}
redirect('index.php');
