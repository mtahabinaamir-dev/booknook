<?php
// Admin: book add/edit/delete and order status updates; every action is POST + CSRF behind the admin gate
require __DIR__ . '/lib.php';
require_admin();

$statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['save_book'])) {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $t = trim(in_str($_POST, 'title')); $a = trim(in_str($_POST, 'author')); $c = trim(in_str($_POST, 'category'));
        $p = in_str($_POST, 'price'); $st = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT);
        $d = trim(in_str($_POST, 'description'));
        if (strlen($t) < 1 || strlen($t) > 255 || strlen($a) < 1 || strlen($a) > 150 || strlen($c) < 1 || strlen($c) > 80) flash('Title, author and category lengths are invalid.', 'error');
        elseif (!is_numeric($p) || (float)$p < 0 || (float)$p > 99999999.99) flash('Price must be between 0 and 99,999,999.99.', 'error');
        elseif ($st === false || $st < 0) flash('Stock must be a whole number, 0 or more.', 'error');
        else {
            $vals = [$t, $a, $c, number_format((float)$p, 2, '.', ''), $st, $d === '' ? null : $d];
            try {
                if ($id) { $vals[] = $id; db()->prepare('UPDATE books SET title = ?, author = ?, category = ?, price = ?, stock = ?, description = ? WHERE id = ?')->execute($vals); flash('Book updated.'); }
                else { db()->prepare('INSERT INTO books (title, author, category, price, stock, description) VALUES (?, ?, ?, ?, ?, ?)')->execute($vals); flash('Book added.'); }
            } catch (PDOException $e) { error_log($e->getMessage()); flash('Could not save the book.', 'error'); }
        }
    } elseif (isset($_POST['delete_book'])) {
        $id = filter_var($_POST['delete_book'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id) {
            try { db()->prepare('DELETE FROM books WHERE id = ?')->execute([$id]); flash('Book deleted.'); }
            catch (PDOException $e) { error_log($e->getMessage()); flash($e->getCode() === '23000' ? 'That book has been ordered and cannot be deleted.' : 'Could not delete the book.', 'error'); }
        }
    } elseif (isset($_POST['order_status'])) {
        $oid = filter_var($_POST['order_status'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($oid && in_array($_POST['status'] ?? '', $statuses, true)) {
            db()->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$_POST['status'], $oid]);
            flash('Order #' . $oid . ' updated.');
        }
    }
    redirect('admin.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $s = db()->prepare('SELECT * FROM books WHERE id = ?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch() ?: null;
}
$books = db()->query('SELECT * FROM books ORDER BY title')->fetchAll();
$orders = db()->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll();
$v = fn($k) => e($edit[$k] ?? '');

page_head('Admin');
echo '<main class="container"><h1>Admin</h1><h2>' . ($edit ? 'Edit book' : 'Add a book') . '</h2>';
echo '<form method="post" class="form-card form-wide">' . csrf_field() . '<input type="hidden" name="id" value="' . $v('id') . '">';
echo '<div class="field"><label for="title">Title</label><input id="title" name="title" required maxlength="255" value="' . $v('title') . '"></div>';
echo '<div class="field"><label for="author">Author</label><input id="author" name="author" required maxlength="150" value="' . $v('author') . '"></div>';
echo '<div class="field"><label for="category">Category</label><input id="category" name="category" required maxlength="80" value="' . $v('category') . '"></div>';
echo '<div class="field"><label for="price">Price ($)</label><input id="price" name="price" required inputmode="decimal" value="' . $v('price') . '"></div>';
echo '<div class="field"><label for="stock">Stock</label><input id="stock" name="stock" required inputmode="numeric" value="' . $v('stock') . '"></div>';
echo '<div class="field"><label for="description">Description</label><textarea id="description" name="description" rows="3">' . $v('description') . '</textarea></div>';
echo '<button class="btn" type="submit" name="save_book" value="1">Save book</button>';
if ($edit) echo ' <a class="btn btn-ghost" href="admin.php">Cancel edit</a>';
echo '</form>';

echo '<h2>Books</h2><div class="table-wrap"><table><tr><th>Title</th><th>Author</th><th>Category</th><th>Price</th><th>Stock</th><th></th></tr>';
foreach ($books as $b) {
    echo '<tr><td>' . e($b['title']) . '</td><td>' . e($b['author']) . '</td><td>' . e($b['category']) . '</td><td>' . money($b['price']) . '</td><td>' . $b['stock'] . '</td>';
    echo '<td class="row-actions"><a href="admin.php?edit=' . $b['id'] . '">Edit</a><form method="post">' . csrf_field() . '<button class="link-danger" type="submit" name="delete_book" value="' . $b['id'] . '">Delete</button></form></td></tr>';
}
echo '</table></div>';

echo '<h2>Orders</h2>';
if (!$orders) echo '<div class="empty"><p>No orders yet.</p></div>';
else {
    echo '<div class="table-wrap"><table><tr><th>Order #</th><th>Date</th><th>Customer</th><th>Total</th><th>Status</th></tr>';
    foreach ($orders as $o) {
        echo '<tr><td>#' . $o['id'] . '</td><td>' . e(date('d M Y', strtotime($o['created_at']))) . '</td><td>' . e($o['name']) . '</td><td>' . money($o['total']) . '</td>';
        echo '<td><form method="post" class="status-form">' . csrf_field() . '<select name="status" aria-label="Status for order ' . $o['id'] . '">';
        foreach ($statuses as $st) echo '<option value="' . $st . '"' . ($o['status'] === $st ? ' selected' : '') . '>' . ucfirst($st) . '</option>';
        echo '</select> <button class="btn btn-ghost" type="submit" name="order_status" value="' . $o['id'] . '">Update</button></form></td></tr>';
    }
    echo '</table></div>';
}
echo '</main>';
page_foot();
