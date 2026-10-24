<?php
// Checkout: login required; one transaction re-reads prices and stock, decrements with a guard, snapshots prices into the order
require __DIR__ . '/lib.php';
require_login();

$cart = $_SESSION['cart'] ?? [];
if (!$cart) { flash('Your cart is empty.', 'error'); redirect('cart.php'); }

$stmt = db()->prepare('SELECT * FROM books WHERE id = ?');
$lines = [];
foreach ($cart as $id => $qty) { $stmt->execute([$id]); if ($b = $stmt->fetch()) $lines[] = [$b, $qty]; }
$total = 0;
foreach ($lines as [$b, $qty]) $total += (float)$b['price'] * $qty;

$err = '';
$old = ['name' => user()['name'], 'phone' => '', 'address' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = ['name' => trim(in_str($_POST, 'name')), 'phone' => trim(in_str($_POST, 'phone')), 'address' => trim(in_str($_POST, 'address'))];
    if (strlen($old['name']) < 1 || strlen($old['name']) > 100) $err = 'Enter your full name.';
    elseif (!preg_match('/^[0-9+\- ]{7,20}$/', $old['phone'])) $err = 'Enter a valid phone number (7-20 digits).';
    elseif (strlen($old['address']) < 10 || strlen($old['address']) > 500) $err = 'Address must be 10-500 characters.';
    else {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $lock = db_driver() === 'mysql' ? ' FOR UPDATE' : '';
            $read = $pdo->prepare('SELECT price, stock FROM books WHERE id = ?' . $lock);
            $dec = $pdo->prepare('UPDATE books SET stock = stock - ? WHERE id = ? AND stock >= ?');
            foreach ($cart as $id => $qty) {
                $read->execute([$id]);
                $row = $read->fetch();
                if (!$row || $row['stock'] < $qty) throw new RuntimeException('stock');
                $dec->execute([$qty, $id, $qty]);
                if ($dec->rowCount() !== 1) throw new RuntimeException('stock');
                $prices[$id] = $row['price'];
            }
            $pdo->prepare('INSERT INTO orders (user_id, total, name, phone, address) VALUES (?, 0, ?, ?, ?)')
                ->execute([user()['id'], $old['name'], $old['phone'], $old['address']]);
            $oid = (int)$pdo->lastInsertId();
            $ins = $pdo->prepare('INSERT INTO order_items (order_id, book_id, qty, price) VALUES (?, ?, ?, ?)');
            foreach ($cart as $id => $qty) $ins->execute([$oid, $id, $qty, $prices[$id]]);
            $pdo->prepare('UPDATE orders SET total = (SELECT COALESCE(SUM(qty * price), 0) FROM order_items WHERE order_id = ?) WHERE id = ?')->execute([$oid, $oid]);
            $pdo->commit();
            $_SESSION['cart'] = [];
            flash('Order #' . $oid . ' placed. Please keep cash ready on delivery.');
            redirect('orders.php');
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = 'Sorry, one of the books in your cart no longer has enough stock. Please review your cart.';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($e->getMessage());
            $err = 'Something went wrong placing your order. Please try again.';
        }
    }
}

page_head('Checkout');
echo '<main class="container"><h1>Checkout</h1>';
if ($err) echo '<div class="alert alert-error">' . e($err) . '</div>';
echo '<div class="summary"><h2>Order summary</h2>';
foreach ($lines as [$b, $qty]) echo '<p>' . e($b['title']) . ' × ' . $qty . ' — ' . money((float)$b['price'] * $qty) . '</p>';
echo '<p class="total">Total: <strong>' . money($total) . '</strong></p><p class="author">Payment: Cash on Delivery</p></div>';
echo '<form method="post" class="form-card form-wide">' . csrf_field();
echo '<div class="field"><label for="name">Full name</label><input id="name" name="name" required maxlength="100" value="' . e($old['name']) . '"></div>';
echo '<div class="field"><label for="phone">Phone</label><input id="phone" name="phone" required maxlength="20" value="' . e($old['phone']) . '"></div>';
echo '<div class="field"><label for="address">Delivery address</label><textarea id="address" name="address" required rows="3" maxlength="500">' . e($old['address']) . '</textarea></div>';
echo '<button class="btn btn-block" type="submit">Place Order</button></form></main>';
page_foot();
