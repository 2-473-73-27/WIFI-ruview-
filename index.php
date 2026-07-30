<?php
require_once 'db.php';

$message = "";

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $customer_name  = htmlspecialchars($_POST['customer_name']);
    $customer_email = htmlspecialchars($_POST['customer_email']);
    $product_id     = (int)$_POST['product_id'];
    $quantity       = (int)$_POST['quantity'];

    $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_email, product_id, quantity) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $customer_name, $customer_email, $product_id, $quantity);

    if ($stmt->execute()) {
        $message = "Order placed successfully!";
    } else {
        $message = "Error placing order: " . $conn->error;
    }
    $stmt->close();
}

// Fetch Products
$products = $conn->query("SELECT * FROM products");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Storefront - Place Order</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eaeded; margin: 0; padding: 20px; }
        .header { background: #131921; color: white; padding: 15px; margin-bottom: 20px; }
        .container { display: flex; gap: 20px; flex-wrap: wrap; }
        .product-card { background: white; padding: 15px; border-radius: 4px; width: 250px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .product-card img { width: 100%; height: 180px; object-fit: cover; }
        .price { font-size: 1.2em; color: #B12704; font-weight: bold; }
        .btn { background: #ffd814; border: 1px solid #fcd200; padding: 8px 12px; border-radius: 20px; cursor: pointer; width: 100%; margin-top: 10px; }
        .alert { background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="header">
    <h2>Online Store</h2>
</div>

<?php if ($message): ?>
    <div class="alert"><?php echo $message; ?></div>
<?php endif; ?>

<div class="container">
    <?php while($row = $products->fetch_assoc()): ?>
        <div class="product-card">
            <img src="<?php echo $row['image_url']; ?>" alt="Product Image">
            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
            <div class="price">$<?php echo number_format($row['price'], 2); ?></div>

            <form method="POST" action="index.php" style="margin-top: 15px;">
                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                
                <label>Your Name:</label>
                <input type="text" name="customer_name" required style="width: 90%; margin-bottom: 5px;">
                
                <label>Your Email:</label>
                <input type="email" name="customer_email" required style="width: 90%; margin-bottom: 5px;">
                
                <label>Quantity:</label>
                <input type="number" name="quantity" value="1" min="1" required style="width: 50px; margin-bottom: 10px;">

                <button type="submit" name="place_order" class="btn">Place Order</button>
            </form>
        </div>
    <?php endwhile; ?>
</div>

</body>
</html>
