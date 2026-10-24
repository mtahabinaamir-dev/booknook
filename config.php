<?php
// Database settings — set driver to mysql on XAMPP, sqlite for local testing; never commit real passwords
$DB = [
    'driver' => 'sqlite',
    'host'   => '127.0.0.1',
    'name'   => 'bookstore',
    'user'   => 'bookstore_app',
    'pass'   => '',
    'sqlite' => dirname(__DIR__) . '/booknook-data/bookstore.sqlite', // outside the web folder so no server can serve the file
];
