<?php
// Home: hero, category line, book grid with GET search and category filter
require __DIR__ . '/lib.php';

$q = trim(in_str($_GET, 'q'));
$cat = trim(in_str($_GET, 'cat'));
$cats = db()->query('SELECT DISTINCT category FROM books ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

$sql = 'SELECT * FROM books';
$where = [];
$params = [];
if ($q !== '') {
    // Escape LIKE wildcards with '!' so users cannot search with % or _ patterns
    $like = '%' . strtr($q, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    $where[] = "(title LIKE ? ESCAPE '!' OR author LIKE ? ESCAPE '!')";
    $params = [$like, $like];
}
if ($cat !== '') { $where[] = 'category = ?'; $params[] = $cat; }
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY title';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

page_head('Find your next book');
echo '<section class="hero"><div class="container"><h1>Find your next book.</h1><p>A small bookshop with honest prices and fast delivery. Browse the shelves, fill your cart, and pay cash when your books arrive.</p></div></section>';
echo '<main class="container">';
if ($cats) {
    echo '<p class="cats">Browse: <a href="index.php">All</a>';
    foreach ($cats as $c) echo ' · <a href="index.php?cat=' . urlencode($c) . '">' . e($c) . '</a>';
    echo '</p>';
}
if ($q !== '') echo '<h2>Results for &ldquo;' . e($q) . '&rdquo;</h2>';
elseif ($cat !== '') echo '<h2>' . e($cat) . '</h2>';
else echo '<h2>Featured books</h2>';

if (!$books) {
    echo '<div class="empty"><p>No books found.</p><a class="btn" href="index.php">Browse all books</a></div>';
} else {
    echo '<div class="grid">';
    foreach ($books as $b) {
        echo '<article class="card">' . cover($b);
        echo '<h3><a href="book.php?id=' . $b['id'] . '">' . e($b['title']) . '</a></h3>';
        echo '<p class="author">' . e($b['author']) . '</p>' . badge($b['stock']);
        echo '<div class="card-foot"><span class="price">' . money($b['price']) . '</span>';
        if ($b['stock'] > 0) {
            echo '<form method="post" action="cart.php">' . csrf_field() . '<input type="hidden" name="add" value="' . $b['id'] . '"><button class="btn" type="submit">Add</button></form>';
        } else {
            echo '<button class="btn" type="button" disabled>Add</button>';
        }
        echo '</div></article>';
    }
    echo '</div>';
}
echo '</main>';
page_foot();
