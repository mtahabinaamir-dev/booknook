<?php
// Book detail page; unknown or malformed id is a 404, never a query error
require __DIR__ . '/lib.php';

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$stmt = $id ? db()->prepare('SELECT * FROM books WHERE id = ?') : null;
if ($stmt) $stmt->execute([$id]);
$b = $stmt ? $stmt->fetch() : null;
if (!$b) { http_response_code(404); page_head('Not found'); echo '<main class="container"><div class="empty"><p>That book could not be found.</p><a class="btn" href="index.php">Browse all books</a></div></main>'; page_foot(); exit; }

page_head($b['title']);
echo '<main class="container"><p><a href="index.php">&larr; Back to Home</a></p><div class="detail">';
echo cover($b);
echo '<div><h1>' . e($b['title']) . '</h1><p class="author">' . e($b['author']) . ' · ' . e($b['category']) . '</p>';
echo '<p class="price">' . money($b['price']) . '</p><p>' . badge($b['stock']) . '</p>';
echo '<p>' . e($b['description']) . '</p>';
if ($b['stock'] > 0) {
    echo '<form method="post" action="cart.php">' . csrf_field() . '<input type="hidden" name="add" value="' . $b['id'] . '"><button class="btn" type="submit">Add to Cart</button></form>';
} else {
    echo '<button class="btn" type="button" disabled>Add to Cart</button>';
}
echo '</div></div></main>';
page_foot();
