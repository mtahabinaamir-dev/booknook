<?php
// Session cart: add / update quantities / remove, all POST with CSRF; prices always read from the DB
require __DIR__ . '/lib.php';

$_SESSION['cart'] = $_SESSION['cart'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $book = db()->prepare('SELECT stock FROM books WHERE id = ?');
    if (isset($_POST['add'])) {
        $id = filter_var($_POST['add'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id) {
            $book->execute([$id]);
            $row = $book->fetch();
            if ($row && $row['stock'] > 0) {
                $_SESSION['cart'][$id] = min(($_SESSION['cart'][$id] ?? 0) + 1, 99, (int)$row['stock']);
                flash('Added to your cart.');
            }
        }
    } elseif (isset($_POST['remove'])) {
        $rid = filter_var($_POST['remove'], FILTER_VALIDATE_INT);
        if ($rid !== false) unset($_SESSION['cart'][$rid]);
        flash('Removed from your cart.');
    } elseif (isset($_POST['update'])) {
        $qtys = is_array($_POST['qty'] ?? null) ? $_POST['qty'] : [];
        foreach ($qtys as $id => $qty) {
            $id = (int)$id;
            if (!isset($_SESSION['cart'][$id])) continue;
            $book->execute([$id]);
            $row = $book->fetch();
            if ($row) $_SESSION['cart'][$id] = max(1, min((int)$qty, 99, (int)$row['stock']));
        }
        flash('Cart updated.');
    }
    redirect('cart.php');
}

$items = [];
$total = 0;
$stmt = db()->prepare('SELECT * FROM books WHERE id = ?');
foreach ($_SESSION['cart'] as $id => $qty) {
    $stmt->execute([$id]);
    if ($b = $stmt->fetch()) { $items[] = [$b, $qty]; $total += (float)$b['price'] * $qty; }
}

page_head('Your cart');
echo '<main class="container"><h1>Your cart</h1>';
if (!$items) {
    echo '<div class="empty"><p>Your cart is empty.</p><a class="btn" href="index.php">Browse books</a></div>';
} else {
    echo '<form method="post"><input type="hidden" name="csrf" value="' . csrf() . '"><div class="table-wrap"><table>';
    echo '<tr><th>Book</th><th>Unit price</th><th>Qty</th><th>Line total</th><th></th></tr>';
    foreach ($items as [$b, $qty]) {
        echo '<tr><td><span class="cell-book">' . cover($b, 'cover-sm') . '<a href="book.php?id=' . $b['id'] . '">' . e($b['title']) . '</a></span></td>';
        echo '<td>' . money($b['price']) . '</td>';
        echo '<td><input class="qty" type="number" name="qty[' . $b['id'] . ']" value="' . $qty . '" min="1" max="99" aria-label="Quantity for ' . e($b['title']) . '"></td>';
        echo '<td>' . money((float)$b['price'] * $qty) . '</td>';
        echo '<td><button class="link-danger" type="submit" name="remove" value="' . $b['id'] . '">Remove</button></td></tr>';
    }
    echo '</table></div>';
    echo '<div class="cart-foot"><button class="btn btn-ghost" type="submit" name="update" value="1">Update quantities</button>';
    echo '<p class="total">Total: <strong>' . money($total) . '</strong></p>';
    echo '<a class="btn" href="checkout.php">Proceed to Checkout</a> <a class="btn btn-ghost" href="index.php">Continue shopping</a></div></form>';
}
echo '</main>';
page_foot();
