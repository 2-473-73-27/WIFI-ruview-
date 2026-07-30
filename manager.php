<?php
require_once 'db.php';

$update_msg = "";

// Handle Order Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status   = htmlspecialchars($_POST['status']);

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);

    if ($stmt->execute()) {
        $update_msg = "Order #$order_id status updated to '$status'.";
    }
    $stmt->close();
}

// Fetch Orders with Product Details
$sql = "SELECT orders.id, orders.customer_name, orders.customer_email, orders.quantity, orders.status, orders.created_at, products.name AS product_name, products.price 
        FROM orders 
        JOIN products ON orders.product_id = products.id 
        ORDER BY orders.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Order Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f7; margin: 20px; }
        h2 { color: #232f3e; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #232f3e; color: white; }
        tr:hover { background: #f1f1f1; }
        .alert { background: #d1ecf1; color: #0c5460; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .select-status { padding: 4px; }
        .btn-update { background: #007185; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; }
    </style>
</head>
<body>

<h2>Manager Dashboard — Order Management</h2>

<?php if ($update_msg): ?>
    <div class="alert"><?php echo $update_msg; ?></div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Email</th>
            <th>Product</th>
            <th>Qty</th>
            <th>Total Price</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_email']); ?></td>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td>$<?php echo number_format($row['price'] * $row['quantity'], 2); ?></td>
                    <td><strong><?php echo $row['status']; ?></strong></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                        <form method="POST" action="manager.php" style="display:inline-block;">
                            <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                            <select name="status" class="select-status">
                                <option value="Pending" <?php echo $row['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Processing" <?php echo $row['status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="Shipped" <?php echo $row['status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="Cancelled" <?php echo $row['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn-update">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9">No orders found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
