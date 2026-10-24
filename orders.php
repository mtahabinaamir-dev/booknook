<?php
// My Orders: the signed-in user's orders, newest first, with item lines and status badges
require __DIR__ . '/lib.php';
require_login();

$s = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
$s->execute([user()['id']]);
$orders = $s->fetchAll();
$items = db()->prepare('SELECT i.qty, i.price, b.title FROM order_items i JOIN books b ON b.id = i.book_id WHERE i.order_id = ? ORDER BY b.title');
$statusCls = ['pending' => 'badge-low', 'confirmed' => '', 'shipped' => '', 'delivered' => '', 'cancelled' => 'badge-out'];

page_head('My Orders');
echo '<main class="container"><h1>My Orders</h1>';
if (!$orders) {
    echo '<div class="empty"><p>You haven&rsquo;t placed any orders yet.</p><a class="btn" href="index.php">Browse books</a></div>';
} else {
    echo '<div class="table-wrap"><table><tr><th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th></tr>';
    foreach ($orders as $o) {
        $items->execute([$o['id']]);
        $lines = [];
        foreach ($items->fetchAll() as $i) $lines[] = e($i['title']) . ' × ' . $i['qty'];
        echo '<tr><td>#' . $o['id'] . '</td><td>' . e(date('d M Y', strtotime($o['created_at']))) . '</td><td>' . implode('<br>', $lines) . '</td>';
        echo '<td>' . money($o['total']) . '</td><td><span class="badge ' . $statusCls[$o['status']] . '">' . e(ucfirst($o['status'])) . '</span></td></tr>';
    }
    echo '</table></div>';
}
echo '</main>';
page_foot();
